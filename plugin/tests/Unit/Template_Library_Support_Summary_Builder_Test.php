<?php
/**
 * Unit tests for Template_Library_Support_Summary_Builder: payload shape, boundedness, redaction (spec §52.1, §45.9, Prompt 198).
 *
 * Example template_library_support_summary payload (no pseudocode):
 * {
 *   "health": { "passed": false, "count_summary": { "section_total": 120, "page_total": 80, "section_target": 250, "page_target": 500 }, "max_share_violations": [], "cta_rule_violations": [{ "template_key": "pt_landing_01", "code": "cta_min_not_met", "message": "[redacted]" }], "preview_readiness": { "sections_missing_preview_count": 0, "pages_missing_one_pager_count": 2 }, "metadata_checks": { "sections_missing_accessibility_count": 0, "sections_invalid_animation_count": 0 }, "export_viability": { "viable": true, "errors_count": 0, "errors": [] } },
 *   "validation_failures": [{ "template_key": "pt_landing_01", "code": "cta_min_not_met", "message": "[redacted]" }],
 *   "cta_violations": [{ "template_key": "pt_landing_01", "code": "cta_min_not_met", "message": "[redacted]" }],
 *   "preview_issues": { "sections_missing_preview": [], "pages_missing_one_pager": ["pt_about_01"] },
 *   "inventory": { "section_total": 120, "page_total": 80 },
 *   "appendix_sync": { "in_sync": true, "section_match": true, "page_match": true, "note": "Appendix totals match compliance counts." },
 *   "version_summary": { "deprecated_sections_count": 0, "deprecated_pages_count": 1 }
 * }
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Export\Template_Library_Support_Summary_Builder;
use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Payload_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ExportRestore/Export/Template_Library_Support_Summary_Builder.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Payload_Schema.php';

final class Template_Library_Support_Summary_Builder_Test extends TestCase {

	public function test_build_returns_stable_top_level_keys(): void {
		$builder = new Template_Library_Support_Summary_Builder( null, null, null, null, null, null, null, null );
		$payload = $builder->build();
		$this->assertIsArray( $payload );
		$this->assertArrayHasKey( 'health', $payload );
		$this->assertArrayHasKey( 'validation_failures', $payload );
		$this->assertArrayHasKey( 'cta_violations', $payload );
		$this->assertArrayHasKey( 'preview_issues', $payload );
		$this->assertArrayHasKey( 'inventory', $payload );
		$this->assertArrayHasKey( 'appendix_sync', $payload );
		$this->assertArrayHasKey( 'version_summary', $payload );
	}

	public function test_build_with_null_dependencies_inventory_is_bounded(): void {
		$builder = new Template_Library_Support_Summary_Builder( null, null, null, null, null, null, null, null );
		$payload = $builder->build();
		$this->assertSame( array( 'section_total' => 0, 'page_total' => 0 ), $payload['inventory'] );
		$this->assertSame( array( 'deprecated_sections_count' => 0, 'deprecated_pages_count' => 0 ), $payload['version_summary'] );
		$this->assertIsArray( $payload['appendix_sync'] );
		$this->assertArrayHasKey( 'in_sync', $payload['appendix_sync'] );
		$this->assertArrayHasKey( 'note', $payload['appendix_sync'] );
	}

	public function test_build_with_null_compliance_health_is_empty(): void {
		$builder = new Template_Library_Support_Summary_Builder( null, null, null, null, null, null, null, null );
		$payload = $builder->build();
		$this->assertSame( array(), $payload['health'] );
		$this->assertSame( array(), $payload['validation_failures'] );
		$this->assertSame( array(), $payload['cta_violations'] );
		$this->assertArrayHasKey( 'sections_missing_preview', $payload['preview_issues'] );
		$this->assertArrayHasKey( 'pages_missing_one_pager', $payload['preview_issues'] );
		$this->assertSame( array(), $payload['preview_issues']['sections_missing_preview'] );
		$this->assertSame( array(), $payload['preview_issues']['pages_missing_one_pager'] );
	}

	public function test_boundedness_no_raw_registry_keys(): void {
		$builder = new Template_Library_Support_Summary_Builder( null, null, null, null, null, null, null, null );
		$payload = $builder->build();
		$this->assertArrayNotHasKey( 'definitions', $payload );
		$this->assertArrayNotHasKey( 'raw_registry', $payload );
		$this->assertArrayNotHasKey( 'api_key', $payload );
	}

	/** Prompt 217: support summary output must pass redaction / prohibited-keys check for support bundle safety. */
	public function test_build_output_has_no_prohibited_keys(): void {
		$builder = new Template_Library_Support_Summary_Builder( null, null, null, null, null, null, null, null );
		$payload = $builder->build();
		$this->assertTrue(
			Reporting_Payload_Schema::has_no_prohibited_keys( $payload, true ),
			'template_library_support_summary must not contain prohibited keys (support bundle redaction)'
		);
	}
}
