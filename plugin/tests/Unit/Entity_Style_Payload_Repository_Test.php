<?php
/**
 * Unit tests for Entity_Style_Payload_Repository and Entity_Style_Payload_Schema (Prompt 251).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Repository;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Repository.php';

final class Entity_Style_Payload_Repository_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$key = Entity_Style_Payload_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	public function test_schema_default_option_has_version_and_payloads(): void {
		$opt = Entity_Style_Payload_Schema::get_default_option();
		$this->assertSame( Entity_Style_Payload_Schema::SCHEMA_VERSION, $opt[ Entity_Style_Payload_Schema::KEY_VERSION ] );
		$this->assertArrayHasKey( 'section_template', $opt[ Entity_Style_Payload_Schema::KEY_PAYLOADS ] );
		$this->assertArrayHasKey( 'page_template', $opt[ Entity_Style_Payload_Schema::KEY_PAYLOADS ] );
	}

	public function test_schema_default_payload_has_version_and_branches(): void {
		$p = Entity_Style_Payload_Schema::get_default_payload();
		$this->assertSame( Entity_Style_Payload_Schema::PAYLOAD_VERSION, $p[ Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION ] );
		$this->assertIsArray( $p[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ] );
		$this->assertIsArray( $p[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ] );
	}

	public function test_is_allowed_entity_type(): void {
		$this->assertTrue( Entity_Style_Payload_Schema::is_allowed_entity_type( 'section_template' ) );
		$this->assertTrue( Entity_Style_Payload_Schema::is_allowed_entity_type( 'page_template' ) );
		$this->assertFalse( Entity_Style_Payload_Schema::is_allowed_entity_type( 'unknown' ) );
	}

	public function test_get_payload_invalid_entity_type_returns_default(): void {
		$repo = new Entity_Style_Payload_Repository();
		$p    = $repo->get_payload( 'invalid_type', 'some_key' );
		$this->assertSame( Entity_Style_Payload_Schema::PAYLOAD_VERSION, $p[ Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION ] );
		$this->assertSame( array(), $p[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ] );
		$this->assertSame( array(), $p[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ] );
	}

	public function test_set_and_get_payload_persists_and_returns_normalized(): void {
		$repo = new Entity_Style_Payload_Repository();
		$payload = array(
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array( 'color' => array( 'primary' => '#333' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES  => array( 'card' => array( '--aio-color-surface' => '#fff' ) ),
		);
		$ok = $repo->set_payload( 'section_template', 'hero_intro', $payload );
		$this->assertTrue( $ok );
		$read = $repo->get_payload( 'section_template', 'hero_intro' );
		$this->assertSame( Entity_Style_Payload_Schema::PAYLOAD_VERSION, $read[ Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION ] );
		$this->assertSame( array( 'color' => array( 'primary' => '#333' ) ), $read[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ] );
		$this->assertSame( array( 'card' => array( '--aio-color-surface' => '#fff' ) ), $read[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ] );
	}

	public function test_set_payload_invalid_entity_type_returns_false(): void {
		$repo = new Entity_Style_Payload_Repository();
		$this->assertFalse( $repo->set_payload( 'invalid', 'key', array() ) );
	}

	public function test_non_string_values_in_branches_stripped(): void {
		$repo = new Entity_Style_Payload_Repository();
		$payload = array(
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES => array( 'color' => array( 'primary' => 123, 'surface' => '#eee' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array(),
		);
		$repo->set_payload( 'page_template', 'pt_landing', $payload );
		$read = $repo->get_payload( 'page_template', 'pt_landing' );
		$this->assertArrayNotHasKey( 'primary', $read[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ]['color'] );
		$this->assertSame( '#eee', $read[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ]['color']['surface'] );
	}

	public function test_get_all_payloads_for_type_and_delete(): void {
		$repo = new Entity_Style_Payload_Repository();
		$repo->set_payload( 'section_template', 'sec_a', Entity_Style_Payload_Schema::get_default_payload() );
		$repo->set_payload( 'section_template', 'sec_b', Entity_Style_Payload_Schema::get_default_payload() );
		$all = $repo->get_all_payloads_for_type( 'section_template' );
		$this->assertCount( 2, $all );
		$this->assertArrayHasKey( 'sec_a', $all );
		$this->assertArrayHasKey( 'sec_b', $all );
		$repo->delete_payload( 'section_template', 'sec_a' );
		$all2 = $repo->get_all_payloads_for_type( 'section_template' );
		$this->assertCount( 1, $all2 );
		$this->assertArrayHasKey( 'sec_b', $all2 );
	}
}
