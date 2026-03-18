<?php
/**
 * Builds industry prompt-pack overlay from input artifact (industry-prompt-pack-overlay-contract.md).
 * Returns planning constraints and guidance for prompt-pack assembly; safe when industry context missing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;

/**
 * Produces industry overlay for prompt-pack assembly. No throw; returns minimal overlay when context incomplete.
 */
final class Industry_Prompt_Pack_Overlay_Service {

	public const OVERLAY_SCHEMA_VERSION = '1';

	/** @var Industry_Pack_Registry|null */
	private ?Industry_Pack_Registry $pack_registry;

	public function __construct( ?Industry_Pack_Registry $pack_registry = null ) {
		$this->pack_registry = $pack_registry;
	}

	/**
	 * Builds overlay from input artifact. Safe: returns minimal overlay when industry_context missing or not ready.
	 *
	 * @param array<string, mixed> $input_artifact Built input artifact (may contain industry_context).
	 * @return array<string, mixed> Overlay with schema_version; optional active_industry_key, required_page_families, discouraged_weak_fit, cta_priorities, industry_guidance_text, etc.
	 */
	public function get_overlay_for_artifact( array $input_artifact ): array {
		$base             = array( 'schema_version' => self::OVERLAY_SCHEMA_VERSION );
		$industry_context = isset( $input_artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ] ) && is_array( $input_artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ] )
			? $input_artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ]
			: null;
		if ( $industry_context === null ) {
			return $base;
		}
		$readiness = isset( $industry_context['readiness'] ) && is_array( $industry_context['readiness'] )
			? $industry_context['readiness']
			: array();
		$state     = isset( $readiness['state'] ) && is_string( $readiness['state'] ) ? $readiness['state'] : '';
		if ( $state === 'none' || $state === 'minimal' ) {
			return $base;
		}
		$profile = isset( $industry_context['industry_profile'] ) && is_array( $industry_context['industry_profile'] )
			? $industry_context['industry_profile']
			: array();
		$primary = isset( $profile['primary_industry_key'] ) && is_string( $profile['primary_industry_key'] )
			? trim( $profile['primary_industry_key'] )
			: '';
		if ( $primary === '' ) {
			return $base;
		}
		$base['active_industry_key'] = $primary;
		if ( $this->pack_registry === null ) {
			return $base;
		}
		$pack = $this->pack_registry->get( $primary );
		if ( $pack === null ) {
			return $base;
		}
		$supported = isset( $pack[ Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES ] ) && is_array( $pack[ Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES ] )
			? $pack[ Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES ]
			: array();
		if ( $supported !== array() ) {
			$base['required_page_families'] = array_values(
				array_filter(
					array_map(
						function ( $v ) {
							return is_string( $v ) ? trim( $v ) : '';
						},
						$supported
					)
				)
			);
		}
		$discouraged = isset( $pack[ Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS ] ) && is_array( $pack[ Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS ] )
			? $pack[ Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS ]
			: array();
		if ( $discouraged !== array() ) {
			$base['discouraged_weak_fit'] = array_values(
				array_filter(
					array_map(
						function ( $v ) {
							return is_string( $v ) ? trim( $v ) : '';
						},
						$discouraged
					)
				)
			);
		}
		$cta = isset( $pack[ Industry_Pack_Schema::FIELD_DEFAULT_CTA_PATTERNS ] ) && is_array( $pack[ Industry_Pack_Schema::FIELD_DEFAULT_CTA_PATTERNS ] )
			? $pack[ Industry_Pack_Schema::FIELD_DEFAULT_CTA_PATTERNS ]
			: ( isset( $pack[ Industry_Pack_Schema::FIELD_PREFERRED_CTA_PATTERNS ] ) && is_array( $pack[ Industry_Pack_Schema::FIELD_PREFERRED_CTA_PATTERNS ] )
				? $pack[ Industry_Pack_Schema::FIELD_PREFERRED_CTA_PATTERNS ]
				: array() );
		if ( $cta !== array() ) {
			$base['cta_priorities'] = array_values(
				array_filter(
					array_map(
						function ( $v ) {
							return is_string( $v ) ? trim( $v ) : '';
						},
						$cta
					)
				)
			);
		}
		$summary = isset( $pack[ Industry_Pack_Schema::FIELD_SUMMARY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_SUMMARY ] )
			? trim( $pack[ Industry_Pack_Schema::FIELD_SUMMARY ] )
			: '';
		if ( $summary !== '' ) {
			$base['industry_guidance_text'] = $summary;
		}
		return $base;
	}
}
