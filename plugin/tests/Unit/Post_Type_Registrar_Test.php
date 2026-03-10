<?php
/**
 * Unit tests for Post_Type_Registrar: all CPTs register with stable keys, restricted visibility, plugin caps (spec §9.1, §10).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Objects\Post_Type_Registrar;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Post_Type_Registrar.php';

final class Post_Type_Registrar_Test extends TestCase {

	private Post_Type_Registrar $registrar;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_registered_post_types'] = array();
		$this->registrar = new Post_Type_Registrar();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_registered_post_types'] );
		parent::tearDown();
	}

	public function test_register_registers_all_eight_cpts(): void {
		$this->registrar->register();
		$registered = $GLOBALS['_aio_registered_post_types'] ?? array();
		$this->assertCount( 8, $registered, 'All 8 plugin object CPTs must be registered' );
		foreach ( Object_Type_Keys::all() as $key ) {
			$this->assertArrayHasKey( $key, $registered, "CPT must be registered: {$key}" );
		}
	}

	public function test_registered_cpts_have_stable_keys(): void {
		$this->registrar->register();
		$registered = $GLOBALS['_aio_registered_post_types'] ?? array();
		$this->assertSame( Object_Type_Keys::SECTION_TEMPLATE, 'aio_section_template' );
		$this->assertArrayHasKey( 'aio_section_template', $registered );
		$this->assertArrayHasKey( 'aio_build_plan', $registered );
		$this->assertArrayHasKey( 'aio_version_snapshot', $registered );
	}

	public function test_front_end_exposure_restricted(): void {
		$this->registrar->register();
		$registered = $GLOBALS['_aio_registered_post_types'] ?? array();
		foreach ( Object_Type_Keys::all() as $key ) {
			$args = $registered[ $key ] ?? array();
			$this->assertFalse( $args['public'] ?? true, "{$key} must not be public" );
			$this->assertFalse( $args['publicly_queryable'] ?? true, "{$key} must not be publicly queryable" );
		}
	}

	public function test_capability_mapping_uses_plugin_caps_not_edit_posts(): void {
		$this->registrar->register();
		$registered = $GLOBALS['_aio_registered_post_types'] ?? array();
		$expected_caps = array(
			Object_Type_Keys::SECTION_TEMPLATE  => Capabilities::MANAGE_SECTION_TEMPLATES,
			Object_Type_Keys::PAGE_TEMPLATE    => Capabilities::MANAGE_PAGE_TEMPLATES,
			Object_Type_Keys::COMPOSITION      => Capabilities::MANAGE_COMPOSITIONS,
			Object_Type_Keys::BUILD_PLAN       => Capabilities::VIEW_BUILD_PLANS,
			Object_Type_Keys::AI_RUN           => Capabilities::VIEW_AI_RUNS,
			Object_Type_Keys::PROMPT_PACK      => Capabilities::MANAGE_PROMPT_PACKS,
			Object_Type_Keys::DOCUMENTATION    => Capabilities::MANAGE_DOCUMENTATION,
			Object_Type_Keys::VERSION_SNAPSHOT => Capabilities::VIEW_VERSION_SNAPSHOTS,
		);
		foreach ( $expected_caps as $post_type => $expected_cap ) {
			$args = $registered[ $post_type ] ?? array();
			$caps = $args['capabilities'] ?? array();
			$this->assertIsArray( $caps, "{$post_type} must have capabilities array" );
			$this->assertArrayHasKey( 'edit_posts', $caps );
			$this->assertSame( $expected_cap, $caps['edit_posts'], "{$post_type} must map edit_posts to plugin cap" );
			$this->assertStringStartsWith( 'aio_', $caps['edit_posts'], "{$post_type} must not use generic edit_posts" );
		}
	}

	public function test_map_meta_cap_enabled(): void {
		$this->registrar->register();
		$registered = $GLOBALS['_aio_registered_post_types'] ?? array();
		foreach ( Object_Type_Keys::all() as $key ) {
			$args = $registered[ $key ] ?? array();
			$this->assertTrue( $args['map_meta_cap'] ?? false, "{$key} must have map_meta_cap true" );
		}
	}

	public function test_show_in_menu_false(): void {
		$this->registrar->register();
		$registered = $GLOBALS['_aio_registered_post_types'] ?? array();
		foreach ( Object_Type_Keys::all() as $key ) {
			$args = $registered[ $key ] ?? array();
			$this->assertFalse( $args['show_in_menu'] ?? true, "{$key} menu exposure must be controlled" );
		}
	}
}
