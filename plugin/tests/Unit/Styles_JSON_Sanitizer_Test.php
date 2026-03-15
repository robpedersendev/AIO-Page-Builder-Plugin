<?php
/**
 * Unit tests for Styles_JSON_Sanitizer (Prompt 252): valid payloads pass, invalid keys/values rejected, prohibited patterns blocked.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use AIOPageBuilder\Domain\Styling\Styles_JSON_Normalizer;
use AIOPageBuilder\Domain\Styling\Styles_JSON_Sanitizer;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Token_Registry.php';
require_once $plugin_root . '/src/Domain/Styling/Component_Override_Registry.php';
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Styles_JSON_Normalizer.php';
require_once $plugin_root . '/src/Domain/Styling/Styles_JSON_Sanitizer.php';

final class Styles_JSON_Sanitizer_Test extends TestCase {

	private Style_Token_Registry $token_registry;
	private Component_Override_Registry $component_registry;
	private Styles_JSON_Normalizer $normalizer;
	private Styles_JSON_Sanitizer $sanitizer;

	protected function setUp(): void {
		parent::setUp();
		$plugin_root   = dirname( __DIR__, 2 );
		$loader        = new Style_Spec_Loader( $plugin_root . '/specs/' );
		$this->token_registry     = new Style_Token_Registry( $loader );
		$this->component_registry = new Component_Override_Registry( $loader );
		$this->normalizer         = new Styles_JSON_Normalizer();
		$this->sanitizer          = new Styles_JSON_Sanitizer( $this->token_registry, $this->component_registry, $this->normalizer );
	}

	public function test_valid_global_tokens_pass(): void {
		$normalized = array( 'color' => array( 'primary' => '#0a0a0a', 'surface' => '#ffffff' ) );
		$result     = $this->sanitizer->sanitize_global_tokens( $normalized );
		$this->assertTrue( $result->is_valid() );
		$this->assertSame( array(), $result->get_errors() );
		$this->assertSame( $normalized, $result->get_sanitized() );
	}

	public function test_invalid_token_group_rejected(): void {
		$normalized = array( 'invalid_group' => array( 'primary' => '#000' ) );
		$result     = $this->sanitizer->sanitize_global_tokens( $normalized );
		$this->assertFalse( $result->is_valid() );
		$this->assertNotEmpty( $result->get_errors() );
		$this->assertSame( array(), $result->get_sanitized() );
	}

	public function test_invalid_token_name_rejected(): void {
		$normalized = array( 'color' => array( 'not_allowed' => '#000' ) );
		$result     = $this->sanitizer->sanitize_global_tokens( $normalized );
		$this->assertFalse( $result->is_valid() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_prohibited_pattern_url_rejected(): void {
		$normalized = array( 'color' => array( 'primary' => 'url(javascript:alert(1))' ) );
		$result     = $this->sanitizer->sanitize_global_tokens( $normalized );
		$this->assertFalse( $result->is_valid() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_prohibited_pattern_expression_rejected(): void {
		$normalized = array( 'spacing' => array( 'md' => 'expression(1)' ) );
		$result     = $this->sanitizer->sanitize_global_tokens( $normalized );
		$this->assertFalse( $result->is_valid() );
	}

	public function test_prohibited_pattern_angle_brackets_rejected(): void {
		$this->assertNotSame( '', $this->sanitizer->validate_value( '<script>' ) );
		$this->assertNotSame( '', $this->sanitizer->validate_value( 'value>' ) );
	}

	public function test_prohibited_pattern_braces_rejected(): void {
		$this->assertNotSame( '', $this->sanitizer->validate_value( 'value { }' ) );
	}

	public function test_safe_value_passes_validate_value(): void {
		$this->assertSame( '', $this->sanitizer->validate_value( '#fff' ) );
		$this->assertSame( '', $this->sanitizer->validate_value( '1rem', 32 ) );
	}

	public function test_value_exceeding_max_length_rejected(): void {
		$long = str_repeat( 'a', 200 );
		$this->assertNotSame( '', $this->sanitizer->validate_value( $long, 64 ) );
	}

	public function test_valid_entity_payload_pass(): void {
		$normalized = array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => '1',
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array( 'color' => array( 'primary' => '#111' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array( 'card' => array( '--aio-color-surface' => '#f5f5f5' ) ),
		);
		$result = $this->sanitizer->sanitize_entity_payload( $normalized );
		$this->assertTrue( $result->is_valid() );
		$this->assertArrayHasKey( Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES, $result->get_sanitized() );
		$this->assertArrayHasKey( Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES, $result->get_sanitized() );
	}

	public function test_deterministic_validation_result_structure(): void {
		$normalized = array( 'color' => array( 'primary' => '#000' ) );
		$result     = $this->sanitizer->sanitize_global_tokens( $normalized );
		$this->assertIsBool( $result->is_valid() );
		$this->assertIsArray( $result->get_errors() );
		$this->assertIsArray( $result->get_sanitized() );
	}

	/** Rejected unsafe style payload (Prompt 259): entity payload with prohibited value must not be valid. */
	public function test_entity_payload_unsafe_value_rejected(): void {
		$normalized = array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => '1',
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => array( 'color' => array( 'primary' => 'url(javascript:alert(1))' ) ),
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => array(),
		);
		$result = $this->sanitizer->sanitize_entity_payload( $normalized );
		$this->assertFalse( $result->is_valid(), 'Entity payload with prohibited value must be rejected.' );
		$this->assertNotEmpty( $result->get_errors() );
	}
}
