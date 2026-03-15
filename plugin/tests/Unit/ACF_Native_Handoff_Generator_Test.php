<?php
/**
 * Unit tests for ACF native handoff generator (Prompt 315).
 * Covers: result shape when ACF unavailable; marker and overwrite logic documented in contract.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder;
use AIOPageBuilder\Domain\ACF\Uninstall\ACF_Native_Handoff_Generator;
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
require_once $plugin_root . '/src/Domain/ACF/Uninstall/ACF_Handoff_Group_Marker.php';
require_once $plugin_root . '/src/Domain/ACF/Uninstall/ACF_Native_Handoff_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Field_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Builder.php';

final class ACF_Native_Handoff_Generator_Test extends TestCase {

	/**
	 * When ACF import/get functions are not available, generate_handoff returns errors and zero imported.
	 */
	public function test_generate_handoff_when_acf_unavailable_returns_errors_and_zero_imported(): void {
		$repo      = $this->createMock( Section_Template_Repository_Interface::class );
		$repo->method( 'get_all_internal_keys' )->willReturn( array( 'st01_hero' ) );
		$blueprint = $this->createMock( Section_Field_Blueprint_Service_Interface::class );
		$blueprint->method( 'get_blueprint_for_section' )->willReturn( $this->minimal_blueprint() );
		$inventory = new ACF_Uninstall_Inventory_Service( $repo, $blueprint );
		$group_builder = new ACF_Group_Builder( new ACF_Field_Builder() );
		$generator = new ACF_Native_Handoff_Generator( $inventory, $blueprint, $group_builder );

		$result = $generator->generate_handoff();

		$this->assertArrayHasKey( 'imported', $result );
		$this->assertArrayHasKey( 'skipped_existing', $result );
		$this->assertArrayHasKey( 'skipped_no_blueprint', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertSame( 0, $result['imported'] );
		$this->assertIsArray( $result['errors'] );
		if ( ! function_exists( 'acf_import_field_group' ) || ! function_exists( 'acf_get_field_group' ) ) {
			$this->assertNotEmpty( $result['errors'], 'When ACF is unavailable, errors must be non-empty.' );
		}
	}

	private function minimal_blueprint(): array {
		return array(
			Field_Blueprint_Schema::SECTION_KEY     => 'st01_hero',
			Field_Blueprint_Schema::SECTION_VERSION => '1',
			Field_Blueprint_Schema::LABEL           => 'Hero Fields',
			Field_Blueprint_Schema::FIELDS          => array(
				array( 'key' => 'field_st01_hero_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text' ),
			),
		);
	}
}
