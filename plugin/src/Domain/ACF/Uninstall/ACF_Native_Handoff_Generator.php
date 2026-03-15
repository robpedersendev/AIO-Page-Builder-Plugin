<?php
/**
 * Materializes plugin-owned runtime ACF groups into persistent/native ACF field groups (acf-native-handoff-contract).
 * Preserves field names and value compatibility; does not mutate saved post meta.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Uninstall;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder;

/**
 * Reads inventory, builds equivalent native ACF group arrays (with page location and marker),
 * and imports them via ACF so they persist after plugin uninstall. Admin-only; no public route.
 */
final class ACF_Native_Handoff_Generator {

	private const GROUP_KEY_PREFIX = 'group_aio_';

	/** @var ACF_Uninstall_Inventory_Service */
	private ACF_Uninstall_Inventory_Service $inventory_service;

	/** @var Section_Field_Blueprint_Service_Interface */
	private Section_Field_Blueprint_Service_Interface $blueprint_service;

	/** @var ACF_Group_Builder */
	private ACF_Group_Builder $group_builder;

	public function __construct(
		ACF_Uninstall_Inventory_Service $inventory_service,
		Section_Field_Blueprint_Service_Interface $blueprint_service,
		ACF_Group_Builder $group_builder
	) {
		$this->inventory_service = $inventory_service;
		$this->blueprint_service = $blueprint_service;
		$this->group_builder      = $group_builder;
	}

	/**
	 * Materializes all plugin-owned runtime groups into persistent/native ACF field groups.
	 * Uses page location so groups remain visible on page edit screens after uninstall.
	 * Skips groups that already exist in ACF storage and are not marked as our handoff.
	 *
	 * @return array{imported: int, skipped_existing: int, skipped_no_blueprint: int, errors: list<string>}
	 */
	public function generate_handoff(): array {
		$result = array(
			'imported'             => 0,
			'skipped_existing'     => 0,
			'skipped_no_blueprint' => 0,
			'errors'               => array(),
		);

		if ( ! function_exists( 'acf_import_field_group' ) || ! function_exists( 'acf_get_field_group' ) ) {
			$result['errors'][] = 'ACF import/get functions not available.';
			return $result;
		}

		$inventory = $this->inventory_service->build_inventory();
		$group_keys = $inventory->get_plugin_runtime_group_keys();

		foreach ( $group_keys as $group_key ) {
			$section_key = $this->group_key_to_section_key( $group_key );
			if ( $section_key === '' ) {
				continue;
			}

			$blueprint = $this->blueprint_service->get_blueprint_for_section( $section_key );
			if ( $blueprint === null ) {
				++$result['skipped_no_blueprint'];
				continue;
			}

			$existing = acf_get_field_group( $group_key );
			if ( is_array( $existing ) && ! ACF_Handoff_Group_Marker::is_handoff_group( $existing ) ) {
				++$result['skipped_existing'];
				continue;
			}

			$group = $this->group_builder->build_group( $blueprint );
			if ( $group === null ) {
				$result['errors'][] = sprintf( 'Failed to build group for %s.', $group_key );
				continue;
			}

			$group['location'] = ACF_Group_Builder::location_for_post_type( 'page' );
			$group = ACF_Handoff_Group_Marker::mark( $group );

			$imported = acf_import_field_group( $group );
			if ( is_array( $imported ) && ! empty( $imported['key'] ) ) {
				++$result['imported'];
			} else {
				$result['errors'][] = sprintf( 'Import failed for %s.', $group_key );
			}
		}

		return $result;
	}

	/**
	 * Extracts section key from plugin group key (group_aio_{section_key}).
	 */
	private function group_key_to_section_key( string $group_key ): string {
		if ( ! str_starts_with( $group_key, self::GROUP_KEY_PREFIX ) ) {
			return '';
		}
		return substr( $group_key, strlen( self::GROUP_KEY_PREFIX ) );
	}
}
