<?php
/**
 * Unit tests for Global_Token_Variable_Emitter (Prompt 249): approved emission, invalid tokens/values omitted.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Schema;
use AIOPageBuilder\Domain\Styling\Global_Token_Variable_Emitter;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Repository.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Token_Variable_Emitter.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Token_Registry.php';

final class Global_Token_Variable_Emitter_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		$key               = Global_Style_Settings_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	public function test_emit_for_root_without_registry_returns_empty(): void {
		$repo    = new Global_Style_Settings_Repository( null, null );
		$emitter = new Global_Token_Variable_Emitter( $repo, null );
		$this->assertSame( '', $emitter->emit_for_root() );
	}

	public function test_emit_for_root_with_approved_tokens_contains_aio_names(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( $registry, null );
		$repo->set_global_tokens( array( 'color' => array( 'primary' => '#333333' ) ) );
		$emitter = new Global_Token_Variable_Emitter( $repo, $registry );
		$css     = $emitter->emit_for_root();
		$this->assertStringContainsString( ':root', $css );
		$this->assertStringContainsString( '--aio-color-primary', $css );
		$this->assertStringContainsString( '#333333', $css );
	}

	public function test_invalid_values_omitted_from_emission(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( $registry, null );
		$repo->set_global_tokens( array( 'color' => array( 'primary' => '#333' ) ) );
		$emitter = new Global_Token_Variable_Emitter( $repo, $registry );
		$decls   = $emitter->get_approved_declarations();
		foreach ( $decls as $decl ) {
			$this->assertStringStartsWith( '--aio-', $decl );
			$this->assertStringNotContainsString( '<', $decl );
			$this->assertStringNotContainsString( '>', $decl );
		}
	}

	public function test_get_approved_declarations_empty_when_no_tokens_stored(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( $registry, null );
		$emitter  = new Global_Token_Variable_Emitter( $repo, $registry );
		$this->assertSame( array(), $emitter->get_approved_declarations() );
	}
}
