<?php
/**
 * Unit tests for Template_Preference_Profile: from_array, to_array, validation, reduced-motion (Prompt 212, spec §59.6).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Profile\Template_Preference_Profile;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Profile/Template_Preference_Profile.php';

final class Template_Preference_Profile_Test extends TestCase {

	public function test_from_array_empty_returns_defaults(): void {
		$profile = Template_Preference_Profile::from_array( array() );
		$this->assertSame( '', $profile->get_page_emphasis() );
		$this->assertSame( '', $profile->get_conversion_posture() );
		$this->assertFalse( $profile->get_reduced_motion_preference() );
	}

	public function test_from_array_valid_values_persisted(): void {
		$profile = Template_Preference_Profile::from_array(
			array(
				'page_emphasis'             => Template_Preference_Profile::PAGE_EMPHASIS_CONVERSION,
				'conversion_posture'        => Template_Preference_Profile::CONVERSION_POSTURE_STRONG,
				'proof_style'               => Template_Preference_Profile::PROOF_STYLE_TESTIMONIALS,
				'content_density'           => Template_Preference_Profile::CONTENT_DENSITY_SPACIOUS,
				'animation_preference'      => Template_Preference_Profile::ANIMATION_REDUCED,
				'cta_intensity_preference'  => Template_Preference_Profile::CTA_INTENSITY_HIGH,
				'reduced_motion_preference' => true,
			)
		);
		$this->assertSame( Template_Preference_Profile::PAGE_EMPHASIS_CONVERSION, $profile->get_page_emphasis() );
		$this->assertSame( Template_Preference_Profile::CONVERSION_POSTURE_STRONG, $profile->get_conversion_posture() );
		$this->assertSame( Template_Preference_Profile::PROOF_STYLE_TESTIMONIALS, $profile->get_proof_style() );
		$this->assertSame( Template_Preference_Profile::CONTENT_DENSITY_SPACIOUS, $profile->get_content_density() );
		$this->assertSame( Template_Preference_Profile::ANIMATION_REDUCED, $profile->get_animation_preference() );
		$this->assertSame( Template_Preference_Profile::CTA_INTENSITY_HIGH, $profile->get_cta_intensity_preference() );
		$this->assertTrue( $profile->get_reduced_motion_preference() );
	}

	public function test_from_array_invalid_values_coerced_to_empty(): void {
		$profile = Template_Preference_Profile::from_array(
			array(
				'page_emphasis'      => 'invalid_emphasis',
				'conversion_posture' => 'invalid_posture',
			)
		);
		$this->assertSame( '', $profile->get_page_emphasis() );
		$this->assertSame( '', $profile->get_conversion_posture() );
	}

	public function test_reduced_motion_preference_handling(): void {
		$profile = Template_Preference_Profile::from_array( array( 'reduced_motion_preference' => '1' ) );
		$this->assertTrue( $profile->get_reduced_motion_preference() );
		$profile2 = Template_Preference_Profile::from_array( array( 'reduced_motion_preference' => true ) );
		$this->assertTrue( $profile2->get_reduced_motion_preference() );
		$profile3 = Template_Preference_Profile::from_array( array( 'reduced_motion_preference' => false ) );
		$this->assertFalse( $profile3->get_reduced_motion_preference() );
	}

	public function test_to_array_stable_payload(): void {
		$profile = Template_Preference_Profile::from_array(
			array(
				'page_emphasis'             => 'balanced',
				'reduced_motion_preference' => true,
			)
		);
		$arr     = $profile->to_array();
		$this->assertArrayHasKey( 'page_emphasis', $arr );
		$this->assertArrayHasKey( 'conversion_posture', $arr );
		$this->assertArrayHasKey( 'proof_style', $arr );
		$this->assertArrayHasKey( 'content_density', $arr );
		$this->assertArrayHasKey( 'animation_preference', $arr );
		$this->assertArrayHasKey( 'cta_intensity_preference', $arr );
		$this->assertArrayHasKey( 'reduced_motion_preference', $arr );
		$this->assertSame( 'balanced', $arr['page_emphasis'] );
		$this->assertTrue( $arr['reduced_motion_preference'] );
	}

	public function test_example_template_preference_profile_payload(): void {
		$example = array(
			'page_emphasis'             => 'conversion',
			'conversion_posture'        => 'moderate',
			'proof_style'               => 'social_proof',
			'content_density'           => 'moderate',
			'animation_preference'      => 'reduced',
			'cta_intensity_preference'  => 'medium',
			'reduced_motion_preference' => true,
		);
		$profile = Template_Preference_Profile::from_array( $example );
		$payload = $profile->to_array();
		$this->assertSame( $example['page_emphasis'], $payload['page_emphasis'] );
		$this->assertSame( $example['conversion_posture'], $payload['conversion_posture'] );
		$this->assertSame( $example['proof_style'], $payload['proof_style'] );
		$this->assertSame( $example['content_density'], $payload['content_density'] );
		$this->assertSame( $example['animation_preference'], $payload['animation_preference'] );
		$this->assertSame( $example['cta_intensity_preference'], $payload['cta_intensity_preference'] );
		$this->assertSame( $example['reduced_motion_preference'], $payload['reduced_motion_preference'] );
	}

	public function test_allowed_arrays_non_empty(): void {
		$this->assertNotEmpty( Template_Preference_Profile::allowed_page_emphasis() );
		$this->assertNotEmpty( Template_Preference_Profile::allowed_conversion_posture() );
		$this->assertNotEmpty( Template_Preference_Profile::allowed_proof_style() );
		$this->assertNotEmpty( Template_Preference_Profile::allowed_content_density() );
		$this->assertNotEmpty( Template_Preference_Profile::allowed_animation_preference() );
		$this->assertNotEmpty( Template_Preference_Profile::allowed_cta_intensity_preference() );
	}
}
