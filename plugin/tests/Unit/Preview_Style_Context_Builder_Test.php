<?php
/**
 * Unit tests for Preview_Style_Context_Builder (Prompt 255): preview style context shape and emitter inclusion.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Preview\Styling\Preview_Style_Context_Builder;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Repository;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use AIOPageBuilder\Domain\Styling\Page_Style_Emitter;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Infrastructure\Config\Plugin_Config;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Plugin_Config.php';
require_once $plugin_root . '/src/Domain/Preview/Styling/Preview_Style_Context_Builder.php';
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Repository.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Token_Registry.php';
require_once $plugin_root . '/src/Domain/Styling/Component_Override_Registry.php';
require_once $plugin_root . '/src/Domain/Styling/Page_Style_Emitter.php';

final class Preview_Style_Context_Builder_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		$key = Entity_Style_Payload_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	public function test_build_for_preview_returns_expected_keys(): void {
		$config = new Plugin_Config();
		$builder = new Preview_Style_Context_Builder( $config, null, null, null );
		$out = $builder->build_for_preview( 'section', '' );
		$this->assertArrayHasKey( 'base_stylesheet_url', $out );
		$this->assertArrayHasKey( 'inline_css', $out );
		$this->assertIsString( $out['base_stylesheet_url'] );
		$this->assertIsString( $out['inline_css'] );
	}

	public function test_build_for_preview_base_url_ends_with_base_css_path(): void {
		$config = new Plugin_Config();
		$builder = new Preview_Style_Context_Builder( $config, null, null, null );
		$out = $builder->build_for_preview( 'section', '' );
		$this->assertStringContainsString( 'aio-page-builder-base.css', $out['base_stylesheet_url'] );
		$this->assertSame( '', $out['inline_css'] );
	}

	public function test_build_for_section_with_page_emitter_does_not_include_page_css(): void {
		$config  = new Plugin_Config();
		$repo    = new Entity_Style_Payload_Repository();
		$loader  = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token   = new Style_Token_Registry( $loader );
		$comp    = new Component_Override_Registry( $loader );
		$page_emitter = new Page_Style_Emitter( $repo, $token, $comp );
		$builder = new Preview_Style_Context_Builder( $config, null, null, $page_emitter );
		$out = $builder->build_for_preview( 'section', 'sec_hero' );
		$this->assertSame( '', $out['inline_css'] );
	}

	public function test_build_for_page_with_entity_key_and_payload_includes_page_css(): void {
		$config = new Plugin_Config();
		$repo   = new Entity_Style_Payload_Repository();
		$payload = array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => '1',
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array( 'color' => array( 'primary' => '#abc' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array(),
		);
		$repo->set_payload( 'page_template', 'pt_landing', $payload );
		$loader = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token  = new Style_Token_Registry( $loader );
		$comp   = new Component_Override_Registry( $loader );
		$page_emitter = new Page_Style_Emitter( $repo, $token, $comp );
		$builder = new Preview_Style_Context_Builder( $config, null, null, $page_emitter );
		$out = $builder->build_for_preview( 'page', 'pt_landing' );
		$this->assertStringContainsString( '.aio-page', $out['inline_css'] );
		$this->assertStringContainsString( '--aio-color-primary', $out['inline_css'] );
	}
}
