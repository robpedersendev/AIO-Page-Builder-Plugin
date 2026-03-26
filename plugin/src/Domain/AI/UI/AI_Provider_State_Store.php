<?php
/**
 * Persists UI-safe AI provider state (no raw secrets).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Stores provider state for admin display and diagnostics.
 *
 * Option `aio_pb_ai_providers` shape:
 * provider_key => {
 *   credential_ref_or_secure_value,
 *   masked_status,
 *   default_model,
 *   last_test_status,
 *   last_tested_at,
 *   last_successful_use_at
 * }
 */
final class AI_Provider_State_Store {

	private Settings_Service $settings;

	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function all(): array {
		$raw = $this->settings->get( Option_Names::PB_AI_PROVIDERS );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * @param string $provider_key
	 * @return array<string, mixed>
	 */
	public function get( string $provider_key ): array {
		$key = sanitize_key( $provider_key );
		if ( $key === '' ) {
			return array();
		}
		$all = $this->all();
		return isset( $all[ $key ] ) && is_array( $all[ $key ] ) ? $all[ $key ] : array();
	}

	/**
	 * @param string               $provider_key
	 * @param array<string, mixed> $updates
	 * @return void
	 */
	public function merge( string $provider_key, array $updates ): void {
		$key = sanitize_key( $provider_key );
		if ( $key === '' ) {
			return;
		}
		$all         = $this->all();
		$cur         = isset( $all[ $key ] ) && is_array( $all[ $key ] ) ? $all[ $key ] : array();
		$all[ $key ] = array_merge( $cur, $updates );
		$this->settings->set( Option_Names::PB_AI_PROVIDERS, $all );
		$hint = isset( $updates['last_test_status'] ) ? 'last_test_status' : ( isset( $updates['last_successful_use_at'] ) ? 'last_successful_use_at' : 'fields=' . (string) count( $updates ) );
		Named_Debug_Log::event( Named_Debug_Log_Event::PROVIDER_STATE_MERGED, 'provider=' . $key . ' ' . $hint );
	}
}
