<?php
/**
 * Bounded diagnostics for the Industry Pack subsystem (Prompt 356).
 * Returns a snapshot of active industry profile, pack refs, overlay usage, and preset.
 * Admin/support-only; no secrets or raw content. Safe when industry subsystem is not loaded.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Application_Service;

/**
 * Builds a bounded diagnostics snapshot for the industry subsystem.
 */
final class Industry_Diagnostics_Service {

	/** @var Industry_Profile_Repository|null */
	private $profile_repository;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Section_Helper_Overlay_Registry|null */
	private $section_overlay_registry;

	/** @var Industry_Page_OnePager_Overlay_Registry|null */
	private $page_overlay_registry;

	/** @var Industry_Style_Preset_Application_Service|null */
	private $preset_application_service;

	/** @var Industry_Content_Gap_Detector|null Optional; when set, snapshot includes content_gaps (Prompt 408). */
	private $content_gap_detector;

	/** @var Industry_Override_Audit_Report_Service|null Optional; when set, snapshot includes override_summary (Prompt 437). */
	private $override_audit_report_service;

	/** @var Industry_Override_Conflict_Detector|null Optional; when set, snapshot includes override_conflicts (Prompt 464). */
	private $override_conflict_detector;

	public function __construct(
		?Industry_Profile_Repository $profile_repository = null,
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Section_Helper_Overlay_Registry $section_overlay_registry = null,
		?Industry_Page_OnePager_Overlay_Registry $page_overlay_registry = null,
		?Industry_Style_Preset_Application_Service $preset_application_service = null,
		?Industry_Content_Gap_Detector $content_gap_detector = null,
		?Industry_Override_Audit_Report_Service $override_audit_report_service = null,
		?Industry_Override_Conflict_Detector $override_conflict_detector = null
	) {
		$this->profile_repository            = $profile_repository;
		$this->pack_registry                 = $pack_registry;
		$this->section_overlay_registry      = $section_overlay_registry;
		$this->page_overlay_registry         = $page_overlay_registry;
		$this->preset_application_service    = $preset_application_service;
		$this->content_gap_detector          = $content_gap_detector;
		$this->override_audit_report_service = $override_audit_report_service;
		$this->override_conflict_detector    = $override_conflict_detector;
	}

	/**
	 * Returns a bounded snapshot for support/diagnostics. No secrets; no raw user content.
	 *
	 * @return array{
	 *   primary_industry: string,
	 *   secondary_industries: array<int, string>,
	 *   profile_readiness: string,
	 *   active_pack_refs: array<int, string>,
	 *   applied_preset_ref: string|null,
	 *   section_overlay_count: int,
	 *   page_overlay_count: int,
	 *   recommendation_mode: string,
	 *   warnings: array<int, string>,
	 *   industry_subsystem_available: bool
	 * }
	 */
	public function get_snapshot(): array {
		$empty = array(
			'primary_industry'             => '',
			'secondary_industries'         => array(),
			'profile_readiness'            => 'none',
			'active_pack_refs'             => array(),
			'applied_preset_ref'           => null,
			'section_overlay_count'        => 0,
			'page_overlay_count'           => 0,
			'recommendation_mode'          => 'inactive',
			'warnings'                     => array(),
			'industry_subsystem_available' => $this->profile_repository !== null,
		);
		if ( $this->profile_repository === null ) {
			return $empty;
		}
		$profile           = $this->profile_repository->get_profile();
		$primary           = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$secondary         = isset( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) && is_array( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			? array_values( array_filter( array_map( 'trim', $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) ) )
			: array();
		$active_pack_refs  = array_filter( array_merge( array( $primary ), $secondary ) );
		$profile_readiness = $primary !== '' ? 'partial' : 'none';
		if ( $primary !== '' && $this->pack_registry !== null ) {
			$pack = $this->pack_registry->get( $primary );
			if ( $pack !== null && ( ( $pack[ Industry_Pack_Schema::FIELD_STATUS ] ?? '' ) === 'active' ) ) {
				$profile_readiness = 'complete';
			}
		}
		$section_overlay_count = 0;
		if ( $primary !== '' && $this->section_overlay_registry !== null ) {
			$section_overlay_count = count( $this->section_overlay_registry->get_for_industry( $primary ) );
		}
		$page_overlay_count = 0;
		if ( $primary !== '' && $this->page_overlay_registry !== null ) {
			$page_overlay_count = count( $this->page_overlay_registry->get_for_industry( $primary ) );
		}
		$applied_preset_ref = null;
		if ( $this->preset_application_service !== null ) {
			$applied = $this->preset_application_service->get_applied_preset();
			if ( $applied !== null && isset( $applied['preset_key'] ) ) {
				$applied_preset_ref = (string) $applied['preset_key'];
			}
		}
		$recommendation_mode = $primary !== '' ? 'active' : 'inactive';
		$warnings            = array();
		if ( $primary !== '' && $this->pack_registry !== null && $this->pack_registry->get( $primary ) === null ) {
			$warnings[] = 'primary_industry_pack_not_found';
		}
		$out = array(
			'primary_industry'             => $primary,
			'secondary_industries'         => $secondary,
			'profile_readiness'            => $profile_readiness,
			'active_pack_refs'             => array_values( $active_pack_refs ),
			'applied_preset_ref'           => $applied_preset_ref,
			'section_overlay_count'        => $section_overlay_count,
			'page_overlay_count'           => $page_overlay_count,
			'recommendation_mode'          => $recommendation_mode,
			'warnings'                     => $warnings,
			'industry_subsystem_available' => true,
		);
		if ( $primary !== '' && $this->content_gap_detector !== null ) {
			$bundle_key          = isset( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
				? trim( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
				: null;
			$out['content_gaps'] = $this->content_gap_detector->detect( $profile, $bundle_key, array() );
		}
		if ( $this->override_audit_report_service !== null ) {
			$out['override_summary'] = $this->override_audit_report_service->build_report();
		}
		if ( $this->override_conflict_detector !== null ) {
			$out['override_conflicts'] = $this->override_conflict_detector->detect();
		}
		return $out;
	}
}
