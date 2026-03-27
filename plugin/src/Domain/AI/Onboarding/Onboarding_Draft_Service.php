<?php
/**
 * Load and save onboarding draft state (onboarding-state-machine.md §7). Secret-free; capability-gated at call site.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Persists and retrieves onboarding draft payload. No secrets; shape per contract §7.
 */
final class Onboarding_Draft_Service {

	public const DRAFT_VERSION = 1;

	/** @var Settings_Service */
	private Settings_Service $settings;

	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Returns current draft payload (normalized). Empty default when none stored.
	 *
	 * @return array<string, mixed>
	 */
	public function get_draft(): array {
		$raw = $this->settings->get( Option_Names::ONBOARDING_DRAFT );
		return $this->normalize_draft( $raw );
	}

	/**
	 * Saves draft payload. Caller must capability-gate and pass sanitized data. No secrets allowed.
	 *
	 * @param array<string, mixed> $payload Draft shape: version, overall_status, current_step_key, step_statuses, profile_snapshot_ref?, crawl_run_id_ref?, provider_refs?, goal_or_intent_text?, updated_at.
	 * @return bool True if saved.
	 */
	public function save_draft( array $payload ): bool {
		$normalized               = $this->normalize_draft( $payload );
		$normalized['updated_at'] = $this->iso8601_now();
		$this->settings->set( Option_Names::ONBOARDING_DRAFT, $normalized );
		$step = isset( $normalized['current_step_key'] ) && is_string( $normalized['current_step_key'] ) ? $normalized['current_step_key'] : '';
		Named_Debug_Log::event( Named_Debug_Log_Event::ONBOARDING_DRAFT_SAVE, 'step=' . $step );
		return true;
	}

	/**
	 * Clears draft (e.g. after submission or abandon). Caller must capability-gate.
	 *
	 * @return void
	 */
	public function clear_draft(): void {
		$this->settings->set( Option_Names::ONBOARDING_DRAFT, $this->default_draft() );
		Named_Debug_Log::event( Named_Debug_Log_Event::ONBOARDING_DRAFT_CLEAR, '' );
	}

	/**
	 * Default draft shape for fresh or cleared state.
	 *
	 * @return array<string, mixed>
	 */
	public function default_draft(): array {
		$steps = array();
		foreach ( Onboarding_Step_Keys::ordered() as $key ) {
			$steps[ $key ] = Onboarding_Statuses::STEP_NOT_STARTED;
		}
		return array(
			'version'                   => self::DRAFT_VERSION,
			'overall_status'            => Onboarding_Statuses::NOT_STARTED,
			'current_step_key'          => Onboarding_Step_Keys::WELCOME,
			'furthest_step_index'       => 0,
			'step_statuses'             => $steps,
			'profile_snapshot_ref'      => null,
			'crawl_run_id_ref'          => null,
			'provider_refs'             => array(),
			'goal_or_intent_text'       => '',
			'last_planning_run_id'      => null,
			'last_planning_run_post_id' => null,
			'linked_build_plan_post_id'  => null,
			'linked_build_plan_key'      => null,
			'build_plan_lineage_mode'    => 'new',
			'fork_lineage_id'            => '',
			'fork_version_purpose'         => '',
			'updated_at'                 => $this->iso8601_now(),
		);
	}

	/**
	 * Normalizes raw payload to contract shape. Strips unknown keys; ensures required keys and types.
	 *
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	private function normalize_draft( array $raw ): array {
		$default       = $this->default_draft();
		$step_statuses = $default['step_statuses'];
		if ( isset( $raw['step_statuses'] ) && is_array( $raw['step_statuses'] ) ) {
			foreach ( Onboarding_Step_Keys::ordered() as $key ) {
				if ( isset( $raw['step_statuses'][ $key ] ) && is_string( $raw['step_statuses'][ $key ] )
					&& in_array( $raw['step_statuses'][ $key ], Onboarding_Statuses::step_statuses(), true ) ) {
					$step_statuses[ $key ] = $raw['step_statuses'][ $key ];
				}
			}
		}
		$current       = isset( $raw['current_step_key'] ) && is_string( $raw['current_step_key'] ) && in_array( $raw['current_step_key'], Onboarding_Step_Keys::ordered(), true )
			? $raw['current_step_key']
			: $default['current_step_key'];
		$overall       = isset( $raw['overall_status'] ) && is_string( $raw['overall_status'] ) && in_array( $raw['overall_status'], Onboarding_Statuses::overall_statuses(), true )
			? $raw['overall_status']
			: $default['overall_status'];
		$provider_refs = array();
		if ( isset( $raw['provider_refs'] ) && is_array( $raw['provider_refs'] ) ) {
			foreach ( $raw['provider_refs'] as $ref ) {
				if ( is_array( $ref ) && isset( $ref['provider_id'] ) && is_string( $ref['provider_id'] ) ) {
					$provider_refs[] = array(
						'provider_id'      => \sanitize_text_field( $ref['provider_id'] ),
						'credential_state' => isset( $ref['credential_state'] ) && is_string( $ref['credential_state'] ) ? $ref['credential_state'] : 'absent',
					);
				}
			}
		}
		$last_run_id   = isset( $raw['last_planning_run_id'] ) && ( is_string( $raw['last_planning_run_id'] ) || $raw['last_planning_run_id'] === null ) ? $raw['last_planning_run_id'] : $default['last_planning_run_id'];
		$last_run_post = isset( $raw['last_planning_run_post_id'] ) && ( is_int( $raw['last_planning_run_post_id'] ) || ( is_string( $raw['last_planning_run_post_id'] ) && $raw['last_planning_run_post_id'] !== '' ) ) ? (int) $raw['last_planning_run_post_id'] : ( $default['last_planning_run_post_id'] ?? null );
		if ( $last_run_post === 0 && $last_run_id === null ) {
			$last_run_post = null;
		}
		$linked_bp_post = isset( $raw['linked_build_plan_post_id'] ) && ( is_int( $raw['linked_build_plan_post_id'] ) || ( is_string( $raw['linked_build_plan_post_id'] ) && $raw['linked_build_plan_post_id'] !== '' ) )
			? (int) $raw['linked_build_plan_post_id']
			: ( $default['linked_build_plan_post_id'] ?? null );
		if ( $linked_bp_post === 0 ) {
			$linked_bp_post = null;
		}
		$linked_bp_key   = isset( $raw['linked_build_plan_key'] ) && is_string( $raw['linked_build_plan_key'] ) && $raw['linked_build_plan_key'] !== ''
			? $raw['linked_build_plan_key']
			: ( $default['linked_build_plan_key'] ?? null );
		$max_step_idx    = count( Onboarding_Step_Keys::ordered() ) - 1;
		$cur_idx         = Onboarding_Step_Keys::index_of( $current );
		$cur_idx_safe    = $cur_idx >= 0 ? $cur_idx : 0;
		$furthest_stored = isset( $raw['furthest_step_index'] ) ? (int) $raw['furthest_step_index'] : $cur_idx_safe;
		$furthest_stored = max( 0, min( $furthest_stored, $max_step_idx ) );
		$furthest_stored = max( $furthest_stored, $cur_idx_safe );
		return array(
			'version'                   => isset( $raw['version'] ) && ( is_int( $raw['version'] ) || is_string( $raw['version'] ) ) ? $raw['version'] : $default['version'],
			'overall_status'            => $overall,
			'current_step_key'          => $current,
			'furthest_step_index'       => $furthest_stored,
			'step_statuses'             => $step_statuses,
			'profile_snapshot_ref'      => isset( $raw['profile_snapshot_ref'] ) && ( is_string( $raw['profile_snapshot_ref'] ) || $raw['profile_snapshot_ref'] === null ) ? $raw['profile_snapshot_ref'] : $default['profile_snapshot_ref'],
			'crawl_run_id_ref'          => isset( $raw['crawl_run_id_ref'] ) && ( is_string( $raw['crawl_run_id_ref'] ) || $raw['crawl_run_id_ref'] === null ) ? ( $raw['crawl_run_id_ref'] ? \sanitize_text_field( (string) $raw['crawl_run_id_ref'] ) : null ) : $default['crawl_run_id_ref'],
			'provider_refs'             => $provider_refs,
			'goal_or_intent_text'       => isset( $raw['goal_or_intent_text'] ) && is_string( $raw['goal_or_intent_text'] ) ? \sanitize_textarea_field( $raw['goal_or_intent_text'] ) : $default['goal_or_intent_text'],
			'last_planning_run_id'      => $last_run_id,
			'last_planning_run_post_id' => $last_run_post,
			'linked_build_plan_post_id'  => $linked_bp_post,
			'linked_build_plan_key'      => $linked_bp_key,
			'build_plan_lineage_mode'    => isset( $raw['build_plan_lineage_mode'] ) && $raw['build_plan_lineage_mode'] === 'fork' ? 'fork' : 'new',
			'fork_lineage_id'            => isset( $raw['fork_lineage_id'] ) && is_string( $raw['fork_lineage_id'] ) ? \sanitize_text_field( $raw['fork_lineage_id'] ) : '',
			'fork_version_purpose'       => isset( $raw['fork_version_purpose'] ) && is_string( $raw['fork_version_purpose'] ) ? \sanitize_textarea_field( $raw['fork_version_purpose'] ) : '',
			'updated_at'                 => isset( $raw['updated_at'] ) && is_string( $raw['updated_at'] ) ? $raw['updated_at'] : $default['updated_at'],
		);
	}

	private function iso8601_now(): string {
		return gmdate( 'Y-m-d\TH:i:s\Z' );
	}
}
