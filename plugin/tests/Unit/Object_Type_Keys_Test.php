<?php
/**
 * Unit tests for Object_Type_Keys: stable CPT slugs, is_plugin_object (spec §10).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';

final class Object_Type_Keys_Test extends TestCase {

	public function test_all_returns_nine_plugin_object_types(): void {
		$all = Object_Type_Keys::all();
		$this->assertCount( 9, $all );
		$this->assertContains( Object_Type_Keys::SECTION_TEMPLATE, $all );
		$this->assertContains( Object_Type_Keys::PAGE_TEMPLATE, $all );
		$this->assertContains( Object_Type_Keys::COMPOSITION, $all );
		$this->assertContains( Object_Type_Keys::BUILD_PLAN, $all );
		$this->assertContains( Object_Type_Keys::AI_RUN, $all );
		$this->assertContains( Object_Type_Keys::PROMPT_PACK, $all );
		$this->assertContains( Object_Type_Keys::DOCUMENTATION, $all );
		$this->assertContains( Object_Type_Keys::VERSION_SNAPSHOT, $all );
	}

	public function test_all_keys_use_aio_prefix(): void {
		foreach ( Object_Type_Keys::all() as $key ) {
			$this->assertStringStartsWith( 'aio_', $key, "CPT key must be prefixed: {$key}" );
		}
	}

	public function test_stable_keys_match_object_model(): void {
		$this->assertSame( 'aio_section_template', Object_Type_Keys::SECTION_TEMPLATE );
		$this->assertSame( 'aio_page_template', Object_Type_Keys::PAGE_TEMPLATE );
		$this->assertSame( 'aio_composition', Object_Type_Keys::COMPOSITION );
		$this->assertSame( 'aio_build_plan', Object_Type_Keys::BUILD_PLAN );
		$this->assertSame( 'aio_ai_run', Object_Type_Keys::AI_RUN );
		$this->assertSame( 'aio_prompt_pack', Object_Type_Keys::PROMPT_PACK );
		$this->assertSame( 'aio_documentation', Object_Type_Keys::DOCUMENTATION );
		$this->assertSame( 'aio_version_snapshot', Object_Type_Keys::VERSION_SNAPSHOT );
		$this->assertSame( 'aio_ai_chat_session', Object_Type_Keys::AI_CHAT_SESSION );
	}

	public function test_is_plugin_object_true_for_all_registered(): void {
		foreach ( Object_Type_Keys::all() as $key ) {
			$this->assertTrue( Object_Type_Keys::is_plugin_object( $key ), "Should be plugin object: {$key}" );
		}
	}

	public function test_is_plugin_object_false_for_page_and_post(): void {
		$this->assertFalse( Object_Type_Keys::is_plugin_object( 'page' ) );
		$this->assertFalse( Object_Type_Keys::is_plugin_object( 'post' ) );
		$this->assertFalse( Object_Type_Keys::is_plugin_object( '' ) );
		$this->assertFalse( Object_Type_Keys::is_plugin_object( 'aio_other' ) );
	}
}
