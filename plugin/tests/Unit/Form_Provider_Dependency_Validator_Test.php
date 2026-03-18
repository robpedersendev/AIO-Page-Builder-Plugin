<?php
/**
 * Unit tests for Form_Provider_Dependency_Validator (spec §33.8, §40.4; Prompt 230).
 *
 * Covers validate_for_template (valid/invalid provider, unknown template, form_embed section detection)
 * and template_uses_form_sections.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Pages\Form_Provider_Dependency_Validator;
use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/FormProvider/Form_Provider_Registry.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Execution/Pages/Form_Provider_Dependency_Validator.php';

final class Form_Provider_Dependency_Validator_Test extends TestCase {

	public function test_validate_for_template_returns_valid_true_for_unknown_template(): void {
		$page_repo = $this->createMock( Page_Template_Repository::class );
		$page_repo->method( 'get_definition_by_key' )->with( 'unknown_tpl' )->willReturn( null );

		$section_repo = $this->createMock( Section_Template_Repository::class );
		$registry     = new Form_Provider_Registry();
		$validator    = new Form_Provider_Dependency_Validator( $registry, $page_repo, $section_repo );

		$result = $validator->validate_for_template( 'unknown_tpl' );
		$this->assertTrue( $result['valid'] );
		$this->assertSame( array(), $result['errors'] );
		$this->assertSame( array(), $result['warnings'] );
	}

	public function test_validate_for_template_returns_valid_true_when_no_form_embed_sections(): void {
		$page_def  = array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'sec_hero' ),
			),
		);
		$page_repo = $this->createMock( Page_Template_Repository::class );
		$page_repo->method( 'get_definition_by_key' )->willReturn( $page_def );

		$section_repo = $this->createMock( Section_Template_Repository::class );
		$section_repo->method( 'get_definition_by_key' )->with( 'sec_hero' )->willReturn(
			array(
				Section_Schema::FIELD_CATEGORY => 'hero',
			)
		);
		$registry  = new Form_Provider_Registry();
		$validator = new Form_Provider_Dependency_Validator( $registry, $page_repo, $section_repo );

		$result = $validator->validate_for_template( 'tpl_landing' );
		$this->assertTrue( $result['valid'] );
		$this->assertSame( array(), $result['errors'] );
	}

	public function test_validate_for_template_returns_valid_false_when_form_embed_provider_not_registered(): void {
		$page_def  = array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'form_section_ndr' ),
			),
		);
		$page_repo = $this->createMock( Page_Template_Repository::class );
		$page_repo->method( 'get_definition_by_key' )->willReturn( $page_def );

		$section_def  = array(
			Section_Schema::FIELD_CATEGORY => 'form_embed',
			'field_blueprint'              => array(
				'fields' => array(
					array(
						'name'          => 'form_provider',
						'default_value' => 'ndr_forms',
					),
				),
			),
		);
		$section_repo = $this->createMock( Section_Template_Repository::class );
		$section_repo->method( 'get_definition_by_key' )->with( 'form_section_ndr' )->willReturn( $section_def );

		// Registry has ndr_forms by default; use a custom registry that does NOT have it to simulate missing provider.
		$registry = $this->createMock( Form_Provider_Registry::class );
		$registry->method( 'has_provider' )->with( 'ndr_forms' )->willReturn( false );

		$validator = new Form_Provider_Dependency_Validator( $registry, $page_repo, $section_repo );
		$result    = $validator->validate_for_template( 'pt_request_form' );

		$this->assertFalse( $result['valid'] );
		$this->assertNotEmpty( $result['errors'] );
		$this->assertStringContainsString( 'ndr_forms', $result['errors'][0] );
	}

	public function test_validate_for_template_returns_valid_true_when_form_embed_provider_registered(): void {
		$page_def  = array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'form_section_ndr' ),
			),
		);
		$page_repo = $this->createMock( Page_Template_Repository::class );
		$page_repo->method( 'get_definition_by_key' )->willReturn( $page_def );

		$section_def  = array(
			Section_Schema::FIELD_CATEGORY => 'form_embed',
			'field_blueprint'              => array(
				'fields' => array(
					array(
						'name'          => 'form_provider',
						'default_value' => 'ndr_forms',
					),
				),
			),
		);
		$section_repo = $this->createMock( Section_Template_Repository::class );
		$section_repo->method( 'get_definition_by_key' )->with( 'form_section_ndr' )->willReturn( $section_def );

		$registry  = new Form_Provider_Registry();
		$validator = new Form_Provider_Dependency_Validator( $registry, $page_repo, $section_repo );
		$result    = $validator->validate_for_template( 'pt_request_form' );

		$this->assertTrue( $result['valid'] );
		$this->assertSame( array(), $result['errors'] );
	}

	public function test_template_uses_form_sections_returns_false_for_empty_or_unknown_template(): void {
		$page_repo = $this->createMock( Page_Template_Repository::class );
		$page_repo->method( 'get_definition_by_key' )->willReturn( null );
		$section_repo = $this->createMock( Section_Template_Repository::class );
		$registry     = new Form_Provider_Registry();
		$validator    = new Form_Provider_Dependency_Validator( $registry, $page_repo, $section_repo );

		$this->assertFalse( $validator->template_uses_form_sections( '' ) );
		$this->assertFalse( $validator->template_uses_form_sections( 'unknown' ) );
	}

	public function test_template_uses_form_sections_returns_true_when_template_has_form_embed_section(): void {
		$page_def  = array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'sec_hero' ),
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'form_section_ndr' ),
			),
		);
		$page_repo = $this->createMock( Page_Template_Repository::class );
		$page_repo->method( 'get_definition_by_key' )->willReturn( $page_def );

		$section_repo = $this->createMock( Section_Template_Repository::class );
		$section_repo->method( 'get_definition_by_key' )
			->willReturnMap(
				array(
					array( 'sec_hero', array( Section_Schema::FIELD_CATEGORY => 'hero' ) ),
					array( 'form_section_ndr', array( Section_Schema::FIELD_CATEGORY => 'form_embed' ) ),
				)
			);
		$registry  = new Form_Provider_Registry();
		$validator = new Form_Provider_Dependency_Validator( $registry, $page_repo, $section_repo );

		$this->assertTrue( $validator->template_uses_form_sections( 'pt_request_form' ) );
	}

	public function test_template_uses_form_sections_returns_false_when_no_form_embed_section(): void {
		$page_def  = array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'sec_hero' ),
			),
		);
		$page_repo = $this->createMock( Page_Template_Repository::class );
		$page_repo->method( 'get_definition_by_key' )->willReturn( $page_def );
		$section_repo = $this->createMock( Section_Template_Repository::class );
		$section_repo->method( 'get_definition_by_key' )->with( 'sec_hero' )->willReturn( array( Section_Schema::FIELD_CATEGORY => 'hero' ) );
		$registry  = new Form_Provider_Registry();
		$validator = new Form_Provider_Dependency_Validator( $registry, $page_repo, $section_repo );

		$this->assertFalse( $validator->template_uses_form_sections( 'tpl_landing' ) );
	}
}
