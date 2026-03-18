<?php
/**
 * Unit tests for Entity_Style_Form_Builder (Prompt 253): field definitions for section/page template entity styling.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Forms\Entity_Style_Form_Builder;
use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Repository;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Repository.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Token_Registry.php';
require_once $plugin_root . '/src/Domain/Styling/Component_Override_Registry.php';
require_once $plugin_root . '/src/Admin/Forms/Entity_Style_Form_Builder.php';

final class Entity_Style_Form_Builder_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		$key               = Entity_Style_Payload_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	public function test_form_key_constant(): void {
		$this->assertSame( 'aio_entity_style', Entity_Style_Form_Builder::FORM_KEY );
	}

	public function test_token_field_definitions_use_form_key(): void {
		$loader    = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token_reg = new Style_Token_Registry( $loader );
		$comp_reg  = new Component_Override_Registry( $loader );
		$repo      = new Entity_Style_Payload_Repository();
		$builder   = new Entity_Style_Form_Builder( $token_reg, $comp_reg, $repo );
		$defs      = $builder->get_token_field_definitions( 'section_template', 'hero_01' );
		$this->assertIsArray( $defs );
		foreach ( $defs as $def ) {
			$this->assertStringContainsString( Entity_Style_Form_Builder::FORM_KEY, $def['name_attr'] ?? '' );
			$this->assertStringContainsString( 'token_overrides', $def['name_attr'] ?? '' );
		}
	}

	public function test_invalid_entity_type_returns_empty_token_definitions(): void {
		$loader    = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token_reg = new Style_Token_Registry( $loader );
		$comp_reg  = new Component_Override_Registry( $loader );
		$repo      = new Entity_Style_Payload_Repository();
		$builder   = new Entity_Style_Form_Builder( $token_reg, $comp_reg, $repo );
		$this->assertSame( array(), $builder->get_token_field_definitions( 'invalid_type', 'key' ) );
	}

	public function test_load_saved_payload_into_field_values(): void {
		$repo    = new Entity_Style_Payload_Repository();
		$payload = array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => '1',
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array( 'color' => array( 'primary' => '#abc' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array(),
		);
		$repo->set_payload( 'section_template', 'test_sec', $payload );
		$loader    = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token_reg = new Style_Token_Registry( $loader );
		$comp_reg  = new Component_Override_Registry( $loader );
		$builder   = new Entity_Style_Form_Builder( $token_reg, $comp_reg, $repo );
		$by_group  = $builder->get_token_fields_by_group( 'section_template', 'test_sec' );
		$this->assertArrayHasKey( 'color', $by_group );
		$primary_field = null;
		foreach ( $by_group['color'] as $f ) {
			if ( ( $f['name'] ?? '' ) === 'primary' ) {
				$primary_field = $f;
				break;
			}
		}
		$this->assertNotNull( $primary_field );
		$this->assertSame( '#abc', $primary_field['value'] ?? '' );
	}
}
