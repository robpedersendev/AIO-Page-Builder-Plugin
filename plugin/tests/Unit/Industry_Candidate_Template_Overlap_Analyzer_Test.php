<?php
/**
 * Unit tests for Industry_Candidate_Template_Overlap_Analyzer (Prompt 461).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Candidate_Template_Overlap_Analyzer;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Candidate_Template_Overlap_Analyzer.php';

final class Industry_Candidate_Template_Overlap_Analyzer_Test extends TestCase {

	public function test_analyze_without_registry_returns_bounded_shape(): void {
		$analyzer = new Industry_Candidate_Template_Overlap_Analyzer( null );
		$out = $analyzer->analyze( array( 'candidate_industry_label' => 'test_vertical' ) );
		$this->assertSame( 'test_vertical', $out['candidate_industry_label'] );
		$this->assertArrayHasKey( 'overlap_score', $out );
		$this->assertArrayHasKey( 'strongest_reusable_families', $out );
		$this->assertArrayHasKey( 'weak_coverage_families', $out );
		$this->assertArrayHasKey( 'notes', $out );
		$this->assertIsFloat( $out['overlap_score'] );
		$this->assertIsArray( $out['strongest_reusable_families'] );
		$this->assertIsArray( $out['weak_coverage_families'] );
		$this->assertIsArray( $out['notes'] );
		$this->assertGreaterThanOrEqual( 0.0, $out['overlap_score'] );
		$this->assertLessThanOrEqual( 1.0, $out['overlap_score'] );
	}

	public function test_analyze_with_candidate_page_families_matching_pack_computes_overlap(): void {
		$registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'realtor',
				Industry_Pack_Schema::FIELD_NAME          => 'Realtor',
				Industry_Pack_Schema::FIELD_SUMMARY       => 'Summary',
				Industry_Pack_Schema::FIELD_STATUS        => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
				Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES => array( 'home', 'about', 'contact' ),
			),
		) );
		$analyzer = new Industry_Candidate_Template_Overlap_Analyzer( $registry );
		$out = $analyzer->analyze( array(
			'candidate_industry_label' => 'estate_agent',
			'page_families'            => array( 'home', 'about' ),
		) );
		$this->assertSame( 1.0, $out['overlap_score'] );
		$this->assertEmpty( $out['weak_coverage_families'] );
	}

	public function test_analyze_with_candidate_families_not_in_pack_reports_weak_coverage(): void {
		$registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'realtor',
				Industry_Pack_Schema::FIELD_NAME          => 'Realtor',
				Industry_Pack_Schema::FIELD_SUMMARY       => 'Summary',
				Industry_Pack_Schema::FIELD_STATUS        => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
				Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES => array( 'home', 'about' ),
			),
		) );
		$analyzer = new Industry_Candidate_Template_Overlap_Analyzer( $registry );
		$out = $analyzer->analyze( array(
			'candidate_industry_label' => 'legal',
			'page_families'            => array( 'home', 'case_results', 'practice_areas' ),
		) );
		$this->assertLessThan( 1.0, $out['overlap_score'] );
		$this->assertNotEmpty( $out['weak_coverage_families'] );
		$this->assertCount( 2, $out['weak_coverage_families'] );
	}

	public function test_analyze_includes_cta_and_lpagery_notes_when_provided(): void {
		$analyzer = new Industry_Candidate_Template_Overlap_Analyzer( null );
		$out = $analyzer->analyze( array(
			'candidate_industry_label' => 'health',
			'cta_pattern_refs'         => array( 'book_now' ),
			'lpagery_rule_ref'         => 'health_01',
			'proof_model_hint'        => 'testimonial',
		) );
		$this->assertNotEmpty( $out['notes'] );
	}
}
