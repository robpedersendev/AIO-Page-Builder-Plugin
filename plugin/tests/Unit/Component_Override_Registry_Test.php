<?php
/**
 * Unit tests for Component_Override_Registry (Prompt 244): lookup, safe failure when not loaded.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Component_Override_Registry.php';

final class Component_Override_Registry_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
	}

	public function test_registry_not_loaded_when_spec_missing(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/nonexistent-' . uniqid() . '/' );
		$registry = new Component_Override_Registry( $loader );
		$this->assertFalse( $registry->is_loaded() );
		$this->assertSame( array(), $registry->get_component_ids() );
		$this->assertSame( array(), $registry->get_component( 'card' ) );
	}

	public function test_get_component_ids_and_component(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Component_Override_Registry( $loader );
		$this->assertTrue( $registry->is_loaded() );
		$ids = $registry->get_component_ids();
		$this->assertContains( 'card', $ids );
		$this->assertContains( 'cta', $ids );
		$card = $registry->get_component( 'card' );
		$this->assertSame( 'card', $card['element_role'] ?? '' );
		$this->assertSame( 'aio-s-{section_key}__card', $registry->get_selector_pattern( 'card' ) );
		$overrides = $registry->get_allowed_token_overrides( 'card' );
		$this->assertContains( '--aio-color-surface', $overrides );
		$this->assertTrue( $registry->is_token_allowed_for_component( 'card', '--aio-radius-card' ) );
		$this->assertFalse( $registry->is_token_allowed_for_component( 'card', '--aio-color-unknown' ) );
	}
}
