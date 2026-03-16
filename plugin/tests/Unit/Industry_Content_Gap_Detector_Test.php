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
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Content_Gap_Detector.php';

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
}
