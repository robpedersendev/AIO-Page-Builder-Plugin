<?php
/**
 * Unit tests for ACF uninstall inventory (Prompt 314).
 * Covers: plugin-owned runtime groups identified, unrelated groups not misclassified, read-only enumeration.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Uninstall\ACF_Uninstall_Inventory_Result;
use AIOPageBuilder\Domain\ACF\Uninstall\ACF_Uninstall_Inventory_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Uninstall/ACF_Uninstall_Inventory_Result.php';
require_once $plugin_root . '/src/Domain/ACF/Uninstall/ACF_Uninstall_Inventory_Service.php';

final class ACF_Uninstall_Inventory_Service_Test extends TestCase {

	public function test_identifies_plugin_runtime_group_keys_from_section_registry(): void {
		$section_keys = array( 'st01_hero', 'st05_faq' );
		$repo        = $this->create_repository_mock_returning_section_keys( $section_keys );
		$blueprint   = $this->create_blueprint_service_mock_with_two_sections();
		$service     = new ACF_Uninstall_Inventory_Service( $repo, $blueprint );

		$result = $service->build_inventory();

		$this->assertInstanceOf( ACF_Uninstall_Inventory_Result::class, $result );
		$group_keys = $result->get_plugin_runtime_group_keys();
		$this->assertContains( 'group_aio_st01_hero', $group_keys );
		$this->assertContains( 'group_aio_st05_faq', $group_keys );
		$this->assertCount( 2, $group_keys );
	}

	public function test_does_not_include_unrelated_or_native_acf_groups(): void {
		$section_keys = array( 'st01_hero' );
		$repo        = $this->create_repository_mock_returning_section_keys( $section_keys );
		$blueprint   = $this->create_blueprint_service_mock_with_one_section();
		$service     = new ACF_Uninstall_Inventory_Service( $repo, $blueprint );

		$result = $service->build_inventory();

		$group_keys = $result->get_plugin_runtime_group_keys();
		$this->assertNotContains( 'group_other_plugin', $group_keys );
		$this->assertNotContains( 'group_some_third_party', $group_keys );
		$this->assertCount( 1, $group_keys );
		$this->assertSame( 'group_aio_st01_hero', $group_keys[0] );
	}

	public function test_enumerates_field_definitions_and_value_meta_keys_without_mutation(): void {
		$section_keys = array( 'st01_hero' );
		$repo        = $this->create_repository_mock_returning_section_keys( $section_keys );
		$blueprint   = $this->create_blueprint_service_mock_with_one_section();
		$service     = new ACF_Uninstall_Inventory_Service( $repo, $blueprint );

		$result = $service->build_inventory();

		$defs = $result->get_field_definitions();
		$this->assertNotEmpty( $defs );
		$first = $defs[0];
		$this->assertArrayHasKey( 'group_key', $first );
		$this->assertArrayHasKey( 'field_key', $first );
		$this->assertArrayHasKey( 'field_name', $first );
		$this->assertSame( 'group_aio_st01_hero', $first['group_key'] );

		$meta_keys = $result->get_value_meta_keys();
		$this->assertContains( 'headline', $meta_keys );
	}

	public function test_persistent_group_keys_empty_by_default(): void {
		$section_keys = array( 'st01_hero' );
		$repo        = $this->create_repository_mock_returning_section_keys( $section_keys );
		$blueprint   = $this->create_blueprint_service_mock_with_one_section();
		$service     = new ACF_Uninstall_Inventory_Service( $repo, $blueprint );

		$result = $service->build_inventory();

		$this->assertSame( array(), $result->get_persistent_group_keys() );
	}

	public function test_cleanup_transient_prefixes_included(): void {
		$repo      = $this->create_repository_mock_returning_section_keys( array() );
		$blueprint = $this->create_blueprint_service_mock_with_one_section();
		$service   = new ACF_Uninstall_Inventory_Service( $repo, $blueprint );

		$result = $service->build_inventory();

		$prefixes = $result->get_cleanup_transient_prefixes();
		$this->assertContains( 'aio_acf_sk_p_', $prefixes );
		$this->assertContains( 'aio_acf_sk_t_', $prefixes );
		$this->assertContains( 'aio_acf_sk_c_', $prefixes );
	}

	public function test_empty_section_list_produces_empty_runtime_groups(): void {
		$repo      = $this->create_repository_mock_returning_section_keys( array() );
		$blueprint = $this->create_blueprint_service_mock_with_one_section();
		$service   = new ACF_Uninstall_Inventory_Service( $repo, $blueprint );

		$result = $service->build_inventory();

		$this->assertSame( array(), $result->get_plugin_runtime_group_keys() );
		$this->assertSame( array(), $result->get_field_definitions() );
		$this->assertSame( array(), $result->get_value_meta_keys() );
	}

	private function create_repository_mock_returning_section_keys( array $section_keys ): Section_Template_Repository_Interface {
		$repo = $this->createMock( Section_Template_Repository_Interface::class );
		$repo->method( 'get_all_internal_keys' )->willReturn( $section_keys );
		return $repo;
	}

	private function create_blueprint_service_mock_with_one_section(): Section_Field_Blueprint_Service_Interface {
		$blueprint_st01 = array(
			Field_Blueprint_Schema::SECTION_KEY     => 'st01_hero',
			Field_Blueprint_Schema::SECTION_VERSION => '1',
			Field_Blueprint_Schema::LABEL           => 'Hero Fields',
			Field_Blueprint_Schema::FIELDS        => array(
				array( 'key' => 'field_st01_hero_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text' ),
			),
		);
		$mock = $this->createMock( Section_Field_Blueprint_Service_Interface::class );
		$mock->method( 'get_blueprint_for_section' )
			->willReturnMap( array(
				array( 'st01_hero', null, $blueprint_st01 ),
				array( 'st05_faq', null, $this->blueprint_st05_faq() ),
			) );
		return $mock;
	}

	private function create_blueprint_service_mock_with_two_sections(): Section_Field_Blueprint_Service_Interface {
		$mock = $this->createMock( Section_Field_Blueprint_Service_Interface::class );
		$mock->method( 'get_blueprint_for_section' )
			->willReturnMap( array(
				array( 'st01_hero', null, $this->blueprint_st01_hero() ),
				array( 'st05_faq', null, $this->blueprint_st05_faq() ),
			) );
		return $mock;
	}

	private function blueprint_st01_hero(): array {
		return array(
			Field_Blueprint_Schema::SECTION_KEY     => 'st01_hero',
			Field_Blueprint_Schema::SECTION_VERSION => '1',
			Field_Blueprint_Schema::LABEL           => 'Hero Fields',
			Field_Blueprint_Schema::FIELDS         => array(
				array( 'key' => 'field_st01_hero_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text' ),
			),
		);
	}

	private function blueprint_st05_faq(): array {
		return array(
			Field_Blueprint_Schema::SECTION_KEY     => 'st05_faq',
			Field_Blueprint_Schema::SECTION_VERSION => '1',
			Field_Blueprint_Schema::LABEL           => 'FAQ Fields',
			Field_Blueprint_Schema::FIELDS         => array(
				array( 'key' => 'field_st05_faq_question', 'name' => 'question', 'label' => 'Question', 'type' => 'text' ),
			),
		);
	}
}
