<?php
/**
 * Seeds the form section and request page template into section/page template registries.
 * Used on plugin install (activation) and via admin "Seed form templates" button.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\FormProvider;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * One-way seed: writes form_section_ndr and pt_request_form definitions to the repositories.
 * Idempotent: re-running overwrites existing definitions for the same internal keys.
 */
final class Form_Template_Seeder {

	/**
	 * Seeds form section and request page template. Call with repository instances (e.g. from container).
	 *
	 * @param Section_Template_Repository $section_repo
	 * @param Page_Template_Repository    $page_repo
	 * @return array{ success: bool, section_id: int, page_id: int, errors: list<string> }
	 */
	public static function run(
		Section_Template_Repository $section_repo,
		Page_Template_Repository $page_repo
	): array {
		$errors = array();
		$section_id = 0;
		$page_id    = 0;

		$section_def = Form_Integration_Definitions::form_section_definition();
		$section_id  = $section_repo->save_definition( $section_def );
		if ( $section_id <= 0 ) {
			$errors[] = __( 'Failed to save form section template.', 'aio-page-builder' );
		}

		$page_def = Form_Integration_Definitions::request_page_template_definition();
		$page_id  = $page_repo->save_definition( $page_def );
		if ( $page_id <= 0 ) {
			$errors[] = __( 'Failed to save request page template.', 'aio-page-builder' );
		}

		return array(
			'success'    => empty( $errors ),
			'section_id' => $section_id,
			'page_id'    => $page_id,
			'errors'     => $errors,
		);
	}
}
