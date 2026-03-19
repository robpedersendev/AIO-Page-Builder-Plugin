<?php
/**
 * Compatibility helpers for ACF assignment refreshes (spec §20.12, §20.15).
 * Preserves stored content relationships where possible. No destructive actions.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Compatibility;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;
use AIOPageBuilder\Domain\Registries\Shared\Deprecation_Metadata;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Provides compatibility-preserving helpers for assignment refinements.
 * Callers must perform capability checks before any mutation.
 */
final class Field_Assignment_Compatibility_Service {

	/** @var Page_Field_Group_Assignment_Service */
	private Page_Field_Group_Assignment_Service $assignment_service;

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repository;

	public function __construct(
		Page_Field_Group_Assignment_Service $assignment_service,
		Section_Template_Repository $section_repository
	) {
		$this->assignment_service = $assignment_service;
		$this->section_repository = $section_repository;
	}

	/**
	 * Returns group keys to retain during refinement (derived + deprecated that are currently assigned).
	 *
	 * @param int          $page_id       WordPress post ID (page).
	 * @param array<int, string> $derived_groups Newly derived group keys from template/composition.
	 * @return array<int, string> Union of derived and assigned deprecated groups to preserve.
	 */
	public function get_groups_to_retain_during_refinement( int $page_id, array $derived_groups ): array {
		$existing = $this->assignment_service->get_visible_groups_for_page( $page_id );
		$retain   = array_flip( $derived_groups );
		foreach ( $existing as $gk ) {
			$retain[ $gk ] = true;
		}
		// Deprecated groups in existing are always retained (spec §20.15).
		return array_values( array_keys( $retain ) );
	}

	/**
	 * Returns whether removing an assignment might risk data loss. Conservative: always true when assigned.
	 *
	 * @param string $group_key ACF field group key (e.g. group_aio_st01_hero).
	 * @param int    $page_id   WordPress post ID.
	 * @return bool True if removal could risk content; assume retention when uncertain.
	 */
	public function would_break_content( string $group_key, int $page_id ): bool {
		$assigned = $this->assignment_service->get_visible_groups_for_page( $page_id );
		if ( ! in_array( $group_key, $assigned, true ) ) {
			return false;
		}
		// Assigned groups may have stored meta; assume removal risks data.
		return true;
	}

	/**
	 * Returns whether a group corresponds to a deprecated section.
	 *
	 * @param string $group_key
	 * @return bool
	 */
	public function is_deprecated_group( string $group_key ): bool {
		$section_key = $this->group_key_to_section_key( $group_key );
		if ( $section_key === '' ) {
			return false;
		}
		$def = $this->section_repository->get_definition_by_key( $section_key );
		return $def !== null && ! Deprecation_Metadata::is_eligible_for_new_use( $def );
	}

	/**
	 * Returns compatibility notes for a page's refinement (for diagnostics/audit).
	 *
	 * @param int $page_id
	 * @return array<int, string>
	 */
	public function get_compatibility_notes_for_page( int $page_id ): array {
		$notes            = array();
		$source           = $this->assignment_service->get_structural_source_for_page( $page_id );
		$groups           = $this->assignment_service->get_visible_groups_for_page( $page_id );
		$deprecated_count = 0;
		foreach ( $groups as $gk ) {
			if ( $this->is_deprecated_group( $gk ) ) {
				++$deprecated_count;
			}
		}
		if ( $deprecated_count > 0 ) {
			$notes[] = sprintf(
				'Page has %d deprecated group assignment(s); retained for existing content.',
				$deprecated_count
			);
		}
		if ( $source === null && ! empty( $groups ) ) {
			$notes[] = 'Page has group assignments but no structural source; consider manual review.';
		}
		return $notes;
	}

	/**
	 * Extracts section_key from group key (group_aio_{section_key}).
	 *
	 * @param string $group_key
	 * @return string
	 */
	private function group_key_to_section_key( string $group_key ): string {
		$prefix = 'group_aio_';
		if ( ! str_starts_with( $group_key, $prefix ) ) {
			return '';
		}
		return substr( $group_key, strlen( $prefix ) );
	}
}
