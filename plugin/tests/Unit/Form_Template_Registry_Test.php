<?php
/**
 * Unit tests for bundled form template registry persistence and visibility (Prompt 227).
 * Verifies definition shape, has_bundled_* helpers, ensure_bundled_form_templates result shape, and section reference in request page template.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\FormProvider\Form_Integration_Definitions;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/FormProvider/Form_Provider_Registry.php';
require_once $plugin_root . '/src/Domain/FormProvider/Form_Integration_Definitions.php';
require_once $plugin_root . '/src/Domain/FormProvider/Form_Template_Seeder.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Deprecation_Metadata.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Definition_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Registry_Result.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Registry_Service.php';

final class Form_Template_Registry_Test extends TestCase {

	private Section_Template_Repository $section_repo;
	private Section_Registry_Service $section_registry;
	private Page_Template_Repository $page_repo;
	private Page_Template_Registry_Service $page_registry;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta']   = array();
		$GLOBALS['_aio_wp_query_posts'] = array();
		$this->section_repo   = new Section_Template_Repository();
		$section_normalizer   = new \AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer();
		$section_validator   = new \AIOPageBuilder\Domain\Registries\Section\Section_Validator( $section_normalizer, $this->section_repo );
		$this->section_registry = new Section_Registry_Service( $section_validator, $this->section_repo );
		$this->page_repo    = new Page_Template_Repository();
		$page_normalizer   = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Normalizer();
		$page_validator    = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Validator( $page_normalizer, $this->page_repo, $this->section_registry );
		$this->page_registry = new Page_Template_Registry_Service( $page_validator, $this->page_repo );
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_wp_insert_post_return'],
			$GLOBALS['_aio_post_meta']
		);
		parent::tearDown();
	}

	public function test_request_page_template_definition_references_form_section(): void {
		$def = Form_Integration_Definitions::request_page_template_definition();
		$this->assertSame( Form_Integration_Definitions::REQUEST_PAGE_TEMPLATE_KEY, $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		$this->assertNotEmpty( $ordered );
		$first = $ordered[0];
		$this->assertSame( Form_Integration_Definitions::FORM_SECTION_KEY, $first[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
	}

	public function test_form_section_definition_has_form_embed_category(): void {
		$def = Form_Integration_Definitions::form_section_definition();
		$this->assertSame( Form_Integration_Definitions::FORM_SECTION_KEY, $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$this->assertSame( 'form_embed', $def[ Section_Schema::FIELD_CATEGORY ] ?? '' );
		$this->assertArrayHasKey( 'field_blueprint', $def );
	}

	public function test_has_bundled_form_section_returns_false_when_not_in_registry(): void {
		$GLOBALS['_aio_wp_query_posts'] = array();
		$this->assertFalse( $this->section_registry->has_bundled_form_section() );
	}

	public function test_has_bundled_request_form_template_returns_false_when_not_in_registry(): void {
		$GLOBALS['_aio_wp_query_posts'] = array();
		$this->assertFalse( $this->page_registry->has_bundled_request_form_template() );
	}

	public function test_ensure_bundled_form_templates_returns_result_shape(): void {
		$GLOBALS['_aio_wp_query_posts'] = array();
		$GLOBALS['_aio_wp_insert_post_return'] = 101;
		$result = $this->section_registry->ensure_bundled_form_templates( $this->page_repo );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'section_id', $result );
		$this->assertArrayHasKey( 'page_id', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertIsArray( $result['errors'] );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 101, $result['section_id'] );
		$this->assertSame( 101, $result['page_id'] );
	}
}
