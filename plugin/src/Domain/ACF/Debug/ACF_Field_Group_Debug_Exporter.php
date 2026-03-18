<?php
/**
 * Debug export of blueprint-family and field-group definitions for support and environment comparison (spec §20, §52, §59.13; Prompt 224).
 * Produces field_group_debug_export_record list and acf_mirror_diff_summary. Internal/debug only; capability-gated.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Debug;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;

/**
 * Builds debug export records and registry-vs-mirror diff summary. No secrets; definition metadata only.
 *
 * Example field_group_debug_export_record:
 * {
 *   "section_key": "st01_hero",
 *   "group_key": "group_aio_st01_hero",
 *   "section_version": "1",
 *   "source": "registry",
 *   "field_count": 3,
 *   "registry_reference": "section_key:st01_hero"
 * }
 *
 * Example acf_mirror_diff_summary:
 * {
 *   "in_registry_not_mirror": ["group_aio_st02_cta"],
 *   "in_mirror_not_registry": [],
 *   "version_mismatch": [{"group_key": "group_aio_st01_hero", "registry_version": "1", "mirror_version": "0"}],
 *   "summary": "2 in sync, 1 only in registry, 0 only in mirror, 0 version mismatch."
 * }
 */
final class ACF_Field_Group_Debug_Exporter {

	/** @var Section_Field_Blueprint_Service_Interface */
	private Section_Field_Blueprint_Service_Interface $blueprint_service;

	/** @var ACF_Local_JSON_Mirror_Service */
	private ACF_Local_JSON_Mirror_Service $mirror_service;

	public function __construct(
		Section_Field_Blueprint_Service_Interface $blueprint_service,
		ACF_Local_JSON_Mirror_Service $mirror_service
	) {
		$this->blueprint_service = $blueprint_service;
		$this->mirror_service    = $mirror_service;
	}

	/**
	 * Builds list of field_group_debug_export_record for all registry-defined groups.
	 *
	 * @return list<array<string, mixed>> field_group_debug_export_record entries.
	 */
	public function build_debug_export(): array {
		$blueprints = $this->blueprint_service->get_all_blueprints();
		$records    = array();
		foreach ( $blueprints as $blueprint ) {
			$section_key = (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' );
			if ( $section_key === '' ) {
				continue;
			}
			$fields      = $blueprint[ Field_Blueprint_Schema::FIELDS ] ?? array();
			$field_count = is_array( $fields ) ? count( $fields ) : 0;
			$records[]   = array(
				'section_key'        => $section_key,
				'group_key'          => Field_Key_Generator::group_key( $section_key ),
				'section_version'    => (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_VERSION ] ?? '' ),
				'source'             => 'registry',
				'field_count'        => $field_count,
				'registry_reference' => 'section_key:' . $section_key,
			);
		}
		return $records;
	}

	/**
	 * Builds acf_mirror_diff_summary by comparing registry manifest to mirror manifest (or list of group keys from mirror).
	 *
	 * @param array<string, mixed> $registry_manifest From ACF_Local_JSON_Mirror_Service::get_manifest_without_writing().
	 * @param array<string, mixed> $mirror_manifest    From a previously generated manifest (e.g. read from mirror manifest.json) or array with keys 'files' or 'group_keys'.
	 * @return array<string, mixed> acf_mirror_diff_summary.
	 */
	public function build_diff_summary( array $registry_manifest, array $mirror_manifest ): array {
		$registry_keys  = array_fill_keys( $registry_manifest['group_keys'] ?? array(), true );
		$mirror_keys    = array_fill_keys( $mirror_manifest['group_keys'] ?? array(), true );
		$registry_files = array();
		foreach ( $registry_manifest['files'] ?? array() as $f ) {
			$gk = (string) ( $f['group_key'] ?? '' );
			if ( $gk !== '' ) {
				$registry_files[ $gk ] = $f;
			}
		}
		$mirror_files = array();
		foreach ( $mirror_manifest['files'] ?? array() as $f ) {
			$gk = (string) ( $f['group_key'] ?? '' );
			if ( $gk !== '' ) {
				$mirror_files[ $gk ] = $f;
			}
		}
		$in_registry_not_mirror = array_values( array_diff( array_keys( $registry_keys ), array_keys( $mirror_keys ) ) );
		$in_mirror_not_registry = array_values( array_diff( array_keys( $mirror_keys ), array_keys( $registry_keys ) ) );
		$version_mismatch       = array();
		foreach ( array_keys( $registry_keys ) as $gk ) {
			if ( ! isset( $mirror_files[ $gk ] ) ) {
				continue;
			}
			$reg_ver = (string) ( $registry_files[ $gk ]['section_version'] ?? '' );
			$mir_ver = (string) ( $mirror_files[ $gk ]['section_version'] ?? '' );
			if ( $reg_ver !== $mir_ver ) {
				$version_mismatch[] = array(
					'group_key'        => $gk,
					'registry_version' => $reg_ver,
					'mirror_version'   => $mir_ver,
				);
			}
		}
		$in_sync = count( array_keys( $registry_keys ) ) - count( $in_registry_not_mirror ) - count( $version_mismatch );
		$summary = sprintf(
			/* translators: 1: in sync count, 2: only in registry, 3: only in mirror, 4: version mismatch */
			__( '%1$d in sync, %2$d only in registry, %3$d only in mirror, %4$d version mismatch.', 'aio-page-builder' ),
			$in_sync,
			count( $in_registry_not_mirror ),
			count( $in_mirror_not_registry ),
			count( $version_mismatch )
		);
		return array(
			'in_registry_not_mirror' => $in_registry_not_mirror,
			'in_mirror_not_registry' => $in_mirror_not_registry,
			'version_mismatch'       => $version_mismatch,
			'summary'                => $summary,
		);
	}
}
