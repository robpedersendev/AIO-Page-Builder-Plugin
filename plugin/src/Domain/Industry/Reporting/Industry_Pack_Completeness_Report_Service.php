<?php
/**
 * Internal completeness report generator for industry packs and subtypes (Prompt 520).
 * Scores packs/subtypes against the completeness model and produces structured summaries of
 * missing assets, weak areas, and likely release blockers. Advisory only; no mutation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry;

/**
 * Applies completeness scoring model to current packs and subtypes; produces grouped report.
 */
final class Industry_Pack_Completeness_Report_Service {

	public const DIMENSION_PACK         = 'pack_definition';
	public const DIMENSION_BUNDLE       = 'starter_bundle';
	public const DIMENSION_OVERLAYS     = 'overlays';
	public const DIMENSION_RULES        = 'rules';
	public const DIMENSION_DOCS         = 'docs';
	public const DIMENSION_QA           = 'qa_evidence';
	public const DIMENSION_SUBTYPE      = 'subtype';
	public const DIMENSION_GOAL_SUPPORT = 'goal_support';

	public const BAND_MINIMAL_VIABLE = 'minimal_viable';
	public const BAND_STRONG         = 'strong';
	public const BAND_RELEASE_GRADE  = 'release_grade';
	public const BAND_BELOW_MINIMAL  = 'below_minimal';

	/** Threshold: total score for minimal_viable (core dimensions must also be >= 1). */
	private const THRESHOLD_MINIMAL = 4;
	/** Threshold: total score for strong. */
	private const THRESHOLD_STRONG = 10;
	/** Threshold: total score for release_grade; QA must be >= 1 and no core at 0. */
	private const THRESHOLD_RELEASE = 14;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Starter_Bundle_Registry|null */
	private $bundle_registry;

	/** @var Industry_Section_Helper_Overlay_Registry|null */
	private $section_overlay_registry;

	/** @var Industry_Page_OnePager_Overlay_Registry|null */
	private $page_overlay_registry;

	/** @var Industry_CTA_Pattern_Registry|null */
	private $cta_registry;

	/** @var Industry_SEO_Guidance_Registry|null */
	private $seo_registry;

	/** @var Industry_Style_Preset_Registry|null */
	private $preset_registry;

	/** @var Industry_LPagery_Rule_Registry|null */
	private $lpagery_registry;

	/** @var Industry_Compliance_Rule_Registry|null */
	private $compliance_registry;

	/** @var Industry_Subtype_Registry|null */
	private $subtype_registry;

	/** @var Industry_Health_Check_Service|null */
	private $health_check;

	public function __construct(
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Starter_Bundle_Registry $bundle_registry = null,
		?Industry_Section_Helper_Overlay_Registry $section_overlay_registry = null,
		?Industry_Page_OnePager_Overlay_Registry $page_overlay_registry = null,
		?Industry_CTA_Pattern_Registry $cta_registry = null,
		?Industry_SEO_Guidance_Registry $seo_registry = null,
		?Industry_Style_Preset_Registry $preset_registry = null,
		?Industry_LPagery_Rule_Registry $lpagery_registry = null,
		?Industry_Compliance_Rule_Registry $compliance_registry = null,
		?Industry_Subtype_Registry $subtype_registry = null,
		?Industry_Health_Check_Service $health_check = null
	) {
		$this->pack_registry            = $pack_registry;
		$this->bundle_registry          = $bundle_registry;
		$this->section_overlay_registry = $section_overlay_registry;
		$this->page_overlay_registry    = $page_overlay_registry;
		$this->cta_registry             = $cta_registry;
		$this->seo_registry             = $seo_registry;
		$this->preset_registry          = $preset_registry;
		$this->lpagery_registry         = $lpagery_registry;
		$this->compliance_registry      = $compliance_registry;
		$this->subtype_registry         = $subtype_registry;
		$this->health_check             = $health_check;
	}

	/**
	 * Generates completeness report for active packs and optionally per-subtype.
	 *
	 * @param bool $include_subtypes When true, score each subtype scope as well.
	 * @return array{
	 *   pack_results: list<array{
	 *     pack_key: string,
	 *     subtype_key: string,
	 *     dimension_scores: array<string, int>,
	 *     total: int,
	 *     band: string,
	 *     missing_assets: list<string>,
	 *     blocker_flags: list<string>,
	 *     notes: list<string>
	 *   }>,
	 *   summary: array{pack_count: int, subtype_count: int, release_grade_count: int, strong_count: int, minimal_count: int, below_minimal_count: int}
	 * }
	 */
	public function generate_report( bool $include_subtypes = true ): array {
		$pack_results         = array();
		$health_errors_by_key = $this->get_health_errors_by_pack();

		if ( $this->pack_registry === null ) {
			return array(
				'pack_results' => array(),
				'summary'      => $this->make_summary( $pack_results ),
			);
		}

		$packs = $this->pack_registry->list_by_status( Industry_Pack_Schema::STATUS_ACTIVE );
		foreach ( $packs as $pack ) {
			$industry_key = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				? trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $industry_key === '' ) {
				continue;
			}
			$pack_results[] = $this->score_scope( $industry_key, '', $pack, $health_errors_by_key );
		}

		if ( $include_subtypes && $this->subtype_registry !== null ) {
			$all_subtypes = $this->subtype_registry->get_all();
			foreach ( $all_subtypes as $sub ) {
				$subtype_key = trim( (string) ( $sub[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] ?? '' ) );
				$parent_key  = trim( (string) ( $sub[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ?? '' ) );
				if ( $subtype_key === '' || $parent_key === '' ) {
					continue;
				}
				$pack           = $this->pack_registry->get( $parent_key );
				$pack_results[] = $this->score_scope( $parent_key, $subtype_key, $pack, $health_errors_by_key );
			}
		}

		return array(
			'pack_results' => $pack_results,
			'summary'      => $this->make_summary( $pack_results ),
		);
	}

	/**
	 * @param array<string, mixed>|null $pack
	 * @param array<string, bool>       $health_errors_by_key Pack/subtype keys that have health errors.
	 * @return array{pack_key: string, subtype_key: string, dimension_scores: array<string, int>, total: int, band: string, missing_assets: list<string>, blocker_flags: list<string>, notes: list<string>}
	 */
	private function score_scope( string $industry_key, string $subtype_key, ?array $pack, array $health_errors_by_key ): array {
		$scope_key      = $subtype_key !== '' ? $industry_key . '|' . $subtype_key : $industry_key;
		$dimensions     = array(
			self::DIMENSION_PACK         => 0,
			self::DIMENSION_BUNDLE       => 0,
			self::DIMENSION_OVERLAYS     => 0,
			self::DIMENSION_RULES        => 0,
			self::DIMENSION_DOCS         => 0,
			self::DIMENSION_QA           => 0,
			self::DIMENSION_SUBTYPE      => -1, // N/A unless pack has subtypes
			self::DIMENSION_GOAL_SUPPORT => -1,
		);
		$missing_assets = array();
		$blocker_flags  = array();
		$notes          = array();

		// Pack definition (industry-level only for pack dimension; subtype row reuses parent pack refs).
		if ( $subtype_key === '' && is_array( $pack ) ) {
			$status  = trim( (string) ( $pack[ Industry_Pack_Schema::FIELD_STATUS ] ?? '' ) );
			$version = $pack[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] ?? null;
			$name    = trim( (string) ( $pack[ Industry_Pack_Schema::FIELD_NAME ] ?? '' ) );
			if ( $status !== Industry_Pack_Schema::STATUS_ACTIVE ) {
				$dimensions[ self::DIMENSION_PACK ] = 0;
				$missing_assets[]                   = 'pack not active';
				$blocker_flags[]                    = 'pack_status_not_active';
			} elseif ( $name === '' ) {
				$dimensions[ self::DIMENSION_PACK ] = 1;
				$notes[]                            = 'Pack name placeholder';
			} else {
				$refs_ok = $this->pack_refs_resolve( $pack );
				if ( $refs_ok ) {
					$dimensions[ self::DIMENSION_PACK ] = $version !== null && $version !== '' ? 3 : 2;
				} else {
					$dimensions[ self::DIMENSION_PACK ] = 1;
					$missing_assets[]                   = 'pack refs unresolved';
					$blocker_flags[]                    = 'pack_refs_broken';
				}
			}
		} elseif ( $subtype_key !== '' ) {
			$dimensions[ self::DIMENSION_PACK ] = -1; // N/A for subtype row
		}

		// Starter bundle.
		if ( $this->bundle_registry !== null ) {
			$bundles        = $this->bundle_registry->get_for_industry( $industry_key, $subtype_key );
			$active_bundles = array_filter(
				$bundles,
				static function ( $b ) {
					return ( $b[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ?? '' ) === Industry_Starter_Bundle_Registry::STATUS_ACTIVE;
				}
			);
			if ( count( $active_bundles ) === 0 ) {
				$dimensions[ self::DIMENSION_BUNDLE ] = 0;
				$missing_assets[]                     = 'starter_bundle';
				$blocker_flags[]                      = 'no_starter_bundle';
			} else {
				$dimensions[ self::DIMENSION_BUNDLE ] = count( $active_bundles ) >= 1 ? 2 : 1;
			}
		} else {
			$dimensions[ self::DIMENSION_BUNDLE ] = 0;
		}

		// Overlays (section + page; industry-level counts).
		$section_count = 0;
		$page_count    = 0;
		if ( $this->section_overlay_registry !== null ) {
			$section_count = count( $this->section_overlay_registry->get_for_industry( $industry_key ) );
		}
		if ( $this->page_overlay_registry !== null ) {
			$page_count = count( $this->page_overlay_registry->get_for_industry( $industry_key ) );
		}
		if ( $section_count === 0 && $page_count === 0 ) {
			$dimensions[ self::DIMENSION_OVERLAYS ] = 0;
			$missing_assets[]                       = 'overlays';
		} elseif ( $section_count > 0 && $page_count > 0 ) {
			$dimensions[ self::DIMENSION_OVERLAYS ] = 2;
		} else {
			$dimensions[ self::DIMENSION_OVERLAYS ] = 1;
		}

		// Rules (CTA, SEO, LPagery, compliance) — pack-level when industry scope.
		if ( $subtype_key === '' && is_array( $pack ) ) {
			$refs_ok                             = $this->pack_refs_resolve( $pack );
			$dimensions[ self::DIMENSION_RULES ] = $refs_ok ? 2 : ( $this->pack_has_any_refs( $pack ) ? 1 : 0 );
			if ( ! $refs_ok && $this->pack_has_any_refs( $pack ) ) {
				$missing_assets[] = 'rules_refs_unresolved';
			}
		} else {
			$dimensions[ self::DIMENSION_RULES ] = 1; // Subtype inherits parent rules
		}

		// Docs: assume 1 (catalog exists in codebase); no runtime catalog check.
		$dimensions[ self::DIMENSION_DOCS ] = 1;

		// QA evidence: 1 if no health errors for this scope (pack or parent pack), else 0.
		$health_key                       = $subtype_key !== '' ? $industry_key : $scope_key;
		$has_health_error                 = isset( $health_errors_by_key[ $health_key ] ) && $health_errors_by_key[ $health_key ];
		$dimensions[ self::DIMENSION_QA ] = $has_health_error ? 0 : 1;
		if ( $has_health_error ) {
			$blocker_flags[] = 'health_errors';
		}

		// Subtype dimension: N/A for subtype row; for pack row, 1 if pack has subtypes and they are present.
		if ( $subtype_key !== '' ) {
			$dimensions[ self::DIMENSION_SUBTYPE ] = -1;
		} elseif ( $this->subtype_registry !== null ) {
			$subtypes_for_pack = array_filter(
				$this->subtype_registry->get_all(),
				static function ( $s ) use ( $industry_key ) {
					return trim( (string) ( $s[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ?? '' ) ) === $industry_key;
				}
			);
			if ( count( $subtypes_for_pack ) === 0 ) {
				$dimensions[ self::DIMENSION_SUBTYPE ] = -1;
			} else {
				$dimensions[ self::DIMENSION_SUBTYPE ] = 1;
			}
		}

		// Goal support: optional; 1 if no broken goal refs, 0 or -1.
		$dimensions[ self::DIMENSION_GOAL_SUPPORT ] = -1; // N/A unless goal overlays checked; leave as -1.

		$scoreable = array_filter(
			$dimensions,
			static function ( $v ) {
				return $v >= 0;
			}
		);
		$total     = array_sum( $scoreable );
		$band      = $this->compute_band( $dimensions, $total, $blocker_flags );

		return array(
			'pack_key'         => $industry_key,
			'subtype_key'      => $subtype_key,
			'dimension_scores' => $dimensions,
			'total'            => $total,
			'band'             => $band,
			'missing_assets'   => $missing_assets,
			'blocker_flags'    => $blocker_flags,
			'notes'            => $notes,
		);
	}

	/**
	 * @param array<string, int> $dimensions
	 * @param list<string>       $blocker_flags
	 */
	private function compute_band( array $dimensions, int $total, array $blocker_flags ): string {
		$pack_score   = $dimensions[ self::DIMENSION_PACK ] ?? 0;
		$bundle_score = $dimensions[ self::DIMENSION_BUNDLE ] ?? 0;
		$qa_score     = $dimensions[ self::DIMENSION_QA ] ?? 0;

		if ( $pack_score === 0 || $bundle_score === 0 ) {
			return self::BAND_BELOW_MINIMAL;
		}
		if ( $total < self::THRESHOLD_MINIMAL ) {
			return self::BAND_BELOW_MINIMAL;
		}
		if ( $total >= self::THRESHOLD_RELEASE && $qa_score >= 1 && empty( $blocker_flags ) ) {
			return self::BAND_RELEASE_GRADE;
		}
		if ( $total >= self::THRESHOLD_STRONG ) {
			return self::BAND_STRONG;
		}
		return self::BAND_MINIMAL_VIABLE;
	}

	private function pack_refs_resolve( array $pack ): bool {
		$preset_ref = trim( (string) ( $pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] ?? '' ) );
		if ( $preset_ref !== '' && $this->preset_registry !== null && $this->preset_registry->get( $preset_ref ) === null ) {
			return false;
		}
		$seo_ref = trim( (string) ( $pack[ Industry_Pack_Schema::FIELD_SEO_GUIDANCE_REF ] ?? '' ) );
		if ( $seo_ref !== '' && $this->seo_registry !== null && $this->seo_registry->get( $seo_ref ) === null ) {
			return false;
		}
		$lpagery_ref = trim( (string) ( $pack[ Industry_Pack_Schema::FIELD_LPAGERY_RULE_REF ] ?? '' ) );
		if ( $lpagery_ref !== '' && $this->lpagery_registry !== null && $this->lpagery_registry->get( $lpagery_ref ) === null ) {
			return false;
		}
		$cta_refs = $pack[ Industry_Pack_Schema::FIELD_PREFERRED_CTA_PATTERNS ] ?? $pack[ Industry_Pack_Schema::FIELD_DEFAULT_CTA_PATTERNS ] ?? array();
		if ( is_array( $cta_refs ) && $this->cta_registry !== null ) {
			foreach ( $cta_refs as $key ) {
				if ( is_string( $key ) && trim( $key ) !== '' && $this->cta_registry->get( trim( $key ) ) === null ) {
					return false;
				}
			}
		}
		return true;
	}

	private function pack_has_any_refs( array $pack ): bool {
		return trim( (string) ( $pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] ?? '' ) ) !== ''
			|| trim( (string) ( $pack[ Industry_Pack_Schema::FIELD_SEO_GUIDANCE_REF ] ?? '' ) ) !== ''
			|| trim( (string) ( $pack[ Industry_Pack_Schema::FIELD_LPAGERY_RULE_REF ] ?? '' ) ) !== ''
			|| ( is_array( $pack[ Industry_Pack_Schema::FIELD_PREFERRED_CTA_PATTERNS ] ?? null ) && count( $pack[ Industry_Pack_Schema::FIELD_PREFERRED_CTA_PATTERNS ] ) > 0 );
	}

	/** @return array<string, bool> Keys scope (pack_key or pack_key|subtype_key), value true if has health error. */
	private function get_health_errors_by_pack(): array {
		$out = array();
		if ( $this->health_check === null ) {
			return $out;
		}
		$result = $this->health_check->run();
		$errors = isset( $result['errors'] ) && is_array( $result['errors'] ) ? $result['errors'] : array();
		foreach ( $errors as $issue ) {
			$key         = isset( $issue['key'] ) && is_string( $issue['key'] ) ? trim( $issue['key'] ) : '';
			$object_type = isset( $issue['object_type'] ) && is_string( $issue['object_type'] ) ? trim( $issue['object_type'] ) : '';
			if ( $key !== '' ) {
				if ( $object_type === Industry_Health_Check_Service::OBJECT_TYPE_PACK ) {
					$out[ $key ] = true;
				} elseif ( $object_type === Industry_Health_Check_Service::OBJECT_TYPE_PROFILE ) {
					$out['profile'] = true;
				}
			}
		}
		return $out;
	}

	/**
	 * @param list<array{pack_key: string, subtype_key: string, band: string}> $pack_results
	 * @return array{pack_count: int, subtype_count: int, release_grade_count: int, strong_count: int, minimal_count: int, below_minimal_count: int}
	 */
	private function make_summary( array $pack_results ): array {
		$pack_count          = 0;
		$subtype_count       = 0;
		$release_grade_count = 0;
		$strong_count        = 0;
		$minimal_count       = 0;
		$below_minimal_count = 0;
		$seen_packs          = array();
		foreach ( $pack_results as $r ) {
			$pk = $r['pack_key'] ?? '';
			$sk = $r['subtype_key'] ?? '';
			if ( $sk === '' ) {
				if ( ! isset( $seen_packs[ $pk ] ) ) {
					$seen_packs[ $pk ] = true;
					++$pack_count;
				}
			} else {
				++$subtype_count;
			}
			$band = $r['band'] ?? self::BAND_BELOW_MINIMAL;
			switch ( $band ) {
				case self::BAND_RELEASE_GRADE:
					++$release_grade_count;
					break;
				case self::BAND_STRONG:
					++$strong_count;
					break;
				case self::BAND_MINIMAL_VIABLE:
					++$minimal_count;
					break;
				default:
					++$below_minimal_count;
					break;
			}
		}
		return array(
			'pack_count'          => $pack_count,
			'subtype_count'       => $subtype_count,
			'release_grade_count' => $release_grade_count,
			'strong_count'        => $strong_count,
			'minimal_count'       => $minimal_count,
			'below_minimal_count' => $below_minimal_count,
		);
	}
}
