<?php
/**
 * Unit tests for Section_Style_Emitter (Prompt 254): section-level inline style and component block; invalid omitted.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Repository;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use AIOPageBuilder\Domain\Styling\Section_Style_Emitter;
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
require_once $plugin_root . '/src/Domain/Styling/Section_Style_Emitter.php';

final class Section_Style_Emitter_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		$key               = Entity_Style_Payload_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	public function test_get_inline_style_empty_section_key_returns_empty(): void {
		$repo = new Entity_Style_Payload_Repository();
		$emit = new Section_Style_Emitter( $repo, null, null );
		$this->assertSame( '', $emit->get_inline_style_for_section( '' ) );
	}

	public function test_get_inline_style_with_valid_payload_returns_declarations(): void {
		$repo    = new Entity_Style_Payload_Repository();
		$payload = array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => '1',
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array( 'color' => array( 'primary' => '#222' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array(),
		);
		$repo->set_payload( 'section_template', 'hero_01', $payload );
		$loader    = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token_reg = new Style_Token_Registry( $loader );
		$emit      = new Section_Style_Emitter( $repo, $token_reg, null );
		$inline    = $emit->get_inline_style_for_section( 'hero_01' );
		$this->assertStringContainsString( '--aio-color-primary', $inline );
		$this->assertStringContainsString( '#222', $inline );
	}

	public function test_get_component_override_style_block_empty_when_no_overrides(): void {
		$repo    = new Entity_Style_Payload_Repository();
		$payload = array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => '1',
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array(),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array(),
		);
		$repo->set_payload( 'section_template', 'card_sec', $payload );
		$comp_reg = new Component_Override_Registry( new Style_Spec_Loader( $this->plugin_root . '/specs/' ) );
		$emit     = new Section_Style_Emitter( $repo, null, $comp_reg );
		$this->assertSame( '', $emit->get_component_override_style_block( 'card_sec' ) );
	}

	public function test_invalid_payload_omitted_from_inline(): void {
		$repo    = new Entity_Style_Payload_Repository();
		$payload = array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => '1',
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array( 'color' => array( 'unknown_name' => '#fff' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array(),
		);
		$repo->set_payload( 'section_template', 'sec', $payload );
		$loader    = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token_reg = new Style_Token_Registry( $loader );
		$emit      = new Section_Style_Emitter( $repo, $token_reg, null );
		$inline    = $emit->get_inline_style_for_section( 'sec' );
		$this->assertSame( '', $inline );
	}
}
