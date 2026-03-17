<?php
/**
 * Unit tests for Industry_Helper_Doc_Composer and Composed_Helper_Doc_Result: base-only, base + overlay,
 * invalid overlay fallback, deterministic output and traceability (Prompt 338).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Composed_Helper_Doc_Result;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Helper_Doc_Composer;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry;
use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Section_Helper_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Composed_Helper_Doc_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Helper_Doc_Composer.php';
require_once $plugin_root . '/src/Domain/Registries/Documentation/Documentation_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Loader.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Registry.php';

final class Industry_Helper_Doc_Composer_Test extends TestCase {

	public function test_base_only_resolution_when_no_overlay(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Section_Helper_Overlay_Registry();
		$overlay_registry->load( array() );
		$composer = new Industry_Helper_Doc_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'test_sec_base_only_999', '' );
		$this->assertInstanceOf( Composed_Helper_Doc_Result::class, $result );
		$this->assertFalse( $result->is_overlay_applied() );
		$this->assertSame( '', $result->get_overlay_industry_key() );
		$this->assertSame( 'test_sec_base_only_999', $result->get_section_key() );
	}

	public function test_base_plus_one_overlay_composition(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Section_Helper_Overlay_Registry();
		$overlay_registry->load( array(
			array(
				Industry_Section_Helper_Overlay_Registry::FIELD_INDUSTRY_KEY => 'legal',
				Industry_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY  => 'test_sec_overlay_998',
				Industry_Section_Helper_Overlay_Registry::FIELD_SCOPE        => Industry_Section_Helper_Overlay_Registry::SCOPE_SECTION_HELPER_OVERLAY,
				Industry_Section_Helper_Overlay_Registry::FIELD_STATUS       => Industry_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
				'tone_notes' => 'Legal tone guidance.',
				'seo_notes'  => 'Legal SEO notes.',
			),
		) );
		$composer = new Industry_Helper_Doc_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'test_sec_overlay_998', 'legal' );
		$this->assertTrue( $result->is_overlay_applied() );
		$this->assertSame( 'legal', $result->get_overlay_industry_key() );
		$doc = $result->get_composed_doc();
		$this->assertSame( 'Legal tone guidance.', $doc['tone_notes'] );
		$this->assertSame( 'Legal SEO notes.', $doc['seo_notes'] );
	}

	public function test_invalid_or_draft_overlay_fails_safely(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Section_Helper_Overlay_Registry();
		$overlay_registry->load( array(
			array(
				Industry_Section_Helper_Overlay_Registry::FIELD_INDUSTRY_KEY => 'legal',
				Industry_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY  => 'test_sec_draft_997',
				Industry_Section_Helper_Overlay_Registry::FIELD_SCOPE        => Industry_Section_Helper_Overlay_Registry::SCOPE_SECTION_HELPER_OVERLAY,
				Industry_Section_Helper_Overlay_Registry::FIELD_STATUS       => 'draft',
				'tone_notes' => 'Should not apply.',
			),
		) );
		$composer = new Industry_Helper_Doc_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'test_sec_draft_997', 'legal' );
		$this->assertFalse( $result->is_overlay_applied() );
		$this->assertSame( '', $result->get_overlay_industry_key() );
		$doc = $result->get_composed_doc();
		$this->assertArrayNotHasKey( 'tone_notes', $doc );
	}

	public function test_no_base_overlay_only_composed_doc_has_overlay_fields(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Section_Helper_Overlay_Registry();
		$overlay_registry->load( array(
			array(
				Industry_Section_Helper_Overlay_Registry::FIELD_INDUSTRY_KEY => 'legal',
				Industry_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY  => 'no_base_section_996',
				Industry_Section_Helper_Overlay_Registry::FIELD_SCOPE        => Industry_Section_Helper_Overlay_Registry::SCOPE_SECTION_HELPER_OVERLAY,
				Industry_Section_Helper_Overlay_Registry::FIELD_STATUS       => Industry_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
				'cta_usage_notes' => 'CTA for legal.',
			),
		) );
		$composer = new Industry_Helper_Doc_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'no_base_section_996', 'legal' );
		$this->assertTrue( $result->is_overlay_applied() );
		$this->assertSame( '', $result->get_base_documentation_id() );
		$doc = $result->get_composed_doc();
		$this->assertSame( 'CTA for legal.', $doc['cta_usage_notes'] );
	}

	public function test_result_to_array_traceability(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Section_Helper_Overlay_Registry();
		$composer = new Industry_Helper_Doc_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'trace_sec_995', '' );
		$arr = $result->to_array();
		$this->assertArrayHasKey( 'section_key', $arr );
		$this->assertArrayHasKey( 'base_documentation_id', $arr );
		$this->assertArrayHasKey( 'overlay_applied', $arr );
		$this->assertArrayHasKey( 'overlay_industry_key', $arr );
		$this->assertArrayHasKey( 'composed_doc_keys', $arr );
		$this->assertSame( 'trace_sec_995', $arr['section_key'] );
	}

	/** Prompt 353: built-in overlays compose correctly; cosmetology_nail + hero_conv_02. */
	public function test_builtin_overlay_composes_with_cosmetology_nail_hero(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Section_Helper_Overlay_Registry();
		$overlay_registry->load( Industry_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions() );
		$composer = new Industry_Helper_Doc_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'hero_conv_02', 'cosmetology_nail' );
		$this->assertTrue( $result->is_overlay_applied() );
		$this->assertSame( 'cosmetology_nail', $result->get_overlay_industry_key() );
		$doc = $result->get_composed_doc();
		$this->assertArrayHasKey( 'tone_notes', $doc );
		$this->assertStringContainsString( 'Warm', $doc['tone_notes'] );
		$this->assertArrayHasKey( 'cta_usage_notes', $doc );
	}

	/** Prompt 401: second-wave overlays load and are discoverable. */
	public function test_builtin_overlays_include_second_wave_section_keys(): void {
		$definitions = Industry_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions();
		$overlay_registry = new Industry_Section_Helper_Overlay_Registry();
		$overlay_registry->load( $definitions );
		$this->assertNotNull( $overlay_registry->get( 'cosmetology_nail', 'mlp_gallery_01' ), 'Second-wave cosmetology gallery overlay must be present.' );
		$this->assertNotNull( $overlay_registry->get( 'realtor', 'mlp_location_info_01' ), 'Second-wave realtor location overlay must be present.' );
		$this->assertNotNull( $overlay_registry->get( 'plumber', 'tp_certification_01' ), 'Second-wave plumber certification overlay must be present.' );
		$this->assertNotNull( $overlay_registry->get( 'disaster_recovery', 'tp_reassurance_01' ), 'Second-wave disaster_recovery reassurance overlay must be present.' );
		$ov = $overlay_registry->get( 'cosmetology_nail', 'mlp_gallery_01' );
		$this->assertArrayHasKey( 'tone_notes', $ov );
		$this->assertSame( 'section_helper_overlay', $ov['scope'] ?? '' );
		$this->assertSame( 'active', $ov['status'] ?? '' );
	}

	/** Prompt 401: second-wave overlay composes (base + overlay). */
	public function test_builtin_second_wave_overlay_composition_cosmetology_gallery(): void {
		$doc_registry = new Documentation_Registry();
		$overlay_registry = new Industry_Section_Helper_Overlay_Registry();
		$overlay_registry->load( Industry_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions() );
		$composer = new Industry_Helper_Doc_Composer( $doc_registry, $overlay_registry );
		$result = $composer->compose( 'mlp_gallery_01', 'cosmetology_nail' );
		$this->assertTrue( $result->is_overlay_applied() );
		$this->assertSame( 'cosmetology_nail', $result->get_overlay_industry_key() );
		$doc = $result->get_composed_doc();
		$this->assertArrayHasKey( 'tone_notes', $doc );
		$this->assertStringContainsString( 'Warm', $doc['tone_notes'] );
		$this->assertArrayHasKey( 'cta_usage_notes', $doc );
	}
}
