<?php
/**
 * Seeds the child/detail page template batch (spec §13, §14.3, §16, §17.7, Prompt 160).
 * Run after section library and nested hub batch so section keys exist.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;

/**
 * One-way seed: writes child/detail page templates to the page template repository.
 */
final class Child_Detail_Page_Template_Seeder {

	/**
	 * Seeds all child/detail page template definitions.
	 *
	 * @param Page_Template_Repository $page_repo
	 * @return array{ success: bool, page_template_ids: list<int>, errors: list<string> }
	 */
	public static function run( Page_Template_Repository $page_repo ): array {
		$errors = array();
		$ids    = array();

		foreach ( Child_Detail_Page_Template_Definitions::all_definitions() as $definition ) {
			$id = $page_repo->save_definition( $definition );
			if ( $id <= 0 ) {
				$key      = (string) ( $definition[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? 'unknown' );
				$errors[] = sprintf( __( 'Failed to save page template: %s', 'aio-page-builder' ), $key );
				continue;
			}
			$ids[] = $id;
		}

		return array(
			'success'           => empty( $errors ),
			'page_template_ids' => $ids,
			'errors'            => $errors,
		);
	}
}
