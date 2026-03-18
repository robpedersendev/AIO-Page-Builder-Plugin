<?php
/**
 * Unit tests for Render_Surface_Style_Registry (Prompt 244): surfaces lookup, safe failure when not loaded.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Render_Surface_Style_Registry;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Render_Surface_Style_Registry.php';

final class Render_Surface_Style_Registry_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
	}

	public function test_registry_not_loaded_when_spec_missing(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/nonexistent-' . uniqid() . '/' );
		$registry = new Render_Surface_Style_Registry( $loader );
		$this->assertFalse( $registry->is_loaded() );
		$this->assertSame( array(), $registry->get_surfaces() );
		$this->assertSame( array(), $registry->get_surface( 'root' ) );
	}

	public function test_get_surfaces_and_selector(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Render_Surface_Style_Registry( $loader );
		$this->assertTrue( $registry->is_loaded() );
		$surfaces = $registry->get_surfaces();
		$this->assertNotEmpty( $surfaces );
		$this->assertSame( ':root', $registry->get_selector_for_surface( 'root' ) );
		$this->assertSame( '.aio-page', $registry->get_selector_for_surface( 'page' ) );
		$this->assertSame( 'global', $registry->get_scope_for_surface( 'root' ) );
		$this->assertSame( 'page', $registry->get_scope_for_surface( 'page' ) );
	}
}
