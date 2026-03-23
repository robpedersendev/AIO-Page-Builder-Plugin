<?php
/**
 * Seeds the curated section expansion pack into the section template registry (spec §12, Prompt 122).
 * Idempotent: re-running overwrites existing definitions for the same internal keys.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\ExpansionPack;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * One-way seed: writes expansion-pack section definitions to the section repository.
 */
final class Section_Expansion_Pack_Seeder {

	/**
	 * Seeds all expansion-pack section definitions.
	 *
	 * @param Section_Template_Repository $section_repo
	 * @return array{ success: bool, section_ids: array<int, int>, errors: array<int, string> }
	 */
	public static function run( Section_Template_Repository $section_repo ): array {
		$errors      = array();
		$section_ids = array();
		foreach ( Section_Expansion_Pack_Definitions::all_definitions() as $definition ) {
			$id = $section_repo->save_definition( $definition );
			if ( $id <= 0 ) {
				$key = (string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? 'unknown' );
				/* translators: %s: internal registry key. */
				$errors[] = sprintf( __( 'Failed to save section: %s', 'aio-page-builder' ), $key );
				continue;
			}
			$section_ids[] = $id;
		}
		return array(
			'success'     => empty( $errors ),
			'section_ids' => $section_ids,
			'errors'      => $errors,
		);
	}
}
