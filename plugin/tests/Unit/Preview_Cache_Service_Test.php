<?php
/**
 * Unit tests for Preview_Cache_Service: cache key determinism, version hash, get/set/has, invalidation (Prompt 184, spec §55.8).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Preview\Preview_Cache_Record;
use AIOPageBuilder\Domain\Preview\Preview_Cache_Service;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Context;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Preview/Preview_Cache_Record.php';
require_once $plugin_root . '/src/Domain/Preview/Preview_Cache_Service.php';
require_once $plugin_root . '/src/Domain/Preview/Synthetic_Preview_Context.php';

final class Preview_Cache_Service_Test extends TestCase {

	private Preview_Cache_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new Preview_Cache_Service( 800 );
	}

	public function test_get_cache_key_deterministic_for_section(): void {
		$ctx = Synthetic_Preview_Context::for_section( 'st_hero', 'hero', 'default', false, 'none' );
		$def = array( 'internal_key' => 'st_hero', 'version' => array( 1, 0 ), 'field_blueprint_ref' => 'hero_bp' );
		$key1 = $this->service->get_cache_key( $ctx, $def );
		$key2 = $this->service->get_cache_key( $ctx, $def );
		$this->assertSame( $key1, $key2 );
		$this->assertStringStartsWith( 'aio_preview_', $key1 );
	}

	public function test_get_cache_key_different_for_different_version_hash(): void {
		$ctx = Synthetic_Preview_Context::for_section( 'st_hero', 'hero', 'default', false, 'none' );
		$def1 = array( 'internal_key' => 'st_hero', 'version' => array( 1, 0 ), 'field_blueprint_ref' => '' );
		$def2 = array( 'internal_key' => 'st_hero', 'version' => array( 2, 0 ), 'field_blueprint_ref' => '' );
		$key1 = $this->service->get_cache_key( $ctx, $def1 );
		$key2 = $this->service->get_cache_key( $ctx, $def2 );
		$this->assertNotSame( $key1, $key2 );
	}

	public function test_definition_version_hash_section_uses_internal_key_version_blueprint(): void {
		$def = array( 'internal_key' => 'st_hero', 'version' => array( 1, 0 ), 'field_blueprint_ref' => 'bp1' );
		$h1 = $this->service->definition_version_hash( $def, Synthetic_Preview_Context::TYPE_SECTION );
		$this->assertNotEmpty( $h1 );
		$def['version'] = array( 2, 0 );
		$h2 = $this->service->definition_version_hash( $def, Synthetic_Preview_Context::TYPE_SECTION );
		$this->assertNotSame( $h1, $h2 );
	}

	public function test_definition_version_hash_page_uses_ordered_sections(): void {
		$def = array(
			'internal_key'      => 'pt_home',
			'version'          => array( 1, 0 ),
			'ordered_sections' => array(
				array( 'section_key' => 'hero' ),
				array( 'section_key' => 'cta' ),
			),
		);
		$h1 = $this->service->definition_version_hash( $def, Synthetic_Preview_Context::TYPE_PAGE );
		$this->assertNotEmpty( $h1 );
		$def['ordered_sections'] = array( array( 'section_key' => 'cta' ), array( 'section_key' => 'hero' ) );
		$h2 = $this->service->definition_version_hash( $def, Synthetic_Preview_Context::TYPE_PAGE );
		$this->assertNotSame( $h1, $h2 );
	}

	public function test_get_missing_returns_null(): void {
		$this->service->invalidate_all();
		$this->assertNull( $this->service->get( 'nonexistent_key_xyz' ) );
		$this->assertFalse( $this->service->has( 'nonexistent_key_xyz' ) );
	}

	public function test_set_and_get_roundtrip(): void {
		$this->service->invalidate_all();
		$record = new Preview_Cache_Record(
			'aio_preview_test_roundtrip',
			Preview_Cache_Record::TYPE_SECTION,
			'st_test',
			'ver123',
			'<div>test html</div>',
			\time(),
			false,
			'none'
		);
		$this->assertTrue( $this->service->set( $record ) );
		$this->assertTrue( $this->service->has( 'aio_preview_test_roundtrip' ) );
		$cached = $this->service->get( 'aio_preview_test_roundtrip' );
		$this->assertInstanceOf( Preview_Cache_Record::class, $cached );
		$this->assertSame( '<div>test html</div>', $cached->get_html() );
		$this->assertSame( 'st_test', $cached->get_template_key() );
		$this->service->invalidate_all();
	}

	public function test_invalidate_for_template_removes_matching_entries(): void {
		$this->service->invalidate_all();
		$r = new Preview_Cache_Record( 'aio_preview_inv1', Preview_Cache_Record::TYPE_SECTION, 'st_foo', 'v1', 'html1', \time(), false, 'none' );
		$this->service->set( $r );
		$removed = $this->service->invalidate_for_template( Preview_Cache_Record::TYPE_SECTION, 'st_foo' );
		$this->assertGreaterThanOrEqual( 1, $removed );
		$this->assertNull( $this->service->get( 'aio_preview_inv1' ) );
		$this->service->invalidate_all();
	}

	public function test_cached_record_preserves_reduced_motion_and_animation_tier(): void {
		$this->service->invalidate_all();
		$record = new Preview_Cache_Record(
			'aio_preview_rm',
			Preview_Cache_Record::TYPE_SECTION,
			'st_hero',
			'v1',
			'<div>reduced motion html</div>',
			\time(),
			true,
			'none'
		);
		$this->service->set( $record );
		$cached = $this->service->get( 'aio_preview_rm' );
		$this->assertNotNull( $cached );
		$this->assertTrue( $cached->is_reduced_motion() );
		$this->assertSame( 'none', $cached->get_animation_tier() );
		$this->service->invalidate_all();
	}

	/** get_max_entries and get_cache_entry_count for performance reporting (Prompt 188). */
	public function test_get_max_entries_returns_configured_value(): void {
		$this->assertSame( 800, $this->service->get_max_entries() );
		$small = new Preview_Cache_Service( 100 );
		$this->assertSame( 100, $small->get_max_entries() );
	}

	public function test_get_cache_entry_count_reflects_store(): void {
		$this->service->invalidate_all();
		$this->assertSame( 0, $this->service->get_cache_entry_count() );
		$record = new Preview_Cache_Record( 'aio_preview_count', Preview_Cache_Record::TYPE_SECTION, 'st_x', 'v1', '<div>x</div>', \time(), false, 'none' );
		$this->service->set( $record );
		$this->assertSame( 1, $this->service->get_cache_entry_count() );
		$this->service->invalidate_all();
	}
}
