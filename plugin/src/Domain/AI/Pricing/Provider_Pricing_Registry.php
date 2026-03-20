<?php
/**
 * In-code pricing registry for known AI provider models (v2-scope-backlog.md §4).
 *
 * IMPORTANT: Rates are manually maintained from public provider pricing pages.
 * These figures are approximate and will drift as providers change pricing.
 * Review and update rates when providers announce pricing changes.
 *
 * Sources:
 *   OpenAI:    https://openai.com/api/pricing/
 *   Anthropic: https://www.anthropic.com/pricing#api
 *
 * Rates are stored in USD per individual token (not per 1 million) to simplify arithmetic.
 * Calculation: cost = (prompt_tokens * input_rate) + (completion_tokens * output_rate).
 *
 * Design: static registry with explicit update cadence note. No remote pricing fetch — avoids
 * additional dependency, failure mode, and trust surface. Returns null for unknown models so
 * callers can render a truthful "cost unavailable" rather than a fake value.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Pricing;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes per-token input/output rates for known provider + model combinations.
 * Rate map keyed by provider_id (e.g. 'openai') → model_id → { input, output }.
 * Rates are in USD per token. Multiply by 1,000,000 to get price per 1M tokens.
 */
final class Provider_Pricing_Registry {

	/**
	 * Per-token rates in USD.
	 * Prices sourced from public pricing pages; last reviewed for v2 implementation.
	 *
	 * ! These rates require manual update when provider pricing changes.
	 *
	 * @var array<string, array<string, array{input: float, output: float}>>
	 */
	private const RATES = array(
		'openai'    => array(
			// gpt-4o: $2.50 / $10.00 per 1M tokens.
			'gpt-4o'              => array( 'input' => 0.0000025, 'output' => 0.00001 ),
			// gpt-4o-mini: $0.15 / $0.60 per 1M tokens.
			'gpt-4o-mini'         => array( 'input' => 0.00000015, 'output' => 0.0000006 ),
			// gpt-4-turbo: $10.00 / $30.00 per 1M tokens.
			'gpt-4-turbo'         => array( 'input' => 0.00001, 'output' => 0.00003 ),
			// gpt-4-turbo-preview: same as gpt-4-turbo.
			'gpt-4-turbo-preview' => array( 'input' => 0.00001, 'output' => 0.00003 ),
			// gpt-3.5-turbo: $0.50 / $1.50 per 1M tokens.
			'gpt-3.5-turbo'       => array( 'input' => 0.0000005, 'output' => 0.0000015 ),
		),
		'anthropic' => array(
			// claude-sonnet-4-20250514: $3.00 / $15.00 per 1M tokens.
			'claude-sonnet-4-20250514'   => array( 'input' => 0.000003, 'output' => 0.000015 ),
			// claude-3-5-sonnet-20241022: $3.00 / $15.00 per 1M tokens.
			'claude-3-5-sonnet-20241022' => array( 'input' => 0.000003, 'output' => 0.000015 ),
			// claude-3-5-sonnet-20240620: $3.00 / $15.00 per 1M tokens.
			'claude-3-5-sonnet-20240620' => array( 'input' => 0.000003, 'output' => 0.000015 ),
			// claude-3-5-haiku-20241022: $0.80 / $4.00 per 1M tokens.
			'claude-3-5-haiku-20241022'  => array( 'input' => 0.0000008, 'output' => 0.000004 ),
			// claude-3-opus-20240229: $15.00 / $75.00 per 1M tokens.
			'claude-3-opus-20240229'     => array( 'input' => 0.000015, 'output' => 0.000075 ),
			// claude-3-haiku-20240307: $0.25 / $1.25 per 1M tokens.
			'claude-3-haiku-20240307'    => array( 'input' => 0.00000025, 'output' => 0.00000125 ),
		),
	);

	/**
	 * Returns input/output per-token rates for the given provider and model.
	 * Model ID lookup first tries exact match, then falls back to prefix/alias matching.
	 *
	 * @param string $provider_id Provider identifier (e.g. 'openai', 'anthropic').
	 * @param string $model_id    Model identifier exactly as used in API requests.
	 * @return array{input: float, output: float}|null Rates or null when unknown.
	 */
	public function get_rates( string $provider_id, string $model_id ): ?array {
		$provider_rates = self::RATES[ $provider_id ] ?? null;
		if ( $provider_rates === null ) {
			return null;
		}
		// Exact match first.
		if ( isset( $provider_rates[ $model_id ] ) ) {
			return $provider_rates[ $model_id ];
		}
		// * Prefix/alias match — handles dated suffixes appended by providers (e.g. gpt-4-turbo-2024-04-09).
		foreach ( $provider_rates as $registered_id => $rates ) {
			if ( str_starts_with( $model_id, $registered_id ) ) {
				return $rates;
			}
		}
		return null;
	}

	/**
	 * Returns all registered provider IDs.
	 *
	 * @return array<int, string>
	 */
	public function get_provider_ids(): array {
		return array_keys( self::RATES );
	}

	/**
	 * Returns all registered model IDs for a given provider, or empty array for unknown providers.
	 *
	 * @param string $provider_id
	 * @return array<int, string>
	 */
	public function get_model_ids_for_provider( string $provider_id ): array {
		return array_keys( self::RATES[ $provider_id ] ?? array() );
	}

	/**
	 * Returns whether a provider + model combination has rates available.
	 *
	 * @param string $provider_id
	 * @param string $model_id
	 * @return bool
	 */
	public function has_rates( string $provider_id, string $model_id ): bool {
		return $this->get_rates( $provider_id, $model_id ) !== null;
	}
}
