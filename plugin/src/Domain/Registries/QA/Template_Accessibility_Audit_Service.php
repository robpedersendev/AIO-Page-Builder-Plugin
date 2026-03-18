<?php
/**
 * Automated semantic, accessibility, and CTA audit over the full template library (Prompt 186).
 * Authority: spec §12.12, §15.9, §15.10, §51.3, §51.6, §51.7, §56.6; semantic-seo-accessibility-extension-contract; cta-sequencing-and-placement-contract; template-library-compliance-matrix SEMANTIC/CTA families.
 * Machine-checkable rules only; does not claim full legal compliance. Human review still required for rendered output.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\QA;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Runs library-wide semantic/accessibility/CTA audit and returns Template_Accessibility_Audit_Result.
 * Enforces machine-checkable rules from contracts; marks remainder as human_review_required.
 */
final class Template_Accessibility_Audit_Service {

	private const LIBRARY_CAP = 2000;

	/** CTA-classified values (cta-sequencing-and-placement-contract §2.1). */
	private const CTA_CLASSIFIED = array( 'primary_cta', 'contact_cta', 'navigation_cta' );

	/** Min CTA sections per page class (contract §3). */
	private const CTA_MIN_BY_CLASS = array(
		'top_level'    => 3,
		'hub'          => 4,
		'nested_hub'   => 4,
		'child_detail' => 5,
	);

	private const NON_CTA_MIN = 8;
	private const NON_CTA_MAX = 14;

	/** Section purpose families that typically contribute to heading outline (semantic contract §3.2, §9.1). */
	private const HEADING_RELEVANT_FAMILIES = array(
		'hero',
		'proof',
		'offer',
		'explainer',
		'legal',
		'utility',
		'listing',
		'comparison',
		'contact',
		'cta',
		'faq',
		'profile',
		'stats',
		'timeline',
		'related',
		'other',
	);

	private Section_Template_Repository $section_repository;
	private Page_Template_Repository $page_repository;

	public function __construct(
		Section_Template_Repository $section_repository,
		Page_Template_Repository $page_repository
	) {
		$this->section_repository = $section_repository;
		$this->page_repository    = $page_repository;
	}

	/**
	 * Runs full semantic/accessibility/CTA audit; returns result for machine and human reporting.
	 */
	public function run(): Template_Accessibility_Audit_Result {
		$sections = $this->section_repository->list_all_definitions_capped( self::LIBRARY_CAP );
		$pages    = $this->page_repository->list_all_definitions_capped( self::LIBRARY_CAP );

		$section_by_key = array();
		foreach ( $sections as $def ) {
			$k = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			if ( $k !== '' ) {
				$section_by_key[ $k ] = $def;
			}
		}

		$violations = array();

		foreach ( $sections as $def ) {
			$this->audit_section( $def, $violations );
		}

		foreach ( $pages as $page_def ) {
			$this->audit_page_cta_rules( $page_def, $section_by_key, $violations );
		}

		$section_violations = array_filter(
			$violations,
			function ( $v ) {
				return ( $v['scope'] ?? '' ) === 'section';
			}
		);
		$page_violations    = array_filter(
			$violations,
			function ( $v ) {
				return ( $v['scope'] ?? '' ) === 'page';
			}
		);

		$section_by_code_summary = array();
		foreach ( $section_violations as $v ) {
			$code                             = $v['rule_code'] ?? 'unknown';
			$section_by_code_summary[ $code ] = ( $section_by_code_summary[ $code ] ?? 0 ) + 1;
		}
		$page_by_code_summary = array();
		foreach ( $page_violations as $v ) {
			$code                          = $v['rule_code'] ?? 'unknown';
			$page_by_code_summary[ $code ] = ( $page_by_code_summary[ $code ] ?? 0 ) + 1;
		}

		$section_audit_summary = array(
			'audited'      => count( $sections ),
			'violations'   => count( $section_violations ),
			'by_rule_code' => $section_by_code_summary,
		);
		$page_audit_summary    = array(
			'audited'      => count( $pages ),
			'violations'   => count( $page_violations ),
			'by_rule_code' => $page_by_code_summary,
		);

		$human_review_required = array(
			'Heading hierarchy in rendered output (single h1, no skip).',
			'Landmark presence (main, nav) in rendered output.',
			'CTA visible text and image-alt in rendered output.',
			'List, table, accordion, and form semantics in rendered output.',
			'Color contrast and focus styling (token/admin).',
		);

		$passed = count( $violations ) === 0;

		return new Template_Accessibility_Audit_Result(
			$passed,
			array_values( $violations ),
			$section_audit_summary,
			$page_audit_summary,
			$human_review_required
		);
	}

	/**
	 * @param array<string, mixed>                                                                 $def
	 * @param list<array{scope: string, template_key: string, rule_code: string, message: string}> $violations
	 */
	private function audit_section( array $def, array &$violations ): void {
		$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		if ( $key === '' ) {
			return;
		}

		$a11y               = $def['accessibility_warnings_or_enhancements'] ?? null;
		$a11y_empty         = ( $a11y === null || $a11y === '' || ( is_string( $a11y ) && trim( (string) $a11y ) === '' ) );
		$cta_classification = (string) ( $def['cta_classification'] ?? '' );
		$is_cta             = in_array( $cta_classification, self::CTA_CLASSIFIED, true );

		if ( $is_cta && $a11y_empty ) {
			$violations[] = array(
				'scope'        => 'section',
				'template_key' => $key,
				'rule_code'    => 'cta_clarity_marker_missing',
				'message'      => 'CTA-classified section must declare CTA clarity or accessibility expectations.',
			);
		} elseif ( ! $is_cta && $a11y_empty ) {
			$violations[] = array(
				'scope'        => 'section',
				'template_key' => $key,
				'rule_code'    => 'accessibility_expectations_missing',
				'message'      => 'Section must declare accessibility warnings or enhancements (spec §12.12).',
			);
		}

		$family = (string) ( $def['section_purpose_family'] ?? '' );
		if ( $family !== '' && in_array( $family, self::HEADING_RELEVANT_FAMILIES, true ) ) {
			$hints       = $def['hierarchy_role_hints'] ?? null;
			$hints_empty = ( $hints === null || $hints === '' || ( is_array( $hints ) && empty( $hints ) ) );
			if ( $hints_empty ) {
				$violations[] = array(
					'scope'        => 'section',
					'template_key' => $key,
					'rule_code'    => 'heading_role_undeclared',
					'message'      => sprintf( 'Section with purpose family "%s" should declare heading role (hierarchy_role_hints).', $family ),
				);
			}
		}
	}

	/**
	 * @param array<string, mixed>                                                                 $page_def
	 * @param array<string, array<string, mixed>>                                                  $section_by_key
	 * @param list<array{scope: string, template_key: string, rule_code: string, message: string}> $violations
	 */
	private function audit_page_cta_rules( array $page_def, array $section_by_key, array &$violations ): void {
		$template_key = (string) ( $page_def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$class        = (string) ( $page_def['template_category_class'] ?? '' );
		$min_cta      = self::CTA_MIN_BY_CLASS[ $class ] ?? 3;

		$ordered = $page_def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		if ( ! is_array( $ordered ) || empty( $ordered ) ) {
			return;
		}

		usort(
			$ordered,
			function ( $a, $b ) {
				$pa = isset( $a[ Page_Template_Schema::SECTION_ITEM_POSITION ] ) ? (int) $a[ Page_Template_Schema::SECTION_ITEM_POSITION ] : 0;
				$pb = isset( $b[ Page_Template_Schema::SECTION_ITEM_POSITION ] ) ? (int) $b[ Page_Template_Schema::SECTION_ITEM_POSITION ] : 0;
				return $pa <=> $pb;
			}
		);

		$cta_flags = array();
		foreach ( $ordered as $item ) {
			$sk          = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			$sec         = $section_by_key[ $sk ] ?? null;
			$cta_flags[] = $sec !== null && $this->is_cta_classified( $sec );
		}

		$cta_count     = (int) array_sum( array_map( 'intval', $cta_flags ) );
		$non_cta_count = count( $cta_flags ) - $cta_count;

		if ( $cta_count < $min_cta ) {
			$violations[] = array(
				'scope'        => 'page',
				'template_key' => $template_key,
				'rule_code'    => 'cta_count_below_minimum',
				'message'      => sprintf( 'Template %s (class %s): %d CTA sections, minimum %d.', $template_key, $class, $cta_count, $min_cta ),
			);
		}
		if ( $non_cta_count < self::NON_CTA_MIN ) {
			$violations[] = array(
				'scope'        => 'page',
				'template_key' => $template_key,
				'rule_code'    => 'non_cta_count_below_minimum',
				'message'      => sprintf( 'Template %s: %d non-CTA sections, minimum %d.', $template_key, $non_cta_count, self::NON_CTA_MIN ),
			);
		}
		if ( $non_cta_count > self::NON_CTA_MAX ) {
			$violations[] = array(
				'scope'        => 'page',
				'template_key' => $template_key,
				'rule_code'    => 'non_cta_count_above_max',
				'message'      => sprintf( 'Template %s: %d non-CTA sections, max %d (warning).', $template_key, $non_cta_count, self::NON_CTA_MAX ),
			);
		}

		$last = end( $cta_flags );
		if ( count( $cta_flags ) > 0 && ! $last ) {
			$violations[] = array(
				'scope'        => 'page',
				'template_key' => $template_key,
				'rule_code'    => 'bottom_cta_missing',
				'message'      => 'Last section is not CTA-classified.',
			);
		}

		for ( $i = 0; $i < count( $cta_flags ) - 1; $i++ ) {
			if ( $cta_flags[ $i ] && $cta_flags[ $i + 1 ] ) {
				$violations[] = array(
					'scope'        => 'page',
					'template_key' => $template_key,
					'rule_code'    => 'adjacent_cta_violation',
					'message'      => sprintf( 'Adjacent CTA sections at positions %d–%d.', $i, $i + 1 ),
				);
			}
		}
	}

	/** @param array<string, mixed> $section_def */
	private function is_cta_classified( array $section_def ): bool {
		$v = (string) ( $section_def['cta_classification'] ?? '' );
		return in_array( $v, self::CTA_CLASSIFIED, true );
	}
}
