<?php
/**
 * Reads and writes schema/table/registry/export version map for migration detection (spec §8.10, §11.9, §58.4).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Migrations;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Config\Versions;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Canonical version map stored in options (VERSION_MARKERS). Used to detect pending migrations and record outcomes.
 * No migration execution here; installer prompts plug into this tracker.
 */
final class Schema_Version_Tracker {

	private const VERSION_MARKERS_KEY = Option_Names::VERSION_MARKERS;

	/** @var Settings_Service */
	private Settings_Service $settings;

	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Returns the installed version for each known version key. Missing keys are treated as "0" (no migration run).
	 *
	 * @return array<string, string> Map of version_key => installed version string.
	 */
	public function get_installed_versions(): array {
		$raw    = $this->settings->get( self::VERSION_MARKERS_KEY );
		$keys   = Versions::version_keys();
		$result = array();
		foreach ( $keys as $key ) {
			$result[ $key ] = ( isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) ) ? $raw[ $key ] : '0';
		}
		return $result;
	}

	/**
	 * Sets the installed version for one key. Persists to options. Call after a successful migration only.
	 *
	 * @param string $key     Version key (e.g. table_schema).
	 * @param string $version Version string to record.
	 * @return void
	 */
	public function set_installed_version( string $key, string $version ): void {
		$current = $this->settings->get( self::VERSION_MARKERS_KEY );
		if ( ! is_array( $current ) ) {
			$current = array();
		}
		$current[ $key ] = $version;
		$this->settings->set( self::VERSION_MARKERS_KEY, $current );
	}

	/**
	 * Returns migrations that apply given current installed versions (applies_to returns true).
	 *
	 * @param array<int, Migration_Contract> $migrations Available migrations in run order.
	 * @return array<int, Migration_Contract> Migrations that are applicable and pending.
	 */
	public function get_pending_migrations( array $migrations ): array {
		$installed = $this->get_installed_versions();
		$pending   = array();
		foreach ( $migrations as $migration ) {
			$key     = $migration->version_key();
			$current = isset( $installed[ $key ] ) ? $installed[ $key ] : '0';
			if ( $migration->applies_to( $current ) ) {
				$pending[] = $migration;
			}
		}
		return $pending;
	}

	/**
	 * Records a migration result for audit/failure handling. Stores last result per migration_id in version_markers.
	 * Minimum fields: migration_id, status, message (sanitized). No secrets.
	 *
	 * @param string           $migration_id Migration identifier.
	 * @param Migration_Result $result       Result (message and notes must be sanitized).
	 * @return void
	 */
	public function record_migration_result( string $migration_id, Migration_Result $result ): void {
		$current = $this->settings->get( self::VERSION_MARKERS_KEY );
		if ( ! is_array( $current ) ) {
			$current = array();
		}
		$log_key = '_migration_log';
		if ( ! isset( $current[ $log_key ] ) || ! is_array( $current[ $log_key ] ) ) {
			$current[ $log_key ] = array();
		}
		$current[ $log_key ][ $migration_id ] = array(
			'status'      => $result->status,
			'message'     => $result->message,
			'safe_retry'  => $result->safe_retry,
			'recorded_at' => gmdate( 'c' ),
		);
		$this->settings->set( self::VERSION_MARKERS_KEY, $current );
	}

	/**
	 * Returns whether the installed version for a key is ahead of or equal to the code's expected version (unsupported future).
	 *
	 * @param string $version_key Version key to check.
	 * @return bool True if installed version is greater than code version (e.g. downgrade scenario).
	 */
	public function is_installed_version_future( string $version_key ): bool {
		$installed   = $this->get_installed_versions();
		$code        = Versions::all();
		$installed_v = isset( $installed[ $version_key ] ) ? $installed[ $version_key ] : '0';
		$code_v      = isset( $code[ $version_key ] ) ? $code[ $version_key ] : '1';
		return version_compare( $installed_v, $code_v, '>' );
	}
}
