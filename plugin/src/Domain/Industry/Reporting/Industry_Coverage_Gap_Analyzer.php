<?php
/**
 * Internal coverage-gap analyzer for industry packs, overlays, bundles, and metadata (Prompt 439).
 * Identifies where an industry or subtype lacks sufficient metadata, overlays, bundle coverage, or caution rules.
 * Internal-only; advisory; no auto-generation or public reports.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry;

/**
 * Analyzes coverage gaps per industry and optional subtype. Produces actionable grouped reports.
 */
final class Industry_Coverage_Gap_Analyzer {

	public const PRIORITY_HIGH   = 'high';
	public const PRIORITY_MEDIUM = 'medium';
	public const PRIORITY_LOW    = 'low';

	/** Gap: no section helper overlays for this industry. */
	public const GAP_SECTION_HELPER_OVERLAYS = 'section_helper_overlays';

	/** Gap: no page one-pager overlays for this industry. */
	public const GAP_PAGE_ONEPAGER_OVERLAYS = 'page_onepager_overlays';

	/** Gap: no starter bundle for this industry (or subtype). */
	public const GAP_STARTER_BUNDLE = 'starter_bundle';

	/** Gap: pack has no or unresolved style preset ref. */
	public const GAP_STYLE_PRESET = 'style_preset';

	/** Gap: no compliance/caution rules for this industry. */
	public const GAP_COMPLIANCE_RULES = 'compliance_rules';

	/** Gap: pack has no or unresolved SEO guidance ref. */
	public const GAP_SEO_GUIDANCE = 'seo_guidance';

	/** Gap: no question pack for this industry. */
	public const GAP_QUESTION_PACK = 'question_pack';

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Section_Helper_Overlay_Registry|null */
	private $section_overlay_registry;

	/** @var Industry_Page_OnePager_Overlay_Registry|null */
	private $page_overlay_registry;

	/** @var Industry_Starter_Bundle_Registry|null */
	private $starter_bundle_registry;

	/** @var Industry_Style_Preset_Registry|null */
	private $preset_registry;

	/** @var Industry_Compliance_Rule_Registry|null */
	private $compliance_registry;

	/** @var Industry_SEO_Guidance_Registry|null */
	private $seo_registry;

	/** @var Industry_Question_Pack_Registry|null */
	private $question_pack_registry;

	/** @var Industry_Subtype_Registry|null */
	private $subtype_registry;

	public function __construct(
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Section_Helper_Overlay_Registry $section_overlay_registry = null,
		?Industry_Page_OnePager_Overlay_Registry $page_overlay_registry = null,
		?Industry_Starter_Bundle_Registry $starter_bundle_registry = null,
		?Industry_Style_Preset_Registry $preset_registry = null,
		?Industry_Compliance_Rule_Registry $compliance_registry = null,
		?Industry_SEO_Guidance_Registry $seo_registry = null,
		?Industry_Question_Pack_Registry $question_pack_registry = null,
		?Industry_Subtype_Registry $subtype_registry = null
	) {
		$this->pack_registry            = $pack_registry;
		$this->section_overlay_registry = $section_overlay_registry;
		$this->page_overlay_registry    = $page_overlay_registry;
		$this->starter_bundle_registry  = $starter_bundle_registry;
		$this->preset_registry          = $preset_registry;
		$this->compliance_registry      = $compliance_registry;
		$this->seo_registry             = $seo_registry;
		$this->question_pack_registry   = $question_pack_registry;
		$this->subtype_registry         = $subtype_registry;
	}

	/**
	 * Runs coverage-gap analysis for active industries and optional subtypes. Returns grouped gaps.
	 *
	 * @param bool $include_subtypes When true, analyze each subtype scope as well.
	 * @return array{gaps: list<array{scope: string, missing_artifact_class: string, priority: string, explanation: string}>, by_scope: array<string, list<array{missing_artifact_class: string, priority: string, explanation: string}>>}
	 */
	public function analyze( bool $include_subtypes = true ): array {
		$gaps     = array();
		$by_scope = array();

		if ( $this->pack_registry === null ) {
			return array( 'gaps' => array(), 'by_scope' => array() );
		}

		$active_packs = $this->pack_registry->list_by_status( Industry_Pack_Schema::STATUS_ACTIVE );
		foreach ( $active_packs as $pack ) {
			$industry_key = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				? trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $industry_key === '' ) {
				continue;
			}
			$this->analyze_scope( $industry_key, '', $pack, $gaps, $by_scope );
		}

		if ( $include_subtypes && $this->subtype_registry !== null ) {
			$all_subtypes = $this->subtype_registry->get_all();
			foreach ( $all_subtypes as $sub ) {
				$subtype_key = trim( (string) ( $sub[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] ?? '' ) );
				$parent_key  = trim( (string) ( $sub[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ?? '' ) );
				if ( $subtype_key === '' || $parent_key === '' ) {
					continue;
				}
				$pack = $this->pack_registry->get( $parent_key );
				$scope = $parent_key . '|' . $subtype_key;
				$this->analyze_scope( $parent_key, $subtype_key, $pack, $gaps, $by_scope, $scope );
			}
		}

		return array( 'gaps' => $gaps, 'by_scope' => $by_scope );
	}

	/**
	 * @param array<string, mixed>|null $pack Pack definition when scope is industry-level.
	 * @param list<array{scope: string, missing_artifact_class: string, priority: string, explanation: string}> $gaps
	 * @param array<string, list<array{missing_artifact_class: string, priority: string, explanation: string}>> $by_scope
	 * @param string $scope_override When set, use this as scope key (e.g. industry|subtype).
	 */
	private function analyze_scope( string $industry_key, string $subtype_key, ?array $pack, array &$gaps, array &$by_scope, string $scope_override = '' ): void {
		$scope = $scope_override !== '' ? $scope_override : $industry_key;

		if ( ! isset( $by_scope[ $scope ] ) ) {
			$by_scope[ $scope ] = array();
		}

		// Section helper overlays.
		if ( $this->section_overlay_registry !== null ) {
			$section_count = count( $this->section_overlay_registry->get_for_industry( $industry_key ) );
			if ( $section_count === 0 ) {
				$entry = array(
					'scope'                 => $scope,
					'missing_artifact_class' => self::GAP_SECTION_HELPER_OVERLAYS,
					'priority'              => self::PRIORITY_MEDIUM,
					'explanation'            => 'No section helper overlays defined for this industry.',
				);
				$gaps[] = $entry;
				$by_scope[ $scope ][] = array(
					'missing_artifact_class' => $entry['missing_artifact_class'],
					'priority'              => $entry['priority'],
					'explanation'            => $entry['explanation'],
				);
			}
		}

		// Page one-pager overlays.
		if ( $this->page_overlay_registry !== null ) {
			$page_count = count( $this->page_overlay_registry->get_for_industry( $industry_key ) );
			if ( $page_count === 0 ) {
				$entry = array(
					'scope'                 => $scope,
					'missing_artifact_class' => self::GAP_PAGE_ONEPAGER_OVERLAYS,
					'priority'              => self::PRIORITY_MEDIUM,
					'explanation'            => 'No page one-pager overlays defined for this industry.',
				);
				$gaps[] = $entry;
				$by_scope[ $scope ][] = array(
					'missing_artifact_class' => $entry['missing_artifact_class'],
					'priority'              => $entry['priority'],
					'explanation'            => $entry['explanation'],
				);
			}
		}

		// Starter bundle.
		if ( $this->starter_bundle_registry !== null ) {
			$bundles = $this->starter_bundle_registry->get_for_industry( $industry_key, $subtype_key );
			if ( $bundles === array() ) {
				$entry = array(
					'scope'                 => $scope,
					'missing_artifact_class' => self::GAP_STARTER_BUNDLE,
					'priority'              => self::PRIORITY_HIGH,
					'explanation'            => $subtype_key !== '' ? 'No starter bundle for this industry and subtype.' : 'No starter bundle for this industry.',
				);
				$gaps[] = $entry;
				$by_scope[ $scope ][] = array(
					'missing_artifact_class' => $entry['missing_artifact_class'],
					'priority'              => $entry['priority'],
					'explanation'            => $entry['explanation'],
				);
			}
		}

		// Style preset (pack-level; only for industry scope).
		if ( $subtype_key === '' && $this->preset_registry !== null && is_array( $pack ) ) {
			$preset_ref = isset( $pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] )
				? trim( $pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] )
				: '';
			if ( $preset_ref === '' ) {
				$entry = array(
					'scope'                 => $scope,
					'missing_artifact_class' => self::GAP_STYLE_PRESET,
					'priority'              => self::PRIORITY_LOW,
					'explanation'            => 'Pack has no token_preset_ref.',
				);
				$gaps[] = $entry;
				$by_scope[ $scope ][] = array(
					'missing_artifact_class' => $entry['missing_artifact_class'],
					'priority'              => $entry['priority'],
					'explanation'            => $entry['explanation'],
				);
			} elseif ( $this->preset_registry->get( $preset_ref ) === null ) {
				$entry = array(
					'scope'                 => $scope,
					'missing_artifact_class' => self::GAP_STYLE_PRESET,
					'priority'              => self::PRIORITY_MEDIUM,
					'explanation'            => 'Pack token_preset_ref does not resolve.',
				);
				$gaps[] = $entry;
				$by_scope[ $scope ][] = array(
					'missing_artifact_class' => $entry['missing_artifact_class'],
					'priority'              => $entry['priority'],
					'explanation'            => $entry['explanation'],
				);
			}
		}

		// Compliance rules.
		if ( $this->compliance_registry !== null ) {
			$rules = $this->compliance_registry->get_for_industry( $industry_key );
			if ( $rules === array() ) {
				$entry = array(
					'scope'                 => $scope,
					'missing_artifact_class' => self::GAP_COMPLIANCE_RULES,
					'priority'              => self::PRIORITY_LOW,
					'explanation'            => 'No compliance/caution rules for this industry.',
				);
				$gaps[] = $entry;
				$by_scope[ $scope ][] = array(
					'missing_artifact_class' => $entry['missing_artifact_class'],
					'priority'              => $entry['priority'],
					'explanation'            => $entry['explanation'],
				);
			}
		}

		// SEO guidance (pack-level).
		if ( $subtype_key === '' && $this->seo_registry !== null && is_array( $pack ) ) {
			$seo_ref = isset( $pack[ Industry_Pack_Schema::FIELD_SEO_GUIDANCE_REF ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_SEO_GUIDANCE_REF ] )
				? trim( $pack[ Industry_Pack_Schema::FIELD_SEO_GUIDANCE_REF ] )
				: '';
			if ( $seo_ref === '' ) {
				$entry = array(
					'scope'                 => $scope,
					'missing_artifact_class' => self::GAP_SEO_GUIDANCE,
					'priority'              => self::PRIORITY_LOW,
					'explanation'            => 'Pack has no seo_guidance_ref.',
				);
				$gaps[] = $entry;
				$by_scope[ $scope ][] = array(
					'missing_artifact_class' => $entry['missing_artifact_class'],
					'priority'              => $entry['priority'],
					'explanation'            => $entry['explanation'],
				);
			} elseif ( $this->seo_registry->get( $seo_ref ) === null ) {
				$entry = array(
					'scope'                 => $scope,
					'missing_artifact_class' => self::GAP_SEO_GUIDANCE,
					'priority'              => self::PRIORITY_MEDIUM,
					'explanation'            => 'Pack seo_guidance_ref does not resolve.',
				);
				$gaps[] = $entry;
				$by_scope[ $scope ][] = array(
					'missing_artifact_class' => $entry['missing_artifact_class'],
					'priority'              => $entry['priority'],
					'explanation'            => $entry['explanation'],
				);
			}
		}

		// Question pack (industry-level).
		if ( $subtype_key === '' && $this->question_pack_registry !== null ) {
			$has_qp = $this->question_pack_registry->get( $industry_key ) !== null;
			if ( ! $has_qp ) {
				$entry = array(
					'scope'                 => $scope,
					'missing_artifact_class' => self::GAP_QUESTION_PACK,
					'priority'              => self::PRIORITY_LOW,
					'explanation'            => 'No question pack for this industry.',
				);
				$gaps[] = $entry;
				$by_scope[ $scope ][] = array(
					'missing_artifact_class' => $entry['missing_artifact_class'],
					'priority'              => $entry['priority'],
					'explanation'            => $entry['explanation'],
				);
			}
		}
	}
}
