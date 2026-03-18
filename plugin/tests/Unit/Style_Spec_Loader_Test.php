<?php
/**
 * Unit tests for Style_Spec_Loader (Prompt 244): load spec, safe failure, no path leakage.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';

final class Style_Spec_Loader_Test extends TestCase {

	/**
	 * Plugin root (plugin/ directory).
	 *
	 * @var string
	 */
	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
	}

	public function test_load_spec_with_invalid_filename_returns_null(): void {
		$loader = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$this->assertNull( $loader->load_spec( 'unknown.json' ) );
		$this->assertNull( $loader->load_spec( '../pb-style-core-spec.json' ) );
	}

	public function test_load_spec_with_missing_dir_returns_null(): void {
		$loader = new Style_Spec_Loader( $this->plugin_root . '/nonexistent-specs-dir-' . uniqid() . '/' );
		$this->assertNull( $loader->load_core_spec() );
		$this->assertNull( $loader->load_components_spec() );
		$this->assertNull( $loader->load_render_surfaces_spec() );
	}

	public function test_load_core_spec_returns_array_when_file_exists(): void {
		$loader = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$spec   = $loader->load_core_spec();
		$this->assertIsArray( $spec );
		$this->assertArrayHasKey( 'spec_version', $spec );
		$this->assertArrayHasKey( 'token_groups', $spec );
		$this->assertSame( '1', $spec['spec_version'] ?? '' );
	}

	public function test_get_spec_version_and_schema(): void {
		$loader = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$spec   = $loader->load_core_spec();
		$this->assertIsArray( $spec );
		$this->assertSame( '1', Style_Spec_Loader::get_spec_version( $spec ) );
		$this->assertSame( 'pb-style-core', Style_Spec_Loader::get_spec_schema( $spec ) );
	}

	public function test_load_components_spec_returns_array_when_file_exists(): void {
		$loader = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$spec   = $loader->load_components_spec();
		$this->assertIsArray( $spec );
		$this->assertArrayHasKey( 'components', $spec );
		$this->assertSame( '1', $spec['spec_version'] ?? '' );
	}

	public function test_load_render_surfaces_spec_returns_array_when_file_exists(): void {
		$loader = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$spec   = $loader->load_render_surfaces_spec();
		$this->assertIsArray( $spec );
		$this->assertArrayHasKey( 'render_surfaces', $spec );
		$this->assertSame( '1', $spec['spec_version'] ?? '' );
	}
}
