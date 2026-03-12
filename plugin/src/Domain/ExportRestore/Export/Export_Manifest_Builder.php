<?php
/**
 * Builds export manifest.json structure (spec §52.3, export-bundle-structure-contract.md §4).
 *
 * No secrets; all fields from contract.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Bundle_Schema;
use AIOPageBuilder\Infrastructure\Config\Versions;

/**
 * Assembles manifest array with required and optional fields. Caller supplies categories and checksum list.
 */
final class Export_Manifest_Builder {

	/**
	 * Builds the full manifest array for inclusion in the bundle.
	 *
	 * @param string               $export_type        Export mode key.
	 * @param string               $source_site_url     Site URL (no credentials).
	 * @param list<string>         $included_categories Categories present in bundle.
	 * @param list<string>         $excluded_categories Categories explicitly excluded (audit).
	 * @param array<string, string> $package_checksum_list Path => "algo:hexdigest".
	 * @param string               $restore_notes       Human-readable notes.
	 * @param list<string>         $optional_included   Optional categories included (if any).
	 * @param string               $package_filename   Optional filename (no path).
	 * @return array<string, mixed> Manifest ready for JSON encoding.
	 */
	public function build(
		string $export_type,
		string $source_site_url,
		array $included_categories,
		array $excluded_categories,
		array $package_checksum_list,
		string $restore_notes = '',
		array $optional_included = array(),
		string $package_filename = ''
	): array {
		$timestamp = gmdate( 'Y-m-d\TH:i:s\Z' );
		$plugin_version = Versions::plugin();
		$schema_version = Versions::export_schema();

		$manifest = array(
			'export_type'            => $export_type,
			'export_timestamp'       => $timestamp,
			'plugin_version'         => $plugin_version,
			'schema_version'         => $schema_version,
			'source_site_url'         => $source_site_url,
			'included_categories'    => $included_categories,
			'excluded_categories'    => $excluded_categories,
			'package_checksum_list'  => $package_checksum_list,
			'restore_notes'          => $restore_notes,
			'compatibility_flags'    => array(
				'schema_version'                => $schema_version,
				'same_major_required'           => true,
				'migration_floor'                => $schema_version,
				'max_supported_export_schema'   => $schema_version,
			),
		);

		if ( $optional_included !== array() ) {
			$manifest['optional_included'] = $optional_included;
		}
		if ( $package_filename !== '' ) {
			$manifest['package_filename'] = $package_filename;
		}
		$manifest['min_import_plugin_version'] = $plugin_version;

		return $manifest;
	}

	/**
	 * Validates that the built manifest has all required keys (Export_Bundle_Schema).
	 *
	 * @param array<string, mixed> $manifest Built manifest.
	 * @return bool
	 */
	public function manifest_has_required_keys( array $manifest ): bool {
		return Export_Bundle_Schema::manifest_has_required_keys( $manifest );
	}
}
