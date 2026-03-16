<?php
/**
 * Unit tests for Subtype_Starter_Bundle_Selection_View_Model: from_profile, parent vs subtype bundles,
 * can_clear_to_parent, display_bundles (Prompt 449).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\ViewModels\Industry\Subtype_Starter_Bundle_Selection_View_Model;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $plugin_root . '/src/Admin/ViewModels/Industry/Subtype_Starter_Bundle_Selection_View_Model.php';

final class Subtype_Starter_Bundle_Selection_View_Model_Test extends TestCase {

	public function test_from_profile_empty_primary_has_no_bundles(): void {
		$profile = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => '' );
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( Industry_Starter_Bundle_Registry::get_builtin_definitions() );
		$vm = Subtype_Starter_Bundle_Selection_View_Model::from_profile( $profile, $registry, null );
		$this->assertFalse( $vm->has_primary );
		$this->assertSame( array(), $vm->parent_bundles );
		$this->assertSame( array(), $vm->display_bundles );
	}

	public function test_from_profile_with_primary_and_subtype_includes_parent_and_subtype_bundles_when_available(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( Industry_Starter_Bundle_Registry::get_builtin_definitions() );
		$subtype_registry = new Industry_Subtype_Registry();
		$subtype_registry->load( Industry_Subtype_Registry::get_builtin_definitions() );
		$profile = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'plumber',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'plumber_residential',
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => 'plumber_residential_starter',
		);
		$vm = Subtype_Starter_Bundle_Selection_View_Model::from_profile( $profile, $registry, $subtype_registry );
		$this->assertTrue( $vm->has_primary );
		$this->assertSame( 'plumber', $vm->primary_industry_key );
		$this->assertSame( 'plumber_residential', $vm->subtype_key );
		$this->assertNotEmpty( $vm->subtype_label );
		$this->assertGreaterThanOrEqual( 1, count( $vm->parent_bundles ), 'Plumber has at least one parent bundle' );
		$this->assertGreaterThanOrEqual( 1, count( $vm->subtype_bundles ), 'Plumber residential has subtype bundles' );
		$this->assertTrue( $vm->has_subtype_bundles );
		$this->assertGreaterThanOrEqual( 2, count( $vm->display_bundles ) );
		$this->assertTrue( $vm->can_clear_to_parent, 'Selected subtype bundle allows clearing to parent' );
	}

	public function test_from_profile_subtype_bundle_selected_can_clear_to_parent(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( Industry_Starter_Bundle_Registry::get_builtin_definitions() );
		$subtype_registry = new Industry_Subtype_Registry();
		$subtype_registry->load( Industry_Subtype_Registry::get_builtin_definitions() );
		$profile = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'plumber',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => 'plumber_residential',
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => 'plumber_starter',
		);
		$vm = Subtype_Starter_Bundle_Selection_View_Model::from_profile( $profile, $registry, $subtype_registry );
		$this->assertFalse( $vm->can_clear_to_parent, 'Parent bundle selected so cannot clear to parent' );
	}
}
