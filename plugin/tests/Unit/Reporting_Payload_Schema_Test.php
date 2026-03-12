<?php
/**
 * Unit tests for reporting payload schema (spec §46, reporting-payload-contract.md; Prompt 091).
 *
 * Covers required-field presence, prohibited-field exclusion, dedupe-key generation.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Event_Types;
use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Payload_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Delivery_Status.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Payload_Schema.php';

final class Reporting_Payload_Schema_Test extends TestCase {

	public function test_schema_version_is_non_empty(): void {
		$this->assertNotEmpty( Reporting_Payload_Schema::SCHEMA_VERSION );
		$this->assertIsString( Reporting_Payload_Schema::SCHEMA_VERSION );
	}

	public function test_envelope_has_required_keys_passes_with_full_envelope(): void {
		$envelope = array(
			'schema_version' => '1.0',
			'event_type'     => Reporting_Event_Types::INSTALL_NOTIFICATION,
			'site_reference'  => 'example.com',
			'plugin_version'  => '1.0.0',
			'timestamp'       => '2025-03-15T00:00:00Z',
			'dedupe_key'      => 'install_example_2025',
			'payload'         => array(),
		);
		$this->assertTrue( Reporting_Payload_Schema::envelope_has_required_keys( $envelope ) );
	}

	public function test_envelope_has_required_keys_fails_when_key_missing(): void {
		$envelope = array(
			'schema_version' => '1.0',
			'event_type'     => Reporting_Event_Types::INSTALL_NOTIFICATION,
			'site_reference' => 'example.com',
			'plugin_version' => '1.0.0',
			'timestamp'      => '2025-03-15T00:00:00Z',
		);
		$this->assertFalse( Reporting_Payload_Schema::envelope_has_required_keys( $envelope ) );
	}

	public function test_payload_has_required_keys_install(): void {
		$payload = array(
			'website_address'               => 'https://example.com',
			'plugin_version'                 => '1.0.0',
			'wordpress_version'              => '6.4',
			'php_version'                    => '8.2',
			'admin_contact_email'            => 'admin@example.com',
			'timestamp'                      => '2025-03-15T00:00:00Z',
			'dependency_readiness_summary'   => 'all ready',
		);
		$this->assertTrue( Reporting_Payload_Schema::payload_has_required_keys( $payload, Reporting_Event_Types::INSTALL_NOTIFICATION ) );
	}

	public function test_payload_has_required_keys_install_fails_when_key_missing(): void {
		$payload = array(
			'website_address' => 'https://example.com',
			'plugin_version'  => '1.0.0',
		);
		$this->assertFalse( Reporting_Payload_Schema::payload_has_required_keys( $payload, Reporting_Event_Types::INSTALL_NOTIFICATION ) );
	}

	public function test_payload_has_required_keys_heartbeat(): void {
		$payload = array(
			'website_address'                            => 'https://example.com',
			'plugin_version'                            => '1.0.0',
			'wordpress_version'                         => '6.4',
			'php_version'                               => '8.2',
			'admin_contact_email'                       => 'admin@example.com',
			'last_successful_ai_run_at'                  => '',
			'last_successful_build_plan_execution_at'    => '',
			'current_health_summary'                    => 'healthy',
			'current_queue_warning_count'               => 0,
			'current_unresolved_critical_error_count'   => 0,
			'timestamp'                                 => '2025-03-15T00:00:00Z',
		);
		$this->assertTrue( Reporting_Payload_Schema::payload_has_required_keys( $payload, Reporting_Event_Types::HEARTBEAT ) );
	}

	public function test_payload_has_required_keys_developer_error(): void {
		$payload = array(
			'severity'                 => 'critical',
			'category'                 => 'execution',
			'sanitized_error_summary'   => 'Build failed.',
			'expected_behavior'        => 'Success',
			'actual_behavior'          => 'Error',
			'website_address'          => 'https://example.com',
			'plugin_version'           => '1.0.0',
			'wordpress_version'        => '6.4',
			'php_version'              => '8.2',
			'admin_contact_email'      => 'admin@example.com',
			'timestamp'                => '2025-03-15T00:00:00Z',
			'log_reference'            => array( 'log_id' => 'err-1' ),
			'related_plan_id'          => '',
			'related_job_id'          => '',
			'related_run_id'           => '',
		);
		$this->assertTrue( Reporting_Payload_Schema::payload_has_required_keys( $payload, Reporting_Event_Types::DEVELOPER_ERROR_REPORT ) );
	}

	public function test_has_no_prohibited_keys_passes_with_safe_payload(): void {
		$data = array( 'website_address' => 'https://example.com', 'plugin_version' => '1.0' );
		$this->assertTrue( Reporting_Payload_Schema::has_no_prohibited_keys( $data ) );
	}

	public function test_has_no_prohibited_keys_fails_when_password_present(): void {
		$data = array( 'website_address' => 'https://example.com', 'password' => 'secret' );
		$this->assertFalse( Reporting_Payload_Schema::has_no_prohibited_keys( $data ) );
	}

	public function test_has_no_prohibited_keys_fails_when_api_key_present(): void {
		$data = array( 'api_key' => 'sk-xxx' );
		$this->assertFalse( Reporting_Payload_Schema::has_no_prohibited_keys( $data ) );
	}

	public function test_has_no_prohibited_keys_fails_when_nested_contains_secret(): void {
		$data = array( 'payload' => array( 'secret' => 'x' ) );
		$this->assertFalse( Reporting_Payload_Schema::has_no_prohibited_keys( $data ) );
	}

	public function test_dedupe_key_install_format(): void {
		$key = Reporting_Payload_Schema::dedupe_key_install( 'example.com', '2025-03-15T14:00:00Z' );
		$this->assertStringStartsWith( 'install_', $key );
		$this->assertStringContainsString( 'example', $key );
		$this->assertStringContainsString( '2025-03-15', $key );
	}

	public function test_dedupe_key_heartbeat_format(): void {
		$key = Reporting_Payload_Schema::dedupe_key_heartbeat( 'example.com', '2025-03' );
		$this->assertSame( 'heartbeat_example.com_2025-03', $key );
	}

	public function test_dedupe_key_error_by_log_id(): void {
		$key = Reporting_Payload_Schema::dedupe_key_error_by_log_id( 'err-a1b2c3d4' );
		$this->assertStringStartsWith( 'error_', $key );
		$this->assertStringContainsString( 'err-a1b2c3d4', $key );
	}

	public function test_dedupe_key_error_by_signature(): void {
		$key = Reporting_Payload_Schema::dedupe_key_error_by_signature( 'execution', 'abc123', '2025-03-15' );
		$this->assertStringStartsWith( 'error_', $key );
		$this->assertStringContainsString( 'execution', $key );
		$this->assertStringContainsString( '2025-03-15', $key );
	}

	public function test_get_required_payload_keys_returns_empty_for_unknown_event_type(): void {
		$keys = Reporting_Payload_Schema::get_required_payload_keys( 'unknown_type' );
		$this->assertSame( array(), $keys );
	}

	public function test_event_types_all_includes_install_heartbeat_developer_error(): void {
		$all = Reporting_Event_Types::all();
		$this->assertContains( Reporting_Event_Types::INSTALL_NOTIFICATION, $all );
		$this->assertContains( Reporting_Event_Types::HEARTBEAT, $all );
		$this->assertContains( Reporting_Event_Types::DEVELOPER_ERROR_REPORT, $all );
		$this->assertCount( 3, $all );
	}

	public function test_event_types_is_valid(): void {
		$this->assertTrue( Reporting_Event_Types::is_valid( Reporting_Event_Types::INSTALL_NOTIFICATION ) );
		$this->assertFalse( Reporting_Event_Types::is_valid( 'invalid' ) );
	}
}
