<?php
/**
 * Seeds the curated page template and composition expansion pack (spec §13, §14, §16, Prompt 123).
 * Idempotent: re-running overwrites existing definitions for the same keys.
 * Run after section expansion pack (and form templates) so section keys exist.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\ExpansionPack;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;

/**
 * One-way seed: writes expansion-pack page templates and compositions to their repositories.
 */
final class Page_Template_And_Composition_Expansion_Pack_Seeder {

	/**
	 * Seeds all expansion-pack page templates and compositions.
	 *
	 * @param Page_Template_Repository $page_repo
	 * @param Composition_Repository   $composition_repo
	 * @return array{ success: bool, page_template_ids: array<int, int>, composition_ids: array<int, int>, errors: array<int, string> }
	 */
	public static function run(
		Page_Template_Repository $page_repo,
		Composition_Repository $composition_repo
	): array {
		$errors            = array();
		$page_template_ids = array();
		$composition_ids   = array();

		foreach ( Page_Template_And_Composition_Expansion_Pack_Definitions::page_template_definitions() as $definition ) {
			$id = $page_repo->save_definition( $definition );
			if ( $id <= 0 ) {
				$key      = (string) ( $definition[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? 'unknown' );
				/* translators: %s: internal registry key. */
				$errors[] = sprintf( __( 'Failed to save page template: %s', 'aio-page-builder' ), $key );
				continue;
			}
			$page_template_ids[] = $id;
		}

		foreach ( Page_Template_And_Composition_Expansion_Pack_Definitions::composition_definitions() as $definition ) {
			$id = $composition_repo->save_definition( $definition );
			if ( $id <= 0 ) {
				$comp_id  = (string) ( $definition[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? 'unknown' );
				/* translators: %s: internal registry key. */
				$errors[] = sprintf( __( 'Failed to save composition: %s', 'aio-page-builder' ), $comp_id );
				continue;
			}
			$composition_ids[] = $id;
		}

		return array(
			'success'           => empty( $errors ),
			'page_template_ids' => $page_template_ids,
			'composition_ids'   => $composition_ids,
			'errors'            => $errors,
		);
	}
}
