<?php
/**
 * Unit tests for Page_Style_Emitter (Prompt 254): page-level style emission, invalid payload omitted.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Repository;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use AIOPageBuilder\Domain\Styling\Page_Style_Emitter;
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
require_once $plugin_root . '/src/Domain/Styling/Page_Style_Emitter.php';

final class Page_Style_Emitter_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		$key               = Entity_Style_Payload_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	public function test_emit_for_page_empty_template_key_returns_empty(): void {
		$repo = new Entity_Style_Payload_Repository();
		$emit = new Page_Style_Emitter( $repo, null, null );
		$this->assertSame( '', $emit->emit_for_page( '' ) );
	}

	public function test_emit_for_page_with_valid_payload_contains_aio_page_selector(): void {
		$repo    = new Entity_Style_Payload_Repository();
		$payload = array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => '1',
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array( 'color' => array( 'primary' => '#111' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array(),
		);
		$repo->set_payload( 'page_template', 'pt_landing', $payload );
		$loader    = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token_reg = new Style_Token_Registry( $loader );
		$comp_reg  = new Component_Override_Registry( $loader );
		$emit      = new Page_Style_Emitter( $repo, $token_reg, $comp_reg );
		$css       = $emit->emit_for_page( 'pt_landing' );
		$this->assertStringContainsString( '.aio-page', $css );
		$this->assertStringContainsString( '--aio-color-primary', $css );
	}

	public function test_emit_for_page_invalid_payload_omitted(): void {
		$repo    = new Entity_Style_Payload_Repository();
		$payload = array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => '1',
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array( 'invalid_group' => array( 'x' => 'y' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array(),
		);
		$repo->set_payload( 'page_template', 'pt_other', $payload );
		$loader    = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token_reg = new Style_Token_Registry( $loader );
		$emit      = new Page_Style_Emitter( $repo, $token_reg, null );
		$css       = $emit->emit_for_page( 'pt_other' );
		$this->assertStringNotContainsString( 'invalid_group', $css );
		$this->assertStringNotContainsString( '--aio-', $css );
	}
}
