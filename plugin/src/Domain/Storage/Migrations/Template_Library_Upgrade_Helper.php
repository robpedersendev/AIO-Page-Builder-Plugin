<?php
/**
 * Narrow upgrade helper for the expanded template library (spec §53.3, §53.4, §58.2, §58.5; Prompt 202).
 * Ensures registry_schema version is recorded in version_markers so upgrade paths and future migrations
 * are version-aware. Idempotent and retry-safe; does not mutate registry data.
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
 * Ensures registry_schema is set in version_markers when missing or "0". No registry mutation.
 */
final class Template_Library_Upgrade_Helper {

	private const VERSION_MARKERS_KEY = Option_Names::VERSION_MARKERS;

	private const REGISTRY_SCHEMA_KEY = 'registry_schema';

	/** @var Settings_Service */
	private Settings_Service $settings;

	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Ensures registry_schema is recorded. If already set to a non-zero value, does nothing.
	 * Idempotent and safe to retry.
	 *
	 * @return array{validated: bool, registry_schema_recorded: bool, message: string}
	 */
	public function run(): array {
		$current = $this->settings->get( self::VERSION_MARKERS_KEY );
		if ( ! is_array( $current ) ) {
			$current = array();
		}
		$installed    = isset( $current[ self::REGISTRY_SCHEMA_KEY ] ) && is_string( $current[ self::REGISTRY_SCHEMA_KEY ] )
			? $current[ self::REGISTRY_SCHEMA_KEY ]
			: '0';
		$code_version = Versions::registry_schema();

		if ( $installed !== '0' && $installed !== '' ) {
			return array(
				'validated'                => true,
				'registry_schema_recorded' => false,
				'message'                  => 'registry_schema already set; no change.',
			);
		}

		$current[ self::REGISTRY_SCHEMA_KEY ] = $code_version;
		$this->settings->set( self::VERSION_MARKERS_KEY, $current );

		return array(
			'validated'                => true,
			'registry_schema_recorded' => true,
			'message'                  => 'registry_schema recorded for template library upgrade compatibility.',
		);
	}
}
