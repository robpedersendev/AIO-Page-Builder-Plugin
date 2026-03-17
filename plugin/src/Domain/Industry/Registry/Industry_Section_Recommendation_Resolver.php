<?php
/**
 * Scores and ranks section templates against Industry Profile and Industry Pack (industry-section-recommendation-contract.md).
 * Read-only; deterministic; safe when profile or pack missing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder;
use AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Resolves section recommendations by industry fit. Does not modify section registry.
 * When cache service and key builder are provided, results are cached (industry-cache-contract).
 */
final class Industry_Section_Recommendation_Resolver {

	public const FIT_RECOMMENDED     = 'recommended';
	public const FIT_ALLOWED_WEAK    = 'allowed_weak_fit';
	public const FIT_DISCOURAGED     = 'discouraged';
	public const FIT_NEUTRAL         = 'neutral';

	private const SCORE_RECOMMENDED_MIN = 20;
	private const SCORE_DISCOURAGED_MAX = -10;
	private const POINTS_PACK_PREFERRED = 50;
	private const POINTS_SECTION_AFFINITY_PRIMARY = 30;
	private const POINTS_SECTION_AFFINITY_SECONDARY = 15;
	private const POINTS_PACK_DISCOURAGED = -50;
	private const POINTS_SECTION_DISCOURAGED_PRIMARY = -30;
	private const POINTS_SECTION_DISCOURAGED_SECONDARY = -15;
	private const POINTS_CTA_FIT = 5;

	/**
	 * Resolves ranked section recommendations. Safe: missing profile or pack yields neutral ranking.
	 *
	 * @param array<string, mixed>       $industry_profile Must contain primary_industry_key (string); optional secondary_industry_keys (array).
	 * @param array<string, mixed>|null  $primary_pack     Industry pack definition (preferred_section_keys, discouraged_section_keys) or null.
	 * @param array<int, array<string, mixed>> $sections   List of section definitions (each with internal_key; optional industry_affinity, industry_discouraged, industry_cta_fit, industry_notes).
	 * @param array<string, mixed>      $options          Optional: subtype_definition (array|null), subtype_extender (Industry_Subtype_Section_Recommendation_Extender|null). When both set, subtype influence is applied after base resolution.
	 * @return Industry_Section_Recommendation_Result
	 */
	public function resolve( array $industry_profile, ?array $primary_pack, array $sections, array $options = array() ): Industry_Section_Recommendation_Result {
		$options_for_key = array( 'subtype_key' => $industry_profile['industry_subtype_key'] ?? $options['subtype_key'] ?? '' );
		if ( $this->cache_service !== null && $this->cache_key_builder !== null ) {
			$base_key = $this->cache_key_builder->for_section_recommendation( $industry_profile, $sections, $options_for_key );
			$cached = $this->cache_service->get( $base_key );
			if ( is_array( $cached ) && isset( $cached['items'] ) && is_array( $cached['items'] ) ) {
				return new Industry_Section_Recommendation_Result( $cached['items'] );
			}
		}

		$primary = isset( $industry_profile['primary_industry_key'] ) && is_string( $industry_profile['primary_industry_key'] )
			? trim( $industry_profile['primary_industry_key'] )
			: '';
		$secondary = isset( $industry_profile['secondary_industry_keys'] ) && is_array( $industry_profile['secondary_industry_keys'] )
			? array_values( array_filter( array_map( function ( $v ) {
				return is_string( $v ) ? trim( $v ) : '';
			}, $industry_profile['secondary_industry_keys'] ) ) )
			: array();

		$items = array();
		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}
			$section_key = isset( $section[ Section_Schema::FIELD_INTERNAL_KEY ] ) && is_string( $section[ Section_Schema::FIELD_INTERNAL_KEY ] )
				? trim( $section[ Section_Schema::FIELD_INTERNAL_KEY ] )
				: '';
			if ( $section_key === '' ) {
				continue;
			}
			$score = 0;
			$reasons = array();
			$source_refs = array();
			$warnings = array();

			if ( $primary === '' ) {
				$items[] = array(
					'section_key'           => $section_key,
					'score'                 => 0,
					'fit_classification'    => self::FIT_NEUTRAL,
					'explanation_reasons'  => array(),
					'industry_source_refs'  => array(),
					'warning_flags'         => array(),
				);
				continue;
			}

			$pack_preferred = $primary_pack !== null && isset( $primary_pack[ Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS ] ) && is_array( $primary_pack[ Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS ] )
				? $primary_pack[ Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS ]
				: array();
			$pack_discouraged = $primary_pack !== null && isset( $primary_pack[ Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS ] ) && is_array( $primary_pack[ Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS ] )
				? $primary_pack[ Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS ]
				: array();

			if ( in_array( $section_key, $pack_preferred, true ) ) {
				$score += self::POINTS_PACK_PREFERRED;
				$reasons[] = 'in_pack_preferred';
				if ( $primary !== '' ) {
					$source_refs[] = $primary;
				}
			}
			if ( in_array( $section_key, $pack_discouraged, true ) ) {
				$score += self::POINTS_PACK_DISCOURAGED;
				$reasons[] = 'in_pack_discouraged';
				if ( $primary !== '' ) {
					$source_refs[] = $primary;
				}
			}

			$affinity = $this->normalize_industry_key_list( $section[ Section_Schema::FIELD_INDUSTRY_AFFINITY ] ?? null );
			$discouraged = $this->normalize_industry_key_list( $section[ Section_Schema::FIELD_INDUSTRY_DISCOURAGED ] ?? null );
			$cta_fit = $section[ Section_Schema::FIELD_INDUSTRY_CTA_FIT ] ?? null;

			if ( $primary !== '' ) {
				if ( in_array( $primary, $affinity, true ) ) {
					$score += self::POINTS_SECTION_AFFINITY_PRIMARY;
					$reasons[] = 'section_affinity_primary';
					$source_refs[] = $primary;
				}
				if ( in_array( $primary, $discouraged, true ) ) {
					$score += self::POINTS_SECTION_DISCOURAGED_PRIMARY;
					$reasons[] = 'section_discouraged_primary';
					$source_refs[] = $primary;
				}
			}
			foreach ( $secondary as $sec_key ) {
				if ( $sec_key === '' ) {
					continue;
				}
				if ( in_array( $sec_key, $affinity, true ) ) {
					$score += self::POINTS_SECTION_AFFINITY_SECONDARY;
					$reasons[] = 'section_affinity_secondary';
					$source_refs[] = $sec_key;
				}
				if ( in_array( $sec_key, $discouraged, true ) ) {
					$score += self::POINTS_SECTION_DISCOURAGED_SECONDARY;
					$reasons[] = 'section_discouraged_secondary';
					$source_refs[] = $sec_key;
				}
			}
			if ( $primary !== '' && $this->section_has_industry_cta_fit( $cta_fit, $primary ) ) {
				$score += self::POINTS_CTA_FIT;
				$reasons[] = 'cta_fit';
			}

			$fit = $score >= self::SCORE_RECOMMENDED_MIN
				? self::FIT_RECOMMENDED
				: ( $score <= self::SCORE_DISCOURAGED_MAX ? self::FIT_DISCOURAGED : ( $score > 0 ? self::FIT_ALLOWED_WEAK : self::FIT_NEUTRAL ) );
			$items[] = array(
				'section_key'           => $section_key,
				'score'                 => $score,
				'fit_classification'    => $fit,
				'explanation_reasons'  => array_values( array_unique( $reasons ) ),
				'industry_source_refs'  => array_values( array_unique( $source_refs ) ),
				'warning_flags'         => $warnings,
			);
		}

		usort( $items, function ( array $a, array $b ) {
			$score_a = $a['score'] ?? 0;
			$score_b = $b['score'] ?? 0;
			if ( $score_b !== $score_a ) {
				return $score_b <=> $score_a;
			}
			return strcmp( $a['section_key'] ?? '', $b['section_key'] ?? '' );
		} );

		$result = new Industry_Section_Recommendation_Result( $items );
		$subtype_def = $options['subtype_definition'] ?? null;
		$extender = $options['subtype_extender'] ?? null;
		if ( is_array( $subtype_def ) && $extender instanceof Industry_Subtype_Section_Recommendation_Extender ) {
			$result = $extender->apply_subtype_influence( $result, $subtype_def, $sections );
		}
		if ( $this->cache_service !== null && $this->cache_key_builder !== null ) {
			$base_key = $this->cache_key_builder->for_section_recommendation( $industry_profile, $sections, $options_for_key );
			$this->cache_service->set( $base_key, $result->to_array() );
		}
		return $result;
	}

	/**
	 * @param mixed $val industry_affinity or industry_discouraged value (array of keys or map).
	 * @return list<string>
	 */
	private function normalize_industry_key_list( $val ): array {
		if ( ! is_array( $val ) ) {
			return array();
		}
		$out = array();
		foreach ( $val as $k => $v ) {
			if ( is_string( $k ) && trim( $k ) !== '' && preg_match( Section_Schema::INDUSTRY_KEY_PATTERN, $k ) ) {
				$out[] = $k;
			} elseif ( is_string( $v ) && trim( $v ) !== '' && preg_match( Section_Schema::INDUSTRY_KEY_PATTERN, trim( $v ) ) ) {
				$out[] = trim( $v );
			}
		}
		return array_values( array_unique( $out ) );
	}

	private function section_has_industry_cta_fit( $cta_fit, string $industry_key ): bool {
		if ( $cta_fit === null ) {
			return false;
		}
		if ( is_array( $cta_fit ) && isset( $cta_fit[ $industry_key ] ) && is_string( $cta_fit[ $industry_key ] ) && trim( $cta_fit[ $industry_key ] ) !== '' ) {
			return true;
		}
		return false;
	}
}
