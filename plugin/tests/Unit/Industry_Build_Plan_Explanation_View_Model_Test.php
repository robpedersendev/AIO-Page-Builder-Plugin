<?php
/**
 * Unit tests for Industry_Build_Plan_Explanation_View_Model (Prompt 365).
 * Verifies generic fallback, industry data display, and sanitization.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\ViewModels\BuildPlan\Industry_Build_Plan_Explanation_View_Model;
use AIOPageBuilder\Domain\Industry\AI\Industry_Build_Plan_Scoring_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Build_Plan_Scoring_Service.php';
require_once $plugin_root . '/src/Admin/ViewModels/BuildPlan/Industry_Build_Plan_Explanation_View_Model.php';

final class Industry_Build_Plan_Explanation_View_Model_Test extends TestCase {

	/** Generic fallback: empty payload yields has_industry_data false. */
	public function test_from_item_payload_empty_yields_no_industry_data(): void {
		$vm = Industry_Build_Plan_Explanation_View_Model::from_item_payload( array() );
		$this->assertFalse( $vm['has_industry_data'] );
		$this->assertSame( array(), $vm['summary_lines'] );
		$this->assertSame( array(), $vm['warning_badges'] );
		$this->assertSame( 'neutral', $vm['fit_classification'] );
		$this->assertSame( array(), $vm['source_refs'] );
	}

	/** Payload with only industry_source_refs yields has_industry_data true. */
	public function test_from_item_payload_with_source_refs_has_industry_data(): void {
		$payload = array(
			Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_SOURCE_REFS => array( 'realtor' ),
		);
		$vm      = Industry_Build_Plan_Explanation_View_Model::from_item_payload( $payload );
		$this->assertTrue( $vm['has_industry_data'] );
		$this->assertSame( array( 'realtor' ), $vm['source_refs'] );
	}

	/** Payload with recommendation_reasons yields summary_lines. */
	public function test_from_item_payload_with_reasons_yields_summary_lines(): void {
		$payload = array(
			Industry_Build_Plan_Scoring_Service::RECORD_RECOMMENDATION_REASONS => array( 'pack_family_fit', 'template_affinity_primary' ),
		);
		$vm      = Industry_Build_Plan_Explanation_View_Model::from_item_payload( $payload );
		$this->assertTrue( $vm['has_industry_data'] );
		$this->assertCount( 2, $vm['summary_lines'] );
		$this->assertStringContainsString( 'Matches industry page family', $vm['summary_lines'][0] );
	}

	/** Payload with industry_warning_flags yields warning_badges. */
	public function test_from_item_payload_with_warning_flags_yields_badges(): void {
		$payload = array(
			Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_WARNING_FLAGS => array( 'discouraged_for_industry', 'weak_fit' ),
		);
		$vm      = Industry_Build_Plan_Explanation_View_Model::from_item_payload( $payload );
		$this->assertTrue( $vm['has_industry_data'] );
		$this->assertCount( 2, $vm['warning_badges'] );
		$this->assertSame( 'discouraged_for_industry', $vm['warning_badges'][0]['code'] );
		$this->assertSame( 'discouraged', $vm['fit_classification'] );
	}

	/** Compliance warnings (Prompt 407) set has_industry_data and appear in compliance_cautions. */
	public function test_from_item_payload_with_compliance_warnings(): void {
		$warnings = array(
			array(
				'rule_key'        => 'test_rule',
				'severity'        => 'caution',
				'caution_summary' => 'Advisory note.',
			),
		);
		$vm       = Industry_Build_Plan_Explanation_View_Model::from_item_payload( array(), $warnings );
		$this->assertTrue( $vm['has_industry_data'] );
		$this->assertSame( $warnings, $vm['compliance_cautions'] );
	}

	/** Invalid reason codes are stripped; unknown codes humanized. */
	public function test_from_item_payload_sanitizes_reasons(): void {
		$payload = array(
			Industry_Build_Plan_Scoring_Service::RECORD_RECOMMENDATION_REASONS => array( 'valid_reason', '' ),
		);
		$vm      = Industry_Build_Plan_Explanation_View_Model::from_item_payload( $payload );
		$this->assertCount( 1, $vm['summary_lines'] );
	}

	/** plan_level_warning_lines returns escaped-safe lines from definition warnings. */
	public function test_plan_level_warning_lines_from_definition(): void {
		$definition = array(
			'warnings' => array(
				array( 'message' => 'Required page family X not present' ),
				array( 'summary' => 'LPagery weak fit' ),
			),
		);
		$lines      = Industry_Build_Plan_Explanation_View_Model::plan_level_warning_lines( $definition );
		$this->assertCount( 2, $lines );
		$this->assertSame( 'Required page family X not present', $lines[0] );
		$this->assertSame( 'LPagery weak fit', $lines[1] );
	}

	/** plan_level_warning_lines empty when no warnings. */
	public function test_plan_level_warning_lines_empty_when_no_warnings(): void {
		$lines = Industry_Build_Plan_Explanation_View_Model::plan_level_warning_lines( array() );
		$this->assertSame( array(), $lines );
	}
}
