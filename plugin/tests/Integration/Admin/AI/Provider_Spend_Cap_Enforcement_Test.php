<?php
/**
 * Integration tests for monthly spend aggregation and cap enforcement.
 *
 * Stubs get_option/update_option within the test namespace so no WordPress is needed.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Integration\Admin\AI;

use AIOPageBuilder\Domain\AI\Budget\Provider_Monthly_Spend_Service;
use AIOPageBuilder\Domain\AI\Budget\Provider_Spend_Cap_Settings;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// WordPress function stubs scoped to this namespace
// ---------------------------------------------------------------------------

if ( ! function_exists( 'AIOPageBuilder\Tests\Integration\Admin\AI\sanitize_key' ) ) {
	function sanitize_key( string $key ): string {
		return (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
	}
}
if ( ! function_exists( 'AIOPageBuilder\Tests\Integration\Admin\AI\gmdate' ) ) {
	function gmdate( string $format, ?int $timestamp = null ): string {
		return $timestamp !== null ? \gmdate( $format, $timestamp ) : \gmdate( $format );
	}
}
if ( ! function_exists( 'AIOPageBuilder\Tests\Integration\Admin\AI\get_option' ) ) {
	/** @return mixed */
	function get_option( string $key, $default = false ) {
		return $GLOBALS['__spend_test_options'][ $key ] ?? $default;
	}
	/** @return bool */
	function update_option( string $key, $value, $autoload = null ): bool {
		$GLOBALS['__spend_test_options'][ $key ] = $value;
		return true;
	}
}

/**
 * @covers \AIOPageBuilder\Domain\AI\Budget\Provider_Monthly_Spend_Service
 * @covers \AIOPageBuilder\Domain\AI\Budget\Provider_Spend_Cap_Settings
 */
final class Provider_Spend_Cap_Enforcement_Test extends TestCase {

	private Provider_Spend_Cap_Settings $cap_settings;
	private Provider_Monthly_Spend_Service $spend_service;

	protected function setUp(): void {
		$GLOBALS['__spend_test_options'] = array();
		$this->cap_settings   = new Provider_Spend_Cap_Settings();
		$this->spend_service  = new Provider_Monthly_Spend_Service( $this->cap_settings );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['__spend_test_options'] );
	}

	public function test_record_and_retrieve_month_total(): void {
		$this->spend_service->record_run_cost( 'openai', 0.0075 );
		$this->spend_service->record_run_cost( 'openai', 0.012 );
		$total = $this->spend_service->get_month_total( 'openai' );
		$this->assertEqualsWithDelta( 0.0195, $total, 1.0e-9 );
	}

	public function test_record_negative_or_zero_cost_is_noop(): void {
		$this->spend_service->record_run_cost( 'openai', 0.0 );
		$this->spend_service->record_run_cost( 'openai', -1.5 );
		$this->assertEqualsWithDelta( 0.0, $this->spend_service->get_month_total( 'openai' ), 1.0e-9 );
	}

	public function test_different_providers_accumulate_independently(): void {
		$this->spend_service->record_run_cost( 'openai', 0.05 );
		$this->spend_service->record_run_cost( 'anthropic', 0.10 );
		$this->assertEqualsWithDelta( 0.05, $this->spend_service->get_month_total( 'openai' ), 1.0e-9 );
		$this->assertEqualsWithDelta( 0.10, $this->spend_service->get_month_total( 'anthropic' ), 1.0e-9 );
	}

	public function test_approaching_threshold_at_80_percent(): void {
		$this->cap_settings->save_settings( 'openai', 10.0, false );
		$this->spend_service->record_run_cost( 'openai', 8.5 );
		$summary = $this->spend_service->get_spend_summary( 'openai' );
		$this->assertTrue( $summary['approaching'] );
		$this->assertFalse( $summary['exceeded'] );
	}

	public function test_exceeded_when_spend_reaches_cap(): void {
		$this->cap_settings->save_settings( 'openai', 10.0, false );
		$this->spend_service->record_run_cost( 'openai', 10.0 );
		$summary = $this->spend_service->get_spend_summary( 'openai' );
		$this->assertTrue( $summary['exceeded'] );
		$this->assertFalse( $summary['approaching'] );
	}

	public function test_no_cap_means_no_enforcement(): void {
		// Cap is 0 = no cap enforced.
		$this->spend_service->record_run_cost( 'openai', 999.0 );
		$summary = $this->spend_service->get_spend_summary( 'openai' );
		$this->assertFalse( $summary['has_cap'] );
		$this->assertFalse( $summary['exceeded'] );
		$this->assertFalse( $summary['approaching'] );
	}

	public function test_override_enabled_reflected_in_summary(): void {
		$this->cap_settings->save_settings( 'openai', 5.0, true );
		$summary = $this->spend_service->get_spend_summary( 'openai' );
		$this->assertTrue( $summary['override_enabled'] );
	}

	public function test_percent_used_correct_when_cap_set(): void {
		$this->cap_settings->save_settings( 'openai', 100.0, false );
		$this->spend_service->record_run_cost( 'openai', 25.0 );
		$summary = $this->spend_service->get_spend_summary( 'openai' );
		$this->assertEqualsWithDelta( 0.25, $summary['percent_used'], 1.0e-9 );
	}

	public function test_reset_month_total_clears_accumulator(): void {
		$this->spend_service->record_run_cost( 'openai', 5.0 );
		$this->spend_service->reset_month_total( 'openai' );
		$this->assertEqualsWithDelta( 0.0, $this->spend_service->get_month_total( 'openai' ), 1.0e-9 );
	}

	public function test_cap_settings_saves_and_reads_cap(): void {
		$this->cap_settings->save_settings( 'anthropic', 50.0, false );
		$this->assertEqualsWithDelta( 50.0, $this->cap_settings->get_cap( 'anthropic' ), 0.001 );
	}

	public function test_cap_settings_saves_override_flag(): void {
		$this->cap_settings->save_settings( 'anthropic', 50.0, true );
		$this->assertTrue( $this->cap_settings->is_override_enabled( 'anthropic' ) );
	}

	public function test_cap_settings_clamps_negative_cap_to_zero(): void {
		$this->cap_settings->save_settings( 'openai', -5.0, false );
		$this->assertEqualsWithDelta( 0.0, $this->cap_settings->get_cap( 'openai' ), 0.001 );
	}

	public function test_cap_settings_clamps_to_max(): void {
		$this->cap_settings->save_settings( 'openai', 99999.0, false );
		$this->assertLessThanOrEqual( Provider_Spend_Cap_Settings::MAX_CAP_USD, $this->cap_settings->get_cap( 'openai' ) );
	}

	public function test_has_cap_false_when_cap_is_zero(): void {
		$this->cap_settings->save_settings( 'openai', 0.0, false );
		$this->assertFalse( $this->cap_settings->has_cap( 'openai' ) );
	}

	public function test_has_cap_true_when_cap_is_set(): void {
		$this->cap_settings->save_settings( 'openai', 25.0, false );
		$this->assertTrue( $this->cap_settings->has_cap( 'openai' ) );
	}
}
