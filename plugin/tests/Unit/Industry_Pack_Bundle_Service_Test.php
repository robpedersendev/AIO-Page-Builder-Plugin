<?php
/**
 * Unit tests for Industry_Pack_Bundle_Service: build_bundle structure, validate_bundle (Prompt 394).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Export/Industry_Pack_Bundle_Service.php';

final class Industry_Pack_Bundle_Service_Test extends TestCase {

	public function test_build_bundle_returns_manifest_and_payload(): void {
		$service = new Industry_Pack_Bundle_Service();
		$sources = array(
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_CTA_PATTERNS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SEO_GUIDANCE => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_LPAGERY_RULES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SECTION_HELPER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_QUESTION_PACKS => array(),
		);
		$bundle  = $service->build_bundle( array(), $sources );
		$this->assertSame( Industry_Pack_Bundle_Service::BUNDLE_VERSION, $bundle[ Industry_Pack_Bundle_Service::MANIFEST_BUNDLE_VERSION ] );
		$this->assertArrayHasKey( Industry_Pack_Bundle_Service::MANIFEST_SCHEMA_VERSION, $bundle );
		$this->assertArrayHasKey( Industry_Pack_Bundle_Service::MANIFEST_CREATED_AT, $bundle );
		$this->assertArrayHasKey( Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES, $bundle );
		$this->assertIsArray( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] );
		$this->assertArrayHasKey( Industry_Pack_Bundle_Service::PAYLOAD_PACKS, $bundle );
		$this->assertArrayHasKey( Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES, $bundle );
		$this->assertArrayHasKey( Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS, $bundle );
		$this->assertArrayNotHasKey( Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE, $bundle );
	}

	public function test_build_bundle_with_include_site_profile_adds_site_profile(): void {
		$service = new Industry_Pack_Bundle_Service();
		$sources = array(
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_CTA_PATTERNS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SEO_GUIDANCE => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_LPAGERY_RULES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SECTION_HELPER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_QUESTION_PACKS => array(),
		);
		$bundle  = $service->build_bundle(
			array(
				'include_site_profile' => true,
				'industry_profile'     => array( 'primary_industry_key' => 'realtor' ),
				'applied_preset'       => array( 'preset_key' => 'realtor_warm' ),
			),
			$sources
		);
		$this->assertContains( Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE, $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] );
		$this->assertArrayHasKey( Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE, $bundle );
		$this->assertSame( 'realtor', $bundle[ Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE ]['industry_profile']['primary_industry_key'] );
		$this->assertSame( 'realtor_warm', $bundle[ Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE ]['applied_preset']['preset_key'] );
	}

	public function test_validate_bundle_accepts_valid_bundle(): void {
		$service = new Industry_Pack_Bundle_Service();
		$sources = array(
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_CTA_PATTERNS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SEO_GUIDANCE => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_LPAGERY_RULES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SECTION_HELPER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_QUESTION_PACKS => array(),
		);
		$bundle  = $service->build_bundle( array(), $sources );
		$errors  = $service->validate_bundle( $bundle );
		$this->assertSame( array(), $errors );
	}

	public function test_validate_bundle_rejects_missing_bundle_version(): void {
		$service = new Industry_Pack_Bundle_Service();
		$sources = array(
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_CTA_PATTERNS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SEO_GUIDANCE => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_LPAGERY_RULES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SECTION_HELPER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_QUESTION_PACKS => array(),
		);
		$bundle  = $service->build_bundle( array(), $sources );
		unset( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_BUNDLE_VERSION ] );
		$errors = $service->validate_bundle( $bundle );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'bundle_version', implode( ' ', $errors ) );
	}

	public function test_validate_bundle_rejects_unsupported_bundle_version(): void {
		$service = new Industry_Pack_Bundle_Service();
		$bundle  = array(
			Industry_Pack_Bundle_Service::MANIFEST_BUNDLE_VERSION => '99',
			Industry_Pack_Bundle_Service::MANIFEST_SCHEMA_VERSION => '1',
			Industry_Pack_Bundle_Service::MANIFEST_CREATED_AT => gmdate( 'c' ),
			Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES => array( Industry_Pack_Bundle_Service::PAYLOAD_PACKS ),
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(),
		);
		$errors  = $service->validate_bundle( $bundle );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'Unsupported', implode( ' ', $errors ) );
	}

	public function test_validate_bundle_rejects_invalid_included_categories(): void {
		$service = new Industry_Pack_Bundle_Service();
		$sources = array(
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_CTA_PATTERNS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SEO_GUIDANCE => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_LPAGERY_RULES => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_SECTION_HELPER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => array(),
			Industry_Pack_Bundle_Service::PAYLOAD_QUESTION_PACKS => array(),
		);
		$bundle  = $service->build_bundle( array(), $sources );
		$bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] = array( 'unknown_category' );
		$errors = $service->validate_bundle( $bundle );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'Unknown category', implode( ' ', $errors ) );
	}
}
