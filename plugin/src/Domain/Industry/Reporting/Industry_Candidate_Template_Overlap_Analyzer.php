<?php
/**
 * Analyzes how well a candidate industry fits the existing template/section library (Prompt 461).
 * Internal, advisory only. Produces overlap score, strongest/weak families, and notes for future-industry scorecard.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;

/**
 * Scores candidate industry against existing pack page_families, section keys, CTA and LPagery usage.
 * Does not create or alter industries; supports future-industry evaluation workflow.
 */
final class Industry_Candidate_Template_Overlap_Analyzer {

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	public function __construct( ?Industry_Pack_Registry $pack_registry = null ) {
		$this->pack_registry = $pack_registry;
	}

	/**
	 * Analyzes overlap between a candidate industry profile and existing packs' template/section/CTA/LPagery usage.
	 *
	 * @param array{
	 *   candidate_industry_label: string,
	 *   page_families?: list<string>,
	 *   section_keys?: list<string>,
	 *   cta_pattern_refs?: list<string>,
	 *   lpagery_rule_ref?: string,
	 *   proof_model_hint?: string
	 * } $candidate
	 * @return array{
	 *   candidate_industry_label: string,
	 *   overlap_score: float,
	 *   strongest_reusable_families: list<string>,
	 *   weak_coverage_families: list<string>,
	 *   notes: list<string>
	 * }
	 */
	public function analyze( array $candidate ): array {
		$label = isset( $candidate['candidate_industry_label'] ) && is_string( $candidate['candidate_industry_label'] )
			? trim( $candidate['candidate_industry_label'] )
			: '';
		$page_families   = isset( $candidate['page_families'] ) && is_array( $candidate['page_families'] )
			? array_values( array_filter( array_map( 'trim', $candidate['page_families'] ) ) )
			: array();
		$section_keys    = isset( $candidate['section_keys'] ) && is_array( $candidate['section_keys'] )
			? array_values( array_filter( array_map( 'trim', $candidate['section_keys'] ) ) )
			: array();
		$cta_refs        = isset( $candidate['cta_pattern_refs'] ) && is_array( $candidate['cta_pattern_refs'] )
			? array_values( array_filter( array_map( 'trim', $candidate['cta_pattern_refs'] ) ) )
			: array();
		$lpagery_ref     = isset( $candidate['lpagery_rule_ref'] ) && is_string( $candidate['lpagery_rule_ref'] )
			? trim( $candidate['lpagery_rule_ref'] )
			: '';
		$proof_hint      = isset( $candidate['proof_model_hint'] ) && is_string( $candidate['proof_model_hint'] )
			? trim( $candidate['proof_model_hint'] )
			: '';

		$existing_page_families = array();
		$existing_section_keys  = array();
		$existing_cta_refs      = array();
		$existing_lpagery_refs  = array();
		$family_pack_count      = array();

		if ( $this->pack_registry !== null ) {
			foreach ( $this->pack_registry->get_all() as $pack ) {
				$families = isset( $pack[ Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES ] ) && is_array( $pack[ Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES ] )
					? $pack[ Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES ]
					: array();
				foreach ( $families as $f ) {
					if ( is_string( $f ) && trim( $f ) !== '' ) {
						$f = trim( $f );
						$existing_page_families[ $f ] = true;
						$family_pack_count[ $f ]     = ( $family_pack_count[ $f ] ?? 0 ) + 1;
					}
				}
				$pref = isset( $pack['preferred_section_keys'] ) && is_array( $pack['preferred_section_keys'] )
					? $pack['preferred_section_keys']
					: array();
				foreach ( $pref as $s ) {
					if ( is_string( $s ) && trim( $s ) !== '' ) {
						$existing_section_keys[ trim( $s ) ] = true;
					}
				}
				foreach ( array( 'preferred_cta_patterns', 'required_cta_patterns', 'discouraged_cta_patterns' ) as $field ) {
					$arr = isset( $pack[ $field ] ) && is_array( $pack[ $field ] ) ? $pack[ $field ] : array();
					foreach ( $arr as $r ) {
						if ( is_string( $r ) && trim( $r ) !== '' ) {
							$existing_cta_refs[ trim( $r ) ] = true;
						}
					}
				}
				$lpr = isset( $pack[ Industry_Pack_Schema::FIELD_LPAGERY_RULE_REF ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_LPAGERY_RULE_REF ] )
					? trim( $pack[ Industry_Pack_Schema::FIELD_LPAGERY_RULE_REF ] )
					: '';
				if ( $lpr !== '' ) {
					$existing_lpagery_refs[ $lpr ] = true;
				}
			}
		}

		$page_overlap   = 0.0;
		$section_overlap = 0.0;
		if ( count( $page_families ) > 0 ) {
			$match = 0;
			foreach ( $page_families as $f ) {
				if ( isset( $existing_page_families[ $f ] ) ) {
					$match++;
				}
			}
			$page_overlap = $match / count( $page_families );
		}
		if ( count( $section_keys ) > 0 ) {
			$match = 0;
			foreach ( $section_keys as $s ) {
				if ( isset( $existing_section_keys[ $s ] ) ) {
					$match++;
				}
			}
			$section_overlap = $match / count( $section_keys );
		}

		$denom = 0;
		$sum   = 0.0;
		if ( count( $page_families ) > 0 ) {
			$denom++;
			$sum += $page_overlap;
		}
		if ( count( $section_keys ) > 0 ) {
			$denom++;
			$sum += $section_overlap;
		}
		$overlap_score = $denom > 0 ? $sum / $denom : 0.0;

		// Strongest reusable: page families that appear in more than one pack (or all if single pack).
		$strongest_reusable_families = array();
		$min_packs = count( $this->pack_registry !== null ? $this->pack_registry->get_all() : array() ) > 1 ? 2 : 1;
		foreach ( array_keys( $family_pack_count ) as $f ) {
			if ( ( $family_pack_count[ $f ] ?? 0 ) >= $min_packs ) {
				$strongest_reusable_families[] = $f;
			}
		}
		sort( $strongest_reusable_families );

		// Weak coverage: candidate page_families or section_keys not present in any pack.
		$weak_coverage_families = array();
		foreach ( $page_families as $f ) {
			if ( ! isset( $existing_page_families[ $f ] ) ) {
				$weak_coverage_families[] = 'page_family:' . $f;
			}
		}
		foreach ( $section_keys as $s ) {
			if ( ! isset( $existing_section_keys[ $s ] ) ) {
				$weak_coverage_families[] = 'section_key:' . $s;
			}
		}
		sort( $weak_coverage_families );

		$notes = array();
		if ( count( $cta_refs ) > 0 ) {
			$all_covered = true;
			foreach ( $cta_refs as $r ) {
				if ( ! isset( $existing_cta_refs[ $r ] ) ) {
					$all_covered = false;
					break;
				}
			}
			$notes[] = $all_covered ? 'CTA patterns: existing registry can cover candidate refs.' : 'CTA patterns: candidate may need new or additional CTA patterns.';
		}
		if ( $lpagery_ref !== '' ) {
			$notes[] = isset( $existing_lpagery_refs[ $lpagery_ref ] )
				? 'LPagery: existing rule ref in use.'
				: 'LPagery: candidate may need new LPagery rule.';
		}
		if ( $proof_hint !== '' ) {
			$notes[] = 'Proof model: assess compatibility with existing content model (advisory).';
		}

		return array(
			'candidate_industry_label'       => $label,
			'overlap_score'                  => round( $overlap_score, 4 ),
			'strongest_reusable_families'    => $strongest_reusable_families,
			'weak_coverage_families'         => $weak_coverage_families,
			'notes'                          => $notes,
		);
	}
}
