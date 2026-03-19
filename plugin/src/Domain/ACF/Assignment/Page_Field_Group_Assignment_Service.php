<?php
/**
 * Page-to-field-group assignment service (spec §20.10, §20.11, §20.12).
 * Derives and applies page-specific ACF field-group visibility. Uses assignment map for persistence.
 * Use Field_Assignment_Compatibility_Service for compatibility notes and retention advice.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Assignment;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Pages\Visible_Group_Key_Query_Result;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Types;

/**
 * Operationalizes acf-page-visibility-contract. Stores normalized assignments.
 * Callers must perform capability checks before mutating.
 */
final class Page_Field_Group_Assignment_Service implements Page_Field_Group_Assignment_Service_Interface {

	/** @var Assignment_Map_Service */
	private Assignment_Map_Service $assignment_map;

	/** @var Field_Group_Derivation_Service */
	private Field_Group_Derivation_Service $derivation_service;

	public function __construct(
		Assignment_Map_Service $assignment_map,
		Field_Group_Derivation_Service $derivation_service
	) {
		$this->assignment_map     = $assignment_map;
		$this->derivation_service = $derivation_service;
	}

	/**
	 * Assigns field groups from page template. Replaces previous assignment when full_replace.
	 *
	 * @param int    $page_id     WordPress post ID (page).
	 * @param string $template_key Page template internal_key.
	 * @param bool   $full_replace If true, replace all. If false, refine (add new, keep existing).
	 * @return array{assigned: int, errors: array<int, string>}
	 */
	public function assign_from_template( int $page_id, string $template_key, bool $full_replace = true ): array {
		$page_ref = (string) $page_id;
		$derived  = $this->derivation_service->derive_from_template( $template_key, true );

		if ( $full_replace ) {
			$this->clear_page_field_groups( $page_ref );
			$this->assignment_map->delete_by_source_and_type( Assignment_Types::PAGE_COMPOSITION, $page_ref );
			$this->assignment_map->delete_by_source_and_type( Assignment_Types::PAGE_TEMPLATE, $page_ref );
			$this->assignment_map->create( Assignment_Types::PAGE_TEMPLATE, $page_ref, $template_key );
		} else {
			$existing = $this->get_visible_groups_for_page( $page_id );
			$derived  = $this->derivation_service->merge_for_refinement( $derived, $existing );
		}

		$assigned = $this->persist_field_groups( $page_ref, $derived );
		do_action( 'aio_acf_assignment_changed', $page_id );
		return array(
			'assigned' => $assigned,
			'errors'   => array(),
		);
	}

	/**
	 * Assigns field groups from composition. Replaces previous assignment when full_replace.
	 *
	 * @param int    $page_id        WordPress post ID (page).
	 * @param string $composition_id Composition id.
	 * @param bool   $full_replace   If true, replace all. If false, refine.
	 * @return array{assigned: int, errors: array<int, string>}
	 */
	public function assign_from_composition( int $page_id, string $composition_id, bool $full_replace = true ): array {
		$page_ref = (string) $page_id;
		$derived  = $this->derivation_service->derive_from_composition( $composition_id, true );

		if ( $full_replace ) {
			$this->clear_page_field_groups( $page_ref );
			$this->assignment_map->delete_by_source_and_type( Assignment_Types::PAGE_TEMPLATE, $page_ref );
			$this->assignment_map->delete_by_source_and_type( Assignment_Types::PAGE_COMPOSITION, $page_ref );
			$this->assignment_map->create( Assignment_Types::PAGE_COMPOSITION, $page_ref, $composition_id );
		} else {
			$existing = $this->get_visible_groups_for_page( $page_id );
			$derived  = $this->derivation_service->merge_for_refinement( $derived, $existing );
		}

		$assigned = $this->persist_field_groups( $page_ref, $derived );
		do_action( 'aio_acf_assignment_changed', $page_id );
		return array(
			'assigned' => $assigned,
			'errors'   => array(),
		);
	}

	/**
	 * Returns visible field group keys for a page (from stored assignments).
	 * Uses optimized read path (target_ref only) per Prompt 295.
	 *
	 * @param int $page_id
	 * @return array<int, string>
	 */
	public function get_visible_groups_for_page( int $page_id ): array {
		return $this->get_visible_groups_result_for_page( $page_id )->get_group_keys();
	}

	/**
	 * Returns visible group keys for a page as a typed result (read-path optimization; Prompt 295).
	 *
	 * @param int $page_id
	 * @return Visible_Group_Key_Query_Result
	 */
	public function get_visible_groups_result_for_page( int $page_id ): Visible_Group_Key_Query_Result {
		$page_ref = (string) $page_id;
		if ( $page_id <= 0 ) {
			return new Visible_Group_Key_Query_Result( array() );
		}
		$target_refs = $this->assignment_map->list_target_refs_by_source( Assignment_Types::PAGE_FIELD_GROUP, $page_ref, 500 );
		return new Visible_Group_Key_Query_Result( $target_refs );
	}

	/**
	 * Re-assigns when page's structural source changes. Derives from current source.
	 *
	 * @param int $page_id
	 * @return array{assigned: int, errors: array<int, string>}
	 */
	public function reassign_from_stored_source( int $page_id ): array {
		$page_ref = (string) $page_id;
		$template = $this->assignment_map->get_target_for_source( Assignment_Types::PAGE_TEMPLATE, $page_ref );
		if ( $template !== null ) {
			return $this->assign_from_template( $page_id, $template, true );
		}
		$composition = $this->assignment_map->get_target_for_source( Assignment_Types::PAGE_COMPOSITION, $page_ref );
		if ( $composition !== null ) {
			return $this->assign_from_composition( $page_id, $composition, true );
		}
		return array(
			'assigned' => 0,
			'errors'   => array( 'No structural source found for page' ),
		);
	}

	/**
	 * Syncs field groups for a page using refinement (add new, keep existing).
	 * Use when template/composition was updated and pages should gain new sections without losing old ones.
	 *
	 * @param int $page_id
	 * @return array{assigned: int, errors: array<int, string>}
	 */
	public function sync_with_refinement( int $page_id ): array {
		$page_ref    = (string) $page_id;
		$template    = $this->assignment_map->get_target_for_source( Assignment_Types::PAGE_TEMPLATE, $page_ref );
		$composition = $this->assignment_map->get_target_for_source( Assignment_Types::PAGE_COMPOSITION, $page_ref );

		if ( $composition !== null ) {
			return $this->assign_from_composition( $page_id, $composition, false );
		}
		if ( $template !== null ) {
			return $this->assign_from_template( $page_id, $template, false );
		}
		return array(
			'assigned' => 0,
			'errors'   => array( 'No structural source found for page' ),
		);
	}

	/**
	 * Returns the structural source for a page (template key or composition id), or null.
	 *
	 * @param int $page_id
	 * @return array{type: 'page_template'|'page_composition', key: string}|null
	 */
	public function get_structural_source_for_page( int $page_id ): ?array {
		$page_ref = (string) $page_id;
		$template = $this->assignment_map->get_target_for_source( Assignment_Types::PAGE_TEMPLATE, $page_ref );
		if ( $template !== null ) {
			return array(
				'type' => 'page_template',
				'key'  => $template,
			);
		}
		$composition = $this->assignment_map->get_target_for_source( Assignment_Types::PAGE_COMPOSITION, $page_ref );
		if ( $composition !== null ) {
			return array(
				'type' => 'page_composition',
				'key'  => $composition,
			);
		}
		return null;
	}

	/**
	 * Clears all page_field_group rows for a page.
	 *
	 * @param string $page_ref
	 */
	private function clear_page_field_groups( string $page_ref ): void {
		$this->assignment_map->delete_by_source_and_type( Assignment_Types::PAGE_FIELD_GROUP, $page_ref );
	}

	/**
	 * Persists page_field_group rows for each group key.
	 *
	 * @param string             $page_ref
	 * @param array<int, string> $group_keys
	 * @return int Number of groups persisted.
	 */
	private function persist_field_groups( string $page_ref, array $group_keys ): int {
		$count = 0;
		foreach ( $group_keys as $gk ) {
			if ( $gk === '' ) {
				continue;
			}
			$id = $this->assignment_map->create( Assignment_Types::PAGE_FIELD_GROUP, $page_ref, $gk );
			if ( $id > 0 ) {
				++$count;
			}
		}
		return $count;
	}
}
