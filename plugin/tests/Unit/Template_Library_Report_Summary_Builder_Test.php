<?php
/**
 * Unit tests for Template_Library_Report_Summary_Builder (spec §4.16, §46, §62.7–62.9, Prompt 214).
 *
 * Covers payload shape, redaction preservation, and example payload keys.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Payload_Schema;
use AIOPageBuilder\Domain\Reporting\Payloads\Template_Library_Report_Summary_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/Reporting/Payloads/Template_Library_Report_Summary_Builder.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Payload_Schema.php';

final class Template_Library_Report_Summary_Builder_Test extends TestCase {

	public function test_build_returns_stable_payload_without_repositories(): void {
		$builder = new Template_Library_Report_Summary_Builder( null, null, null, false, null );
		$summary = $builder->build();
		$this->assertIsArray( $summary );
		$this->assertArrayHasKey( 'section_template_count', $summary );
		$this->assertArrayHasKey( 'page_template_count', $summary );
		$this->assertArrayHasKey( 'composition_count', $summary );
		$this->assertArrayHasKey( 'library_version_marker', $summary );
		$this->assertArrayHasKey( 'plugin_version_marker', $summary );
		$this->assertArrayHasKey( 'appendices_available', $summary );
		$this->assertArrayHasKey( 'compliance_summary', $summary );
		$this->assertSame( 0, $summary['section_template_count'] );
		$this->assertSame( 0, $summary['page_template_count'] );
		$this->assertSame( 0, $summary['composition_count'] );
		$this->assertContains( $summary['compliance_summary'], array( 'ok', 'warning', 'critical', 'unknown' ) );
	}

	public function test_build_payload_has_no_prohibited_keys(): void {
		$builder = new Template_Library_Report_Summary_Builder( null, null, null, false, null );
		$summary = $builder->build();
		$this->assertTrue(
			Reporting_Payload_Schema::has_no_prohibited_keys( $summary, true ),
			'template_library_report_summary must not contain prohibited keys'
		);
	}

	public function test_example_template_library_report_summary_payload_keys(): void {
		$builder = new Template_Library_Report_Summary_Builder( null, null, null, true, null );
		$payload = $builder->build();
		$this->assertArrayHasKey( 'section_template_count', $payload );
		$this->assertArrayHasKey( 'page_template_count', $payload );
		$this->assertArrayHasKey( 'composition_count', $payload );
		$this->assertArrayHasKey( 'library_version_marker', $payload );
		$this->assertArrayHasKey( 'plugin_version_marker', $payload );
		$this->assertArrayHasKey( 'appendices_available', $payload );
		$this->assertArrayHasKey( 'compliance_summary', $payload );
		$this->assertIsString( $payload['library_version_marker'] );
		$this->assertIsString( $payload['plugin_version_marker'] );
		$this->assertIsBool( $payload['appendices_available'] );
		$this->assertSame( true, $payload['appendices_available'] );
	}

	public function test_compliance_status_provider_accepted_values(): void {
		$builder = new Template_Library_Report_Summary_Builder( null, null, null, false, function () {
			return 'ok';
		} );
		$payload = $builder->build();
		$this->assertSame( 'ok', $payload['compliance_summary'] );

		$builder_warning = new Template_Library_Report_Summary_Builder( null, null, null, false, function () {
			return 'warning';
		} );
		$this->assertSame( 'warning', $builder_warning->build()['compliance_summary'] );
	}
}
