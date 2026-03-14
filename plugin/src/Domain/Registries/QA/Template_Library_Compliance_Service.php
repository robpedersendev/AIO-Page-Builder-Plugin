<?php
/**
 * Automated template library compliance pass (Prompt 176).
 * Validates section/page counts, category coverage, CTA sequencing, preview/one-pager, metadata, and export.
 * Authority: template-library-coverage-matrix, cta-sequencing-and-placement-contract, template-library-compliance-matrix.
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
 * Runs library-wide validation and returns a stable compliance result.
 */
final class Template_Library_Compliance_Service {

	private const SECTION_TARGET = 250;
	private const PAGE_TARGET    = 500;

	/** Section purpose family minimums (template-library-coverage-matrix §2.2). */
	private const SECTION_FAMILY_MIN = array(
		'hero'       => 12,
		'proof'      => 18,
		'offer'      => 14,
		'explainer'  => 14,
		'legal'      => 6,
		'utility'    => 8,
		'listing'    => 14,
		'comparison' => 8,
		'contact'    => 8,
		'cta'        => 20,
		'faq'        => 10,
		'profile'    => 8,
		'stats'      => 8,
		'timeline'   => 6,
		'related'    => 8,
	);

	/** Page template_category_class minimums (template-library-coverage-matrix §3.2). */
	private const PAGE_CLASS_MIN = array(
		'top_level'     => 80,
		'hub'           => 120,
		'nested_hub'    => 100,
		'child_detail'  => 200,
	);

	/** Min CTA sections per page class (cta-sequencing-and-placement-contract §3). */
	private const CTA_MIN_BY_CLASS = array(
		'top_level'    => 3,
		'hub'          => 4,
		'nested_hub'   => 4,
		'child_detail' => 5,
	);

	private const NON_CTA_MIN = 8;
	private const NON_CTA_MAX = 14;

	/** CTA-classified values (contract §2.1). */
	private const CTA_CLASSIFIED = array( 'primary_cta', 'contact_cta', 'navigation_cta' );

	private const MAX_SECTION_FAMILY_SHARE = 0.25;
	private const MAX_SECTION_CATEGORY_SHARE = 0.28;
	private const MAX_PAGE_CLASS_SHARE = 0.45;
	private const MAX_PAGE_FAMILY_SHARE = 0.22;

	/** Allowed animation tiers (animation-support-and-fallback-contract). */
	private const ALLOWED_ANIMATION_TIERS = array( 'none', 'subtle', 'enhanced', 'premium' );

	private const LIBRARY_CAP = 2000;

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
	 * Runs full compliance pass; returns result for machine and human reporting.
	 */
	public function run(): Template_Library_Compliance_Result {
		$sections = $this->section_repository->list_all_definitions_capped( self::LIBRARY_CAP );
		$pages    = $this->page_repository->list_all_definitions_capped( self::LIBRARY_CAP );

		$section_by_key = array();
		foreach ( $sections as $def ) {
			$k = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			if ( $k !== '' ) {
				$section_by_key[ $k ] = $def;
			}
		}

		$count_summary            = $this->build_count_summary( $sections, $pages );
		$category_coverage_summary = $this->build_category_coverage( $count_summary, $sections, $pages );
		$cta_rule_violations      = $this->validate_cta_rules( $pages, $section_by_key );
		$preview_readiness        = $this->check_preview_readiness( $sections, $pages );
		$metadata_checks         = $this->check_metadata( $sections );
		$export_viability         = $this->check_export( $sections, $pages );

		$passed = $this->compute_passed(
			$count_summary,
			$category_coverage_summary,
			$cta_rule_violations,
			$preview_readiness,
			$metadata_checks,
			$export_viability
		);

		return new Template_Library_Compliance_Result(
			$count_summary,
			$category_coverage_summary,
			$cta_rule_violations,
			$preview_readiness,
			$metadata_checks,
			$export_viability,
			$passed
		);
	}

	/**
	 * @param list<array<string, mixed>> $sections
	 * @param list<array<string, mixed>> $pages
	 * @return array{section_total: int, page_total: int, section_target: int, page_target: int, by_section_purpose_family: array<string, int>, by_page_category_class: array<string, int>, by_page_family: array<string, int>}
	 */
	private function build_count_summary( array $sections, array $pages ): array {
		$by_section_family = array();
		foreach ( $sections as $def ) {
			$fam = (string) ( $def['section_purpose_family'] ?? '' );
			if ( $fam === '' ) {
				$fam = 'other';
			}
			$by_section_family[ $fam ] = ( $by_section_family[ $fam ] ?? 0 ) + 1;
		}

		$by_page_class = array();
		$by_page_family = array();
		foreach ( $pages as $def ) {
			$cls = (string) ( $def['template_category_class'] ?? '' );
			if ( $cls !== '' ) {
				$by_page_class[ $cls ] = ( $by_page_class[ $cls ] ?? 0 ) + 1;
			}
			$fam = (string) ( $def['template_family'] ?? '' );
			if ( $fam !== '' ) {
				$by_page_family[ $fam ] = ( $by_page_family[ $fam ] ?? 0 ) + 1;
			}
		}

		return array(
			'section_total'             => count( $sections ),
			'page_total'                => count( $pages ),
			'section_target'            => self::SECTION_TARGET,
			'page_target'               => self::PAGE_TARGET,
			'by_section_purpose_family' => $by_section_family,
			'by_page_category_class'    => $by_page_class,
			'by_page_family'            => $by_page_family,
		);
	}

	/**
	 * @param array $count_summary
	 * @param list<array<string, mixed>> $sections
	 * @param list<array<string, mixed>> $pages
	 * @return array{section_family_minimums: array<string, bool>, page_class_minimums: array<string, bool>, max_share_violations: list<string>}
	 */
	private function build_category_coverage( array $count_summary, array $sections, array $pages ): array {
		$section_family_minimums = array();
		foreach ( self::SECTION_FAMILY_MIN as $fam => $min ) {
			$count = $count_summary['by_section_purpose_family'][ $fam ] ?? 0;
			$section_family_minimums[ $fam ] = $count >= $min;
		}

		$page_class_minimums = array();
		foreach ( self::PAGE_CLASS_MIN as $cls => $min ) {
			$count = $count_summary['by_page_category_class'][ $cls ] ?? 0;
			$page_class_minimums[ $cls ] = $count >= $min;
		}

		$max_share_violations = array();
		$section_total = $count_summary['section_total'];
		$page_total    = $count_summary['page_total'];

		if ( $section_total > 0 ) {
			foreach ( $count_summary['by_section_purpose_family'] as $fam => $cnt ) {
				if ( $cnt / $section_total > self::MAX_SECTION_FAMILY_SHARE ) {
					$max_share_violations[] = "section_family:{$fam}";
				}
			}
			$by_cat = array();
			foreach ( $sections as $def ) {
				$c = (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' );
				if ( $c !== '' ) {
					$by_cat[ $c ] = ( $by_cat[ $c ] ?? 0 ) + 1;
				}
			}
			foreach ( $by_cat as $cat => $cnt ) {
				if ( $cnt / $section_total > self::MAX_SECTION_CATEGORY_SHARE ) {
					$max_share_violations[] = "section_category:{$cat}";
				}
			}
		}

		if ( $page_total > 0 ) {
			foreach ( $count_summary['by_page_category_class'] as $cls => $cnt ) {
				if ( $cnt / $page_total > self::MAX_PAGE_CLASS_SHARE ) {
					$max_share_violations[] = "page_class:{$cls}";
				}
			}
			foreach ( $count_summary['by_page_family'] as $fam => $cnt ) {
				if ( $cnt / $page_total > self::MAX_PAGE_FAMILY_SHARE ) {
					$max_share_violations[] = "page_family:{$fam}";
				}
			}
		}

		return array(
			'section_family_minimums' => $section_family_minimums,
			'page_class_minimums'    => $page_class_minimums,
			'max_share_violations'   => $max_share_violations,
		);
	}

	/**
	 * @param list<array<string, mixed>> $pages
	 * @param array<string, array<string, mixed>> $section_by_key
	 * @return list<array{template_key: string, code: string, message: string}>
	 */
	private function validate_cta_rules( array $pages, array $section_by_key ): array {
		$violations = array();
		foreach ( $pages as $page_def ) {
			$template_key = (string) ( $page_def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$class = (string) ( $page_def['template_category_class'] ?? '' );
			$min_cta = self::CTA_MIN_BY_CLASS[ $class ] ?? 3;

			$ordered = $page_def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			if ( ! is_array( $ordered ) || empty( $ordered ) ) {
				continue;
			}

			usort( $ordered, function ( $a, $b ) {
				$pa = isset( $a[ Page_Template_Schema::SECTION_ITEM_POSITION ] ) ? (int) $a[ Page_Template_Schema::SECTION_ITEM_POSITION ] : 0;
				$pb = isset( $b[ Page_Template_Schema::SECTION_ITEM_POSITION ] ) ? (int) $b[ Page_Template_Schema::SECTION_ITEM_POSITION ] : 0;
				return $pa <=> $pb;
			} );

			$cta_flags = array();
			foreach ( $ordered as $item ) {
				$sk = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
				$sec = $section_by_key[ $sk ] ?? null;
				$cta_flags[] = $sec !== null && $this->is_cta_classified( $sec );
			}

			$cta_count = (int) array_sum( array_map( 'intval', $cta_flags ) );
			$non_cta_count = count( $cta_flags ) - $cta_count;

			if ( $cta_count < $min_cta ) {
				$violations[] = array(
					'template_key' => $template_key,
					'code'         => 'cta_count_below_minimum',
					'message'      => sprintf( 'Template %s (class %s): %d CTA sections, minimum %d.', $template_key, $class, $cta_count, $min_cta ),
				);
			}
			if ( $non_cta_count < self::NON_CTA_MIN ) {
				$violations[] = array(
					'template_key' => $template_key,
					'code'         => 'non_cta_count_below_minimum',
					'message'      => sprintf( 'Template %s: %d non-CTA sections, minimum %d.', $template_key, $non_cta_count, self::NON_CTA_MIN ),
				);
			}
			if ( $non_cta_count > self::NON_CTA_MAX ) {
				$violations[] = array(
					'template_key' => $template_key,
					'code'         => 'non_cta_count_above_max',
					'message'      => sprintf( 'Template %s: %d non-CTA sections, max %d (warning).', $template_key, $non_cta_count, self::NON_CTA_MAX ),
				);
			}

			$last = end( $cta_flags );
			if ( count( $cta_flags ) > 0 && ! $last ) {
				$violations[] = array(
					'template_key' => $template_key,
					'code'         => 'bottom_cta_missing',
					'message'      => 'Template ' . $template_key . ': last section is not CTA-classified.',
				);
			}

			for ( $i = 0; $i < count( $cta_flags ) - 1; $i++ ) {
				if ( $cta_flags[ $i ] && $cta_flags[ $i + 1 ] ) {
					$violations[] = array(
						'template_key' => $template_key,
						'code'         => 'adjacent_cta_violation',
						'message'      => sprintf( 'Template %s: adjacent CTA sections at positions %d–%d.', $template_key, $i, $i + 1 ),
					);
				}
			}
		}
		return $violations;
	}

	private function is_cta_classified( array $section_def ): bool {
		$v = (string) ( $section_def['cta_classification'] ?? '' );
		return in_array( $v, self::CTA_CLASSIFIED, true );
	}

	/**
	 * @param list<array<string, mixed>> $sections
	 * @param list<array<string, mixed>> $pages
	 * @return array{sections_missing_preview: list<string>, pages_missing_one_pager: list<string>}
	 */
	private function check_preview_readiness( array $sections, array $pages ): array {
		$sections_missing = array();
		foreach ( $sections as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			if ( $key === '' ) {
				continue;
			}
			$preview_defaults = $def['preview_defaults'] ?? null;
			$has_preview = ( is_array( $preview_defaults ) && ! empty( $preview_defaults ) )
				|| ( (string) ( $def['preview_image_ref'] ?? '' ) ) !== ''
				|| ( (string) ( $def['preview_description'] ?? '' ) ) !== '';
			if ( ! $has_preview ) {
				$sections_missing[] = $key;
			}
		}

		$pages_missing = array();
		foreach ( $pages as $def ) {
			$key = (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$one_pager = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? null;
			if ( $key !== '' && ( $one_pager === null || ! is_array( $one_pager ) ) ) {
				$pages_missing[] = $key;
			}
		}
		return array(
			'sections_missing_preview' => $sections_missing,
			'pages_missing_one_pager'  => $pages_missing,
		);
	}

	/**
	 * @param list<array<string, mixed>> $sections
	 * @return array{sections_missing_accessibility: list<string>, sections_invalid_animation: list<string>}
	 */
	private function check_metadata( array $sections ): array {
		$missing_a11y = array();
		$invalid_anim = array();
		foreach ( $sections as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			if ( $key === '' ) {
				continue;
			}
			$a11y = $def['accessibility_warnings_or_enhancements'] ?? null;
			if ( $a11y === null || $a11y === '' ) {
				$missing_a11y[] = $key;
			}
			$tier = (string) ( $def['animation_tier'] ?? 'none' );
			if ( ! in_array( $tier, self::ALLOWED_ANIMATION_TIERS, true ) ) {
				$invalid_anim[] = $key;
			}
		}
		return array(
			'sections_missing_accessibility' => $missing_a11y,
			'sections_invalid_animation'     => $invalid_anim,
		);
	}

	/**
	 * @param list<array<string, mixed>> $sections
	 * @param list<array<string, mixed>> $pages
	 * @return array{viable: bool, errors: list<string>}
	 */
	private function check_export( array $sections, array $pages ): array {
		$errors = array();
		foreach ( $sections as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$json = wp_json_encode( $def );
			if ( $json === false ) {
				$errors[] = "section:{$key}: encode failed";
			} else {
				$decoded = json_decode( $json, true );
				if ( ! is_array( $decoded ) ) {
					$errors[] = "section:{$key}: decode failed";
				}
			}
		}
		foreach ( $pages as $def ) {
			$key = (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$json = wp_json_encode( $def );
			if ( $json === false ) {
				$errors[] = "page:{$key}: encode failed";
			} else {
				$decoded = json_decode( $json, true );
				if ( ! is_array( $decoded ) ) {
					$errors[] = "page:{$key}: decode failed";
				}
			}
		}
		return array(
			'viable' => empty( $errors ),
			'errors' => $errors,
		);
	}

	private function compute_passed(
		array $count_summary,
		array $category_coverage_summary,
		array $cta_rule_violations,
		array $preview_readiness,
		array $metadata_checks,
		array $export_viability
	): bool {
		if ( ( $count_summary['section_total'] ?? 0 ) < self::SECTION_TARGET ) {
			return false;
		}
		if ( ( $count_summary['page_total'] ?? 0 ) < self::PAGE_TARGET ) {
			return false;
		}
		foreach ( $category_coverage_summary['section_family_minimums'] ?? array() as $met ) {
			if ( ! $met ) {
				return false;
			}
		}
		foreach ( $category_coverage_summary['page_class_minimums'] ?? array() as $met ) {
			if ( ! $met ) {
				return false;
			}
		}
		if ( ! empty( $category_coverage_summary['max_share_violations'] ?? array() ) ) {
			return false;
		}
		$hard_cta = array_filter( $cta_rule_violations, function ( $v ) {
			return ( $v['code'] ?? '' ) !== 'non_cta_count_above_max';
		} );
		if ( ! empty( $hard_cta ) ) {
			return false;
		}
		if ( ! empty( $preview_readiness['sections_missing_preview'] ?? array() ) ) {
			return false;
		}
		if ( ! empty( $preview_readiness['pages_missing_one_pager'] ?? array() ) ) {
			return false;
		}
		if ( ! empty( $metadata_checks['sections_missing_accessibility'] ?? array() ) ) {
			return false;
		}
		if ( ! empty( $metadata_checks['sections_invalid_animation'] ?? array() ) ) {
			return false;
		}
		if ( ! ( $export_viability['viable'] ?? true ) ) {
			return false;
		}
		return true;
	}
}
