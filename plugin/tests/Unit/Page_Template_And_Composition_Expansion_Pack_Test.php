<?php
/**
 * Unit tests for page template and composition expansion pack: validation,
 * one-pager metadata completeness, export serialization (spec §13, §14, §16, Prompt 123).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validation_Result;
use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Fragment_Builder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ExpansionPack\Page_Template_And_Composition_Expansion_Pack_Definitions;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Normalizer;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Validator;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Validator;
use AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Definition_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Section/ExpansionPack/Section_Expansion_Pack_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/ExpansionPack/Page_Template_And_Composition_Expansion_Pack_Definitions.php';
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/Registries/Export/Registry_Export_Fragment_Builder.php';

final class Page_Template_And_Composition_Expansion_Pack_Test extends TestCase {

	private Page_Template_Normalizer $pt_normalizer;
	private Page_Template_Validator $pt_validator;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta']    = array();
		$GLOBALS['_aio_wp_query_posts'] = array();
		$section_repo   = new Section_Template_Repository();
		$section_norm   = new Section_Definition_Normalizer();
		$section_valid  = new Section_Validator( $section_norm, $section_repo );
		$section_registry = new Section_Registry_Service( $section_valid, $section_repo );
		$page_repo = new Page_Template_Repository();
		$this->pt_normalizer = new Page_Template_Normalizer();
		$this->pt_validator  = new Page_Template_Validator( $this->pt_normalizer, $page_repo, $section_registry );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'], $GLOBALS['_aio_wp_query_posts'] );
		parent::tearDown();
	}

	public function test_page_template_definitions_have_required_keys(): void {
		$templates = Page_Template_And_Composition_Expansion_Pack_Definitions::page_template_definitions();
		$this->assertCount( 2, $templates );
		$required = Page_Template_Schema::get_required_fields();
		foreach ( $templates as $def ) {
			foreach ( $required as $field ) {
				$this->assertArrayHasKey( $field, $def, "Page template must have required field: {$field}" );
			}
		}
	}

	public function test_each_page_template_passes_completeness_after_normalize(): void {
		foreach ( Page_Template_And_Composition_Expansion_Pack_Definitions::page_template_definitions() as $def ) {
			$normalized = $this->pt_normalizer->normalize( $def );
			$errors = $this->pt_validator->validate_completeness( $normalized );
			$key = (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '?' );
			$this->assertEmpty( $errors, "Page template {$key} should pass completeness: " . implode( ', ', $errors ) );
		}
	}

	public function test_each_page_template_has_one_pager_with_page_purpose_summary(): void {
		foreach ( Page_Template_And_Composition_Expansion_Pack_Definitions::page_template_definitions() as $def ) {
			$one_pager = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
			$this->assertIsArray( $one_pager );
			$summary = (string) ( $one_pager['page_purpose_summary'] ?? '' );
			$this->assertNotSame( '', $summary, 'One-pager page_purpose_summary is required and non-empty' );
		}
	}

	public function test_composition_definitions_have_required_fields_and_valid_status(): void {
		$compositions = Page_Template_And_Composition_Expansion_Pack_Definitions::composition_definitions();
		$this->assertCount( 2, $compositions );
		$required = Composition_Schema::get_required_fields();
		foreach ( $compositions as $def ) {
			foreach ( $required as $field ) {
				$this->assertArrayHasKey( $field, $def, "Composition must have required field: {$field}" );
			}
			$status = (string) ( $def[ Composition_Schema::FIELD_VALIDATION_STATUS ] ?? '' );
			$this->assertSame( Composition_Validation_Result::VALID, $status );
		}
	}

	public function test_composition_definitions_have_source_template_ref(): void {
		foreach ( Page_Template_And_Composition_Expansion_Pack_Definitions::composition_definitions() as $def ) {
			$ref = (string) ( $def[ Composition_Schema::FIELD_SOURCE_TEMPLATE_REF ] ?? '' );
			$this->assertNotSame( '', $ref, 'Curated composition must have source_template_ref (provenance)' );
		}
	}

	public function test_export_fragment_for_page_templates(): void {
		foreach ( Page_Template_And_Composition_Expansion_Pack_Definitions::page_template_definitions() as $def ) {
			$fragment = Registry_Export_Fragment_Builder::for_page_template( $def );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE, $fragment );
			$this->assertSame( Registry_Export_Fragment_Builder::OBJECT_TYPE_PAGE, $fragment[ Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE ] );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_OBJECT_KEY, $fragment );
			$this->assertSame( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ], $fragment[ Registry_Export_Fragment_Builder::KEY_OBJECT_KEY ] );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_PAYLOAD, $fragment );
		}
	}

	public function test_export_fragment_for_compositions(): void {
		foreach ( Page_Template_And_Composition_Expansion_Pack_Definitions::composition_definitions() as $def ) {
			$fragment = Registry_Export_Fragment_Builder::for_composition( $def );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE, $fragment );
			$this->assertSame( Registry_Export_Fragment_Builder::OBJECT_TYPE_COMPOSITION, $fragment[ Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE ] );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_OBJECT_KEY, $fragment );
			$this->assertSame( $def[ Composition_Schema::FIELD_COMPOSITION_ID ], $fragment[ Registry_Export_Fragment_Builder::KEY_OBJECT_KEY ] );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_PAYLOAD, $fragment );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_SOURCE_METADATA, $fragment );
			$this->assertSame( 'valid', $fragment[ Registry_Export_Fragment_Builder::KEY_SOURCE_METADATA ]['validation_status'] ?? '' );
		}
	}

	public function test_template_keys_and_composition_ids_match_constants(): void {
		$templates = Page_Template_And_Composition_Expansion_Pack_Definitions::page_template_definitions();
		$keys = array_column( $templates, Page_Template_Schema::FIELD_INTERNAL_KEY );
		$this->assertContains( Page_Template_And_Composition_Expansion_Pack_Definitions::PAGE_TEMPLATE_LANDING_STATS_CTA_FAQ, $keys );
		$this->assertContains( Page_Template_And_Composition_Expansion_Pack_Definitions::PAGE_TEMPLATE_FAQ_PAGE, $keys );

		$compositions = Page_Template_And_Composition_Expansion_Pack_Definitions::composition_definitions();
		$ids = array_column( $compositions, Composition_Schema::FIELD_COMPOSITION_ID );
		$this->assertContains( Page_Template_And_Composition_Expansion_Pack_Definitions::COMPOSITION_LANDING_STATS_CTA_FAQ, $ids );
		$this->assertContains( Page_Template_And_Composition_Expansion_Pack_Definitions::COMPOSITION_FAQ_CTA, $ids );
	}
}
