<?php
/**
 * Unit tests for Secret_Redactor: redaction of secret-bearing keys (provider-secret-storage-contract.md §5–6).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Secrets\Secret_Redactor;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Secrets/Secret_Redactor.php';

final class Secret_Redactor_Test extends TestCase {

	private function redactor(): Secret_Redactor {
		return new Secret_Redactor();
	}

	public function test_redact_array_replaces_api_key_with_placeholder(): void {
		$r    = $this->redactor();
		$in   = array( 'provider_id' => 'openai', 'api_key' => 'sk-secret123', 'default_model' => 'gpt-4o' );
		$out  = $r->redact_array( $in );
		$this->assertSame( 'openai', $out['provider_id'] );
		$this->assertSame( Secret_Redactor::REDACTED_PLACEHOLDER, $out['api_key'] );
		$this->assertSame( 'gpt-4o', $out['default_model'] );
		$this->assertSame( 'sk-secret123', $in['api_key'], 'Original array must not be modified' );
	}

	public function test_redact_array_redacts_nested_secret_keys(): void {
		$r   = $this->redactor();
		$in  = array(
			'config' => array(
				'api_key'   => 'sk-nested',
				'endpoint'  => 'https://api.example.com',
				'password'  => 'pwd123',
			),
		);
		$out = $r->redact_array( $in );
		$this->assertSame( Secret_Redactor::REDACTED_PLACEHOLDER, $out['config']['api_key'] );
		$this->assertSame( 'https://api.example.com', $out['config']['endpoint'] );
		$this->assertSame( Secret_Redactor::REDACTED_PLACEHOLDER, $out['config']['password'] );
	}

	public function test_redact_array_redacts_token_and_secret(): void {
		$r   = $this->redactor();
		$in  = array( 'access_token' => 'tok_xyz', 'client_secret' => 'cs_abc' );
		$out = $r->redact_array( $in );
		$this->assertSame( Secret_Redactor::REDACTED_PLACEHOLDER, $out['access_token'] );
		$this->assertSame( Secret_Redactor::REDACTED_PLACEHOLDER, $out['client_secret'] );
	}

	public function test_redact_array_preserves_non_secret_keys(): void {
		$r   = $this->redactor();
		$in  = array( 'provider_id' => 'openai', 'default_model' => 'gpt-4o', 'timeout' => 60 );
		$out = $r->redact_array( $in );
		$this->assertSame( $in, $out );
	}

	public function test_redacted_output_contains_no_raw_secret(): void {
		$r   = $this->redactor();
		$in  = array( 'api_key' => 'sk-proj-abc123xyz', 'provider_id' => 'openai' );
		$out = $r->redact_array( $in );
		$str = wp_json_encode( $out );
		$this->assertStringNotContainsString( 'sk-proj-abc123xyz', $str );
		$this->assertStringContainsString( Secret_Redactor::REDACTED_PLACEHOLDER, $str );
	}

	public function test_is_secret_key_recognizes_suffixes(): void {
		$r = $this->redactor();
		$this->assertTrue( $r->is_secret_key( 'api_key' ) );
		$this->assertTrue( $r->is_secret_key( 'custom_secret' ) );
		$this->assertTrue( $r->is_secret_key( 'bearer_token' ) );
		$this->assertTrue( $r->is_secret_key( 'user_password' ) );
		$this->assertFalse( $r->is_secret_key( 'provider_id' ) );
		$this->assertFalse( $r->is_secret_key( 'default_model' ) );
	}

	public function test_redact_string_with_json_redacts_secrets(): void {
		$r     = $this->redactor();
		$json  = '{"provider_id":"openai","api_key":"sk-leak","model":"gpt-4o"}';
		$out   = $r->redact_string( $json );
		$this->assertStringNotContainsString( 'sk-leak', $out );
		$this->assertStringContainsString( Secret_Redactor::REDACTED_PLACEHOLDER, $out );
		$decoded = json_decode( $out, true );
		$this->assertIsArray( $decoded );
		$this->assertSame( Secret_Redactor::REDACTED_PLACEHOLDER, $decoded['api_key'] );
	}

	public function test_redact_string_plain_text_unchanged(): void {
		$r    = $this->redactor();
		$plain = 'Some error message without JSON structure';
		$this->assertSame( $plain, $r->redact_string( $plain ) );
	}

	public function test_redacted_array_is_safe_for_serialization_export(): void {
		$r   = $this->redactor();
		$in  = array(
			'api_key'       => 'sk-export-never',
			'client_secret' => 'cs-export-never',
			'token'         => 'tok-export-never',
		);
		$out = $r->redact_array( $in );
		foreach ( array( 'api_key', 'client_secret', 'token' ) as $key ) {
			$this->assertSame( Secret_Redactor::REDACTED_PLACEHOLDER, $out[ $key ], "Key {$key} must be redacted" );
		}
		$serialized = wp_json_encode( $out );
		$this->assertStringNotContainsString( 'sk-export-never', $serialized );
		$this->assertStringNotContainsString( 'cs-export-never', $serialized );
		$this->assertStringNotContainsString( 'tok-export-never', $serialized );
	}
}
