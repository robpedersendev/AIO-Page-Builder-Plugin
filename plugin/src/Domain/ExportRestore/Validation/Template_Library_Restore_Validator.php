<?php
/**
 * Validates template-library restore order and coherence after restore (Prompt 185, spec §52.8, §62.11, §62.12).
 * Ensures restore order (registries before compositions), registry coherence, and appendix regenerability from restored state.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Validation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Domain\Registries\Docs\Page_Template_Inventory_Appendix_Generator;
use AIOPageBuilder\Domain\Registries\Docs\Section_Inventory_Appendix_Generator;

/**
 * Validates restore result for template-library coherence and appendix regeneration.
 *
 * Example template_library_restore_summary payload:
 * [
 *   'valid' => true,
 *   'restore_order_ok' => true,
 *   'section_count' => 42,
 *   'page_template_count' => 18,
 *   'composition_count' => 5,
 *   'appendix_regenerable' => true,
 *   'errors' => [],
 *   'warnings' => [],
 *   'log_reference' => 'tlib-restore-2025-03-13T12:00:00Z',
 * ]
 */
final class Template_Library_Restore_Validator {

	/** Expected restore order: registries before compositions (spec §52.8). Styling after settings (Prompt 257). */
	private const EXPECTED_ORDER = array( 'settings', 'styling', 'profiles', 'registries', 'compositions', 'token_sets', 'plans', 'uninstall_restore_metadata' );

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repo;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $page_repo;

	/** @var Composition_Repository */
	private Composition_Repository $composition_repo;

	/** @var Section_Inventory_Appendix_Generator|null */
	private ?Section_Inventory_Appendix_Generator $section_appendix;

	/** @var Page_Template_Inventory_Appendix_Generator|null */
	private ?Page_Template_Inventory_Appendix_Generator $page_appendix;

	public function __construct(
		Section_Template_Repository $section_repo,
		Page_Template_Repository $page_repo,
		Composition_Repository $composition_repo,
		?Section_Inventory_Appendix_Generator $section_appendix = null,
		?Page_Template_Inventory_Appendix_Generator $page_appendix = null
	) {
		$this->section_repo     = $section_repo;
		$this->page_repo        = $page_repo;
		$this->composition_repo = $composition_repo;
		$this->section_appendix = $section_appendix;
		$this->page_appendix    = $page_appendix;
	}

	/**
	 * Validates restored state for template-library coherence and appendix regenerability.
	 *
	 * @param list<string>         $restored_categories Categories that were restored (from Restore_Result).
	 * @param array<string, mixed> $manifest           Decoded manifest (included_categories, etc.).
	 * @return array{valid: bool, restore_order_ok: bool, section_count: int, page_template_count: int, composition_count: int, appendix_regenerable: bool, errors: list<string>, warnings: list<string>, log_reference: string} template_library_restore_summary
	 */
	public function validate( array $restored_categories, array $manifest ): array {
		$log_ref              = 'tlib-restore-' . gmdate( 'Y-m-d\TH:i:s\Z' );
		$errors               = array();
		$warnings             = array();
		$restore_order_ok     = true;
		$appendix_regenerable = true;

		$included     = isset( $manifest['included_categories'] ) && is_array( $manifest['included_categories'] )
			? $manifest['included_categories']
			: array();
		$restored_set = array_flip( $restored_categories );

		$last_index = -1;
		foreach ( self::EXPECTED_ORDER as $idx => $cat ) {
			if ( ! in_array( $cat, $included, true ) ) {
				continue;
			}
			if ( isset( $restored_set[ $cat ] ) ) {
				if ( $idx < $last_index ) {
					$restore_order_ok = false;
					$errors[]         = 'Restore order violation: ' . $cat . ' restored after a later category.';
				}
				$last_index = $idx;
			}
		}

		$section_count       = 0;
		$page_template_count = 0;
		$composition_count   = 0;

		if ( in_array( 'registries', $restored_categories, true ) ) {
			$section_count       = count( $this->section_repo->list_all_definitions_capped( 5000 ) );
			$page_template_count = count( $this->page_repo->list_all_definitions_capped( 5000 ) );
		}
		if ( in_array( 'compositions', $restored_categories, true ) ) {
			$composition_count = count( $this->composition_repo->list_all_definitions( 5000, 0 ) );
		}

		if ( $this->section_appendix !== null && $section_count > 0 ) {
			try {
				$this->section_appendix->build_result();
			} catch ( \Throwable $e ) {
				$errors[]             = 'Section appendix regeneration failed after restore: ' . $e->getMessage();
				$appendix_regenerable = false;
			}
		}

		if ( $this->page_appendix !== null && $page_template_count > 0 ) {
			try {
				$this->page_appendix->build_result();
			} catch ( \Throwable $e ) {
				$errors[]             = 'Page template appendix regeneration failed after restore: ' . $e->getMessage();
				$appendix_regenerable = false;
			}
		}

		if ( in_array( 'registries', $included, true ) && ! in_array( 'registries', $restored_categories, true ) ) {
			$warnings[] = 'Registries were in package but not restored.';
		}

		$valid = count( $errors ) === 0;
		return array(
			'valid'                => $valid,
			'restore_order_ok'     => $restore_order_ok,
			'section_count'        => $section_count,
			'page_template_count'  => $page_template_count,
			'composition_count'    => $composition_count,
			'appendix_regenerable' => $appendix_regenerable,
			'errors'               => $errors,
			'warnings'             => $warnings,
			'log_reference'        => $log_ref,
		);
	}
}
