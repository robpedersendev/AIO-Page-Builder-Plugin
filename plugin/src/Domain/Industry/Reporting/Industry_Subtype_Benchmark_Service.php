<?php
/**
 * Benchmark harness for subtype support (Prompt 480). Compares parent vs subtype across overlays, bundles, and caution rules.
 * Internal only; no live state mutation; produces readable summaries of meaningful vs weak differentiation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Subtype_Compliance_Rule_Registry;

/**
 * Compares parent-industry and subtype outcomes for overlays, bundles, and caution rules. Read-only.
 */
final class Industry_Subtype_Benchmark_Service {

	/** @var Industry_Subtype_Registry */
	private $subtype_registry;

	/** @var Subtype_Section_Helper_Overlay_Registry|null */
	private $subtype_section_overlay_registry;

	/** @var Subtype_Page_OnePager_Overlay_Registry|null */
	private $subtype_page_overlay_registry;

	/** @var Industry_Starter_Bundle_Registry|null */
	private $starter_bundle_registry;

	/** @var Subtype_Compliance_Rule_Registry|null */
	private $subtype_compliance_registry;

	public function __construct(
		Industry_Subtype_Registry $subtype_registry,
		?Subtype_Section_Helper_Overlay_Registry $subtype_section_overlay_registry = null,
		?Subtype_Page_OnePager_Overlay_Registry $subtype_page_overlay_registry = null,
		?Industry_Starter_Bundle_Registry $starter_bundle_registry = null,
		?Subtype_Compliance_Rule_Registry $subtype_compliance_registry = null
	) {
		$this->subtype_registry                 = $subtype_registry;
		$this->subtype_section_overlay_registry = $subtype_section_overlay_registry;
		$this->subtype_page_overlay_registry    = $subtype_page_overlay_registry;
		$this->starter_bundle_registry          = $starter_bundle_registry;
		$this->subtype_compliance_registry      = $subtype_compliance_registry;
	}

	/**
	 * Runs the subtype benchmark: compares each active subtype to parent on overlays, bundles, caution. Returns structured report.
	 *
	 * @return array{
	 *   generated_at: string,
	 *   subtypes_evaluated: array<int, string>,
	 *   per_subtype: array<string, array{subtype_key: string, parent_industry_key: string, label: string, section_overlay_count: int, page_overlay_count: int, has_bundle_ref: bool, caution_rule_count: int, differentiation_note: string, strength: string}>,
	 *   summary: array{total_subtypes: int, meaningful_count: int, weak_count: int, findings: array<int, string>}
	 * }
	 */
	public function run_benchmark(): array {
		$subtypes    = $this->subtype_registry->get_all();
		$active      = array_values(
			array_filter(
				$subtypes,
				function ( $s ) {
					$status = $s[ Industry_Subtype_Registry::FIELD_STATUS ] ?? '';
					return $status === Industry_Subtype_Registry::STATUS_ACTIVE;
				}
			)
		);
		$per_subtype = array();
		$meaningful  = 0;
		$weak        = 0;
		foreach ( $active as $sub ) {
			$subtype_key = (string) ( $sub[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] ?? '' );
			$parent      = (string) ( $sub[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ?? '' );
			$label       = (string) ( $sub[ Industry_Subtype_Registry::FIELD_LABEL ] ?? $subtype_key );
			if ( $subtype_key === '' ) {
				continue;
			}
			$section_count   = $this->count_section_overlays_for_subtype( $subtype_key );
			$page_count      = $this->count_page_overlays_for_subtype( $subtype_key );
			$has_bundle      = $this->subtype_has_bundle_ref( $sub );
			$caution_count   = $this->count_caution_rules_for_subtype( $parent, $subtype_key );
			$differentiation = $this->differentiation_note( $section_count, $page_count, $has_bundle, $caution_count );
			$strength        = ( $section_count > 0 || $page_count > 0 || $has_bundle || $caution_count > 0 ) ? 'meaningful' : 'weak';
			if ( $strength === 'meaningful' ) {
				++$meaningful;
			} else {
				++$weak;
			}
			$per_subtype[ $subtype_key ] = array(
				'subtype_key'           => $subtype_key,
				'parent_industry_key'   => $parent,
				'label'                 => $label,
				'section_overlay_count' => $section_count,
				'page_overlay_count'    => $page_count,
				'has_bundle_ref'        => $has_bundle,
				'caution_rule_count'    => $caution_count,
				'differentiation_note'  => $differentiation,
				'strength'              => $strength,
			);
		}
		$findings = array();
		if ( $weak > 0 ) {
			$findings[] = "{$weak} subtype(s) have weak differentiation (no overlays, bundle ref, or caution rules).";
		}
		if ( $meaningful > 0 ) {
			$findings[] = "{$meaningful} subtype(s) show meaningful differentiation.";
		}
		return array(
			'generated_at'       => gmdate( 'c' ),
			'subtypes_evaluated' => array_keys( $per_subtype ),
			'per_subtype'        => $per_subtype,
			'summary'            => array(
				'total_subtypes'   => count( $per_subtype ),
				'meaningful_count' => $meaningful,
				'weak_count'       => $weak,
				'findings'         => $findings,
			),
		);
	}

	private function count_section_overlays_for_subtype( string $subtype_key ): int {
		if ( $this->subtype_section_overlay_registry === null ) {
			return 0;
		}
		$all = $this->subtype_section_overlay_registry->get_for_subtype( $subtype_key );
		return count( $all );
	}

	private function count_page_overlays_for_subtype( string $subtype_key ): int {
		if ( $this->subtype_page_overlay_registry === null ) {
			return 0;
		}
		$all = $this->subtype_page_overlay_registry->get_for_subtype( $subtype_key );
		return count( $all );
	}

	/**
	 * @param array<string, mixed> $subtype_def
	 */
	private function subtype_has_bundle_ref( array $subtype_def ): bool {
		$ref = $subtype_def['starter_bundle_ref'] ?? null;
		return is_string( $ref ) && trim( $ref ) !== '';
	}

	private function count_caution_rules_for_subtype( string $parent_industry_key, string $subtype_key ): int {
		if ( $this->subtype_compliance_registry === null ) {
			return 0;
		}
		$all = $this->subtype_compliance_registry->get_for_subtype( $parent_industry_key, $subtype_key );
		return count( $all );
	}

	private function differentiation_note( int $section_count, int $page_count, bool $has_bundle, int $caution_count ): string {
		$parts = array();
		if ( $section_count > 0 ) {
			$parts[] = "{$section_count} section overlay(s)";
		}
		if ( $page_count > 0 ) {
			$parts[] = "{$page_count} page overlay(s)";
		}
		if ( $has_bundle ) {
			$parts[] = 'distinct bundle ref';
		}
		if ( $caution_count > 0 ) {
			$parts[] = "{$caution_count} caution rule(s)";
		}
		if ( count( $parts ) === 0 ) {
			return 'No overlays, bundle ref, or caution rules; nominal subtype.';
		}
		return 'Differentiation: ' . implode( '; ', $parts ) . '.';
	}
}
