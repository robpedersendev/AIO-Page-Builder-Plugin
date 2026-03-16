<?php
/**
 * Read-only inspection service for the industry subsystem (Prompt 398).
 * Provides profile summary, health summary, diagnostics snapshot, and recommendation preview for CLI/support use. No mutation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface;

/**
 * Bounded, read-only inspection for industry profile, health, diagnostics, and recommendation preview.
 * Intended for internal CLI/scripted use and support. No secrets; no mutation.
 */
final class Industry_Inspection_Command_Service {

	/** Default cap for page templates in recommendation preview. */
	private const PREVIEW_TEMPLATE_CAP = 50;

	/** Default top-N template keys in recommendation preview. */
	private const PREVIEW_TOP_N = 10;

	/** @var Industry_Profile_Repository|null */
	private $profile_repo;

	/** @var Industry_Health_Check_Service|null */
	private $health_service;

	/** @var Industry_Diagnostics_Service|null */
	private $diagnostics_service;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Page_Template_Recommendation_Resolver|null */
	private $page_resolver;

	/** @var Industry_Section_Recommendation_Resolver|null */
	private $section_resolver;

	/** @var Page_Template_Repository_Interface|null */
	private $page_repo;

	/** @var Industry_Starter_Bundle_Registry|null */
	private $starter_bundle_registry;

	/** @var callable(): list<array<string, mixed>>|null Section list provider for preview. */
	private $section_list_provider;

	public function __construct(
		?Industry_Profile_Repository $profile_repo = null,
		?Industry_Health_Check_Service $health_service = null,
		?Industry_Diagnostics_Service $diagnostics_service = null,
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Page_Template_Recommendation_Resolver $page_resolver = null,
		?Industry_Section_Recommendation_Resolver $section_resolver = null,
		?Page_Template_Repository_Interface $page_repo = null,
		?Industry_Starter_Bundle_Registry $starter_bundle_registry = null,
		?callable $section_list_provider = null
	) {
		$this->profile_repo             = $profile_repo;
		$this->health_service           = $health_service;
		$this->diagnostics_service      = $diagnostics_service;
		$this->pack_registry            = $pack_registry;
		$this->page_resolver            = $page_resolver;
		$this->section_resolver         = $section_resolver;
		$this->page_repo                = $page_repo;
		$this->starter_bundle_registry  = $starter_bundle_registry;
		$this->section_list_provider    = $section_list_provider;
	}

	/**
	 * Returns a bounded profile summary for inspection. No secrets.
	 *
	 * @return array{primary_industry_key: string, secondary_industry_keys: list<string>, selected_starter_bundle_key: string, readiness: string, available: bool}
	 */
	public function get_profile_summary(): array {
		$empty = array(
			'primary_industry_key'       => '',
			'secondary_industry_keys'    => array(),
			'selected_starter_bundle_key' => '',
			'readiness'                  => 'none',
			'available'                  => $this->profile_repo !== null,
		);
		if ( $this->profile_repo === null ) {
			return $empty;
		}
		$profile = $this->profile_repo->get_profile();
		$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$secondary = isset( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) && is_array( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			? array_values( array_filter( array_map( 'trim', $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) ) )
			: array();
		$starter = isset( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			: '';
		$readiness = 'none';
		if ( $primary !== '' && $this->profile_repo !== null ) {
			$readiness = $primary !== '' ? 'partial' : 'none';
			if ( $this->pack_registry !== null ) {
				$pack = $this->pack_registry->get( $primary );
				if ( $pack !== null && ( ( $pack[ Industry_Pack_Schema::FIELD_STATUS ] ?? '' ) === 'active' ) ) {
					$readiness = 'complete';
				}
			}
		}
		return array(
			'primary_industry_key'        => $primary,
			'secondary_industry_keys'      => $secondary,
			'selected_starter_bundle_key'  => $starter,
			'readiness'                    => $readiness,
			'available'                   => true,
		);
	}

	/**
	 * Returns a bounded health check summary (errors/warnings counts and sample issues). No mutation.
	 *
	 * @return array{errors_count: int, warnings_count: int, sample_errors: list<array{object_type: string, key: string, issue_summary: string}>, sample_warnings: list<array{object_type: string, key: string, issue_summary: string}>, available: bool}
	 */
	public function get_health_summary(): array {
		$empty = array(
			'errors_count'    => 0,
			'warnings_count'  => 0,
			'sample_errors'   => array(),
			'sample_warnings' => array(),
			'available'       => $this->health_service !== null,
		);
		if ( $this->health_service === null ) {
			return $empty;
		}
		$result = $this->health_service->run();
		$errors = $result['errors'] ?? array();
		$warnings = $result['warnings'] ?? array();
		$sample_errors = array_slice( array_map( function ( $e ) {
			return array(
				'object_type'   => $e['object_type'] ?? '',
				'key'           => $e['key'] ?? '',
				'issue_summary' => $e['issue_summary'] ?? '',
			);
		}, $errors ), 0, 5 );
		$sample_warnings = array_slice( array_map( function ( $w ) {
			return array(
				'object_type'   => $w['object_type'] ?? '',
				'key'           => $w['key'] ?? '',
				'issue_summary' => $w['issue_summary'] ?? '',
			);
		}, $warnings ), 0, 5 );
		return array(
			'errors_count'    => count( $errors ),
			'warnings_count'  => count( $warnings ),
			'sample_errors'   => $sample_errors,
			'sample_warnings' => $sample_warnings,
			'available'       => true,
		);
	}

	/**
	 * Returns the diagnostics snapshot from Industry_Diagnostics_Service. No mutation.
	 *
	 * @return array<string, mixed> Snapshot shape from Industry_Diagnostics_Service::get_snapshot().
	 */
	public function get_diagnostics_snapshot(): array {
		if ( $this->diagnostics_service === null ) {
			return array(
				'primary_industry'             => '',
				'secondary_industries'         => array(),
				'profile_readiness'            => 'none',
				'active_pack_refs'             => array(),
				'applied_preset_ref'           => null,
				'section_overlay_count'       => 0,
				'page_overlay_count'          => 0,
				'recommendation_mode'         => 'inactive',
				'warnings'                    => array(),
				'industry_subsystem_available' => false,
			);
		}
		return $this->diagnostics_service->get_snapshot();
	}

	/**
	 * Returns a bounded recommendation preview for a given industry key: top template keys and optionally top section keys.
	 * Read-only; uses current registries and repo. No mutation.
	 *
	 * @param string $industry_key Industry key to preview (e.g. realtor).
	 * @param int    $top_templates Max number of top page template keys to return (0 = use default).
	 * @param int    $top_sections  Max number of top section keys to return (0 = skip sections).
	 * @return array{industry_key: string, top_template_keys: list<string>, top_section_keys: list<string>, template_count: int, section_count: int, pack_found: bool}
	 */
	public function get_recommendation_preview( string $industry_key, int $top_templates = 0, int $top_sections = 0 ): array {
		$top_templates = $top_templates > 0 ? $top_templates : self::PREVIEW_TOP_N;
		$profile = array(
			'primary_industry_key'   => $industry_key,
			'secondary_industry_keys' => array(),
		);
		$primary_pack = $this->pack_registry !== null ? $this->pack_registry->get( $industry_key ) : null;
		$top_template_keys = array();
		$template_count = 0;
		if ( $this->page_resolver !== null && $this->page_repo !== null ) {
			$templates = $this->page_repo->list_all_definitions( self::PREVIEW_TEMPLATE_CAP, 0 );
			$template_count = count( $templates );
			$result = $this->page_resolver->resolve( $profile, $primary_pack, $templates, array() );
			$top_template_keys = array_slice( array_values( $result->get_ranked_keys() ), 0, $top_templates );
		}
		$top_section_keys = array();
		$section_count = 0;
		if ( $top_sections > 0 && $this->section_resolver !== null && $this->section_list_provider !== null ) {
			$sections = ( $this->section_list_provider )();
			$section_count = count( $sections );
			$result = $this->section_resolver->resolve( $profile, $primary_pack, $sections, array() );
			$top_section_keys = array_slice( array_values( $result->get_ranked_keys() ), 0, $top_sections );
		}
		return array(
			'industry_key'      => $industry_key,
			'top_template_keys' => $top_template_keys,
			'top_section_keys'  => $top_section_keys,
			'template_count'   => $template_count,
			'section_count'    => $section_count,
			'pack_found'       => $primary_pack !== null,
		);
	}

	/**
	 * Returns starter bundle keys available for an industry. Read-only.
	 *
	 * @param string $industry_key Industry key.
	 * @return list<string> Bundle keys.
	 */
	public function get_starter_bundles_for_industry( string $industry_key ): array {
		if ( $this->starter_bundle_registry === null ) {
			return array();
		}
		$bundles = $this->starter_bundle_registry->get_for_industry( $industry_key );
		$keys = array();
		foreach ( $bundles as $bundle ) {
			$key = $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '';
			if ( is_string( $key ) && $key !== '' ) {
				$keys[] = trim( $key );
			}
		}
		return $keys;
	}
}
