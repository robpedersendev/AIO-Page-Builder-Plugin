<?php
/**
 * Seeds the gap-closing page template super-batch (PT-14) into the page template registry (Prompt 183, spec §13, §62.12).
 * Idempotent: re-running overwrites existing definitions for the same internal keys.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\GapClosingSuperBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;

/**
 * One-way seed: writes gap-closing batch page template definitions to the page template repository.
 */
final class Page_Template_Gap_Closing_Super_Batch_Seeder {

	/**
	 * Seeds all gap-closing batch page template definitions.
	 *
	 * @param Page_Template_Repository $page_repo
	 * @return array{ success: bool, page_template_ids: list<int>, errors: list<string>, template_keys: list<string> }
	 */
	public static function run( Page_Template_Repository $page_repo ): array {
		$errors        = array();
		$ids           = array();
		$template_keys = array();
		foreach ( Page_Template_Gap_Closing_Super_Batch_Definitions::all_definitions() as $definition ) {
			$key             = (string) ( $definition[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$template_keys[] = $key;
			$id              = $page_repo->save_definition( $definition );
			if ( $id <= 0 ) {
				$errors[] = sprintf( __( 'Failed to save page template: %s', 'aio-page-builder' ), $key );
				continue;
			}
			$ids[] = $id;
		}
		return array(
			'success'           => empty( $errors ),
			'page_template_ids' => $ids,
			'errors'            => $errors,
			'template_keys'     => $template_keys,
		);
	}
}
