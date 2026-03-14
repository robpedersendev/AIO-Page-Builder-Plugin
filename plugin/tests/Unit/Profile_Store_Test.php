<?php
/**
 * Unit tests for Profile_Store: default reads, write/read-back, partial merge (spec §22, §8.5).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Normalizer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Profile/Template_Preference_Profile.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Normalizer.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Store.php';

final class Profile_Store_Test extends TestCase {

	private Settings_Service $settings;
	private Profile_Normalizer $normalizer;
	private Profile_Store $store;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_test_options'] = array();
		$this->settings   = new Settings_Service();
		$this->normalizer = new Profile_Normalizer();
		$this->store      = new Profile_Store( $this->settings, $this->normalizer );
	}

	protected function tearDown(): void {
		$GLOBALS['_aio_test_options'] = array();
		parent::tearDown();
	}

	public function test_get_brand_profile_returns_normalized_defaults_on_fresh_install(): void {
		$brand = $this->store->get_brand_profile();
		$this->assertArrayHasKey( 'brand_positioning_summary', $brand );
		$this->assertArrayHasKey( Profile_Schema::BRAND_VOICE_TONE, $brand );
		$this->assertSame( '', $brand['brand_voice_summary'] );
	}

	public function test_get_business_profile_returns_normalized_defaults_on_fresh_install(): void {
		$business = $this->store->get_business_profile();
		$this->assertArrayHasKey( 'business_name', $business );
		$this->assertSame( array(), $business[ Profile_Schema::BUSINESS_PERSONAS ] );
	}

	public function test_valid_profile_write_then_read_back(): void {
		$this->store->set_brand_profile( array(
			'brand_positioning_summary' => 'We are the trusted local partner.',
			'brand_voice_summary'       => 'Professional and approachable.',
		) );
		$brand = $this->store->get_brand_profile();
		$this->assertSame( 'We are the trusted local partner.', $brand['brand_positioning_summary'] );
		$this->assertSame( 'Professional and approachable.', $brand['brand_voice_summary'] );

		$this->store->set_business_profile( array(
			'business_name'           => 'Acme LLC',
			'primary_offers_summary'  => 'Tax and bookkeeping',
			'core_geographic_market'   => 'Denver',
		) );
		$business = $this->store->get_business_profile();
		$this->assertSame( 'Acme LLC', $business['business_name'] );
		$this->assertSame( 'Tax and bookkeeping', $business['primary_offers_summary'] );
	}

	public function test_partial_merge_brand_does_not_corrupt_unrelated_fields(): void {
		$this->store->set_brand_profile( array(
			'brand_positioning_summary' => 'Original positioning',
			'preferred_cta_style'       => 'Soft CTA',
		) );
		$this->store->merge_brand_profile( array( 'brand_voice_summary' => 'Friendly only' ) );
		$brand = $this->store->get_brand_profile();
		$this->assertSame( 'Original positioning', $brand['brand_positioning_summary'] );
		$this->assertSame( 'Soft CTA', $brand['preferred_cta_style'] );
		$this->assertSame( 'Friendly only', $brand['brand_voice_summary'] );
	}

	public function test_partial_merge_business_does_not_corrupt_unrelated_fields(): void {
		$this->store->set_business_profile( array(
			'business_name'         => 'Acme',
			'primary_offers_summary' => 'Services',
		) );
		$this->store->merge_business_profile( array( 'core_geographic_market' => 'Boston' ) );
		$business = $this->store->get_business_profile();
		$this->assertSame( 'Acme', $business['business_name'] );
		$this->assertSame( 'Services', $business['primary_offers_summary'] );
		$this->assertSame( 'Boston', $business['core_geographic_market'] );
	}

	public function test_get_full_profile_returns_both_roots(): void {
		$this->store->set_brand_profile( array( 'brand_voice_summary' => 'A' ) );
		$this->store->set_business_profile( array( 'business_name' => 'B' ) );
		$full = $this->store->get_full_profile();
		$this->assertArrayHasKey( Profile_Schema::ROOT_BRAND, $full );
		$this->assertArrayHasKey( Profile_Schema::ROOT_BUSINESS, $full );
		$this->assertSame( 'A', $full[ Profile_Schema::ROOT_BRAND ]['brand_voice_summary'] );
		$this->assertSame( 'B', $full[ Profile_Schema::ROOT_BUSINESS ]['business_name'] );
	}

	public function test_get_full_profile_includes_template_preference_profile(): void {
		$full = $this->store->get_full_profile();
		$this->assertArrayHasKey( Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE, $full );
		$prefs = $full[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ];
		$this->assertIsArray( $prefs );
		$this->assertArrayHasKey( 'page_emphasis', $prefs );
		$this->assertArrayHasKey( 'reduced_motion_preference', $prefs );
	}

	public function test_set_and_get_template_preference_profile(): void {
		$this->store->set_template_preference_profile( array(
			'page_emphasis'       => 'conversion',
			'conversion_posture'  => 'moderate',
			'reduced_motion_preference' => true,
		) );
		$arr = $this->store->get_template_preference_profile_array();
		$this->assertSame( 'conversion', $arr['page_emphasis'] );
		$this->assertSame( 'moderate', $arr['conversion_posture'] );
		$this->assertTrue( $arr['reduced_motion_preference'] );
	}

	public function test_merge_template_preference_profile(): void {
		$this->store->set_template_preference_profile( array(
			'page_emphasis' => 'balanced',
			'proof_style'   => 'testimonials',
		) );
		$this->store->merge_template_preference_profile( array( 'content_density' => 'spacious' ) );
		$arr = $this->store->get_template_preference_profile_array();
		$this->assertSame( 'balanced', $arr['page_emphasis'] );
		$this->assertSame( 'testimonials', $arr['proof_style'] );
		$this->assertSame( 'spacious', $arr['content_density'] );
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
		$this->store->set_template_preference_profile( $example );
		$payload = $this->store->get_template_preference_profile_array();
		$this->assertSame( $example, $payload );
		$this->assertArrayHasKey( 'page_emphasis', $payload );
		$this->assertArrayHasKey( 'reduced_motion_preference', $payload );
	}

	public function test_option_root_is_profile_current(): void {
		$this->store->set_brand_profile( array( 'brand_voice_summary' => 'x' ) );
		$this->assertArrayHasKey( Option_Names::PROFILE_CURRENT, $GLOBALS['_aio_test_options'] );
	}
}
