<?php
/**
 * Generates the Section Template Inventory Appendix from registry data (spec §62.11, §57.9, §60.6).
 * Deterministic, repeatable; output is markdown suitable for docs/appendices/section-template-inventory.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Docs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Builds the Section Template Inventory Appendix markdown from live section registry.
 */
final class Section_Inventory_Appendix_Generator {

	/** Max definitions to load (align with large-library cap). */
	private const CAP = 1000;

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repository;

	public function __construct( Section_Template_Repository $section_repository ) {
		$this->section_repository = $section_repository;
	}

	/**
	 * Generates the full appendix markdown content.
	 *
	 * @return string Markdown for the section template inventory appendix.
	 */
	public function generate(): string {
		$defs = $this->section_repository->list_all_definitions_capped( self::CAP );
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
		$defs = $this->section_repository->list_all_definitions_capped( self::CAP );
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
	 * @return array<string, mixed> Appendix row: key, name, purpose, category, variants, helper_status, deprecation_status, version.
	 */
	private function build_row( array $def ): array {
		$key     = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$name    = (string) ( $def[ Section_Schema::FIELD_NAME ] ?? $key );
		$purpose  = (string) ( $def[ Section_Schema::FIELD_PURPOSE_SUMMARY ] ?? '' );
		$category = (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? $def['section_purpose_family'] ?? '' );
		$variants = $def[ Section_Schema::FIELD_VARIANTS ] ?? array();
		$variant_list = \is_array( $variants ) ? array_keys( $variants ) : array();
		$helper_ref = (string) ( $def[ Section_Schema::FIELD_HELPER_REF ] ?? '' );
		$helper_status = $helper_ref !== '' ? 'yes' : 'no';
		$status   = (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' );
		$deprecation_status = ( $status === 'deprecated' ) ? 'deprecated' : 'active';
		$version_data = $def[ Section_Schema::FIELD_VERSION ] ?? array();
		$version = \is_array( $version_data ) && isset( $version_data['version'] ) ? (string) $version_data['version'] : '1';

		return array(
			'key'                 => $key,
			'name'                => $name,
			'purpose'             => $purpose,
			'category'            => $category,
			'variants'             => $variant_list,
			'helper_status'        => $helper_status,
			'deprecation_status'   => $deprecation_status,
			'version'              => $version,
		);
	}

	/**
	 * @param list<array<string, mixed>> $rows
	 * @return array<string, list<array<string, mixed>>> category => rows
	 */
	private function group_by_category( array $rows ): array {
		$grouped = array();
		foreach ( $rows as $row ) {
			$cat = (string) ( $row['category'] ?? '' );
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
		$out[] = '# Section Template Inventory Appendix';
		$out[] = '';
		$out[] = '**Spec**: §62.11 Section Template Inventory Appendix; §57.9 Documentation Standards; §60.6 Documentation Completion Requirements.';
		$out[] = '';
		$out[] = 'This appendix is **generated** from the live section template registry. Do not edit by hand; regenerate after library changes.';
		$out[] = '';
		$out[] = '**Total section templates**: ' . $total;
		$out[] = '';
		$out[] = '---';
		$out[] = '';

		foreach ( $grouped as $category => $rows ) {
			$cat_label = $category === '_ungrouped' ? 'Ungrouped' : \ucfirst( \str_replace( array( '_', '-' ), ' ', $category ) );
			$out[] = '## ' . $cat_label;
			$out[] = '';
			$out[] = '| Key | Name | Purpose | Variants | Helper | Deprecation | Version |';
			$out[] = '|-----|------|---------|----------|--------|-------------|---------|';
			foreach ( $rows as $r ) {
				$key   = $this->escape_table_cell( (string) ( $r['key'] ?? '' ) );
				$name  = $this->escape_table_cell( (string) ( $r['name'] ?? '' ) );
				$purpose = $this->escape_table_cell( (string) ( $r['purpose'] ?? '' ) );
				$variants = \is_array( $r['variants'] ?? null ) ? \implode( ', ', $r['variants'] ) : '';
				$variants = $this->escape_table_cell( $variants );
				$helper = $this->escape_table_cell( (string) ( $r['helper_status'] ?? '' ) );
				$dep   = $this->escape_table_cell( (string) ( $r['deprecation_status'] ?? '' ) );
				$ver   = $this->escape_table_cell( (string) ( $r['version'] ?? '' ) );
				$out[] = '| ' . $key . ' | ' . $name . ' | ' . $purpose . ' | ' . $variants . ' | ' . $helper . ' | ' . $dep . ' | ' . $ver . ' |';
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
