<?php
/**
 * Unit tests for Documentation Registry and section helper coverage (Prompts 261–270).
 * Verifies Hero, CTA, Proof, Legal/Policy, Process/FAQ, Feature/Benefit, Media/Listing/Profile, Gap-Closing, Contact/Form/Conversion, and Pricing/Offer section helpers, and page_template_one_pager resolution (Prompts 271–273).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;
use AIOPageBuilder\Domain\Registries\Docs\Documentation_Loader;
use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry;
use AIOPageBuilder\Domain\Registries\Section\CtaSuperLibraryBatch\CTA_Super_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\FeatureBenefitBatch\Feature_Benefit_Value_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\GapClosingSuperBatch\Section_Gap_Closing_Super_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\HeroBatch\Hero_Intro_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\LegalPolicyUtilityBatch\Legal_Policy_Utility_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\MediaListingProfileBatch\Media_Listing_Profile_Detail_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\ProcessTimelineFaqBatch\Process_Timeline_FAQ_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\TrustProofBatch\Trust_Proof_Library_Batch_Definitions;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Documentation/Documentation_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Loader.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Registry.php';
require_once $plugin_root . '/src/Domain/Registries/Section/HeroBatch/Hero_Intro_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/CtaSuperLibraryBatch/CTA_Super_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/TrustProofBatch/Trust_Proof_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/LegalPolicyUtilityBatch/Legal_Policy_Utility_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/ProcessTimelineFaqBatch/Process_Timeline_FAQ_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/FeatureBenefitBatch/Feature_Benefit_Value_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/MediaListingProfileBatch/Media_Listing_Profile_Detail_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/GapClosingSuperBatch/Section_Gap_Closing_Super_Batch_Definitions.php';

final class Documentation_Registry_Test extends TestCase {

	private static string $docs_base_path;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		$plugin_root     = dirname( __DIR__, 2 );
		self::$docs_base_path = $plugin_root . '/src/Domain/Registries/Docs';
	}

	/**
	 * Every Hero batch section has a section_helper doc loadable by section key.
	 */
	public function test_every_hero_section_has_helper_doc(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = Hero_Intro_Library_Batch_Definitions::section_keys();
		foreach ( $keys as $section_key ) {
			$doc = $registry->get_by_section_key( $section_key );
			$this->assertNotNull( $doc, "Hero section {$section_key} must have a helper doc." );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '', "documentation_id must be doc-helper-{$section_key}" );
			$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $section_key, $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * Every CTA batch section has a section_helper doc loadable by section key.
	 */
	public function test_every_cta_section_has_helper_doc(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = CTA_Super_Library_Batch_Definitions::section_keys();
		foreach ( $keys as $section_key ) {
			$doc = $registry->get_by_section_key( $section_key );
			$this->assertNotNull( $doc, "CTA section {$section_key} must have a helper doc." );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '', "documentation_id must be doc-helper-{$section_key}" );
			$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $section_key, $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * Every Proof/Trust batch section has a section_helper doc loadable by section key.
	 */
	public function test_every_proof_section_has_helper_doc(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = Trust_Proof_Library_Batch_Definitions::section_keys();
		foreach ( $keys as $section_key ) {
			$doc = $registry->get_by_section_key( $section_key );
			$this->assertNotNull( $doc, "Proof section {$section_key} must have a helper doc." );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '', "documentation_id must be doc-helper-{$section_key}" );
			$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $section_key, $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * Every Legal/Policy batch section has a section_helper doc loadable by section key.
	 */
	public function test_every_legal_policy_section_has_helper_doc(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = Legal_Policy_Utility_Library_Batch_Definitions::section_keys();
		foreach ( $keys as $section_key ) {
			$doc = $registry->get_by_section_key( $section_key );
			$this->assertNotNull( $doc, "Legal/Policy section {$section_key} must have a helper doc." );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '', "documentation_id must be doc-helper-{$section_key}" );
			$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $section_key, $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * Every Process/FAQ batch section has a section_helper doc loadable by section key.
	 */
	public function test_every_process_faq_section_has_helper_doc(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = Process_Timeline_FAQ_Library_Batch_Definitions::section_keys();
		foreach ( $keys as $section_key ) {
			$doc = $registry->get_by_section_key( $section_key );
			$this->assertNotNull( $doc, "Process/FAQ section {$section_key} must have a helper doc." );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '', "documentation_id must be doc-helper-{$section_key}" );
			$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $section_key, $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * Every Feature/Benefit batch section has a section_helper doc loadable by section key.
	 */
	public function test_every_feature_benefit_section_has_helper_doc(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = Feature_Benefit_Value_Library_Batch_Definitions::section_keys();
		foreach ( $keys as $section_key ) {
			$doc = $registry->get_by_section_key( $section_key );
			$this->assertNotNull( $doc, "Feature/Benefit section {$section_key} must have a helper doc." );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '', "documentation_id must be doc-helper-{$section_key}" );
			$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $section_key, $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * Every Media/Listing/Profile batch section has a section_helper doc loadable by section key.
	 */
	public function test_every_media_listing_profile_section_has_helper_doc(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = Media_Listing_Profile_Detail_Library_Batch_Definitions::section_keys();
		foreach ( $keys as $section_key ) {
			$doc = $registry->get_by_section_key( $section_key );
			$this->assertNotNull( $doc, "Media/Listing/Profile section {$section_key} must have a helper doc." );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '', "documentation_id must be doc-helper-{$section_key}" );
			$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $section_key, $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * Every Gap-Closing batch section has a section_helper doc loadable by section key.
	 */
	public function test_every_gap_closing_section_has_helper_doc(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = Section_Gap_Closing_Super_Batch_Definitions::section_keys();
		foreach ( $keys as $section_key ) {
			$doc = $registry->get_by_section_key( $section_key );
			$this->assertNotNull( $doc, "Gap-Closing section {$section_key} must have a helper doc." );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '', "documentation_id must be doc-helper-{$section_key}" );
			$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $section_key, $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * All loaded section helpers have required schema fields and valid status.
	 */
	public function test_all_section_helpers_are_schema_compliant(): void {
		$loader = new Documentation_Loader( self::$docs_base_path );
		$docs   = $loader->load_section_helpers();
		$this->assertGreaterThan( 0, count( $docs ), 'At least one section helper must be loaded.' );
		$required = Documentation_Schema::get_required_fields();
		foreach ( $docs as $doc ) {
			$this->assertIsArray( $doc );
			foreach ( $required as $field ) {
				$this->assertArrayHasKey( $field, $doc, "Section helper must have required field: {$field}" );
				$this->assertNotSame( '', $doc[ $field ], "Section helper field {$field} must be non-empty." );
			}
			$this->assertTrue(
				Documentation_Schema::is_valid_documentation_type( (string) $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ),
				'documentation_type must be valid.'
			);
			$this->assertTrue(
				Documentation_Schema::is_valid_status( (string) $doc[ Documentation_Schema::FIELD_STATUS ] ),
				'status must be valid.'
			);
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$section_key = $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '';
			$this->assertNotSame( '', $section_key, 'section_helper must have source_reference.section_template_key.' );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
		}
	}

	/**
	 * get_by_id returns the same doc as get_by_section_key for section helpers.
	 */
	public function test_get_by_id_and_get_by_section_key_consistent(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$by_key   = $registry->get_by_section_key( 'hero_conv_01' );
		$this->assertNotNull( $by_key );
		$id = $by_key[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '';
		$this->assertSame( 'doc-helper-hero_conv_01', $id );
		$by_id = $registry->get_by_id( $id );
		$this->assertNotNull( $by_id );
		$this->assertSame( $by_key[ Documentation_Schema::FIELD_CONTENT_BODY ] ?? '', $by_id[ Documentation_Schema::FIELD_CONTENT_BODY ] ?? '' );
	}

	/** Contact/Form/Conversion batch section keys (Prompt 269). */
	private static function contact_form_conversion_section_keys(): array {
		return array(
			'cta_contact_01', 'cta_contact_02', 'cta_inquiry_01', 'cta_inquiry_02',
			'cta_booking_01', 'cta_booking_02', 'cta_quote_request_01', 'cta_quote_request_02',
			'cta_consultation_01', 'cta_consultation_02', 'cta_purchase_01', 'cta_purchase_02',
			'cta_trust_confirm_01', 'cta_trust_confirm_02', 'cta_support_01', 'cta_support_02',
			'lpu_contact_panel_01', 'lpu_contact_detail_01', 'lpu_inquiry_support_01',
		);
	}

	/**
	 * Every Contact/Form/Conversion batch section has a section_helper doc loadable by section key (Prompt 269).
	 */
	public function test_every_contact_form_conversion_section_has_helper_doc(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = self::contact_form_conversion_section_keys();
		foreach ( $keys as $section_key ) {
			$doc = $registry->get_by_section_key( $section_key );
			$this->assertNotNull( $doc, "Contact/Form/Conversion section {$section_key} must have a helper doc." );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
			$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $section_key, $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
		}
	}

	/** Pricing/Offer batch section keys (Prompt 270): fb_* + gc_offer_*. */
	private static function pricing_offer_section_keys(): array {
		return array(
			'fb_offer_compare_01', 'fb_package_summary_01', 'fb_offer_highlight_01',
			'gc_offer_value_01', 'gc_offer_value_02', 'gc_offer_pricing_01',
			'gc_offer_feature_01', 'gc_offer_feature_02', 'gc_offer_local_01',
			'gc_offer_product_01', 'gc_offer_product_02', 'gc_offer_bundle_01', 'gc_offer_compare_01',
			'gc_offer_01', 'gc_offer_02', 'gc_offer_03', 'gc_offer_04', 'gc_offer_05',
			'gc_offer_06', 'gc_offer_07', 'gc_offer_08', 'gc_offer_09', 'gc_offer_10',
		);
	}

	/**
	 * Every Pricing/Offer batch section has a section_helper doc loadable by section key (Prompt 270).
	 */
	public function test_every_pricing_offer_section_has_helper_doc(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = self::pricing_offer_section_keys();
		foreach ( $keys as $section_key ) {
			$doc = $registry->get_by_section_key( $section_key );
			$this->assertNotNull( $doc, "Pricing/Offer section {$section_key} must have a helper doc." );
			$this->assertSame( 'doc-helper-' . $section_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
			$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $section_key, $ref[ Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * Every Top-Level Home page template has a page_template_one_pager loadable by page_template_key (Prompt 271).
	 */
	public function test_every_top_level_home_template_has_one_pager(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = array( 'pt_home_conversion_01', 'pt_home_trust_01' );
		foreach ( $keys as $page_template_key ) {
			$doc = $registry->get_by_page_template_key( $page_template_key );
			$this->assertNotNull( $doc, "Home template {$page_template_key} must have a one-pager doc." );
			$this->assertSame( 'doc-onepager-' . $page_template_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
			$this->assertSame( Documentation_Schema::TYPE_PAGE_TEMPLATE_ONE_PAGER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $page_template_key, $ref[ Documentation_Schema::SOURCE_PAGE_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * Every Top-Level About page template has a page_template_one_pager loadable by page_template_key (Prompt 272).
	 */
	public function test_every_top_level_about_template_has_one_pager(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = array( 'pt_about_story_01', 'pt_about_team_01' );
		foreach ( $keys as $page_template_key ) {
			$doc = $registry->get_by_page_template_key( $page_template_key );
			$this->assertNotNull( $doc, "About template {$page_template_key} must have a one-pager doc." );
			$this->assertSame( 'doc-onepager-' . $page_template_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
			$this->assertSame( Documentation_Schema::TYPE_PAGE_TEMPLATE_ONE_PAGER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $page_template_key, $ref[ Documentation_Schema::SOURCE_PAGE_TEMPLATE_KEY ] ?? '' );
		}
	}

	/**
	 * Every Top-Level Contact page template has a page_template_one_pager loadable by page_template_key (Prompt 273).
	 */
	public function test_every_top_level_contact_template_has_one_pager(): void {
		$loader   = new Documentation_Loader( self::$docs_base_path );
		$registry = new Documentation_Registry( $loader );
		$keys     = array( 'pt_contact_request_01', 'pt_contact_directions_01' );
		foreach ( $keys as $page_template_key ) {
			$doc = $registry->get_by_page_template_key( $page_template_key );
			$this->assertNotNull( $doc, "Contact template {$page_template_key} must have a one-pager doc." );
			$this->assertSame( 'doc-onepager-' . $page_template_key, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
			$this->assertSame( Documentation_Schema::TYPE_PAGE_TEMPLATE_ONE_PAGER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] ?? '' );
			$ref = $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] ?? array();
			$this->assertIsArray( $ref );
			$this->assertSame( $page_template_key, $ref[ Documentation_Schema::SOURCE_PAGE_TEMPLATE_KEY ] ?? '' );
		}
	}
}
