<?php
/**
 * Typed read/write for global plugin options (spec §9.4). All writes must be capability-gated by callers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Settings;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Stable option roots: aio_page_builder_settings, aio_page_builder_reporting, aio_page_builder_dependency_notices,
 * aio_page_builder_uninstall_prefs. No direct get_option/update_option outside this layer for these domains.
 */
final class Option_Store {

	private const KEY_MAIN       = Option_Names::MAIN_SETTINGS;
	private const KEY_REPORTING  = Option_Names::REPORTING_SETTINGS;
	private const KEY_DISMISSALS = Option_Names::DEPENDENCY_NOTICE_DISMISSALS;
	private const KEY_UNINSTALL  = Option_Names::UNINSTALL_PREFS;

	/** @var Settings_Service */
	private Settings_Service $settings;

	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	/** @return array<string, mixed> */
	public function get_main_settings(): array {
		return $this->settings->get( self::KEY_MAIN );
	}

	/**
	 * @param array<string, mixed> $value Caller-sanitized value; must not contain secrets.
	 */
	public function set_main_settings( array $value ): void {
		$this->settings->set( self::KEY_MAIN, $value );
	}

	/** @return array<string, mixed> */
	public function get_reporting_settings(): array {
		return $this->settings->get( self::KEY_REPORTING );
	}

	/**
	 * @param array<string, mixed> $value Caller-sanitized value; must not contain secrets.
	 */
	public function set_reporting_settings( array $value ): void {
		$this->settings->set( self::KEY_REPORTING, $value );
	}

	/** @return array<string, mixed> */
	public function get_dependency_dismissals(): array {
		return $this->settings->get( self::KEY_DISMISSALS );
	}

	/** @param array<string, mixed> $value */
	public function set_dependency_dismissals( array $value ): void {
		$this->settings->set( self::KEY_DISMISSALS, $value );
	}

	/** @return array<string, mixed> */
	public function get_uninstall_prefs(): array {
		return $this->settings->get( self::KEY_UNINSTALL );
	}

	/** @param array<string, mixed> $value */
	public function set_uninstall_prefs( array $value ): void {
		$this->settings->set( self::KEY_UNINSTALL, $value );
	}
}
