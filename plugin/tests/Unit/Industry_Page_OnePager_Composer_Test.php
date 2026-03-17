<?php
/**
 * Unit tests for Industry_Page_OnePager_Composer and Composed_Page_OnePager_Result: base-only, base + overlay,
 * invalid overlay fallback, ordered sections preserved (Prompt 339).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Composed_Page_OnePager_Result;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Composer;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry;
use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Page_OnePager_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Subtype_Page_OnePager_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Composed_Page_OnePager_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Page_OnePager_Composer.php';
require_once $plugin_root . '/src/Domain/Registries/Documentation/Documentation_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Loader.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Registry.php';

final class Industry_Page_OnePager_Composer_Test extends TestCase {

	public function test_base_only_when_no_overlay(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Page_OnePager_Overlay_Registry();
		$overlay_registry->load( array() );
		$composer = new Industry_Page_OnePager_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'pt_test_base_999', '' );
		$this->assertInstanceOf( Composed_Page_OnePager_Result::class, $result );
		$this->assertFalse( $result->is_overlay_applied() );
		$this->assertSame( '', $result->get_overlay_industry_key() );
		$this->assertSame( 'pt_test_base_999', $result->get_page_template_key() );
	}

	public function test_base_plus_one_overlay_composition(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Page_OnePager_Overlay_Registry();
		$overlay_registry->load( array(
			array(
				Industry_Page_OnePager_Overlay_Registry::FIELD_INDUSTRY_KEY      => 'legal',
				Industry_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_overlay_998',
				Industry_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Industry_Page_OnePager_Overlay_Registry::SCOPE_PAGE_ONEPAGER_OVERLAY,
				Industry_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Industry_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
				'hierarchy_hints'   => 'Hub then child-detail.',
				'lpagery_seo_notes' => 'Legal SEO notes.',
			),
		) );
		$composer = new Industry_Page_OnePager_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'pt_overlay_998', 'legal' );
		$this->assertTrue( $result->is_overlay_applied() );
		$this->assertSame( 'legal', $result->get_overlay_industry_key() );
		$onepager = $result->get_composed_onepager();
		$this->assertSame( 'Hub then child-detail.', $onepager['hierarchy_hints'] );
		$this->assertSame( 'Legal SEO notes.', $onepager['lpagery_seo_notes'] );
	}

	public function test_draft_overlay_fails_safely_to_base(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Page_OnePager_Overlay_Registry();
		$overlay_registry->load( array(
			array(
				Industry_Page_OnePager_Overlay_Registry::FIELD_INDUSTRY_KEY      => 'legal',
				Industry_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_draft_997',
				Industry_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Industry_Page_OnePager_Overlay_Registry::SCOPE_PAGE_ONEPAGER_OVERLAY,
				Industry_Page_OnePager_Overlay_Registry::FIELD_STATUS           => 'draft',
				'cta_strategy' => 'Should not apply.',
			),
		) );
		$composer = new Industry_Page_OnePager_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'pt_draft_997', 'legal' );
		$this->assertFalse( $result->is_overlay_applied() );
		$onepager = $result->get_composed_onepager();
		$this->assertArrayNotHasKey( 'cta_strategy', $onepager );
	}

	public function test_ordered_sections_preserved_in_composed(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Page_OnePager_Overlay_Registry();
		$overlay_registry->load( array() );
		$composer = new Industry_Page_OnePager_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'pt_ordered_996', 'legal' );
		$onepager = $result->get_composed_onepager();
		$this->assertIsArray( $onepager );
		$arr = $result->to_array();
		$this->assertSame( 'pt_ordered_996', $arr['page_template_key'] );
		$this->assertArrayHasKey( 'composed_onepager_keys', $arr );
	}

	public function test_result_to_array_traceability(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Page_OnePager_Overlay_Registry();
		$composer = new Industry_Page_OnePager_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'pt_trace_995', '' );
		$arr = $result->to_array();
		$this->assertArrayHasKey( 'page_template_key', $arr );
		$this->assertArrayHasKey( 'base_documentation_id', $arr );
		$this->assertArrayHasKey( 'overlay_applied', $arr );
		$this->assertArrayHasKey( 'overlay_industry_key', $arr );
	}

	public function test_compose_with_subtype_overlay_merges_subtype_content(): void {
		$doc_registry = new Documentation_Registry();
		$industry_overlay = new Industry_Page_OnePager_Overlay_Registry();
		$industry_overlay->load( array(
			array(
				Industry_Page_OnePager_Overlay_Registry::FIELD_INDUSTRY_KEY      => 'realtor',
				Industry_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
				Industry_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Industry_Page_OnePager_Overlay_Registry::SCOPE_PAGE_ONEPAGER_OVERLAY,
				Industry_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Industry_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
				'cta_strategy' => 'Industry-level CTA.',
			),
		) );
		$subtype_overlay = new Subtype_Page_OnePager_Overlay_Registry();
		$subtype_overlay->load( array(
			array(
				Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'realtor_buyer_agent',
				Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
				Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
				Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
				'cta_strategy' => 'Primary: start your search, get buyer updates (subtype).',
			),
		) );
		$composer = new Industry_Page_OnePager_Composer( $doc_registry, $industry_overlay, null, $subtype_overlay );
		$result = $composer->compose( 'pt_home_conversion_01', 'realtor', 'realtor_buyer_agent' );
		$onepager = $result->get_composed_onepager();
		$this->assertSame( 'Primary: start your search, get buyer updates (subtype).', $onepager['cta_strategy'] ?? '' );
		$this->assertTrue( $result->is_overlay_applied() );
		$this->assertSame( 'realtor', $result->get_overlay_industry_key() );
	}

	public function test_compose_with_empty_subtype_key_skips_subtype_overlay(): void {
		$doc_registry = new Documentation_Registry();
		$industry_overlay = new Industry_Page_OnePager_Overlay_Registry();
		$industry_overlay->load( array(
			array(
				Industry_Page_OnePager_Overlay_Registry::FIELD_INDUSTRY_KEY      => 'realtor',
				Industry_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
				Industry_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Industry_Page_OnePager_Overlay_Registry::SCOPE_PAGE_ONEPAGER_OVERLAY,
				Industry_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Industry_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
				'cta_strategy' => 'Industry-level CTA only.',
			),
		) );
		$subtype_overlay = new Subtype_Page_OnePager_Overlay_Registry();
		$subtype_overlay->load( array(
			array(
				Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'realtor_buyer_agent',
				Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
				Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
				Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
				'cta_strategy' => 'Should not appear.',
			),
		) );
		$composer = new Industry_Page_OnePager_Composer( $doc_registry, $industry_overlay, null, $subtype_overlay );
		$result = $composer->compose( 'pt_home_conversion_01', 'realtor', '' );
		$onepager = $result->get_composed_onepager();
		$this->assertSame( 'Industry-level CTA only.', $onepager['cta_strategy'] ?? '' );
	}
}
