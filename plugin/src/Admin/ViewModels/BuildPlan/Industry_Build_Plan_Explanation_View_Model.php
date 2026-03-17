<?php
/**
 * Builds a UI-facing view model for industry-aware Build Plan item explanations (Prompt 365).
 * Consumes item payload industry metadata; produces safe summary lines, warning badges, fit classification.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\BuildPlan;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\AI\Industry_Build_Plan_Scoring_Service;

/**
 * View model for industry rationale, hierarchy/CTA/LPagery notes, and weak-fit cautions in Build Plan review.
 */
final class Industry_Build_Plan_Explanation_View_Model {

	/** Known recommendation reason codes → human-readable labels. */
	private const REASON_LABELS = array(
		'pack_family_fit'         => 'Matches industry page family',
		'template_affinity_primary'=> 'Template fits primary industry',
		'hierarchy_fit'          => 'Fits hierarchy rules',
		'cta_fit'                => 'CTA pattern aligned',
		'lpagery_fit'             => 'LPagery posture aligned',
		'section_affinity'        => 'Section fit for industry',
		'neutral'                 => 'Neutral fit',
	);

	/** Known warning flag codes → human-readable labels. */
	private const WARNING_LABELS = array(
		'cta_mismatch'             => 'CTA pattern may not match industry preference',
		'discouraged_for_industry'  => 'Discouraged for this industry',
		'weak_fit'                 => 'Weak fit for industry',
		'hierarchy_depth_missing'  => 'Hierarchy depth may be incomplete',
		'weak_fit_local_page'      => 'Local page generation may be weak fit',
		'required_tokens_for_central_lpagery' => 'Central LPagery may need token setup',
	);

	/** Max summary lines to show. */
	private const MAX_SUMMARY_LINES = 8;

	/** Max source refs to display. */
	private const MAX_SOURCE_REFS = 5;

	/**
	 * Builds view model from one plan item payload. Safe when payload has no industry keys.
	 *
	 * @param array<string, mixed> $item_payload Item payload (may contain industry_source_refs, recommendation_reasons, industry_fit_score, industry_warning_flags).
	 * @param list<array{rule_key: string, severity: string, caution_summary: string}> $compliance_warnings Optional advisory compliance cautions from Industry_Compliance_Warning_Resolver (Prompt 407).
	 * @return array{has_industry_data: bool, summary_lines: list<string>, warning_badges: list<array{code: string, label: string}>, fit_classification: string, source_refs: list<string>, compliance_cautions: list<array{rule_key: string, severity: string, caution_summary: string}>}
	 */
	public static function from_item_payload( array $item_payload, array $compliance_warnings = array() ): array {
		$source_refs   = self::sanitize_source_refs( $item_payload );
		$reasons       = self::sanitize_reasons( $item_payload );
		$warning_flags = self::sanitize_warning_flags( $item_payload );
		$fit_score     = isset( $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_FIT_SCORE ] )
			? (int) $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_FIT_SCORE ]
			: null;
		$has_data      = $source_refs !== array() || $reasons !== array() || $warning_flags !== array() || $fit_score !== null;

		$summary_lines = array();
		foreach ( array_slice( $reasons, 0, self::MAX_SUMMARY_LINES ) as $code ) {
			$label = self::REASON_LABELS[ $code ] ?? self::humanize_code( $code );
			$summary_lines[] = $label;
		}
		if ( $fit_score !== null && $summary_lines === array() ) {
			$summary_lines[] = \sprintf( /* translators: %d: numeric fit score */ __( 'Industry fit score: %d', 'aio-page-builder' ), $fit_score );
		}

		$warning_badges = array();
		foreach ( $warning_flags as $code ) {
			$warning_badges[] = array(
				'code'  => $code,
				'label' => self::WARNING_LABELS[ $code ] ?? self::humanize_code( $code ),
			);
		}

		$fit_classification = self::derive_fit_classification( $fit_score, $warning_flags );

		$conflict_results = isset( $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_CONFLICT_RESULTS ] ) && is_array( $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_CONFLICT_RESULTS ] )
			? $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_CONFLICT_RESULTS ]
			: array();
		$explanation_summary = isset( $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_EXPLANATION_SUMMARY ] ) && is_string( $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_EXPLANATION_SUMMARY ] )
			? trim( $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_EXPLANATION_SUMMARY ] )
			: '';
		if ( $conflict_results !== array() || $explanation_summary !== '' ) {
			$has_data = true;
		}
		if ( $compliance_warnings !== array() ) {
			$has_data = true;
		}

		return array(
			'has_industry_data'   => $has_data,
			'summary_lines'       => $summary_lines,
			'warning_badges'      => $warning_badges,
			'fit_classification'  => $fit_classification,
			'source_refs'         => array_slice( array_map( 'strval', $source_refs ), 0, self::MAX_SOURCE_REFS ),
			'conflict_results'    => $conflict_results,
			'explanation_summary' => $explanation_summary,
			'compliance_cautions' => $compliance_warnings,
		);
	}

	/**
	 * Builds plan-level industry warning lines from plan definition (warnings array).
	 *
	 * @param array<string, mixed> $plan_definition Plan root (may contain warnings).
	 * @return list<string> Escaped lines for display; empty when none.
	 */
	public static function plan_level_warning_lines( array $plan_definition ): array {
		$warnings = isset( $plan_definition['warnings'] ) && is_array( $plan_definition['warnings'] )
			? $plan_definition['warnings']
			: array();
		$out = array();
		foreach ( array_slice( $warnings, 0, 10 ) as $w ) {
			if ( ! is_array( $w ) ) {
				continue;
			}
			$msg = (string) ( $w['message'] ?? $w['summary'] ?? '' );
			if ( $msg !== '' ) {
				$out[] = $msg;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $item_payload
	 * @return list<string>
	 */
	private static function sanitize_source_refs( array $item_payload ): array {
		$raw = $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_SOURCE_REFS ] ?? null;
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $ref ) {
			if ( is_string( $ref ) && $ref !== '' && strlen( $ref ) <= 64 && preg_match( '#^[a-z0-9_-]+$#', $ref ) ) {
				$out[] = $ref;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $item_payload
	 * @return list<string>
	 */
	private static function sanitize_reasons( array $item_payload ): array {
		$raw = $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_RECOMMENDATION_REASONS ] ?? null;
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$allowed = array_keys( self::REASON_LABELS );
		$out = array();
		foreach ( $raw as $r ) {
			if ( is_string( $r ) && $r !== '' && ( in_array( $r, $allowed, true ) || preg_match( '#^[a-z0-9_]+$#', $r ) ) ) {
				$out[] = $r;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $item_payload
	 * @return list<string>
	 */
	private static function sanitize_warning_flags( array $item_payload ): array {
		$raw = $item_payload[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_WARNING_FLAGS ] ?? null;
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$allowed = array_keys( self::WARNING_LABELS );
		$out = array();
		foreach ( $raw as $f ) {
			if ( is_string( $f ) && $f !== '' && ( in_array( $f, $allowed, true ) || preg_match( '#^[a-z0-9_]+$#', $f ) ) ) {
				$out[] = $f;
			}
		}
		return $out;
	}

	private static function humanize_code( string $code ): string {
		return \str_replace( '_', ' ', $code );
	}

	/**
	 * @param int|null $fit_score
	 * @param list<string> $warning_flags
	 */
	private static function derive_fit_classification( ?int $fit_score, array $warning_flags ): string {
		if ( in_array( 'discouraged_for_industry', $warning_flags, true ) ) {
			return 'discouraged';
		}
		if ( in_array( 'weak_fit', $warning_flags, true ) || in_array( 'weak_fit_local_page', $warning_flags, true ) ) {
			return 'weak_fit';
		}
		if ( $fit_score !== null ) {
			if ( $fit_score >= 70 ) {
				return 'recommended';
			}
			if ( $fit_score >= 40 ) {
				return 'allowed_weak_fit';
			}
		}
		return 'neutral';
	}
}
