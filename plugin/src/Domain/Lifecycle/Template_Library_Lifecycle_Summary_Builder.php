<?php
/**
 * Builds template-library-aware lifecycle summary for deactivation, uninstall, export, and restore UX (spec §4.18, §59.13, Prompt 213).
 *
 * Clarifies what survives, what is exportable, what is regenerable, and what requires explicit backup or restore.
 * No secrets; no change to built-page survival guarantees or destructive uninstall behavior.
 *
 * Example template_library_lifecycle_summary payload (build()):
 * [
 *   'built_pages_survive' => true,
 *   'built_pages_description' => '...',
 *   'template_registry_exportable' => true,
 *   'template_registry_description' => '...',
 *   'one_pagers_description' => '...',
 *   'appendices_description' => '...',
 *   'previews_description' => '...',
 *   'restore_guidance' => '...',
 *   'deactivation_message' => '...',
 *   'section_template_count' => 250,   // optional when repos injected
 *   'page_template_count' => 500,
 *   'composition_count' => 120,
 * ]
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Lifecycle;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Produces stable template_library_lifecycle_summary payload for admin screens and lifecycle guidance.
 */
final class Template_Library_Lifecycle_Summary_Builder {

	/** @var Section_Template_Repository|null */
	private ?Section_Template_Repository $section_repository;

	/** @var Page_Template_Repository|null */
	private ?Page_Template_Repository $page_repository;

	/** @var Composition_Repository|null */
	private ?Composition_Repository $composition_repository;

	public function __construct(
		?Section_Template_Repository $section_repository = null,
		?Page_Template_Repository $page_repository = null,
		?Composition_Repository $composition_repository = null
	) {
		$this->section_repository    = $section_repository;
		$this->page_repository       = $page_repository;
		$this->composition_repository = $composition_repository;
	}

	/**
	 * Builds the template_library_lifecycle_summary payload. Safe for display; no secrets.
	 *
	 * @return array<string, mixed> Keys: built_pages_survive, built_pages_description, template_registry_exportable, template_registry_description, one_pagers_description, appendices_description, previews_description, restore_guidance, deactivation_message, optional: section_template_count, page_template_count, composition_count.
	 */
	public function build(): array {
		$counts = $this->build_counts();
		$built_pages_description = __(
			'Pages created with the builder (built pages) remain on your site after deactivation or uninstall. Only plugin-owned data (template definitions, compositions, settings, plans, logs) is removed when you uninstall.',
			'aio-page-builder'
		);
		$template_registry_description = __(
			'Section templates, page templates, and compositions are included in full backup and template-only exports. Export before removal if you want to restore the template library later.',
			'aio-page-builder'
		);
		$one_pagers_description = __(
			'One-pager documentation is stored with template definitions. Exporting the template library preserves it; after restore you can regenerate one-pager references from the restored registries.',
			'aio-page-builder'
		);
		$appendices_description = __(
			'Section and page inventory appendices (docs) are generated from the live registries. They are not stored as persistent files. After restore, regenerate appendices from the plugin if needed.',
			'aio-page-builder'
		);
		$previews_description = __(
			'Template previews are operational views generated from registry data. They are not exported as standalone content. After restoring template registries, previews are available again.',
			'aio-page-builder'
		);
		$restore_guidance = __(
			'To restore template registries: use a full backup or template-only export package in Import / Export. Validate the package, choose conflict resolution, then run restore. Restored section and page templates and compositions will be available; appendices can be regenerated from the plugin.',
			'aio-page-builder'
		);
		$deactivation_message = __(
			'On deactivation, nothing is removed. Built pages, template definitions, and all plugin data remain. Only runtime behavior (menus, cron) stops until you reactivate.',
			'aio-page-builder'
		);

		return array_merge(
			array(
				'built_pages_survive'         => true,
				'built_pages_description'    => $built_pages_description,
				'template_registry_exportable' => true,
				'template_registry_description' => $template_registry_description,
				'one_pagers_description'     => $one_pagers_description,
				'appendices_description'     => $appendices_description,
				'previews_description'       => $previews_description,
				'restore_guidance'            => $restore_guidance,
				'deactivation_message'       => $deactivation_message,
			),
			$counts
		);
	}

	/**
	 * Optional counts when repositories are available. No secrets.
	 *
	 * @return array<string, int>
	 */
	private function build_counts(): array {
		$out = array();
		if ( $this->section_repository !== null ) {
			$all = $this->section_repository->list_definitions_by_status( 'active', 10000, 0 );
			$out['section_template_count'] = is_array( $all ) ? count( $all ) : 0;
		}
		if ( $this->page_repository !== null ) {
			$all = $this->page_repository->list_definitions_by_status( 'active', 10000, 0 );
			$out['page_template_count'] = is_array( $all ) ? count( $all ) : 0;
		}
		if ( $this->composition_repository !== null ) {
			$all = $this->composition_repository->list_all_definitions( 10000, 0 );
			$out['composition_count'] = is_array( $all ) ? count( $all ) : 0;
		}
		return $out;
	}
}
