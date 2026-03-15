<?php
/**
 * Unit tests for Style_Token_Registry (Prompt 244): lookup, spec version, safe failure when not loaded.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Token_Registry.php';

final class Style_Token_Registry_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
	}

	public function test_registry_not_loaded_when_spec_missing(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/nonexistent-' . uniqid() . '/' );
		$registry = new Style_Token_Registry( $loader );
		$this->assertFalse( $registry->is_loaded() );
		$this->assertSame( '', $registry->get_spec_version() );
		$this->assertSame( array(), $registry->get_token_group_names() );
		$this->assertSame( array(), $registry->get_allowed_names_for_group( 'color' ) );
	}

	public function test_registry_loaded_has_version_and_groups(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$this->assertTrue( $registry->is_loaded() );
		$this->assertSame( '1', $registry->get_spec_version() );
		$groups = $registry->get_token_group_names();
		$this->assertContains( 'color', $groups );
		$this->assertContains( 'typography', $groups );
	}

	public function test_get_allowed_names_and_token_variable_name(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$names    = $registry->get_allowed_names_for_group( 'color' );
		$this->assertContains( 'primary', $names );
		$this->assertSame( '--aio-color-primary', $registry->get_token_variable_name( 'color', 'primary' ) );
		$this->assertSame( '', $registry->get_token_variable_name( 'color', 'invalid_name' ) );
	}

	public function test_is_allowed_token_name(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$this->assertTrue( $registry->is_allowed_token_name( '--aio-color-primary' ) );
		$this->assertFalse( $registry->is_allowed_token_name( '--aio-color-unknown' ) );
		$this->assertFalse( $registry->is_allowed_token_name( '--other-prefix' ) );
	}

	public function test_get_sanitization_for_group(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$san      = $registry->get_sanitization_for_group( 'color' );
		$this->assertIsArray( $san );
		$this->assertArrayHasKey( 'value_type', $san );
		$this->assertSame( 'color', $san['value_type'] ?? '' );
	}
}
