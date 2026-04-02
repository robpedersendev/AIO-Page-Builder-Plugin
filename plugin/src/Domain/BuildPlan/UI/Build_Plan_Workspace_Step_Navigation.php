<?php
/**
 * Workspace step Back/Next progression: required-item counts match stepper unresolved (spec 31.2–31.3).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Computes prev/next URLs, visibility, and WP_DEBUG_LOG snapshot for the build plan workspace.
 */
final class Build_Plan_Workspace_Step_Navigation {

	/**
	 * @param array<int, array<string, mixed>> $stepper_steps Payload from {@see Build_Plan_Stepper_Builder::build()}.
	 * @param string                           $workspace_base_url Plan workspace URL without step (hub tab preserved).
	 * @return array<string, mixed>
	 */
	public static function compute( int $active_index, array $stepper_steps, string $workspace_base_url ): array {
		$n       = count( $stepper_steps );
		$current = $stepper_steps[ $active_index ] ?? null;
		if ( ! is_array( $current ) ) {
			return self::empty_context( $n, $active_index, $workspace_base_url );
		}
		$required_remaining = (int) ( $current['unresolved_count'] ?? 0 );
		$current_blocked    = ! empty( $current['is_blocked'] );
		$step_type          = (string) ( $current['step_type'] ?? '' );
		$requirements_met   = $required_remaining === 0 && ! $current_blocked;

		$has_prev = $active_index > 0 && $n > 0;
		$has_next = $active_index < $n - 1;

		$next_step    = $has_next ? $stepper_steps[ $active_index + 1 ] : null;
		$next_blocked = is_array( $next_step ) && ! empty( $next_step['is_blocked'] );
		$show_next    = $requirements_met && $has_next;
		$next_enabled = $show_next && ! $next_blocked;
		$prev_url     = $has_prev ? \add_query_arg( 'step', (string) ( $active_index - 1 ), $workspace_base_url ) : '';
		$next_url     = $has_next ? \add_query_arg( 'step', (string) ( $active_index + 1 ), $workspace_base_url ) : '';

		return array(
			'required_remaining'   => $required_remaining,
			'current_step_blocked' => $current_blocked,
			'step_type'            => $step_type,
			'requirements_met'     => $requirements_met,
			'show_back'            => $n > 0,
			'back_enabled'         => $has_prev,
			'prev_url'             => $prev_url,
			'show_next'            => $show_next,
			'next_enabled'         => $next_enabled,
			'next_blocked'         => $next_blocked,
			'next_url'             => $next_url,
			'active_index'         => $active_index,
			'total_steps'          => $n,
		);
	}

	/**
	 * @param array<string, mixed> $computed {@see compute()}.
	 */
	public static function log_snapshot( string $plan_id, array $computed ): void {
		if ( ! Named_Debug_Log::build_plan_meta_trace_enabled() ) {
			return;
		}
		$hash = $plan_id !== '' ? substr( hash( 'sha256', $plan_id ), 0, 8 ) : '';
		Named_Debug_Log::event_json_payload(
			Named_Debug_Log_Event::BP_WORKSPACE_STEP_NAV_SNAPSHOT,
			array(
				'plan_id_len'          => strlen( $plan_id ),
				'plan_id_hash'         => $hash,
				'step_index'           => (int) ( $computed['active_index'] ?? 0 ),
				'step_type'            => substr( (string) ( $computed['step_type'] ?? '' ), 0, 64 ),
				'required_remaining'   => (int) ( $computed['required_remaining'] ?? 0 ),
				'current_step_blocked' => ! empty( $computed['current_step_blocked'] ),
				'requirements_met'     => ! empty( $computed['requirements_met'] ),
				'show_back'            => ! empty( $computed['show_back'] ),
				'back_enabled'         => ! empty( $computed['back_enabled'] ),
				'show_next'            => ! empty( $computed['show_next'] ),
				'next_enabled'         => ! empty( $computed['next_enabled'] ),
				'next_step_blocked'    => ! empty( $computed['next_blocked'] ),
				'total_steps'          => (int) ( $computed['total_steps'] ?? 0 ),
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function empty_context( int $n, int $active_index, string $workspace_base_url ): array {
		$has_prev = $active_index > 0 && $n > 0;
		$prev_url = $has_prev ? \add_query_arg( 'step', (string) ( $active_index - 1 ), $workspace_base_url ) : '';
		return array(
			'required_remaining'   => 0,
			'current_step_blocked' => false,
			'step_type'            => '',
			'requirements_met'     => false,
			'show_back'            => $n > 0,
			'back_enabled'         => $has_prev,
			'prev_url'             => $prev_url,
			'show_next'            => false,
			'next_enabled'         => false,
			'next_blocked'         => false,
			'next_url'             => '',
			'active_index'         => $active_index,
			'total_steps'          => $n,
		);
	}
}
