<?php
/**
 * Seeds the gap-closing section super-batch (SEC-09) into the section template registry (Prompt 182, spec §12, §62.11).
 * Idempotent: re-running overwrites existing definitions for the same internal keys.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\GapClosingSuperBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * One-way seed: writes gap-closing batch section definitions to the section repository.
 */
final class Section_Gap_Closing_Super_Batch_Seeder {

	/**
	 * Seeds all gap-closing batch section definitions.
	 *
	 * @param Section_Template_Repository $section_repo
	 * @return array{ success: bool, section_ids: array<int, int>, errors: array<int, string>, section_keys: array<int, string> }
	 */
	public static function run( Section_Template_Repository $section_repo ): array {
		$errors       = array();
		$section_ids  = array();
		$section_keys = array();
		foreach ( Section_Gap_Closing_Super_Batch_Definitions::all_definitions() as $definition ) {
			$key            = (string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$section_keys[] = $key;
			$id             = $section_repo->save_definition( $definition );
			if ( $id <= 0 ) {
				$errors[] = sprintf( __( 'Failed to save gap-closing section: %s', 'aio-page-builder' ), $key );
				continue;
			}
			$section_ids[] = $id;
		}
		return array(
			'success'      => empty( $errors ),
			'section_ids'  => $section_ids,
			'errors'       => $errors,
			'section_keys' => $section_keys,
		);
	}
}
