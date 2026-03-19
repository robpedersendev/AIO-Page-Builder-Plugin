<?php
/**
 * Serializes registry-owned objects into stable export-ready fragments (spec §52.2, §52.4, §52.6).
 * Internal, admin-governed. No ZIP packaging or download endpoints.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Fragment_Builder;
use AIOPageBuilder\Domain\Storage\Repositories\Documentation_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository;

/**
 * Deterministic serialization of section templates, page templates, compositions, documentation, snapshots.
 * Callers must enforce capability checks before use.
 */
final class Registry_Export_Serializer {

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repo;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $page_template_repo;

	/** @var Composition_Repository */
	private Composition_Repository $composition_repo;

	/** @var Documentation_Repository */
	private Documentation_Repository $documentation_repo;

	/** @var Version_Snapshot_Repository */
	private Version_Snapshot_Repository $snapshot_repo;

	public function __construct(
		Section_Template_Repository $section_repo,
		Page_Template_Repository $page_template_repo,
		Composition_Repository $composition_repo,
		Documentation_Repository $documentation_repo,
		Version_Snapshot_Repository $snapshot_repo
	) {
		$this->section_repo       = $section_repo;
		$this->page_template_repo = $page_template_repo;
		$this->composition_repo   = $composition_repo;
		$this->documentation_repo = $documentation_repo;
		$this->snapshot_repo      = $snapshot_repo;
	}

	/**
	 * Serializes a single section definition into an export fragment.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	public function serialize_section( array $definition ): array {
		return Registry_Export_Fragment_Builder::for_section( $definition );
	}

	/**
	 * Serializes a single page template definition into an export fragment.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	public function serialize_page_template( array $definition ): array {
		return Registry_Export_Fragment_Builder::for_page_template( $definition );
	}

	/**
	 * Serializes a single composition definition into an export fragment.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	public function serialize_composition( array $definition ): array {
		return Registry_Export_Fragment_Builder::for_composition( $definition );
	}

	/**
	 * Serializes a single documentation definition into an export fragment.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	public function serialize_documentation( array $definition ): array {
		return Registry_Export_Fragment_Builder::for_documentation( $definition );
	}

	/**
	 * Serializes a single snapshot definition into an export fragment.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	public function serialize_snapshot( array $definition ): array {
		return Registry_Export_Fragment_Builder::for_snapshot( $definition );
	}

	/**
	 * Builds registry bundle fragments (sections, page templates, compositions).
	 * Excludes documentation and snapshots (optional categories).
	 *
	 * @param int $limit Max definitions per type (0 = default).
	 * @return array{registries: array{sections: array<int, array>, page_templates: array<int, array>, compositions: array<int, array>}}
	 */
	public function build_registry_bundle( int $limit = 0 ): array {
		$sec_limit  = $limit > 0 ? $limit : 9999;
		$pt_limit   = $limit > 0 ? $limit : 9999;
		$comp_limit = $limit > 0 ? $limit : 9999;

		$sections       = $this->section_repo->list_all_definitions( $sec_limit, 0 );
		$page_templates = $this->page_template_repo->list_all_definitions( $pt_limit, 0 );
		$compositions   = $this->composition_repo->list_all_definitions( $comp_limit, 0 );

		$sections_out = array();
		foreach ( $sections as $def ) {
			$sections_out[] = Registry_Export_Fragment_Builder::for_section( $def );
		}
		$page_out = array();
		foreach ( $page_templates as $def ) {
			$page_out[] = Registry_Export_Fragment_Builder::for_page_template( $def );
		}
		$comp_out = array();
		foreach ( $compositions as $def ) {
			$comp_out[] = Registry_Export_Fragment_Builder::for_composition( $def );
		}

		return array(
			'registries' => array(
				'sections'       => $sections_out,
				'page_templates' => $page_out,
				'compositions'   => $comp_out,
			),
		);
	}

	/**
	 * Builds manifest fragment (export metadata only, no payloads).
	 *
	 * @return array<string, mixed>
	 */
	public function build_manifest_fragment(): array {
		return array(
			'export_schema_version' => \AIOPageBuilder\Infrastructure\Config\Versions::export_schema(),
			'object_types'          => array(
				Registry_Export_Fragment_Builder::OBJECT_TYPE_SECTION,
				Registry_Export_Fragment_Builder::OBJECT_TYPE_PAGE,
				Registry_Export_Fragment_Builder::OBJECT_TYPE_COMPOSITION,
				Registry_Export_Fragment_Builder::OBJECT_TYPE_DOCUMENTATION,
				Registry_Export_Fragment_Builder::OBJECT_TYPE_SNAPSHOT,
			),
		);
	}
}
