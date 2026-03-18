<?php
/**
 * Extends what-if simulation to support alternate conversion-goal inputs (Prompt 501).
 * Allows comparing no-goal vs goal-aware recommendations and Build Plan posture without mutating live state.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;

/**
 * Extender that adds conversion_goal_key to simulated profile for what-if comparison.
 */
final class Conversion_Goal_What_If_Extender {

	/** Param key for alternate conversion goal in simulation params. */
	public const PARAM_ALTERNATE_CONVERSION_GOAL = 'alternate_conversion_goal_key';

	/** Launch goal set for validation. */
	private const LAUNCH_GOALS = array( 'calls', 'bookings', 'estimates', 'consultations', 'valuations', 'lead_capture' );

	/**
	 * Builds simulated profile overrides for conversion goal. Merge into the params passed to Industry_What_If_Simulation_Service::run_simulation().
	 * When PARAM_ALTERNATE_CONVERSION_GOAL is present, the simulated profile should have conversion_goal_key set accordingly.
	 *
	 * @param array<string, mixed> $params Simulation params (may include PARAM_ALTERNATE_CONVERSION_GOAL).
	 * @return array<string, mixed> Overrides to apply to simulated profile: key => value for conversion_goal_key when param valid.
	 */
	public function get_simulated_goal_overrides( array $params ): array {
		if ( ! array_key_exists( self::PARAM_ALTERNATE_CONVERSION_GOAL, $params ) ) {
			return array();
		}
		$v    = $params[ self::PARAM_ALTERNATE_CONVERSION_GOAL ];
		$goal = is_string( $v ) ? trim( $v ) : '';
		if ( $goal === '' ) {
			return array( Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => '' );
		}
		if ( strlen( $goal ) > 64 || ! preg_match( '#^[a-z0-9_-]+$#', $goal ) ) {
			return array();
		}
		if ( ! in_array( $goal, self::LAUNCH_GOALS, true ) ) {
			return array( Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => $goal );
		}
		return array( Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => $goal );
	}

	/**
	 * Whether the given goal key is in the launch set (for validation/warnings).
	 */
	public function is_launch_goal( string $goal_key ): bool {
		return in_array( trim( $goal_key ), self::LAUNCH_GOALS, true );
	}
}
