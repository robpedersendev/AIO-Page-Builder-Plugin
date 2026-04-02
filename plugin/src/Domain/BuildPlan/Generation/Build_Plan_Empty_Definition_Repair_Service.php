<?php
/**
 * Re-materializes a Build Plan definition from a completed AI run when meta is missing or has no steps.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Generation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Uses run metadata {@see AI_Run_Repository::find_latest_completed_run_internal_key_for_build_plan_ref} to locate
 * the planning run, then {@see AI_Run_To_Build_Plan_Service::create_from_completed_run} with reuse of the plan post.
 */
final class Build_Plan_Empty_Definition_Repair_Service {

	private Build_Plan_Repository $plan_repository;

	private AI_Run_Repository $run_repository;

	private AI_Run_To_Build_Plan_Service $from_run_service;

	public function __construct(
		Build_Plan_Repository $plan_repository,
		AI_Run_Repository $run_repository,
		AI_Run_To_Build_Plan_Service $from_run_service
	) {
		$this->plan_repository  = $plan_repository;
		$this->run_repository   = $run_repository;
		$this->from_run_service = $from_run_service;
	}

	/**
	 * @param array<string, mixed> $definition Decoded plan root from meta.
	 */
	public static function definition_lacks_steps( array $definition ): bool {
		if ( $definition === array() ) {
			return true;
		}
		$steps = $definition[ Build_Plan_Schema::KEY_STEPS ] ?? null;
		return ! is_array( $steps ) || $steps === array();
	}

	/**
	 * When the plan CPT exists but stored JSON is empty or has no steps, rebuild from the linked completed run.
	 *
	 * @param int    $plan_post_id   Build plan post ID.
	 * @param string $plan_lookup_key Stable plan id (internal_key / URL plan_id).
	 * @return bool True when a new definition was persisted successfully.
	 */
	public function repair_if_needed( int $plan_post_id, string $plan_lookup_key ): bool {
		if ( $plan_post_id <= 0 ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_SKIP_INVALID_INPUT,
				'reason=bad_post_id plan_post_id=' . (string) $plan_post_id
			);
			return false;
		}
		$plan_lookup_key = \sanitize_text_field( $plan_lookup_key );
		if ( $plan_lookup_key === '' ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_SKIP_INVALID_INPUT,
				'reason=empty_plan_key plan_post_id=' . (string) $plan_post_id
			);
			return false;
		}
		$definition = $this->plan_repository->get_plan_definition( $plan_post_id );
		if ( ! self::definition_lacks_steps( $definition ) ) {
			$steps  = $definition[ Build_Plan_Schema::KEY_STEPS ] ?? null;
			$step_n = is_array( $steps ) ? count( $steps ) : 0;
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_SKIP_ALREADY_HAS_STEPS,
				'plan_post_id=' . (string) $plan_post_id . ' step_count=' . (string) $step_n
			);
			return false;
		}
		$skip_key = 'aio_bp_empty_def_repair_skip_' . (string) $plan_post_id;
		if ( \get_transient( $skip_key ) ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_SKIP_BACKOFF_TRANSIENT,
				'plan_post_id=' . (string) $plan_post_id
			);
			return false;
		}
		$run_id = $this->resolve_source_run_internal_key( $plan_post_id, $plan_lookup_key );
		if ( $run_id === null || $run_id === '' ) {
			\set_transient( $skip_key, '1', 180 );
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_RESOLVE_FAILED,
				'plan_post_id=' . (string) $plan_post_id . ' plan_key=' . $plan_lookup_key
			);
			Named_Debug_Log::event_json_payload(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_RESOLVE_FAILED,
				array(
					'plan_post_id' => $plan_post_id,
					'plan_key_h12' => substr( \hash( 'sha256', $plan_lookup_key ), 0, 12 ),
					'plan_key_len' => strlen( $plan_lookup_key ),
					'phase'        => 'resolve_run_id_exhausted',
				)
			);
			return false;
		}
		Named_Debug_Log::event(
			Named_Debug_Log_Event::BP_EMPTY_REPAIR_CREATE_START,
			'plan_post_id=' . (string) $plan_post_id . ' run_id=' . $run_id
		);
		\update_post_meta( $plan_post_id, Build_Plan_Repository::META_PLAN_SOURCE_AI_RUN_REF, \sanitize_text_field( $run_id ) );
		$result = $this->from_run_service->create_from_completed_run( $run_id, $plan_post_id );
		if ( ! $result->is_success() ) {
			\set_transient( $skip_key, '1', 180 );
			$enc        = \wp_json_encode( $result->get_errors() );
			$err_detail = is_string( $enc ) ? $enc : '[]';
			if ( strlen( $err_detail ) > 800 ) {
				$err_detail = substr( $err_detail, 0, 800 ) . '…';
			}
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_CREATE_FAIL,
				'plan_post_id=' . (string) $plan_post_id . ' run_id=' . $run_id . ' errors=' . $err_detail
			);
			return false;
		}
		\delete_transient( $skip_key );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::BP_EMPTY_REPAIR_CREATE_OK,
			'plan_post_id=' . (string) $plan_post_id . ' run_id=' . $run_id . ' plan_id=' . (string) ( $result->get_plan_id() ?? '' )
		);
		return true;
	}

	/**
	 * Prefers run metadata build_plan_ref match; falls back to plan post meta written when JSON persists.
	 *
	 * @return string|null Run internal key.
	 */
	private function resolve_source_run_internal_key( int $plan_post_id, string $plan_lookup_key ): ?string {
		$from_ref = $this->run_repository->find_latest_completed_run_internal_key_for_build_plan_ref( $plan_lookup_key, 120, true );
		if ( $from_ref !== null && $from_ref !== '' ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_RESOLVE_FROM_REF,
				'plan_post_id=' . (string) $plan_post_id . ' plan_key=' . $plan_lookup_key . ' run_id=' . $from_ref
			);
			return $from_ref;
		}
		// * Structured empty-scan line: {@see Named_Debug_Log_Event::BP_AI_RUN_BUILD_PLAN_REF_SCAN_SUMMARY} (emitted from repository).
		$stored = trim( (string) \get_post_meta( $plan_post_id, Build_Plan_Repository::META_PLAN_SOURCE_AI_RUN_REF, true ) );
		if ( $stored === '' ) {
			Named_Debug_Log::event_json_payload(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_RESOLVE_NULL_DETAIL,
				array(
					'plan_post_id'   => $plan_post_id,
					'plan_key_h12'   => substr( \hash( 'sha256', $plan_lookup_key ), 0, 12 ),
					'plan_key_len'   => strlen( $plan_lookup_key ),
					'reason'         => 'no_stored_ai_run_ref_meta',
					'stored_run_len' => 0,
				)
			);
			return null;
		}
		$record = $this->run_repository->get_by_key( $stored );
		if ( $record === null || (string) ( $record['status'] ?? '' ) !== 'completed' ) {
			$st = $record !== null ? (string) ( $record['status'] ?? '' ) : 'no_record';
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_RESOLVE_META_INVALID,
				'plan_post_id=' . (string) $plan_post_id . ' stored_run_key=' . $stored . ' status=' . $st
			);
			Named_Debug_Log::event_json_payload(
				Named_Debug_Log_Event::BP_EMPTY_REPAIR_RESOLVE_NULL_DETAIL,
				array(
					'plan_post_id'   => $plan_post_id,
					'plan_key_h12'   => substr( \hash( 'sha256', $plan_lookup_key ), 0, 12 ),
					'plan_key_len'   => strlen( $plan_lookup_key ),
					'reason'         => 'stored_run_missing_or_not_completed',
					'stored_run_len' => strlen( $stored ),
					'record_status'  => $st,
				)
			);
			return null;
		}
		Named_Debug_Log::event(
			Named_Debug_Log_Event::BP_EMPTY_REPAIR_RESOLVE_FROM_POST_META,
			'plan_post_id=' . (string) $plan_post_id . ' run_id=' . $stored
		);
		return $stored;
	}
}
