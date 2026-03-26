<?php
/**
 * Aggregates month-to-date AI provider spend and compares against cap thresholds (v2-scope-backlog.md §4).
 *
 * Uses WordPress options as a lightweight accumulator per provider per calendar month.
 * Option key pattern: aio_pb_monthly_spend_{provider_id}_{YYYY_MM}.
 * Old monthly counters are left in options but do not affect enforcement.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Budget;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Records run costs and provides spend summaries per provider for the current calendar month.
 *
 * Uses get_option/update_option directly because monthly spend keys are per-provider, per-month
 * operational accumulators (dynamic key names) that do not fit the typed Settings_Service whitelist.
 */
final class Provider_Monthly_Spend_Service {

	/** Option key prefix. */
	public const OPTION_PREFIX = 'aio_pb_monthly_spend_';

	/** Approaching-cap threshold (fraction of the cap). */
	public const APPROACHING_THRESHOLD = 0.80;

	/** @var Provider_Spend_Cap_Settings */
	private Provider_Spend_Cap_Settings $cap_settings;

	public function __construct( Provider_Spend_Cap_Settings $cap_settings ) {
		$this->cap_settings = $cap_settings;
	}

	// -------------------------------------------------------------------------
	// Write
	// -------------------------------------------------------------------------

	/**
	 * Records a completed run cost for the current calendar month.
	 * No-op when cost_usd is zero or negative.
	 *
	 * @param string $provider_id Provider identifier.
	 * @param float  $cost_usd    Computed run cost in USD.
	 * @return void
	 */
	public function record_run_cost( string $provider_id, float $cost_usd ): void {
		if ( $cost_usd <= 0.0 ) {
			return;
		}
		$key     = $this->option_key( $provider_id );
		$current = (float) ( \get_option( $key, 0.0 ) ?? 0.0 );
		$new     = round( $current + $cost_usd, 10 );
		\update_option( $key, $new, false );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::MONTHLY_SPEND_RECORDED,
			'provider=' . $provider_id . ' delta=' . (string) $cost_usd . ' month_total=' . (string) $new
		);
	}

	// -------------------------------------------------------------------------
	// Read
	// -------------------------------------------------------------------------

	/**
	 * Returns the month-to-date spend total for a provider (current calendar month).
	 *
	 * @param string      $provider_id
	 * @param string|null $year_month Optional override in 'YYYY_MM' format; defaults to current month.
	 * @return float
	 */
	public function get_month_total( string $provider_id, ?string $year_month = null ): float {
		$key = $this->option_key( $provider_id, $year_month );
		return (float) ( \get_option( $key, 0.0 ) ?? 0.0 );
	}

	/**
	 * Returns a structured spend summary for a provider.
	 *
	 * @param string $provider_id
	 * @return array{
	 *   month_total: float,
	 *   cap: float,
	 *   has_cap: bool,
	 *   percent_used: float,
	 *   approaching: bool,
	 *   exceeded: bool,
	 *   override_enabled: bool
	 * }
	 */
	public function get_spend_summary( string $provider_id ): array {
		$month_total = $this->get_month_total( $provider_id );
		$cap         = $this->cap_settings->get_cap( $provider_id );
		$has_cap     = $cap > 0.0;
		$percent     = ( $has_cap && $cap > 0.0 ) ? ( $month_total / $cap ) : 0.0;
		$approaching = $has_cap && $percent >= self::APPROACHING_THRESHOLD && $percent < 1.0;
		$exceeded    = $has_cap && $month_total >= $cap;
		$override    = $this->cap_settings->is_override_enabled( $provider_id );

		return array(
			'month_total'      => $month_total,
			'cap'              => $cap,
			'has_cap'          => $has_cap,
			'percent_used'     => $percent,
			'approaching'      => $approaching,
			'exceeded'         => $exceeded,
			'override_enabled' => $override,
		);
	}

	/**
	 * Resets the month-to-date spend counter for a provider (current month by default).
	 * Intended for testing and admin reset operations.
	 *
	 * @param string      $provider_id
	 * @param string|null $year_month Optional 'YYYY_MM' override.
	 * @return void
	 */
	public function reset_month_total( string $provider_id, ?string $year_month = null ): void {
		$key = $this->option_key( $provider_id, $year_month );
		\update_option( $key, 0.0, false );
	}

	// -------------------------------------------------------------------------
	// Internals
	// -------------------------------------------------------------------------

	/**
	 * Builds the option key for the current (or specified) calendar month.
	 *
	 * @param string      $provider_id
	 * @param string|null $year_month Optional 'YYYY_MM' override for past-month queries.
	 * @return string
	 */
	private function option_key( string $provider_id, ?string $year_month = null ): string {
		$month = $year_month ?? \gmdate( 'Y_m' );
		return self::OPTION_PREFIX . \sanitize_key( $provider_id ) . '_' . $month;
	}
}
