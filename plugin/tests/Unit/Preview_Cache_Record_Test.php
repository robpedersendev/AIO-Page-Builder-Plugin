<?php
/**
 * Unit tests for Preview_Cache_Record: to_array, from_array, roundtrip (Prompt 184, spec §55.8).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Preview\Preview_Cache_Record;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Preview/Preview_Cache_Record.php';

final class Preview_Cache_Record_Test extends TestCase {

	public function test_to_array_contains_all_fields(): void {
		$record = new Preview_Cache_Record(
			'aio_preview_abc123',
			Preview_Cache_Record::TYPE_SECTION,
			'hero_conv_01',
			'def456',
			'<div class="aio-s-hero_conv_01">…</div>',
			1234567890,
			false,
			'none'
		);
		$arr = $record->to_array();
		$this->assertSame( 'aio_preview_abc123', $arr['cache_key'] );
		$this->assertSame( 'section', $arr['type'] );
		$this->assertSame( 'hero_conv_01', $arr['template_key'] );
		$this->assertSame( 'def456', $arr['version_hash'] );
		$this->assertStringContainsString( 'aio-s-hero_conv_01', $arr['html'] );
		$this->assertSame( 1234567890, $arr['created_at'] );
		$this->assertFalse( $arr['reduced_motion'] );
		$this->assertSame( 'none', $arr['animation_tier'] );
	}

	public function test_from_array_restores_record(): void {
		$payload = array(
			'cache_key'      => 'aio_preview_xyz',
			'type'           => 'page',
			'template_key'   => 'pt_landing',
			'version_hash'   => 'v789',
			'html'           => '<div class="aio-page">…</div>',
			'created_at'     => 9876543210,
			'reduced_motion' => true,
			'animation_tier' => 'none',
		);
		$record = Preview_Cache_Record::from_array( $payload );
		$this->assertSame( 'aio_preview_xyz', $record->get_cache_key() );
		$this->assertSame( 'page', $record->get_type() );
		$this->assertSame( 'pt_landing', $record->get_template_key() );
		$this->assertSame( 'v789', $record->get_version_hash() );
		$this->assertStringContainsString( 'aio-page', $record->get_html() );
		$this->assertSame( 9876543210, $record->get_created_at() );
		$this->assertTrue( $record->is_reduced_motion() );
		$this->assertSame( 'none', $record->get_animation_tier() );
	}

	public function test_roundtrip_to_array_from_array(): void {
		$record = new Preview_Cache_Record(
			'key_sec_01',
			Preview_Cache_Record::TYPE_SECTION,
			'st_hero',
			'ver_hash',
			'<section>…</section>',
			time(),
			true,
			'none'
		);
		$arr = $record->to_array();
		$restored = Preview_Cache_Record::from_array( $arr );
		$this->assertSame( $record->get_cache_key(), $restored->get_cache_key() );
		$this->assertSame( $record->get_html(), $restored->get_html() );
		$this->assertSame( $record->is_reduced_motion(), $restored->is_reduced_motion() );
	}

	/** Example preview-cache record payload (Prompt 184). */
	public function test_example_preview_cache_record_payload(): void {
		$example = array(
			'cache_key'      => 'aio_preview_abc123def456',
			'type'           => 'section',
			'template_key'   => 'hero_conv_01',
			'version_hash'   => 'def456789',
			'html'           => '<div class="aio-s-hero_conv_01">…</div>',
			'created_at'     => 1234567890,
			'reduced_motion' => false,
			'animation_tier' => 'none',
		);
		$record = Preview_Cache_Record::from_array( $example );
		$this->assertSame( $example, $record->to_array() );
	}
}
