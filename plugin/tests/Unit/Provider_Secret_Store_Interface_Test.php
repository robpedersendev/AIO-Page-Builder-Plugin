<?php
/**
 * Unit tests for Provider_Secret_Store_Interface: stub implementation and contract (provider-secret-storage-contract.md).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Secrets/Provider_Secret_Store_Interface.php';

/**
 * Stub implementation: stores credentials in memory only. Used to verify interface contract and that state is safe to serialize.
 */
final class Stub_Provider_Secret_Store implements Provider_Secret_Store_Interface {

	/** @var array<string, string> */
	private array $credentials = array();

	/** @var array<string, string> */
	private array $states = array();

	public function get_credential_for_provider( string $provider_id ): ?string {
		return $this->credentials[ $provider_id ] ?? null;
	}

	public function get_credential_state( string $provider_id ): string {
		return $this->states[ $provider_id ] ?? self::STATE_ABSENT;
	}

	public function has_credential( string $provider_id ): bool {
		return isset( $this->credentials[ $provider_id ] );
	}

	public function set_credential( string $provider_id, string $value ): bool {
		$this->credentials[ $provider_id ] = $value;
		$this->states[ $provider_id ]      = self::STATE_PENDING_VALIDATION;
		return true;
	}

	public function delete_credential( string $provider_id ): bool {
		$had = isset( $this->credentials[ $provider_id ] );
		unset( $this->credentials[ $provider_id ], $this->states[ $provider_id ] );
		return $had;
	}

	public function set_state( string $provider_id, string $state ): void {
		$this->states[ $provider_id ] = $state;
	}
}

final class Provider_Secret_Store_Interface_Test extends TestCase {

	public function test_stub_roundtrip_get_set_delete(): void {
		$store = new Stub_Provider_Secret_Store();
		$this->assertFalse( $store->has_credential( 'openai' ) );
		$this->assertSame( Provider_Secret_Store_Interface::STATE_ABSENT, $store->get_credential_state( 'openai' ) );
		$this->assertNull( $store->get_credential_for_provider( 'openai' ) );

		$store->set_credential( 'openai', 'sk-secret' );
		$this->assertTrue( $store->has_credential( 'openai' ) );
		$this->assertSame( 'sk-secret', $store->get_credential_for_provider( 'openai' ) );
		$this->assertSame( Provider_Secret_Store_Interface::STATE_PENDING_VALIDATION, $store->get_credential_state( 'openai' ) );

		$store->set_state( 'openai', Provider_Secret_Store_Interface::STATE_CONFIGURED );
		$this->assertSame( Provider_Secret_Store_Interface::STATE_CONFIGURED, $store->get_credential_state( 'openai' ) );

		$this->assertTrue( $store->delete_credential( 'openai' ) );
		$this->assertFalse( $store->has_credential( 'openai' ) );
		$this->assertSame( Provider_Secret_Store_Interface::STATE_ABSENT, $store->get_credential_state( 'openai' ) );
		$this->assertNull( $store->get_credential_for_provider( 'openai' ) );
	}

	public function test_credential_state_constants_are_safe_to_serialize(): void {
		$states = array(
			Provider_Secret_Store_Interface::STATE_ABSENT,
			Provider_Secret_Store_Interface::STATE_CONFIGURED,
			Provider_Secret_Store_Interface::STATE_INVALID,
			Provider_Secret_Store_Interface::STATE_ROTATED,
			Provider_Secret_Store_Interface::STATE_PENDING_VALIDATION,
		);
		$payload = array( 'provider_id' => 'openai', 'credential_state' => $states[1] );
		$json    = wp_json_encode( $payload );
		$this->assertStringNotContainsString( 'sk-', $json );
		$this->assertStringContainsString( 'configured', $json );
	}

	public function test_export_safe_config_must_not_include_secret_from_store(): void {
		$store = new Stub_Provider_Secret_Store();
		$store->set_credential( 'openai', 'sk-never-export-this' );
		// Simulate building export-safe config: only state and provider_id, never get_credential_for_provider.
		$export_safe = array(
			'provider_id'       => 'openai',
			'credential_state'  => $store->get_credential_state( 'openai' ),
		);
		$serialized = wp_json_encode( $export_safe );
		$this->assertStringNotContainsString( 'sk-never-export-this', $serialized );
		$this->assertStringContainsString( 'openai', $serialized );
		$this->assertStringContainsString( 'pending_validation', $serialized );
	}
}
