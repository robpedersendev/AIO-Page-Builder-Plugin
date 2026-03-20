<?php
/**
 * Reads and writes per-provider monthly spend cap configuration (v2-scope-backlog.md §4).
 *
 * All writes must be capability- and nonce-gated by callers. Does not store secrets.
 * Cap value 0 means "no cap enforced."
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Budget;

defined( 'ABSPATH' ) || exit;

/**
 * Spend cap configuration storage for each provider. Option key per provider:
 * aio_pb_spend_cap_{provider_id} → { monthly_cap_usd: float, override_cap_exceeded: bool }
 *
 * Uses get_option/update_option directly because spend cap keys are per-provider operational
 * options (dynamic key names) that do not fit the typed Settings_Service key whitelist.
 */
final class Provider_Spend_Cap_Settings {

	/** Option key prefix. */
	public const OPTION_PREFIX = 'aio_pb_spend_cap_';

	/** Maximum allowed cap value (sanity bound). */
	public const MAX_CAP_USD = 9999.99;

	/**
	 * Returns the monthly spend cap in USD for a provider (0.0 = no cap).
	 *
	 * @param string $provider_id
	 * @return float
	 */
	public function get_cap( string $provider_id ): float {
		$data = $this->load( $provider_id );
		return (float) ( $data['monthly_cap_usd'] ?? 0.0 );
	}

	/**
	 * Returns whether the operator has enabled the "override cap" flag for a provider.
	 * When enabled, runs are allowed even when the cap is exceeded.
	 *
	 * @param string $provider_id
	 * @return bool
	 */
	public function is_override_enabled( string $provider_id ): bool {
		$data = $this->load( $provider_id );
		return (bool) ( $data['override_cap_exceeded'] ?? false );
	}

	/**
	 * Persists spend cap configuration. Sanitizes inputs; does not enforce capabilities (caller's job).
	 *
	 * @param string $provider_id   Provider identifier.
	 * @param float  $cap_usd       Monthly cap in USD (0 = no cap; must be ≥ 0 and ≤ MAX_CAP_USD).
	 * @param bool   $override      Whether to allow runs even when cap is exceeded.
	 * @return bool True on successful save.
	 */
	public function save_settings( string $provider_id, float $cap_usd, bool $override ): bool {
		$sanitized_cap = max( 0.0, min( self::MAX_CAP_USD, round( $cap_usd, 2 ) ) );
		\update_option(
			self::OPTION_PREFIX . \sanitize_key( $provider_id ),
			array(
				'monthly_cap_usd'       => $sanitized_cap,
				'override_cap_exceeded' => $override,
			),
			false // * Not autoloaded — spend cap settings are admin-only, not loaded on every request.
		);
		return true;
	}

	/**
	 * Returns whether the spend cap is configured (non-zero).
	 *
	 * @param string $provider_id
	 * @return bool
	 */
	public function has_cap( string $provider_id ): bool {
		return $this->get_cap( $provider_id ) > 0.0;
	}

	/**
	 * Loads raw option data for a provider. Returns empty array when not set.
	 *
	 * @param string $provider_id
	 * @return array<string, mixed>
	 */
	private function load( string $provider_id ): array {
		$key  = self::OPTION_PREFIX . \sanitize_key( $provider_id );
		$data = \get_option( $key, array() );
		return is_array( $data ) ? $data : array();
	}
}
