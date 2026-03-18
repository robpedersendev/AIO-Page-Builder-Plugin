<?php
/**
 * Seeds the hero/intro library batch (SEC-01) into the section template registry (spec §12, §55.8, Prompt 147).
 * Idempotent: re-running overwrites existing definitions for the same internal keys.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\HeroBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * One-way seed: writes hero/intro batch section definitions to the section repository.
 */
final class Hero_Intro_Library_Batch_Seeder {

	/**
	 * Seeds all hero/intro batch section definitions.
	 *
	 * @param Section_Template_Repository $section_repo
	 * @return array{ success: bool, section_ids: list<int>, errors: list<string> }
	 */
	public static function run( Section_Template_Repository $section_repo ): array {
		$errors      = array();
		$section_ids = array();
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $definition ) {
			$id = $section_repo->save_definition( $definition );
			if ( $id <= 0 ) {
				$key      = (string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? 'unknown' );
				$errors[] = sprintf( __( 'Failed to save hero section: %s', 'aio-page-builder' ), $key );
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
