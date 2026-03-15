<?php
/**
 * Unit tests for Styles_JSON_Normalizer (Prompt 252): deterministic shapes for tokens, component overrides, entity payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use AIOPageBuilder\Domain\Styling\Styles_JSON_Normalizer;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Styles_JSON_Normalizer.php';

final class Styles_JSON_Normalizer_Test extends TestCase {

	private Styles_JSON_Normalizer $normalizer;

	protected function setUp(): void {
		parent::setUp();
		$this->normalizer = new Styles_JSON_Normalizer();
	}

	public function test_normalize_global_tokens_returns_only_string_keys_and_values(): void {
		$raw = array(
			'color' => array( 'primary' => '#111', 'surface' => '#fff' ),
			1       => array( 'x' => 'y' ),
			'typography' => array( 'heading' => 'Georgia', 2 => 'dropped' ),
		);
		$out = $this->normalizer->normalize_global_tokens( $raw );
		$this->assertArrayHasKey( 'color', $out );
		$this->assertSame( array( 'primary' => '#111', 'surface' => '#fff' ), $out['color'] );
		$this->assertArrayHasKey( 'typography', $out );
		$this->assertSame( array( 'heading' => 'Georgia' ), $out['typography'] );
		$this->assertArrayNotHasKey( 1, $out );
	}

	public function test_normalize_global_tokens_non_array_returns_empty(): void {
		$this->assertSame( array(), $this->normalizer->normalize_global_tokens( null ) );
		$this->assertSame( array(), $this->normalizer->normalize_global_tokens( 'string' ) );
	}

	public function test_normalize_global_component_overrides_shape(): void {
		$raw = array( 'card' => array( '--aio-color-primary' => '#333' ) );
		$out = $this->normalizer->normalize_global_component_overrides( $raw );
		$this->assertSame( array( 'card' => array( '--aio-color-primary' => '#333' ) ), $out );
	}

	public function test_normalize_entity_payload_deterministic(): void {
		$raw = array(
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array( 'color' => array( 'primary' => '#000' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array(),
		);
		$out = $this->normalizer->normalize_entity_payload( $raw );
		$this->assertArrayHasKey( Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION, $out );
		$this->assertArrayHasKey( Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES, $out );
		$this->assertArrayHasKey( Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES, $out );
		$this->assertSame( array( 'color' => array( 'primary' => '#000' ) ), $out[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ] );
		$this->assertSame( array(), $out[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ] );
	}

	public function test_normalize_entity_payload_non_array_returns_default(): void {
		$out = $this->normalizer->normalize_entity_payload( null );
		$this->assertSame( Entity_Style_Payload_Schema::get_default_payload(), $out );
	}
}
