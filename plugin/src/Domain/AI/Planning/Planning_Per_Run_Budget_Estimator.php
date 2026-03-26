<?php
/**
 * USD estimates for planning + expand using {@see Provider_Cost_Calculator}.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Planning;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Planning\Planning_Structured_Output_Limits;
use AIOPageBuilder\Domain\AI\Pricing\Provider_Cost_Calculator;

/**
 * Cost estimation for observability. The ~USD 5 suggested target is not a ceiling — runs may be cheaper or pricier.
 */
final class Planning_Per_Run_Budget_Estimator {

	/** @var Provider_Cost_Calculator */
	private Provider_Cost_Calculator $calculator;

	public function __construct( Provider_Cost_Calculator $calculator ) {
		$this->calculator = $calculator;
	}

	/**
	 * Filterable rough budget target in USD for messaging and logging (not a cap).
	 *
	 * Legacy filter {@see 'aio_pb_planning_per_run_max_usd'} is applied after {@see 'aio_pb_planning_suggested_budget_usd'} for backward compatibility.
	 */
	public function get_suggested_planning_budget_usd(): float {
		$default = Planning_Breadth_Constants::DEFAULT_SUGGESTED_PLANNING_BUDGET_USD;
		$v       = \apply_filters( 'aio_pb_planning_suggested_budget_usd', $default );
		$v       = \apply_filters( 'aio_pb_planning_per_run_max_usd', is_float( $v ) || is_int( $v ) ? (float) $v : $default );
		$v       = is_float( $v ) || is_int( $v ) ? (float) $v : $default;
		return max( 0.0, round( $v, 2 ) );
	}

	/**
	 * @deprecated Use {@see get_suggested_planning_budget_usd()}.
	 */
	public function get_per_run_max_budget_usd(): float {
		return $this->get_suggested_planning_budget_usd();
	}

	/**
	 * Upper-bound cost for one completion call (prompt + max completion tokens).
	 */
	public function estimate_call_upper_bound_usd(
		string $provider_id,
		string $model_id,
		int $estimated_prompt_tokens,
		int $max_completion_tokens
	): ?float {
		return $this->calculator->calculate( $provider_id, $model_id, max( 0, $estimated_prompt_tokens ), max( 0, $max_completion_tokens ) );
	}

	/**
	 * Rough token estimate from UTF-8 byte length (industry rule of thumb ~4 chars / token).
	 */
	public static function estimate_prompt_tokens_from_text( string $system_prompt, string $user_message ): int {
		$len = strlen( $system_prompt ) + strlen( $user_message );
		return (int) max( 1, ceil( $len / 4 ) );
	}

	/**
	 * Worst-case primary + optional expand for a full planning submission (used before any API call).
	 *
	 * @return float|null Sum in USD, or null when pricing for the model is unknown.
	 */
	public function estimate_full_run_upper_bound_usd( string $provider_id, string $model_id, string $system_prompt, string $user_message ): ?float {
		$prompt_tokens     = self::estimate_prompt_tokens_from_text( $system_prompt, $user_message );
		$primary           = $this->estimate_call_upper_bound_usd(
			$provider_id,
			$model_id,
			$prompt_tokens,
			Planning_Structured_Output_Limits::DEFAULT_MAX_OUTPUT_TOKENS
		);
		$expand_prompt_est = 3000;
		$expand            = $this->estimate_call_upper_bound_usd(
			$provider_id,
			$model_id,
			$expand_prompt_est,
			Planning_Breadth_Constants::EXPAND_PASS_MAX_OUTPUT_TOKENS
		);
		if ( $primary === null || $expand === null ) {
			return null;
		}
		return round( $primary + $expand, Provider_Cost_Calculator::PRECISION );
	}

	/**
	 * Actual cost from usage when the driver populated cost_usd; otherwise recomputed from token counts.
	 *
	 * @param array<string, mixed> $usage
	 */
	public function resolve_actual_cost_usd( string $provider_id, string $model_id, array $usage ): ?float {
		if ( isset( $usage['cost_usd'] ) && ( is_float( $usage['cost_usd'] ) || is_int( $usage['cost_usd'] ) ) ) {
			return round( (float) $usage['cost_usd'], Provider_Cost_Calculator::PRECISION );
		}
		$pt = isset( $usage['prompt_tokens'] ) ? (int) $usage['prompt_tokens'] : 0;
		$ct = isset( $usage['completion_tokens'] ) ? (int) $usage['completion_tokens'] : 0;
		if ( $pt <= 0 && $ct <= 0 ) {
			return null;
		}
		return $this->calculator->calculate( $provider_id, $model_id, $pt, $ct );
	}
}
