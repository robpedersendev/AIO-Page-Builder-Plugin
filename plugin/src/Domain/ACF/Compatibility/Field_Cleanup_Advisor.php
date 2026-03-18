<?php
/**
 * Advisory service for ACF field/group cleanup (spec §20.15, §58.4, §58.5).
 * Detection and recommendations only; never performs destructive operations.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Compatibility;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Assignment\Field_Group_Derivation_Service;
use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;
use AIOPageBuilder\Domain\Registries\Shared\Deprecation_Metadata;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Types;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Analyzes assignments and returns structured cleanup advice.
 * Refuses destructive cleanup; returns sanitized reasons when unsafe.
 */
final class Field_Cleanup_Advisor {

	/** @var Page_Field_Group_Assignment_Service */
	private Page_Field_Group_Assignment_Service $assignment_service;

	/** @var Field_Group_Derivation_Service */
	private Field_Group_Derivation_Service $derivation_service;

	/** @var Assignment_Map_Service */
	private Assignment_Map_Service $assignment_map;

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repository;

	private const GROUP_PREFIX = 'group_aio_';

	public function __construct(
		Page_Field_Group_Assignment_Service $assignment_service,
		Field_Group_Derivation_Service $derivation_service,
		Assignment_Map_Service $assignment_map,
		Section_Template_Repository $section_repository
	) {
		$this->assignment_service = $assignment_service;
		$this->derivation_service = $derivation_service;
		$this->assignment_map     = $assignment_map;
		$this->section_repository = $section_repository;
	}

	/**
	 * Analyzes a page's assignments and returns structured cleanup result.
	 *
	 * @param int $page_id WordPress post ID (page).
	 * @return Cleanup_Result
	 */
	public function analyze_page( int $page_id ): Cleanup_Result {
		$result   = new Cleanup_Result();
		$page_ref = (string) $page_id;
		$source   = $this->assignment_service->get_structural_source_for_page( $page_id );

		$assigned_groups = $this->assignment_service->get_visible_groups_for_page( $page_id );
		$derived_groups  = array();
		if ( $source !== null ) {
			if ( $source['type'] === 'page_template' ) {
				$derived_groups = $this->derivation_service->derive_from_template( $source['key'], false );
			} else {
				$derived_groups = $this->derivation_service->derive_from_composition( $source['key'], false );
			}
		}

		// Stale: assigned but no longer in current source derivation.
		$derived_set = array_flip( $derived_groups );
		foreach ( $assigned_groups as $gk ) {
			if ( ! isset( $derived_set[ $gk ] ) ) {
				$section_key                 = $this->group_key_to_section_key( $gk );
				$reason                      = $section_key !== ''
					? sprintf( 'Group %s (section %s) no longer in current structural source.', $gk, $section_key )
					: sprintf( 'Group %s no longer in current structural source.', $gk );
				$result->stale_assignments[] = array(
					'page_ref'  => $page_ref,
					'group_key' => $gk,
					'reason'    => $reason,
				);
			}
		}

		// Deprecated groups: section is deprecated.
		foreach ( $assigned_groups as $gk ) {
			$section_key = $this->group_key_to_section_key( $gk );
			if ( $section_key === '' ) {
				continue;
			}
			$def = $this->section_repository->get_definition_by_key( $section_key );
			if ( $def !== null && ! Deprecation_Metadata::is_eligible_for_new_use( $def ) ) {
				$dep                              = $def['deprecation'] ?? array();
				$reason                           = (string) ( $dep['deprecated_reason'] ?? $dep['reason'] ?? 'Section deprecated.' );
				$result->deprecated_groups[]      = array(
					'group_key'   => $gk,
					'section_key' => $section_key,
					'reason'      => $reason,
				);
				$result->requires_manual_review[] = array(
					'group_key' => $gk,
					'reason'    => sprintf( 'Deprecated section %s; retain for existing content unless migrated.', $section_key ),
				);
			}
		}

		// safe_to_remove: conservative; without content scan we assume none are safe.
		$result->safe_to_remove = array();

		// Deduplicate requires_manual_review by group_key.
		$seen                           = array();
		$result->requires_manual_review = array_values(
			array_filter(
				$result->requires_manual_review,
				function ( $r ) use ( &$seen ) {
					$k = $r['group_key'];
					if ( isset( $seen[ $k ] ) ) {
						return false;
					}
					$seen[ $k ] = true;
					return true;
				}
			)
		);

		// Compatibility notes.
		if ( ! empty( $result->stale_assignments ) ) {
			$result->compatibility_notes[] = sprintf(
				'Page has %d stale assignment(s); use refinement sync to add new groups without removing existing.',
				count( $result->stale_assignments )
			);
		}
		if ( ! empty( $result->deprecated_groups ) ) {
			$result->compatibility_notes[] = 'Deprecated groups are retained for existing content relationships.';
		}
		if ( $source === null && ! empty( $assigned_groups ) ) {
			$result->compatibility_notes[] = 'No structural source; assignments may be orphaned.';
		}

		return $result;
	}

	/**
	 * Returns whether destructive cleanup is allowed. Always false; returns refusal reasons.
	 *
	 * @param int $page_id
	 * @return array{allowed: bool, reasons: list<string>}
	 */
	public function recommend_destructive_cleanup( int $page_id ): array {
		$result  = $this->analyze_page( $page_id );
		$reasons = array();

		if ( ! empty( $result->deprecated_groups ) ) {
			$reasons[] = 'Deprecated groups assigned; removal may orphan content.';
		}
		if ( ! empty( $result->stale_assignments ) ) {
			$reasons[] = 'Stale assignments may reference stored content.';
		}
		$reasons[] = 'Destructive cleanup requires explicit migration workflow.';

		return array(
			'allowed' => false,
			'reasons' => $reasons,
		);
	}

	/**
	 * Analyzes all pages with PAGE_FIELD_GROUP assignments (bounded scan for diagnostics).
	 *
	 * @param int $limit Max pages to analyze.
	 * @return array<int, Cleanup_Result> Page ID => result.
	 */
	public function analyze_pages_with_assignments( int $limit = 100 ): array {
		$rows     = $this->assignment_map->list_by_type( Assignment_Types::PAGE_FIELD_GROUP, $limit, 0 );
		$page_ids = array();
		foreach ( $rows as $row ) {
			$src = (string) ( $row['source_ref'] ?? '' );
			if ( $src !== '' && ctype_digit( $src ) ) {
				$page_ids[ (int) $src ] = true;
			}
		}
		$out = array();
		foreach ( array_keys( $page_ids ) as $pid ) {
			$out[ $pid ] = $this->analyze_page( $pid );
		}
		return $out;
	}

	private function group_key_to_section_key( string $group_key ): string {
		if ( ! str_starts_with( $group_key, self::GROUP_PREFIX ) ) {
			return '';
		}
		return substr( $group_key, strlen( self::GROUP_PREFIX ) );
	}
}
