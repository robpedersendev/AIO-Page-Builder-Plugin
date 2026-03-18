<?php
/**
 * Unit tests for FormProviderIntegrationRegressionHarness (Prompt 238): fixture-driven regression.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Tests\Regression\FormProviderIntegrationRegressionHarness;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );
require_once __DIR__ . '/bootstrap-i18n.php';

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/tests/Regression/FormProviderIntegrationRegressionHarness.php';

final class Form_Provider_Integration_Regression_Harness_Test extends TestCase {

	private function fixtures_base(): string {
		return dirname( __DIR__ ) . '/fixtures/form-provider-integration';
	}

	public function test_harness_run_all_returns_results(): void {
		$harness = new FormProviderIntegrationRegressionHarness( $this->fixtures_base() );
		$results = $harness->run_all();
		$this->assertIsArray( $results );
		$this->assertGreaterThan( 0, count( $results ) );
		foreach ( $results as $r ) {
			$this->assertArrayHasKey( 'scenario_id', $r );
			$this->assertArrayHasKey( 'pass', $r );
			$this->assertArrayHasKey( 'message', $r );
			$this->assertArrayHasKey( 'details', $r );
		}
	}

	public function test_section_form_embed_valid_passes(): void {
		$harness = new FormProviderIntegrationRegressionHarness( $this->fixtures_base() );
		$results = $harness->run_all();
		$valid   = array_values(
			array_filter(
				$results,
				static function ( $r ) {
					return ( $r['scenario_id'] ?? '' ) === 'section-form-embed-valid';
				}
			)
		);
		$this->assertCount( 1, $valid );
		$this->assertTrue( $valid[0]['pass'], $valid[0]['message'] ?? '' );
		$this->assertTrue( $valid[0]['details']['shortcode_builds'] ?? false );
		$this->assertStringContainsString( 'ndr_forms', $valid[0]['details']['shortcode'] ?? '' );
	}

	public function test_section_missing_provider_passes(): void {
		$harness = new FormProviderIntegrationRegressionHarness( $this->fixtures_base() );
		$results = $harness->run_all();
		$missing = array_values(
			array_filter(
				$results,
				static function ( $r ) {
					return ( $r['scenario_id'] ?? '' ) === 'section-missing-provider';
				}
			)
		);
		$this->assertCount( 1, $missing );
		$this->assertTrue( $missing[0]['pass'], $missing[0]['message'] ?? '' );
		$this->assertFalse( $missing[0]['details']['provider_registered'] ?? true );
		$this->assertFalse( $missing[0]['details']['shortcode_builds'] ?? true );
	}

	public function test_section_invalid_form_id_passes(): void {
		$harness = new FormProviderIntegrationRegressionHarness( $this->fixtures_base() );
		$results = $harness->run_all();
		$invalid = array_values(
			array_filter(
				$results,
				static function ( $r ) {
					return ( $r['scenario_id'] ?? '' ) === 'section-invalid-form-id';
				}
			)
		);
		$this->assertCount( 1, $invalid );
		$this->assertTrue( $invalid[0]['pass'], $invalid[0]['message'] ?? '' );
		$this->assertFalse( $invalid[0]['details']['shortcode_builds'] ?? true );
	}

	public function test_request_form_page_valid_passes(): void {
		$harness = new FormProviderIntegrationRegressionHarness( $this->fixtures_base() );
		$results = $harness->run_all();
		$req     = array_values(
			array_filter(
				$results,
				static function ( $r ) {
					return ( $r['scenario_id'] ?? '' ) === 'request-form-page-valid';
				}
			)
		);
		$this->assertCount( 1, $req );
		$this->assertTrue( $req[0]['pass'], $req[0]['message'] ?? '' );
		$this->assertTrue( $req[0]['details']['shortcode_builds'] ?? false );
	}

	public function test_summary_shape(): void {
		$harness = new FormProviderIntegrationRegressionHarness( $this->fixtures_base() );
		$results = $harness->run_all();
		$summary = FormProviderIntegrationRegressionHarness::summary( $results );
		$this->assertArrayHasKey( 'ran_at', $summary );
		$this->assertArrayHasKey( 'total', $summary );
		$this->assertArrayHasKey( 'passed', $summary );
		$this->assertArrayHasKey( 'failed', $summary );
		$this->assertSame( count( $results ), $summary['total'] );
		$this->assertSame( $summary['passed'] + $summary['failed'], $summary['total'] );
	}
}
