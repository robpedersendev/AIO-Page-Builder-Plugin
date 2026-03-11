<?php
/**
 * Derives field group keys from page templates and compositions (spec §20.10, §20.11).
 * Maps section keys to group_aio_{section_key}. Respects deprecated section rules.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Assignment;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Shared\Deprecation_Metadata;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Derives visible field group keys from structural source (template or composition).
 * For new assignments: excludes deprecated sections. For refinement: keeps existing deprecated.
 */
final class Field_Group_Derivation_Service {

	/** @var Page_Template_Repository */
	private Page_Template_Repository $page_template_repository;

	/** @var Composition_Repository */
	private Composition_Repository $composition_repository;

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repository;

	public function __construct(
		Page_Template_Repository $page_template_repository,
		Composition_Repository $composition_repository,
		Section_Template_Repository $section_repository
	) {
		$this->page_template_repository = $page_template_repository;
		$this->composition_repository  = $composition_repository;
		$this->section_repository      = $section_repository;
	}

	/**
	 * Derives field group keys from page template.
	 *
	 * @param string $template_key Page template internal_key.
	 * @param bool   $for_new_page If true, exclude deprecated sections. If false, include all.
	 * @return list<string> Group keys (group_aio_*).
	 */
	public function derive_from_template( string $template_key, bool $for_new_page = true ): array {
		$definition = $this->page_template_repository->get_definition_by_key( $template_key );
		if ( $definition === null ) {
			return array();
		}
		$ordered = $definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		return $this->section_keys_to_groups( $this->extract_section_keys( $ordered ), $for_new_page );
	}

	/**
	 * Derives field group keys from composition.
	 *
	 * @param string $composition_id Composition id.
	 * @param bool   $for_new_page   If true, exclude deprecated sections.
	 * @return list<string>
	 */
	public function derive_from_composition( string $composition_id, bool $for_new_page = true ): array {
		$definition = $this->composition_repository->get_definition_by_key( $composition_id );
		if ( $definition === null ) {
			return array();
		}
		$ordered = $definition[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? array();
		return $this->section_keys_to_groups( $this->extract_section_keys_from_composition( $ordered ), $for_new_page );
	}

	/**
	 * Merges derived groups with existing groups for refinement (preserves deprecated on existing pages).
	 *
	 * @param list<string> $derived  Newly derived group keys.
	 * @param list<string> $existing Existing page assignment group keys.
	 * @return list<string> Union: derived + existing (deprecated sections kept).
	 */
	public function merge_for_refinement( array $derived, array $existing ): array {
		$union = array_flip( $derived );
		foreach ( $existing as $g ) {
			$union[ $g ] = true;
		}
		return array_values( array_keys( $union ) );
	}

	/**
	 * Extracts section keys from page template ordered_sections.
	 *
	 * @param list<array<string, mixed>> $ordered
	 * @return list<string>
	 */
	private function extract_section_keys( array $ordered ): array {
		$keys = array();
		foreach ( $ordered as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$sk = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			if ( $sk !== '' ) {
				$keys[] = $sk;
			}
		}
		return array_values( array_unique( $keys ) );
	}

	/**
	 * Extracts section keys from composition ordered_section_list.
	 *
	 * @param list<array<string, mixed>> $ordered
	 * @return list<string>
	 */
	private function extract_section_keys_from_composition( array $ordered ): array {
		$keys = array();
		foreach ( $ordered as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$sk = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
			if ( $sk !== '' ) {
				$keys[] = $sk;
			}
		}
		return array_values( array_unique( $keys ) );
	}

	/**
	 * Maps section keys to group keys, optionally excluding deprecated.
	 *
	 * @param list<string> $section_keys
	 * @param bool         $exclude_deprecated
	 * @return list<string>
	 */
	private function section_keys_to_groups( array $section_keys, bool $exclude_deprecated ): array {
		$groups = array();
		foreach ( $section_keys as $sk ) {
			if ( $exclude_deprecated ) {
				$def = $this->section_repository->get_definition_by_key( $sk );
				if ( $def !== null && ! Deprecation_Metadata::is_eligible_for_new_use( $def ) ) {
					continue;
				}
			}
			$groups[] = Field_Key_Generator::group_key( $sk );
		}
		return array_values( array_unique( $groups ) );
	}
}
