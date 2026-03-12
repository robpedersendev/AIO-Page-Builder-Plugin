<?php
/**
 * Runs provider connection tests and persists result and last-successful-use (spec §49.9).
 * Call sites must enforce aio_manage_ai_providers and nonce verification.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers\Drivers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Request_Context_Builder;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Performs a minimal request to verify provider reachability and credential validity.
 * Does not run a full plan; connection tests must not become arbitrary remote-request tools.
 */
final class Provider_Connection_Test_Service {

	/** @var Provider_Request_Context_Builder */
	private Provider_Request_Context_Builder $request_context_builder;

	/** @var Provider_Capability_Resolver */
	private Provider_Capability_Resolver $capability_resolver;

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** Max tokens for connection-test request. */
	private const CONNECTION_TEST_MAX_TOKENS = 10;

	/** Timeout for connection-test request (seconds). */
	private const CONNECTION_TEST_TIMEOUT = 15;

	public function __construct(
		Provider_Request_Context_Builder $request_context_builder,
		Provider_Capability_Resolver $capability_resolver,
		Settings_Service $settings
	) {
		$this->request_context_builder = $request_context_builder;
		$this->capability_resolver      = $capability_resolver;
		$this->settings                = $settings;
	}

	/**
	 * Runs a connection test for the given driver and persists result and last_successful_use.
	 *
	 * @param AI_Provider_Interface $driver Provider driver to test.
	 * @return Provider_Connection_Test_Result
	 */
	public function run_test( AI_Provider_Interface $driver ): Provider_Connection_Test_Result {
		$provider_id = $driver->get_provider_id();
		$model       = $this->capability_resolver->resolve_default_model_for_connection_test( $driver );
		if ( $model === null || $model === '' ) {
			$result = new Provider_Connection_Test_Result(
				false,
				$provider_id,
				'unknown',
				array(
					'category'      => 'unsupported_feature',
					'user_message'  => 'No model available for connection test.',
					'internal_code'  => 'unsupported_feature',
					'provider_raw'   => null,
					'retry_posture'  => 'no_retry',
				),
				gmdate( 'c' ),
				'No model available for connection test.'
			);
			$this->persist_result( $provider_id, $result, null );
			return $result;
		}

		$request_id = 'conn_test_' . uniqid( '', true );
		$request    = $this->request_context_builder->build(
			$request_id,
			$model,
			'',
			'Connection test.',
			array(
				'max_tokens'       => self::CONNECTION_TEST_MAX_TOKENS,
				'timeout_seconds'  => self::CONNECTION_TEST_TIMEOUT,
			)
		);

		$response = $driver->request( $request );
		$tested_at = gmdate( 'c' );

		if ( ! empty( $response['success'] ) ) {
			$result = new Provider_Connection_Test_Result(
				true,
				$provider_id,
				(string) ( $response['model_used'] ?? $model ),
				null,
				$tested_at,
				'Connection successful.'
			);
			$this->persist_result( $provider_id, $result, $tested_at );
			return $result;
		}

		$normalized_error = isset( $response['normalized_error'] ) && is_array( $response['normalized_error'] )
			? $response['normalized_error']
			: null;
		$user_message     = $normalized_error['user_message'] ?? 'Connection test failed.';
		$result           = new Provider_Connection_Test_Result(
			false,
			$provider_id,
			(string) ( $response['model_used'] ?? $model ),
			$normalized_error,
			$tested_at,
			$user_message
		);
		$this->persist_result( $provider_id, $result, null );
		return $result;
	}

	/**
	 * Returns the last connection test result for a provider (if any).
	 *
	 * @param string $provider_id Provider identifier.
	 * @return Provider_Connection_Test_Result|null
	 */
	public function get_last_result( string $provider_id ): ?Provider_Connection_Test_Result {
		$health = $this->settings->get( Option_Names::PROVIDER_HEALTH_STATE );
		$entry  = $health[ $provider_id ] ?? null;
		if ( ! is_array( $entry ) || empty( $entry['connection_test_result'] ) || ! is_array( $entry['connection_test_result'] ) ) {
			return null;
		}
		return Provider_Connection_Test_Result::from_array( $entry['connection_test_result'] );
	}

	/**
	 * Returns the last successful provider use timestamp (ISO 8601) for a provider, or null.
	 *
	 * @param string $provider_id Provider identifier.
	 * @return string|null
	 */
	public function get_last_successful_use( string $provider_id ): ?string {
		$health = $this->settings->get( Option_Names::PROVIDER_HEALTH_STATE );
		$entry  = $health[ $provider_id ] ?? null;
		if ( ! is_array( $entry ) || empty( $entry['last_successful_use'] ) || ! is_string( $entry['last_successful_use'] ) ) {
			return null;
		}
		return $entry['last_successful_use'];
	}

	/**
	 * Persists connection test result and optionally last_successful_use. No secrets.
	 *
	 * @param string                        $provider_id        Provider id.
	 * @param Provider_Connection_Test_Result $result           Test result.
	 * @param string|null                  $last_successful_use ISO 8601 when test succeeded; null to leave unchanged.
	 * @return void
	 */
	private function persist_result( string $provider_id, Provider_Connection_Test_Result $result, ?string $last_successful_use ): void {
		$health = $this->settings->get( Option_Names::PROVIDER_HEALTH_STATE );
		if ( ! is_array( $health ) ) {
			$health = array();
		}
		$entry = $health[ $provider_id ] ?? array();
		if ( ! is_array( $entry ) ) {
			$entry = array();
		}
		$entry['connection_test_result'] = $result->to_array();
		if ( $last_successful_use !== null ) {
			$entry['last_successful_use'] = $last_successful_use;
		}
		$health[ $provider_id ] = $entry;
		$this->settings->set( Option_Names::PROVIDER_HEALTH_STATE, $health );
	}

	/**
	 * Records last successful provider use (e.g. after a successful planning request). Call from planner/orchestrator.
	 *
	 * @param string $provider_id Provider identifier.
	 * @param string $timestamp   ISO 8601 timestamp.
	 * @return void
	 */
	public function record_last_successful_use( string $provider_id, string $timestamp ): void {
		$health = $this->settings->get( Option_Names::PROVIDER_HEALTH_STATE );
		if ( ! is_array( $health ) ) {
			$health = array();
		}
		$entry = $health[ $provider_id ] ?? array();
		if ( ! is_array( $entry ) ) {
			$entry = array();
		}
		$entry['last_successful_use'] = $timestamp;
		$health[ $provider_id ]      = $entry;
		$this->settings->set( Option_Names::PROVIDER_HEALTH_STATE, $health );
	}
}
