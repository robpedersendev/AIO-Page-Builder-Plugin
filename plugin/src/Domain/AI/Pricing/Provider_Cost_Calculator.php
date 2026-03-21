<?php
/**
 * Computes cost_usd from token counts and the pricing registry (v2-scope-backlog.md §4).
 *
 * Centralizes all cost arithmetic so drivers stay thin and both providers use the same formula.
 * Returns null when rates are unavailable rather than returning a fake zero value.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Pricing;

defined( 'ABSPATH' ) || exit;

/**
 * Computes cost_usd = (prompt_tokens * input_rate) + (completion_tokens * output_rate).
 * Precision: rounded to 10 decimal places to preserve sub-cent accuracy for aggregation.
 */
final class Provider_Cost_Calculator {

	/** Number of decimal places to round computed cost values. */
	public const PRECISION = 10;

	/** @var Provider_Pricing_Registry */
	private Provider_Pricing_Registry $registry;

	public function __construct( Provider_Pricing_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Computes the USD cost for a single API request.
	 *
	 * @param string $provider_id       Provider identifier (e.g. 'openai').
	 * @param string $model_id          Model identifier as used in the API request.
	 * @param int    $prompt_tokens     Input/prompt token count (authoritative from provider).
	 * @param int    $completion_tokens Output/completion token count (authoritative from provider).
	 * @return float|null Cost in USD, or null when rates are unavailable for this provider+model.
	 */
	public function calculate(
		string $provider_id,
		string $model_id,
		int $prompt_tokens,
		int $completion_tokens
	): ?float {
		$rates = $this->registry->get_rates( $provider_id, $model_id );
		if ( $rates === null ) {
			return null;
		}
		if ( $prompt_tokens < 0 || $completion_tokens < 0 ) {
			return null;
		}
		$cost = ( $prompt_tokens * $rates['input'] ) + ( $completion_tokens * $rates['output'] );
		return round( $cost, self::PRECISION );
	}

	/**
	 * Returns whether a cost can be computed for the given provider and model.
	 *
	 * @param string $provider_id
	 * @param string $model_id
	 * @return bool
	 */
	public function has_pricing( string $provider_id, string $model_id ): bool {
		return $this->registry->has_rates( $provider_id, $model_id );
	}
}
