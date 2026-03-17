<?php
/**
 * Unit tests for Industry_Content_Gap_Detector: detect returns gaps per industry,
 * content_hints suppress gaps, no-gap and partial-gap cases (Prompt 408).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Content_Gap_Detector;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Content_Gap_Extender;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Content_Gap_Detector.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Subtype_Content_Gap_Extender.php';

final class Industry_Content_Gap_Detector_Test extends TestCase {

	public function test_detect_empty_profile_returns_empty(): void {
		$detector = new Industry_Content_Gap_Detector( null );
		$this->assertSame( array(), $detector->detect( array(), null, array() ) );
		$this->assertSame( array(), $detector->detect( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => '' ), null, array() ) );
	}

	public function test_detect_unknown_industry_returns_empty(): void {
		$detector = new Industry_Content_Gap_Detector( null );
		$profile = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'unknown_industry_xyz' );
		$this->assertSame( array(), $detector->detect( $profile, null, array() ) );
	}

	public function test_detect_realtor_returns_expected_gaps(): void {
		$detector = new Industry_Content_Gap_Detector( null );
		$profile = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor' );
		$gaps = $detector->detect( $profile, null, array() );
		$this->assertGreaterThanOrEqual( 2, count( $gaps ) );
		$types = array_column( $gaps, 'gap_type' );
		$this->assertContains( Industry_Content_Gap_Detector::GAP_GALLERY, $types );
		$this->assertContains( Industry_Content_Gap_Detector::GAP_VALUATION_CONVERSION, $types );
		foreach ( $gaps as $gap ) {
			$this->assertArrayHasKey( 'gap_type', $gap );
			$this->assertArrayHasKey( 'severity', $gap );
			$this->assertArrayHasKey( 'related_page_families', $gap );
			$this->assertArrayHasKey( 'related_section_families', $gap );
			$this->assertArrayHasKey( 'recommended_action_summary', $gap );
			$this->assertNotEmpty( $gap['recommended_action_summary'] );
		}
	}

	public function test_detect_plumber_includes_emergency_and_service_area(): void {
		$detector = new Industry_Content_Gap_Detector( null );
		$profile = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'plumber' );
		$gaps = $detector->detect( $profile, null, array() );
		$types = array_column( $gaps, 'gap_type' );
		$this->assertContains( Industry_Content_Gap_Detector::GAP_EMERGENCY_RESPONSE, $types );
		$this->assertContains( Industry_Content_Gap_Detector::GAP_SERVICE_AREA, $types );
	}

	public function test_detect_content_hints_suppress_gaps(): void {
		$detector = new Industry_Content_Gap_Detector( null );
		$profile = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor' );
		$options = array(
			Industry_Content_Gap_Detector::OPT_CONTENT_HINTS => array(
				'has_gallery' => true,
				'has_trust_proof' => true,
				'has_valuation_assets' => true,
			),
		);
		$gaps = $detector->detect( $profile, null, $options );
		$types = array_column( $gaps, 'gap_type' );
		$this->assertNotContains( Industry_Content_Gap_Detector::GAP_GALLERY, $types );
		$this->assertNotContains( Industry_Content_Gap_Detector::GAP_TRUST_PROOF, $types );
		$this->assertNotContains( Industry_Content_Gap_Detector::GAP_VALUATION_CONVERSION, $types );
	}

	public function test_detect_all_hints_present_returns_empty(): void {
		$detector = new Industry_Content_Gap_Detector( null );
		$profile = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'cosmetology_nail' );
		$options = array(
			Industry_Content_Gap_Detector::OPT_CONTENT_HINTS => array(
				'has_staff_bios' => true,
				'has_gallery' => true,
				'has_trust_proof' => true,
			),
		);
		$gaps = $detector->detect( $profile, null, $options );
		$this->assertSame( array(), $gaps );
	}

	/** Prompt 448: with subtype extender and industry_subtype_key, gaps can include subtype_influence; parent-only fallback when no subtype. */
	public function test_detect_with_subtype_extender_adds_subtype_influence_when_refinement_exists(): void {
		$extender = new Industry_Subtype_Content_Gap_Extender();
		$detector = new Industry_Content_Gap_Detector( null, $extender );
		$profile = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'cosmetology_nail',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'cosmetology_nail_mobile_tech',
		);
		$gaps = $detector->detect( $profile, null, array() );
		$this->assertGreaterThanOrEqual( 1, count( $gaps ) );
		$service_area_gap = null;
		foreach ( $gaps as $gap ) {
			if ( ( $gap['gap_type'] ?? '' ) === Industry_Content_Gap_Detector::GAP_SERVICE_AREA ) {
				$service_area_gap = $gap;
				break;
			}
		}
		$this->assertNotNull( $service_area_gap, 'Expected GAP_SERVICE_AREA for cosmetology_nail_mobile_tech' );
		$this->assertArrayHasKey( Industry_Content_Gap_Detector::RESULT_SUBTYPE_INFLUENCE, $service_area_gap );
		$this->assertArrayHasKey( 'refined_action_summary', $service_area_gap[ Industry_Content_Gap_Detector::RESULT_SUBTYPE_INFLUENCE ] );
		$this->assertArrayHasKey( 'additive_note', $service_area_gap[ Industry_Content_Gap_Detector::RESULT_SUBTYPE_INFLUENCE ] );
	}

	/** Prompt 448: parent-only fallback when subtype empty or extender null; no subtype_influence. */
	public function test_detect_without_subtype_has_no_subtype_influence(): void {
		$extender = new Industry_Subtype_Content_Gap_Extender();
		$detector = new Industry_Content_Gap_Detector( null, $extender );
		$profile = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'cosmetology_nail' );
		$gaps = $detector->detect( $profile, null, array() );
		foreach ( $gaps as $gap ) {
			$this->assertArrayNotHasKey( Industry_Content_Gap_Detector::RESULT_SUBTYPE_INFLUENCE, $gap );
		}
		$detector_no_extender = new Industry_Content_Gap_Detector( null, null );
		$profile_with_subtype = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'cosmetology_nail',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'cosmetology_nail_mobile_tech',
		);
		$gaps2 = $detector_no_extender->detect( $profile_with_subtype, null, array() );
		foreach ( $gaps2 as $gap ) {
			$this->assertArrayNotHasKey( Industry_Content_Gap_Detector::RESULT_SUBTYPE_INFLUENCE, $gap );
		}
	}
}
