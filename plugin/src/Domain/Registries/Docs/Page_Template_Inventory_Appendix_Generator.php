<?php
/**
 * Generates the Page Template Inventory Appendix from registry data (spec §62.12, §57.9, §60.6).
 * Deterministic, repeatable; output is markdown suitable for docs/appendices/page-template-inventory.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Docs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;

/**
 * Builds the Page Template Inventory Appendix markdown from live page template registry.
 */
final class Page_Template_Inventory_Appendix_Generator {

	/** Max definitions to load (align with large-library cap). */
	private const CAP = 1000;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $page_repository;

	public function __construct( Page_Template_Repository $page_repository ) {
		$this->page_repository = $page_repository;
	}

	/**
	 * Generates the full appendix markdown content.
	 *
	 * @return string Markdown for the page template inventory appendix.
	 */
	public function generate(): string {
		$defs = $this->page_repository->list_all_definitions_capped( self::CAP );
		return $this->generate_from_definitions( $defs );
	}

	/**
	 * Generates markdown from a given list of definitions (deterministic; for tests).
	 *
	 * @param list<array<string, mixed>> $definitions
	 * @return string
	 */
	public function generate_from_definitions( array $definitions ): string {
		$result = $this->build_result_from_definitions( $definitions );
		$grouped = $this->group_by_category( $result['rows'] );
		return $this->render_markdown( $grouped, $result['total'] );
	}

	/**
	 * Returns a stable result payload for tests and callers (list of row data, not raw markdown).
	 *
	 * @return array{rows: list<array<string, mixed>>, total: int}
	 */
	public function build_result(): array {
		$defs = $this->page_repository->list_all_definitions_capped( self::CAP );
		return $this->build_result_from_definitions( $defs );
	}

	/**
	 * Builds result payload from a given list of definitions (deterministic; for tests and regeneration from alternate sources).
	 *
	 * @param list<array<string, mixed>> $definitions
	 * @return array{rows: list<array<string, mixed>>, total: int}
	 */
	public function build_result_from_definitions( array $definitions ): array {
		$rows = array();
		foreach ( $definitions as $def ) {
			$rows[] = $this->build_row( $def );
		}
		return array(
			'rows'  => $rows,
			'total' => count( $rows ),
		);
	}

	/**
	 * @param array<string, mixed> $def
	 * @return array<string, mixed> Appendix row: key, name, purpose, ordered_sections, optional_sections, hierarchy_hint, one_pager_status, version, deprecation_status.
	 */
	private function build_row( array $def ): array {
		$key    = (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$name   = (string) ( $def[ Page_Template_Schema::FIELD_NAME ] ?? $key );
		$purpose = (string) ( $def[ Page_Template_Schema::FIELD_PURPOSE_SUMMARY ] ?? '' );
		$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		$ordered_list = array();
		$optional_list = array();
		if ( \is_array( $ordered ) ) {
			foreach ( $ordered as $item ) {
				if ( ! \is_array( $item ) ) {
					continue;
				}
				$sk = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? $item['section_key'] ?? '' );
				if ( $sk !== '' ) {
					$ordered_list[] = $sk;
					$required = $item[ Page_Template_Schema::SECTION_ITEM_REQUIRED ] ?? true;
					if ( $required === false ) {
						$optional_list[] = $sk;
					}
				}
			}
		}
		$section_requirements = $def[ Page_Template_Schema::FIELD_SECTION_REQUIREMENTS ] ?? array();
		if ( \is_array( $section_requirements ) && count( $optional_list ) === 0 ) {
			foreach ( $section_requirements as $sec_key => $req ) {
				if ( \is_array( $req ) && ( $req['required'] ?? true ) === false && \in_array( $sec_key, $ordered_list, true ) ) {
					if ( ! \in_array( $sec_key, $optional_list, true ) ) {
						$optional_list[] = $sec_key;
					}
				}
			}
		}
		$category_class = (string) ( $def['template_category_class'] ?? '' );
		$family         = (string) ( $def['template_family'] ?? '' );
		$hierarchy_role = (string) ( $def['hierarchy_role'] ?? '' );
		$hierarchy_hints = $def['hierarchy_hints'] ?? array();
		$hint_parts = array();
		if ( $category_class !== '' ) {
			$hint_parts[] = $category_class;
		}
		if ( $family !== '' ) {
			$hint_parts[] = $family;
		}
		if ( $hierarchy_role !== '' ) {
			$hint_parts[] = $hierarchy_role;
		}
		if ( \is_array( $hierarchy_hints ) && isset( $hierarchy_hints['hierarchy_role'] ) ) {
			$hr = (string) $hierarchy_hints['hierarchy_role'];
			if ( $hr !== '' && ! \in_array( $hr, $hint_parts, true ) ) {
				$hint_parts[] = $hr;
			}
		}
		$hierarchy_hint = \implode( ', ', $hint_parts );

		$one_pager = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? $def['one_pager'] ?? array();
		$one_pager_status = 'no';
		if ( \is_array( $one_pager ) && ( isset( $one_pager['link'] ) || isset( $one_pager['page_purpose_summary'] ) || ( isset( $one_pager['ref'] ) && $one_pager['ref'] !== '' ) ) ) {
			$one_pager_status = 'yes';
		}

		$status = (string) ( $def[ Page_Template_Schema::FIELD_STATUS ] ?? '' );
		$deprecation_status = ( $status === 'deprecated' ) ? 'deprecated' : 'active';
		$version_data = $def[ Page_Template_Schema::FIELD_VERSION ] ?? array();
		$version = \is_array( $version_data ) && isset( $version_data['version'] ) ? (string) $version_data['version'] : '1';

		return array(
			'key'                 => $key,
			'name'                => $name,
			'purpose'             => $purpose,
			'ordered_sections'    => $ordered_list,
			'optional_sections'   => $optional_list,
			'category_class'      => $category_class,
			'hierarchy_hint'      => $hierarchy_hint,
			'one_pager_status'    => $one_pager_status,
			'version'             => $version,
			'deprecation_status'   => $deprecation_status,
		);
	}

	/**
	 * @param list<array<string, mixed>> $rows
	 * @return array<string, list<array<string, mixed>>> category => rows
	 */
	private function group_by_category( array $rows ): array {
		$grouped = array();
		foreach ( $rows as $row ) {
			$cat = (string) ( $row['category_class'] ?? $row['hierarchy_hint'] ?? '' );
			if ( $cat !== '' ) {
				$cat = \trim( \explode( ',', $cat )[0] );
			}
			if ( $cat === '' ) {
				$cat = '_ungrouped';
			}
			if ( ! isset( $grouped[ $cat ] ) ) {
				$grouped[ $cat ] = array();
			}
			$grouped[ $cat ][] = $row;
		}
		ksort( $grouped );
		if ( isset( $grouped['_ungrouped'] ) ) {
			$ungrouped = $grouped['_ungrouped'];
			unset( $grouped['_ungrouped'] );
			$grouped['_ungrouped'] = $ungrouped;
		}
		return $grouped;
	}

	/**
	 * @param array<string, list<array<string, mixed>>> $grouped
	 * @param int $total
	 * @return string
	 */
	private function render_markdown( array $grouped, int $total ): string {
		$out = array();
		$out[] = '# Page Template Inventory Appendix';
		$out[] = '';
		$out[] = '**Spec**: §62.12 Page Template Inventory Appendix; §57.9 Documentation Standards; §60.6 Documentation Completion Requirements.';
		$out[] = '';
		$out[] = 'This appendix is **generated** from the live page template registry. Do not edit by hand; regenerate after library changes.';
		$out[] = '';
		$out[] = '**Total page templates**: ' . $total;
		$out[] = '';
		$out[] = '---';
		$out[] = '';

		foreach ( $grouped as $category => $rows ) {
			$cat_label = $category === '_ungrouped' ? 'Ungrouped' : \ucfirst( \str_replace( array( '_', '-' ), ' ', $category ) );
			$out[] = '## ' . $cat_label;
			$out[] = '';
			$out[] = '| Key | Name | Purpose | Ordered sections | Optional sections | Hierarchy | One-pager | Version | Deprecation |';
			$out[] = '|-----|------|---------|------------------|-------------------|-----------|-----------|---------|-------------|';
			foreach ( $rows as $r ) {
				$key   = $this->escape_table_cell( (string) ( $r['key'] ?? '' ) );
				$name  = $this->escape_table_cell( (string) ( $r['name'] ?? '' ) );
				$purpose = $this->escape_table_cell( (string) ( $r['purpose'] ?? '' ) );
				$ordered = \is_array( $r['ordered_sections'] ?? null ) ? \implode( ', ', $r['ordered_sections'] ) : '';
				$ordered = $this->escape_table_cell( $ordered );
				$optional = \is_array( $r['optional_sections'] ?? null ) ? \implode( ', ', $r['optional_sections'] ) : '—';
				$optional = $this->escape_table_cell( $optional );
				$hint   = $this->escape_table_cell( (string) ( $r['hierarchy_hint'] ?? '' ) );
				$op     = $this->escape_table_cell( (string) ( $r['one_pager_status'] ?? '' ) );
				$ver    = $this->escape_table_cell( (string) ( $r['version'] ?? '' ) );
				$dep    = $this->escape_table_cell( (string) ( $r['deprecation_status'] ?? '' ) );
				$out[] = '| ' . $key . ' | ' . $name . ' | ' . $purpose . ' | ' . $ordered . ' | ' . $optional . ' | ' . $hint . ' | ' . $op . ' | ' . $ver . ' | ' . $dep . ' |';
			}
			$out[] = '';
		}

		return \implode( "\n", $out );
	}

	private function escape_table_cell( string $s ): string {
		$s = \str_replace( array( '|', "\n", "\r" ), array( '\\|', ' ', '' ), $s );
		return $s;
	}
}
