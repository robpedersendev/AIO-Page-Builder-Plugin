<?php
/**
 * Seeds the media/listing/profile/detail library batch (SEC-06) into the section template registry (spec §12, Prompt 151).
 * Idempotent: re-running overwrites existing definitions for the same internal keys.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\MediaListingProfileBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * One-way seed: writes media/listing/profile/detail batch section definitions to the section repository.
 */
final class Media_Listing_Profile_Detail_Library_Batch_Seeder {

	/**
	 * Seeds all media/listing/profile/detail batch section definitions.
	 *
	 * @param Section_Template_Repository $section_repo
	 * @return array{ success: bool, section_ids: array<int, int>, errors: array<int, string> }
	 */
	public static function run( Section_Template_Repository $section_repo ): array {
		$errors      = array();
		$section_ids = array();
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $definition ) {
			$id = $section_repo->save_definition( $definition );
			if ( $id <= 0 ) {
				$key = (string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? 'unknown' );
				/* translators: %s: internal registry key. */
				$errors[] = sprintf( __( 'Failed to save media/listing/profile section: %s', 'aio-page-builder' ), $key );
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
