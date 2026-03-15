<?php
/**
 * Unit tests for Entity_Style_UI_State_Builder (Prompt 253): state shape and validation errors.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Forms\Entity_Style_Form_Builder;
use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Repository;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use AIOPageBuilder\Domain\Styling\Entity_Style_UI_State_Builder;
use AIOPageBuilder\Domain\Styling\Style_Validation_Result;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Repository.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Token_Registry.php';
require_once $plugin_root . '/src/Domain/Styling/Component_Override_Registry.php';
require_once $plugin_root . '/src/Admin/Forms/Entity_Style_Form_Builder.php';
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_UI_State_Builder.php';

final class Entity_Style_UI_State_Builder_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		$key = Entity_Style_Payload_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	public function test_build_state_has_required_keys(): void {
		$loader    = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token_reg = new Style_Token_Registry( $loader );
		$comp_reg  = new Component_Override_Registry( $loader );
		$repo      = new Entity_Style_Payload_Repository();
		$form      = new Entity_Style_Form_Builder( $token_reg, $comp_reg, $repo );
		$builder   = new Entity_Style_UI_State_Builder( $form, $repo );
		$state     = $builder->build_state( 'section_template', 'hero_01', null );
		$this->assertArrayHasKey( 'payload', $state );
		$this->assertArrayHasKey( 'token_fields_by_group', $state );
		$this->assertArrayHasKey( 'component_fields_by_component', $state );
		$this->assertArrayHasKey( 'validation_errors', $state );
		$this->assertArrayHasKey( 'nonce_action', $state );
		$this->assertArrayHasKey( 'save_action', $state );
		$this->assertSame( Entity_Style_UI_State_Builder::NONCE_ACTION, $state['nonce_action'] );
		$this->assertSame( Entity_Style_UI_State_Builder::SAVE_ACTION, $state['save_action'] );
		$this->assertSame( 'section_template', $state['entity_type'] );
		$this->assertSame( 'hero_01', $state['entity_key'] );
	}

	public function test_build_state_with_last_result_includes_validation_errors(): void {
		$loader    = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token_reg = new Style_Token_Registry( $loader );
		$comp_reg  = new Component_Override_Registry( $loader );
		$repo      = new Entity_Style_Payload_Repository();
		$form      = new Entity_Style_Form_Builder( $token_reg, $comp_reg, $repo );
		$builder   = new Entity_Style_UI_State_Builder( $form, $repo );
		$last      = new Style_Validation_Result( false, array( 'Invalid token group' ) );
		$state     = $builder->build_state( 'page_template', 'pt_landing', $last );
		$this->assertSame( array( 'Invalid token group' ), $state['validation_errors'] );
	}
}
