<?php
/**
 * Unit tests for Global_Component_Override_Form_Builder (Prompt 248): field definitions from component spec and repository.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Forms\Global_Component_Override_Form_Builder;
use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Repository.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Token_Registry.php';
require_once $plugin_root . '/src/Domain/Styling/Component_Override_Registry.php';
require_once $plugin_root . '/src/Admin/Forms/Global_Component_Override_Form_Builder.php';

final class Global_Component_Override_Form_Builder_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		$key = \AIOPageBuilder\Domain\Styling\Global_Style_Settings_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	public function test_form_overrides_key_constant(): void {
		$this->assertSame( 'aio_global_component_overrides', Global_Component_Override_Form_Builder::FORM_OVERRIDES_KEY );
	}

	public function test_field_definitions_include_only_approved_components(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$comp_reg = new Component_Override_Registry( $loader );
		$token_reg = new Style_Token_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( null, $comp_reg );
		$builder  = new Global_Component_Override_Form_Builder( $comp_reg, $repo, $token_reg );
		$defs     = $builder->get_field_definitions();
		$allowed_ids = $comp_reg->get_component_ids();
		foreach ( $defs as $def ) {
			$this->assertContains( $def['component_id'], $allowed_ids );
			$this->assertStringStartsWith( '--aio-', $def['token_var_name'] );
		}
	}

	public function test_field_definitions_include_required_keys(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$comp_reg = new Component_Override_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( null, $comp_reg );
		$builder  = new Global_Component_Override_Form_Builder( $comp_reg, $repo, null );
		$defs     = $builder->get_field_definitions();
		$required = array( 'component_id', 'token_var_name', 'name_attr', 'label', 'value', 'max_length' );
		foreach ( $defs as $def ) {
			foreach ( $required as $key ) {
				$this->assertArrayHasKey( $key, $def );
			}
			$this->assertStringContainsString( 'aio_global_component_overrides', $def['name_attr'] );
		}
	}
}
