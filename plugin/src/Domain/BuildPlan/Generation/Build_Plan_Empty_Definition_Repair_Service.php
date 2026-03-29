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
			return false;
		}
		$plan_lookup_key = \sanitize_text_field( $plan_lookup_key );
		if ( $plan_lookup_key === '' ) {
			return false;
		}
		$definition = $this->plan_repository->get_plan_definition( $plan_post_id );
		if ( ! self::definition_lacks_steps( $definition ) ) {
			return false;
		}
		$skip_key = 'aio_bp_empty_def_repair_skip_' . (string) $plan_post_id;
		if ( \get_transient( $skip_key ) ) {
			return false;
		}
		$run_id = $this->run_repository->find_latest_completed_run_internal_key_for_build_plan_ref( $plan_lookup_key );
		if ( $run_id === null || $run_id === '' ) {
			\set_transient( $skip_key, '1', 600 );
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BUILD_PLAN_EMPTY_DEFINITION_REPAIR,
				'no_run plan_post_id=' . (string) $plan_post_id . ' plan_key=' . $plan_lookup_key
			);
			return false;
		}
		Named_Debug_Log::event(
			Named_Debug_Log_Event::BUILD_PLAN_EMPTY_DEFINITION_REPAIR,
			'attempt plan_post_id=' . (string) $plan_post_id . ' run_id=' . $run_id
		);
		$result = $this->from_run_service->create_from_completed_run( $run_id, $plan_post_id );
		if ( ! $result->is_success() ) {
			\set_transient( $skip_key, '1', 300 );
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BUILD_PLAN_EMPTY_DEFINITION_REPAIR,
				'failed plan_post_id=' . (string) $plan_post_id . ' errors=' . \wp_json_encode( $result->get_errors() )
			);
			return false;
		}
		\delete_transient( $skip_key );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::BUILD_PLAN_EMPTY_DEFINITION_REPAIR,
			'ok plan_post_id=' . (string) $plan_post_id
		);
		return true;
	}
}
