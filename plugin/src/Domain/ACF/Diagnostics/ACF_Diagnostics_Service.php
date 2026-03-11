<?php
/**
 * ACF diagnostics service (spec §20, §45, §56, §59.5).
 * Summarizes blueprint health, registration, assignment, and compatibility state. Read-only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Diagnostics;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use AIOPageBuilder\Domain\ACF\Compatibility\Field_Cleanup_Advisor;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Registrar;
use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Types;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Support\Logging\Error_Record;
use AIOPageBuilder\Support\Logging\Log_Categories;
use AIOPageBuilder\Support\Logging\Log_Severities;
use AIOPageBuilder\Support\Logging\Logger_Interface;

/**
 * Diagnostics payload keys (stable; documented for future UI).
 *
 * - blueprints: Blueprint validity summary.
 * - registered_groups: Group registration summary.
 * - page_assignments: Page-to-group assignment summary.
 * - compatibility_warnings: Compatibility and deprecation warnings.
 * - stale_items: Stale assignment entries.
 *
 * Example payload from get_full_payload():
 *
 * [
 *   'blueprints' => [
 *     'valid_count' => 2,
 *     'valid' => [
 *       ['section_key' => 'st01_hero', 'group_key' => 'group_aio_st01_hero'],
 *       ['section_key' => 'st05_faq', 'group_key' => 'group_aio_st05_faq'],
 *     ],
 *     'invalid_count' => 0,
 *     'summary' => '2 blueprint(s) with valid structure.',
 *   ],
 *   'registered_groups' => [
 *     'acf_available' => true,
 *     'expected_count' => 2,
 *     'expected_keys' => ['group_aio_st01_hero', 'group_aio_st05_faq'],
 *     'summary' => '2 group(s) available for registration.',
 *   ],
 *   'page_assignments' => [
 *     'total_assignment_rows' => 3,
 *     'pages_with_assignments' => 2,
 *     'pages_with_structural_source' => 1,
 *     'by_page' => ['1001' => ['group_aio_st01_hero', 'group_aio_st05_faq'], '1002' => ['group_aio_st01_hero']],
 *     'summary' => '2 page(s) with assignments; 1 with structural source.',
 *   ],
 *   'compatibility_warnings' => [
 *     'count' => 1,
 *     'warnings' => [['page_id' => 1003, 'type' => 'deprecated_group', 'group_key' => 'group_aio_st_old', 'reason' => 'Section deprecated.']],
 *     'summary' => '1 compatibility warning(s) detected.',
 *   ],
 *   'stale_items' => [
 *     'count' => 1,
 *     'items' => [['page_id' => 1002, 'page_ref' => '1002', 'group_key' => 'group_aio_st_removed', 'reason' => 'Group group_aio_st_removed (section st_removed) no longer in current structural source.']],
 *     'summary' => '1 stale assignment(s); consider refinement sync.',
 *   ],
 * ]
 */
final class ACF_Diagnostics_Service {

	/** @var Section_Field_Blueprint_Service */
	private Section_Field_Blueprint_Service $blueprint_service;

	/** @var ACF_Group_Registrar */
	private ACF_Group_Registrar $group_registrar;

	/** @var Page_Field_Group_Assignment_Service */
	private Page_Field_Group_Assignment_Service $assignment_service;

	/** @var Assignment_Map_Service */
	private Assignment_Map_Service $assignment_map;

	/** @var Field_Cleanup_Advisor */
	private Field_Cleanup_Advisor $cleanup_advisor;

	/** @var Logger_Interface|null */
	private ?Logger_Interface $logger;

	public function __construct(
		Section_Field_Blueprint_Service $blueprint_service,
		ACF_Group_Registrar $group_registrar,
		Page_Field_Group_Assignment_Service $assignment_service,
		Assignment_Map_Service $assignment_map,
		Field_Cleanup_Advisor $cleanup_advisor,
		?Logger_Interface $logger = null
	) {
		$this->blueprint_service  = $blueprint_service;
		$this->group_registrar    = $group_registrar;
		$this->assignment_service = $assignment_service;
		$this->assignment_map     = $assignment_map;
		$this->cleanup_advisor    = $cleanup_advisor;
		$this->logger             = $logger;
	}

	/**
	 * Returns full diagnostics payload for admin/QA use.
	 *
	 * @return array<string, mixed>
	 */
	public function get_full_payload(): array {
		return array(
			'blueprints'             => $this->get_blueprint_summary(),
			'registered_groups'      => $this->get_registered_groups_summary(),
			'page_assignments'       => $this->get_page_assignments_summary(),
			'compatibility_warnings' => $this->get_compatibility_warnings(),
			'stale_items'            => $this->get_stale_items(),
		);
	}

	/**
	 * Blueprint health summary: valid count, invalid count, sections without blueprints.
	 *
	 * @return array<string, mixed>
	 */
	public function get_blueprint_summary(): array {
		$blueprints = $this->blueprint_service->get_all_blueprints();
		$valid = array();
		$invalid = array();
		foreach ( $blueprints as $bp ) {
			$section_key = (string) ( $bp['section_key'] ?? '' );
			if ( $section_key !== '' ) {
				$valid[] = array( 'section_key' => $section_key, 'group_key' => Field_Key_Generator::group_key( $section_key ) );
			}
		}
		return array(
			'valid_count'   => count( $valid ),
			'valid'         => $valid,
			'invalid_count' => 0,
			'summary'       => sprintf( '%d blueprint(s) with valid structure.', count( $valid ) ),
		);
	}

	/**
	 * Group registration summary: ACF availability, expected group keys from blueprints.
	 *
	 * @return array<string, mixed>
	 */
	public function get_registered_groups_summary(): array {
		$acf_available = $this->group_registrar->is_acf_available();
		$blueprints = $this->blueprint_service->get_all_blueprints();
		$expected_keys = array();
		foreach ( $blueprints as $bp ) {
			$section_key = (string) ( $bp['section_key'] ?? '' );
			if ( $section_key !== '' ) {
				$expected_keys[] = Field_Key_Generator::group_key( $section_key );
			}
		}
		if ( ! $acf_available && ! empty( $expected_keys ) && $this->logger !== null ) {
			$this->logger->log( new Error_Record(
				'acf-diag-' . uniqid( '', true ),
				Log_Categories::DEPENDENCY,
				Log_Severities::WARNING,
				'ACF not available; field groups cannot be registered.',
				'',
				'',
				'acf',
				'Install or activate ACF to enable field group registration.',
				''
			) );
		}
		return array(
			'acf_available'  => $acf_available,
			'expected_count' => count( $expected_keys ),
			'expected_keys'   => $expected_keys,
			'summary'        => $acf_available
				? sprintf( '%d group(s) available for registration.', count( $expected_keys ) )
				: 'ACF not available; registration skipped.',
		);
	}

	/**
	 * Page assignment summary: pages with structural source, total assignment rows.
	 *
	 * @return array<string, mixed>
	 */
	public function get_page_assignments_summary(): array {
		$rows = $this->assignment_map->list_by_type( Assignment_Types::PAGE_FIELD_GROUP, 500, 0 );
		$page_ids = array();
		$by_page = array();
		foreach ( $rows as $row ) {
			$src = (string) ( $row['source_ref'] ?? '' );
			$tgt = (string) ( $row['target_ref'] ?? '' );
			if ( $src !== '' && $tgt !== '' ) {
				$page_ids[ $src ] = true;
				if ( ! isset( $by_page[ $src ] ) ) {
					$by_page[ $src ] = array();
				}
				$by_page[ $src ][] = $tgt;
			}
		}
		$pages_with_source = 0;
		foreach ( array_keys( $page_ids ) as $page_ref ) {
			if ( ctype_digit( $page_ref ) ) {
				$source = $this->assignment_service->get_structural_source_for_page( (int) $page_ref );
				if ( $source !== null ) {
					$pages_with_source++;
				}
			}
		}
		return array(
			'total_assignment_rows' => count( $rows ),
			'pages_with_assignments' => count( $page_ids ),
			'pages_with_structural_source' => $pages_with_source,
			'by_page' => array_map( 'array_values', $by_page ),
			'summary' => sprintf( '%d page(s) with assignments; %d with structural source.', count( $page_ids ), $pages_with_source ),
		);
	}

	/**
	 * Compatibility warnings from cleanup advisor (deprecated groups, orphaned assignments).
	 *
	 * @return array<string, mixed>
	 */
	public function get_compatibility_warnings(): array {
		$warnings = array();
		$results = $this->cleanup_advisor->analyze_pages_with_assignments( 50 );
		foreach ( $results as $page_id => $result ) {
			$notes = $result->compatibility_notes;
			if ( ! empty( $notes ) ) {
				$warnings[] = array(
					'page_id' => $page_id,
					'messages' => $notes,
				);
			}
			if ( ! empty( $result->deprecated_groups ) ) {
				foreach ( $result->deprecated_groups as $dg ) {
					$warnings[] = array(
						'page_id'   => $page_id,
						'type'      => 'deprecated_group',
						'group_key' => $dg['group_key'],
						'reason'    => $dg['reason'],
					);
				}
			}
		}
		return array(
			'count'    => count( $warnings ),
			'warnings' => $warnings,
			'summary'  => count( $warnings ) > 0
				? sprintf( '%d compatibility warning(s) detected.', count( $warnings ) )
				: 'No compatibility warnings.',
		);
	}

	/**
	 * Stale assignment items from cleanup advisor.
	 *
	 * @return array<string, mixed>
	 */
	public function get_stale_items(): array {
		$stale = array();
		$results = $this->cleanup_advisor->analyze_pages_with_assignments( 50 );
		foreach ( $results as $page_id => $result ) {
			foreach ( $result->stale_assignments as $sa ) {
				$stale[] = array(
					'page_id'   => $page_id,
					'page_ref'  => $sa['page_ref'],
					'group_key' => $sa['group_key'],
					'reason'    => $sa['reason'],
				);
			}
		}
		return array(
			'count'  => count( $stale ),
			'items'  => $stale,
			'summary' => count( $stale ) > 0
				? sprintf( '%d stale assignment(s); consider refinement sync.', count( $stale ) )
				: 'No stale assignments.',
		);
	}
}
