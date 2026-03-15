<?php
/**
 * Read-only inventory of plugin-related ACF groups, field definitions, and value storage (acf-uninstall-inventory-contract).
 * Classifies runtime-only groups, field keys/names, value meta keys, and safe-to-remove artifacts for handoff/uninstall.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Uninstall;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository_Interface;

/**
 * Builds ACF_Uninstall_Inventory_Result from section registry and blueprints.
 * Read-only; does not mutate field groups, fields, or post meta.
 */
final class ACF_Uninstall_Inventory_Service {

	/** Transient prefixes used by plugin for section-key cache (safe to remove on uninstall). */
	private const CLEANUP_TRANSIENT_PREFIXES = array(
		'aio_acf_sk_p_',
		'aio_acf_sk_t_',
		'aio_acf_sk_c_',
	);

	/** @var Section_Template_Repository_Interface */
	private Section_Template_Repository_Interface $section_repository;

	/** @var Section_Field_Blueprint_Service_Interface */
	private Section_Field_Blueprint_Service_Interface $blueprint_service;

	public function __construct(
		Section_Template_Repository_Interface $section_repository,
		Section_Field_Blueprint_Service_Interface $blueprint_service
	) {
		$this->section_repository = $section_repository;
		$this->blueprint_service  = $blueprint_service;
	}

	/**
	 * Builds a deterministic inventory of plugin-owned ACF groups, fields, and value meta keys.
	 * Does not alter any stored data.
	 *
	 * @return ACF_Uninstall_Inventory_Result
	 */
	public function build_inventory(): ACF_Uninstall_Inventory_Result {
		$plugin_runtime_group_keys = array();
		$field_definitions         = array();
		$value_meta_keys           = array();

		$section_keys = $this->section_repository->get_all_internal_keys();

		foreach ( $section_keys as $section_key ) {
			$section_key = (string) $section_key;
			if ( $section_key === '' ) {
				continue;
			}

			$blueprint = $this->blueprint_service->get_blueprint_for_section( $section_key );
			if ( $blueprint === null ) {
				continue;
			}

			$group_key = Field_Key_Generator::group_key( $section_key );
			$plugin_runtime_group_keys[] = $group_key;

			$fields = $blueprint[ Field_Blueprint_Schema::FIELDS ] ?? null;
			if ( ! is_array( $fields ) ) {
				continue;
			}

			$this->collect_fields_from_blueprint( $group_key, $section_key, $fields, $field_definitions, $value_meta_keys );
		}

		$plugin_runtime_group_keys = array_values( array_unique( $plugin_runtime_group_keys ) );

		return new ACF_Uninstall_Inventory_Result(
			$plugin_runtime_group_keys,
			$field_definitions,
			$value_meta_keys,
			array(), // persistent_group_keys: plugin does not create native ACF groups today.
			self::CLEANUP_TRANSIENT_PREFIXES,
			array()  // cleanup_option_keys: document when options are added.
		);
	}

	/**
	 * Recursively collects top-level and nested field key/name for definitions and value meta keys.
	 *
	 * @param string   $group_key
	 * @param string   $section_key
	 * @param array    $fields
	 * @param array    $field_definitions By-ref accumulator.
	 * @param array    $value_meta_keys   By-ref accumulator (unique names for value storage).
	 */
	private function collect_fields_from_blueprint(
		string $group_key,
		string $section_key,
		array $fields,
		array &$field_definitions,
		array &$value_meta_keys
	): void {
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$field_key  = (string) ( $field[ Field_Blueprint_Schema::FIELD_KEY ] ?? '' );
			$field_name = (string) ( $field[ Field_Blueprint_Schema::FIELD_NAME ] ?? $field_key ?? '' );
			if ( $field_name === '' && $field_key === '' ) {
				continue;
			}
			if ( $field_key === '' ) {
				$field_key = Field_Key_Generator::field_key( $section_key, $field_name );
			}

			$field_definitions[] = array(
				'group_key'  => $group_key,
				'field_key'  => $field_key,
				'field_name' => $field_name,
			);
			$value_meta_keys[] = $field_name;

			$sub_fields = $field['sub_fields'] ?? null;
			if ( is_array( $sub_fields ) && ! empty( $sub_fields ) ) {
				$this->collect_fields_from_blueprint( $group_key, $section_key, $sub_fields, $field_definitions, $value_meta_keys );
			}
		}
	}
}
