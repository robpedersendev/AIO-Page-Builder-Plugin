<?php
/**
 * Unit tests for Template_Library_Compliance_Result (Prompt 176).
 * Covers to_array() payload shape, to_summary_lines() human-readable excerpt, and is_passed().
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\QA\Template_Library_Compliance_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/QA/Template_Library_Compliance_Result.php';

final class Template_Library_Compliance_Result_Test extends TestCase {

	public function test_to_array_returns_stable_payload_keys(): void {
		$count    = array(
			'section_total'             => 120,
			'page_total'                => 225,
			'section_target'            => 250,
			'page_target'               => 500,
			'by_section_purpose_family' => array( 'hero' => 12 ),
			'by_page_category_class'    => array( 'top_level' => 77 ),
			'by_page_family'            => array(),
		);
		$category = array(
			'section_family_minimums' => array( 'hero' => true ),
			'page_class_minimums'     => array( 'top_level' => false ),
			'max_share_violations'    => array(),
		);
		$result   = new Template_Library_Compliance_Result(
			$count,
			$category,
			array(),
			array(
				'sections_missing_preview' => array(),
				'pages_missing_one_pager'  => array(),
			),
			array(
				'sections_missing_accessibility' => array(),
				'sections_invalid_animation'     => array(),
			),
			array(
				'viable' => true,
				'errors' => array(),
			),
			false
		);
		$a        = $result->to_array();
		$this->assertArrayHasKey( 'count_summary', $a );
		$this->assertArrayHasKey( 'category_coverage_summary', $a );
		$this->assertArrayHasKey( 'cta_rule_violations', $a );
		$this->assertArrayHasKey( 'preview_readiness', $a );
		$this->assertArrayHasKey( 'metadata_checks', $a );
		$this->assertArrayHasKey( 'export_viability', $a );
		$this->assertArrayHasKey( 'passed', $a );
		$this->assertSame( 120, $a['count_summary']['section_total'] );
		$this->assertSame( 250, $a['count_summary']['section_target'] );
		$this->assertFalse( $a['passed'] );
	}

	/**
	 * One example compliance-result payload (machine-readable).
	 */
	public function test_example_compliance_result_payload(): void {
		$count_summary             = array(
			'section_total'             => 120,
			'page_total'                => 225,
			'section_target'            => 250,
			'page_target'               => 500,
			'by_section_purpose_family' => array(
				'hero' => 12,
				'cta'  => 26,
			),
			'by_page_category_class'    => array(
				'top_level' => 77,
				'hub'       => 43,
			),
			'by_page_family'            => array( 'home' => 8 ),
		);
		$category_coverage_summary = array(
			'section_family_minimums' => array(
				'hero' => true,
				'cta'  => true,
			),
			'page_class_minimums'     => array(
				'top_level' => false,
				'hub'       => false,
			),
			'max_share_violations'    => array(),
		);
		$cta_rule_violations       = array(
			array(
				'template_key' => 'pt_example',
				'code'         => 'bottom_cta_missing',
				'message'      => 'Template pt_example: last section is not CTA-classified.',
			),
		);
		$preview_readiness         = array(
			'sections_missing_preview' => array(),
			'pages_missing_one_pager'  => array(),
		);
		$metadata_checks           = array(
			'sections_missing_accessibility' => array(),
			'sections_invalid_animation'     => array(),
		);
		$export_viability          = array(
			'viable' => true,
			'errors' => array(),
		);
		$result                    = new Template_Library_Compliance_Result(
			$count_summary,
			$category_coverage_summary,
			$cta_rule_violations,
			$preview_readiness,
			$metadata_checks,
			$export_viability,
			false
		);
		$payload                   = $result->to_array();
		$this->assertSame( 120, $payload['count_summary']['section_total'] );
		$this->assertSame( 225, $payload['count_summary']['page_total'] );
		$this->assertCount( 1, $payload['cta_rule_violations'] );
		$this->assertSame( 'bottom_cta_missing', $payload['cta_rule_violations'][0]['code'] );
		$this->assertFalse( $payload['passed'] );
	}

	/**
	 * One human-readable summary excerpt.
	 */
	public function test_to_summary_lines_human_readable_excerpt(): void {
		$count_summary = array(
			'section_total'             => 120,
			'page_total'                => 225,
			'section_target'            => 250,
			'page_target'               => 500,
			'by_section_purpose_family' => array(),
			'by_page_category_class'    => array(),
			'by_page_family'            => array(),
		);
		$result        = new Template_Library_Compliance_Result(
			$count_summary,
			array(
				'section_family_minimums' => array(),
				'page_class_minimums'     => array(),
				'max_share_violations'    => array(),
			),
			array(),
			array(
				'sections_missing_preview' => array(),
				'pages_missing_one_pager'  => array(),
			),
			array(
				'sections_missing_accessibility' => array(),
				'sections_invalid_animation'     => array(),
			),
			array(
				'viable' => true,
				'errors' => array(),
			),
			false
		);
		$lines         = $result->to_summary_lines();
		$this->assertNotEmpty( $lines );
		$first = $lines[0];
		$this->assertStringContainsString( '120', $first );
		$this->assertStringContainsString( '250', $first );
		$this->assertStringContainsString( '225', $first );
		$this->assertStringContainsString( '500', $first );
		$last = end( $lines );
		$this->assertStringContainsString( 'FAILED', $last );
	}

	public function test_passed_true_when_no_violations(): void {
		$count  = array(
			'section_total'             => 250,
			'page_total'                => 500,
			'section_target'            => 250,
			'page_target'               => 500,
			'by_section_purpose_family' => array( 'hero' => 12 ),
			'by_page_category_class'    => array( 'top_level' => 80 ),
			'by_page_family'            => array(),
		);
		$result = new Template_Library_Compliance_Result(
			$count,
			array(
				'section_family_minimums' => array( 'hero' => true ),
				'page_class_minimums'     => array( 'top_level' => true ),
				'max_share_violations'    => array(),
			),
			array(),
			array(
				'sections_missing_preview' => array(),
				'pages_missing_one_pager'  => array(),
			),
			array(
				'sections_missing_accessibility' => array(),
				'sections_invalid_animation'     => array(),
			),
			array(
				'viable' => true,
				'errors' => array(),
			),
			true
		);
		$this->assertTrue( $result->is_passed() );
		$lines = $result->to_summary_lines();
		$this->assertStringContainsString( 'PASSED', end( $lines ) );
	}
}
