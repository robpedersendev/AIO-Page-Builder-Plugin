<?php
/**
 * Typed get/set wrappers for global plugin options. Capability-gating and sanitization are required at call sites (spec §43.13).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Settings;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Reads and writes only known option keys with documented defaults. No secrets in exportable options.
 */
final class Settings_Service {

	/** @var array<string, array<string, mixed>|array> Default structure per option key. */
	private const DEFAULTS = array(
		Option_Names::MAIN_SETTINGS                 => array(),
		Option_Names::VERSION_MARKERS               => array(),
		Option_Names::REPORTING_SETTINGS            => array(),
		Option_Names::DEPENDENCY_NOTICE_DISMISSALS  => array(),
		Option_Names::UNINSTALL_PREFS               => array(),
		Option_Names::PROVIDER_CONFIG_REF           => array(),
		Option_Names::PROVIDER_HEALTH_STATE         => array(),
		Option_Names::PROFILE_CURRENT               => array(),
		Option_Names::ONBOARDING_DRAFT              => array(),
		Option_Names::PROMPT_EXPERIMENTS            => array( 'definitions' => array() ),
	);

	/**
	 * Returns the value for a known option key, or its default when not set.
	 *
	 * @param string $key One of Option_Names constants.
	 * @return array<string, mixed>|array Stored value or default structure.
	 * @throws \InvalidArgumentException When key is not a known option name.
	 */
	public function get( string $key ): array {
		if ( ! Option_Names::is_valid( $key ) ) {
			throw new \InvalidArgumentException( 'Unknown option key: ' . $key );
		}
		$value = \get_option( $key, self::DEFAULTS[ $key ] );
		if ( ! is_array( $value ) ) {
			return self::DEFAULTS[ $key ];
		}
		return $value;
	}

	/**
	 * Persists a value for a known option key. Callers must sanitize and capability-gate; no secrets in exportable options.
	 *
	 * @param string $key   One of Option_Names constants.
	 * @param array  $value Array structure to store; must be sanitized by caller.
	 * @return void
	 * @throws \InvalidArgumentException When key is not a known option name.
	 */
	public function set( string $key, array $value ): void {
		if ( ! Option_Names::is_valid( $key ) ) {
			throw new \InvalidArgumentException( 'Unknown option key: ' . $key );
		}
		\update_option( $key, $value );
	}

	/**
	 * Returns the default structure for a known option key (for schema/docs and tests).
	 *
	 * @param string $key One of Option_Names constants.
	 * @return array<string, mixed>|array
	 * @throws \InvalidArgumentException When key is not a known option name.
	 */
	public function get_default( string $key ): array {
		if ( ! Option_Names::is_valid( $key ) ) {
			throw new \InvalidArgumentException( 'Unknown option key: ' . $key );
		}
		return self::DEFAULTS[ $key ];
	}

	/**
	 * Returns whether the option key is known.
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	public function has_key( string $key ): bool {
		return Option_Names::is_valid( $key );
	}
}
