<?php
/**
 * Unit tests for Profile_Normalizer: defaults, enum sanitization, URL validation, prohibited fields (spec §22.12).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Normalizer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Storage/Profile/Profile_Normalizer.php';

final class Profile_Normalizer_Test extends TestCase {

	private Profile_Normalizer $normalizer;

	protected function setUp(): void {
		parent::setUp();
		$this->normalizer = new Profile_Normalizer();
	}

	public function test_normalize_brand_profile_returns_full_shape_with_defaults(): void {
		$out = $this->normalizer->normalize_brand_profile( array() );
		$this->assertArrayHasKey( 'brand_positioning_summary', $out );
		$this->assertArrayHasKey( 'brand_voice_summary', $out );
		$this->assertArrayHasKey( Profile_Schema::BRAND_VOICE_TONE, $out );
		$this->assertArrayHasKey( Profile_Schema::BRAND_ASSET_REFERENCES, $out );
		$this->assertSame( '', $out['brand_positioning_summary'] );
		$this->assertIsArray( $out[ Profile_Schema::BRAND_VOICE_TONE ] );
		$this->assertSame( array(), $out[ Profile_Schema::BRAND_ASSET_REFERENCES ] );
	}

	public function test_normalize_brand_profile_sanitizes_formality_level_enum(): void {
		$out = $this->normalizer->normalize_brand_profile( array(
			'voice_tone' => array( 'formality_level' => 'neutral' ),
		) );
		$this->assertSame( 'neutral', $out[ Profile_Schema::BRAND_VOICE_TONE ]['formality_level'] );
	}

	public function test_normalize_brand_profile_rejects_invalid_formality_level(): void {
		$out = $this->normalizer->normalize_brand_profile( array(
			'voice_tone' => array( 'formality_level' => 'super_casual' ),
		) );
		$this->assertSame( '', $out[ Profile_Schema::BRAND_VOICE_TONE ]['formality_level'] );
	}

	public function test_normalize_brand_profile_sanitizes_clarity_vs_sophistication(): void {
		$out = $this->normalizer->normalize_brand_profile( array(
			'voice_tone' => array( 'clarity_vs_sophistication' => 'sophistication' ),
		) );
		$this->assertSame( 'sophistication', $out[ Profile_Schema::BRAND_VOICE_TONE ]['clarity_vs_sophistication'] );
	}

	public function test_normalize_business_profile_returns_full_shape_with_defaults(): void {
		$out = $this->normalizer->normalize_business_profile( array() );
		$this->assertArrayHasKey( 'business_name', $out );
		$this->assertArrayHasKey( Profile_Schema::BUSINESS_PERSONAS, $out );
		$this->assertArrayHasKey( Profile_Schema::BUSINESS_SERVICES_OFFERS, $out );
		$this->assertArrayHasKey( Profile_Schema::BUSINESS_COMPETITORS, $out );
		$this->assertArrayHasKey( Profile_Schema::BUSINESS_GEOGRAPHY, $out );
		$this->assertSame( array(), $out[ Profile_Schema::BUSINESS_PERSONAS ] );
	}

	public function test_normalize_business_profile_sanitizes_dedicated_pages_likely(): void {
		$out = $this->normalizer->normalize_business_profile( array(
			'services_offers' => array(
				array( 'name' => 'Tax', 'dedicated_pages_likely' => 'yes' ),
				array( 'name' => 'Other', 'dedicated_pages_likely' => 'invalid' ),
			),
		) );
		$this->assertSame( 'yes', $out[ Profile_Schema::BUSINESS_SERVICES_OFFERS ][0]['dedicated_pages_likely'] );
		$this->assertSame( '', $out[ Profile_Schema::BUSINESS_SERVICES_OFFERS ][1]['dedicated_pages_likely'] );
	}

	public function test_normalize_business_profile_sanitizes_in_person_vs_remote(): void {
		$out = $this->normalizer->normalize_business_profile( array(
			'geography' => array(
				array( 'primary_location' => 'Denver', 'in_person_vs_remote' => 'both' ),
				array( 'in_person_vs_remote' => 'invalid_enum' ),
			),
		) );
		$this->assertSame( 'both', $out[ Profile_Schema::BUSINESS_GEOGRAPHY ][0]['in_person_vs_remote'] );
		$this->assertSame( '', $out[ Profile_Schema::BUSINESS_GEOGRAPHY ][1]['in_person_vs_remote'] );
	}

	public function test_normalize_business_profile_invalid_site_url_stored_empty(): void {
		$out = $this->normalizer->normalize_business_profile( array( 'current_site_url' => 'not-a-valid-url' ) );
		$this->assertSame( '', $out['current_site_url'] );
	}

	public function test_normalize_business_profile_valid_site_url_preserved(): void {
		$out = $this->normalizer->normalize_business_profile( array( 'current_site_url' => 'https://example.com' ) );
		$this->assertSame( 'https://example.com', $out['current_site_url'] );
	}

	public function test_validate_business_profile_adds_error_for_invalid_url_but_returns_sanitized(): void {
		$result = $this->normalizer->validate_business_profile( array( 'current_site_url' => 'not-a-url' ) );
		$this->assertFalse( $result->valid );
		$this->assertNotEmpty( $result->errors );
		$this->assertNotNull( $result->sanitized_payload );
		$this->assertSame( '', $result->sanitized_payload['current_site_url'] );
	}

	public function test_prohibited_secret_like_fields_not_stored_in_brand(): void {
		$out = $this->normalizer->normalize_brand_profile( array(
			'brand_voice_summary' => 'Fine',
			'api_key'            => 'secret123',
			'password'           => 'hidden',
		) );
		$this->assertArrayNotHasKey( 'api_key', $out );
		$this->assertArrayNotHasKey( 'password', $out );
		$this->assertSame( 'Fine', $out['brand_voice_summary'] );
	}

	public function test_asset_references_role_enum_sanitized(): void {
		$out = $this->normalizer->normalize_brand_profile( array(
			'asset_references' => array(
				array( 'role' => 'logo', 'notes' => 'Main' ),
				array( 'role' => 'invalid_role', 'notes' => 'X' ),
			),
		) );
		$this->assertSame( 'logo', $out[ Profile_Schema::BRAND_ASSET_REFERENCES ][0]['role'] );
		$this->assertSame( '', $out[ Profile_Schema::BRAND_ASSET_REFERENCES ][1]['role'] );
	}
}
