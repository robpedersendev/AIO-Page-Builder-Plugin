<?php
/**
 * Unit tests for Plugin_Path_Manager: uploads base, child paths, safe segment, path traversal rejection (spec §9.8, Prompt 020).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Files/Plugin_Path_Manager.php';

final class Plugin_Path_Manager_Test extends TestCase {

	private Plugin_Path_Manager $manager;

	protected function setUp(): void {
		parent::setUp();
		$base = rtrim( sys_get_temp_dir(), '/\\' ) . '/aio-pm-test-' . (string) getmypid();
		$GLOBALS['_aio_wp_upload_dir'] = array( 'basedir' => $base, 'error' => false );
		$this->manager = new Plugin_Path_Manager();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_wp_upload_dir'] );
		parent::tearDown();
	}

	public function test_get_uploads_base_returns_path_ending_with_plugin_subdir(): void {
		$base = $this->manager->get_uploads_base();
		$this->assertNotSame( '', $base );
		$this->assertStringEndsWith( 'aio-page-builder/', $base );
	}

	public function test_get_uploads_base_includes_configured_basedir(): void {
		$base = $this->manager->get_uploads_base();
		$expected = rtrim( $GLOBALS['_aio_wp_upload_dir']['basedir'], '/\\' ) . '/aio-page-builder/';
		$this->assertSame( $expected, $base );
	}

	public function test_get_child_path_returns_path_for_valid_child(): void {
		$path = $this->manager->get_child_path( Plugin_Path_Manager::CHILD_ARTIFACTS );
		$this->assertStringEndsWith( 'aio-page-builder/artifacts/', $path );
		$path = $this->manager->get_child_path( Plugin_Path_Manager::CHILD_EXPORTS );
		$this->assertStringEndsWith( 'exports/', $path );
	}

	public function test_get_child_path_returns_empty_for_invalid_child(): void {
		$this->assertSame( '', $this->manager->get_child_path( 'invalid' ) );
		$this->assertSame( '', $this->manager->get_child_path( '' ) );
		$this->assertSame( '', $this->manager->get_child_path( '../artifacts' ) );
	}

	public function test_get_child_path_with_segment_appends_safe_segment(): void {
		$path = $this->manager->get_child_path_with_segment( Plugin_Path_Manager::CHILD_ARTIFACTS, 'run-123' );
		$this->assertStringEndsWith( 'artifacts/run-123/', $path );
	}

	public function test_get_child_path_with_segment_rejects_path_traversal(): void {
		$this->assertSame( '', $this->manager->get_child_path_with_segment( Plugin_Path_Manager::CHILD_ARTIFACTS, '..' ) );
		$this->assertSame( '', $this->manager->get_child_path_with_segment( Plugin_Path_Manager::CHILD_ARTIFACTS, '../etc' ) );
		$this->assertSame( '', $this->manager->get_child_path_with_segment( Plugin_Path_Manager::CHILD_ARTIFACTS, 'seg/ment' ) );
		$this->assertSame( '', $this->manager->get_child_path_with_segment( Plugin_Path_Manager::CHILD_ARTIFACTS, 'seg\\ment' ) );
	}

	public function test_is_safe_segment_accepts_alphanumeric_hyphen_underscore(): void {
		$this->assertTrue( $this->manager->is_safe_segment( 'run_1' ) );
		$this->assertTrue( $this->manager->is_safe_segment( 'pkg-2025' ) );
		$this->assertTrue( $this->manager->is_safe_segment( 'a' ) );
		$this->assertTrue( $this->manager->is_safe_segment( '' ) );
	}

	public function test_is_safe_segment_rejects_unsafe(): void {
		$this->assertFalse( $this->manager->is_safe_segment( '..' ) );
		$this->assertFalse( $this->manager->is_safe_segment( '../x' ) );
		$this->assertFalse( $this->manager->is_safe_segment( 'a/b' ) );
		$this->assertFalse( $this->manager->is_safe_segment( 'a\\b' ) );
		$this->assertFalse( $this->manager->is_safe_segment( 'a b' ) );
	}

	public function test_base_exists_after_ensure_base(): void {
		$base = $this->manager->get_uploads_base();
		$this->assertNotSame( '', $base );
		$created = $this->manager->ensure_base();
		$this->assertTrue( $created );
		$this->assertTrue( $this->manager->base_exists() );
		// Cleanup so temp dir doesn't accumulate.
		if ( is_dir( $base ) ) {
			@rmdir( $base );
		}
	}

	public function test_is_under_base_true_for_path_under_base(): void {
		$this->manager->ensure_base();
		$base = $this->manager->get_uploads_base();
		$sub  = $base . 'artifacts/';
		wp_mkdir_p( $sub );
		$real_base = realpath( $base );
		$real_sub  = realpath( $sub );
		$this->assertNotFalse( $real_base );
		$this->assertNotFalse( $real_sub );
		$this->assertTrue( $this->manager->is_under_base( $real_sub ) );
		@rmdir( $sub );
		@rmdir( $base );
	}

	public function test_is_under_base_false_for_path_outside_base(): void {
		$base = $this->manager->get_uploads_base();
		$this->assertFalse( $this->manager->is_under_base( sys_get_temp_dir() ) );
		$this->assertFalse( $this->manager->is_under_base( '/nonexistent' ) );
	}
}
