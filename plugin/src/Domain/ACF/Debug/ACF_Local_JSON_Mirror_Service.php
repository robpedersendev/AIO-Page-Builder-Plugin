<?php
/**
 * Generates deterministic local JSON mirror artifacts for plugin-owned ACF field groups (spec §20.3, §20.4, §20.15, §52; Prompt 224).
 * Registry remains source of truth; mirror is for debug, environment comparison, and recovery support only.
 * No secrets or live content; version markers and registry references included.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Debug;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder;
use AIOPageBuilder\Infrastructure\Config\Versions;

/**
 * Writes one JSON file per plugin-owned field group to a target directory and returns acf_local_json_manifest.
 *
 * Example acf_local_json_manifest payload:
 * {
 *   "schema_version": "1",
 *   "mirror_version": "1",
 *   "generated_at": "2025-03-14T12:00:00Z",
 *   "plugin_version": "1.0.0",
 *   "source": "registry",
 *   "group_keys": ["group_aio_st01_hero", "group_aio_st05_faq"],
 *   "files": [
 *     { "group_key": "group_aio_st01_hero", "section_key": "st01_hero", "section_version": "1", "path_relative": "group_aio_st01_hero.json" }
 *   ],
 *   "label": "ACF field groups mirror (internal/debug). Registry is source of truth."
 * }
 */
final class ACF_Local_JSON_Mirror_Service {

	/** Manifest schema version for acf_local_json_manifest. */
	public const MANIFEST_SCHEMA_VERSION = '1';

	/** @var Section_Field_Blueprint_Service_Interface */
	private Section_Field_Blueprint_Service_Interface $blueprint_service;

	/** @var ACF_Group_Builder */
	private ACF_Group_Builder $group_builder;

	public function __construct(
		Section_Field_Blueprint_Service_Interface $blueprint_service,
		ACF_Group_Builder $group_builder
	) {
		$this->blueprint_service = $blueprint_service;
		$this->group_builder     = $group_builder;
	}

	/**
	 * Generates mirror JSON files into the target directory and returns the manifest.
	 * One file per plugin-owned group: {group_key}.json. Creates directory if needed.
	 *
	 * @param string $target_dir Absolute path to the directory (e.g. staging/acf_field_groups_mirror).
	 * @return array<string, mixed> acf_local_json_manifest.
	 */
	public function generate_mirror_to_directory( string $target_dir ): array {
		$target_dir = rtrim( $target_dir, '/\\' ) . '/';
		if ( ! is_dir( $target_dir ) && ! wp_mkdir_p( $target_dir ) ) {
			return $this->manifest_failure( 'Could not create mirror directory.' );
		}
		$blueprints = $this->blueprint_service->get_all_blueprints();
		$files      = array();
		$group_keys = array();
		foreach ( $blueprints as $blueprint ) {
			$section_key = (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' );
			if ( $section_key === '' ) {
				continue;
			}
			$group = $this->group_builder->build_group( $blueprint );
			if ( $group === null ) {
				continue;
			}
			$group_key       = (string) ( $group['key'] ?? Field_Key_Generator::group_key( $section_key ) );
			$section_version = (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_VERSION ] ?? '' );
			$filename        = $group_key . '.json';
			$path_relative   = $filename;
			$full_path       = $target_dir . $filename;
			$safe_group      = $this->sanitize_group_for_export( $group );
			$json            = \wp_json_encode( $safe_group, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
			if ( $json !== false && file_put_contents( $full_path, $json ) !== false ) {
				$files[]      = array(
					'group_key'       => $group_key,
					'section_key'     => $section_key,
					'section_version' => $section_version,
					'path_relative'   => $path_relative,
				);
				$group_keys[] = $group_key;
			}
		}
		return $this->build_manifest( $files, $group_keys, false );
	}

	/**
	 * Builds acf_local_json_manifest without writing files (for diffing or export metadata).
	 *
	 * @return array<string, mixed> acf_local_json_manifest with empty files list and group_keys from registry.
	 */
	public function get_manifest_without_writing(): array {
		$blueprints = $this->blueprint_service->get_all_blueprints();
		$group_keys = array();
		$files      = array();
		foreach ( $blueprints as $blueprint ) {
			$section_key = (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' );
			if ( $section_key === '' ) {
				continue;
			}
			$group_key    = Field_Key_Generator::group_key( $section_key );
			$group_keys[] = $group_key;
			$files[]      = array(
				'group_key'       => $group_key,
				'section_key'     => $section_key,
				'section_version' => (string) ( $blueprint[ Field_Blueprint_Schema::SECTION_VERSION ] ?? '' ),
				'path_relative'   => $group_key . '.json',
			);
		}
		return $this->build_manifest( $files, $group_keys, false );
	}

	/**
	 * Sanitizes group array for export: no live values, no secrets; definition only.
	 *
	 * @param array<string, mixed> $group
	 * @return array<string, mixed>
	 */
	private function sanitize_group_for_export( array $group ): array {
		$out     = array();
		$allowed = array( 'key', 'title', 'fields', 'location', 'menu_order', 'position', 'style', 'label_placement', 'instruction_placement', 'description', 'hide_on_screen', '_aio_section_key', '_aio_section_version' );
		foreach ( $group as $k => $v ) {
			if ( ! in_array( $k, $allowed, true ) ) {
				continue;
			}
			if ( $k === 'fields' && is_array( $v ) ) {
				$out[ $k ] = $this->sanitize_fields_for_export( $v );
			} else {
				$out[ $k ] = $v;
			}
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $fields
	 * @return array<int, array<string, mixed>>
	 */
	private function sanitize_fields_for_export( array $fields ): array {
		$out           = array();
		$field_allowed = array( 'key', 'name', 'label', 'type', 'required', 'default_value', 'instructions', 'sub_fields', 'min', 'max', 'layout', 'return_format', 'preview_size' );
		foreach ( $fields as $f ) {
			if ( ! is_array( $f ) ) {
				continue;
			}
			$row = array();
			foreach ( $f as $fk => $fv ) {
				if ( ! in_array( $fk, $field_allowed, true ) ) {
					continue;
				}
				if ( $fk === 'sub_fields' && is_array( $fv ) ) {
					$row[ $fk ] = $this->sanitize_fields_for_export( $fv );
				} else {
					$row[ $fk ] = $fv;
				}
			}
			$out[] = $row;
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $files
	 * @param array<int, string>               $group_keys
	 * @param bool                       $failure
	 * @return array<string, mixed>
	 */
	private function build_manifest( array $files, array $group_keys, bool $failure ): array {
		$manifest = array(
			'schema_version' => self::MANIFEST_SCHEMA_VERSION,
			'mirror_version' => self::MANIFEST_SCHEMA_VERSION,
			'generated_at'   => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'plugin_version' => Versions::plugin(),
			'source'         => 'registry',
			'group_keys'     => $group_keys,
			'files'          => $files,
			'label'          => __( 'ACF field groups mirror (internal/debug). Registry is source of truth.', 'aio-page-builder' ),
		);
		if ( $failure ) {
			$manifest['error']   = true;
			$manifest['message'] = 'Mirror generation failed.';
		}
		return $manifest;
	}

	/** @return array<string, mixed> */
	private function manifest_failure( string $message ): array {
		return $this->build_manifest( array(), array(), true ) + array( 'message' => $message );
	}
}
