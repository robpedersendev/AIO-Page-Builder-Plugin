<?php
/**
 * Scores and ranks page templates against Industry Profile and Industry Pack (industry-page-template-recommendation-contract.md).
 * Read-only; deterministic; safe when profile or pack missing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder;
use AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Resolves page-template recommendations by industry fit. Does not modify page-template registry.
 * When cache service and key builder are provided, results are cached (industry-cache-contract).
 */
final class Industry_Page_Template_Recommendation_Resolver {

	public const FIT_RECOMMENDED  = 'recommended';
	public const FIT_ALLOWED_WEAK = 'allowed_weak_fit';
	public const FIT_DISCOURAGED  = 'discouraged';
	public const FIT_NEUTRAL      = 'neutral';

	private const SCORE_RECOMMENDED_MIN        = 20;
	private const SCORE_DISCOURAGED_MAX        = -10;
	private const POINTS_AFFINITY_PRIMARY      = 35;
	private const POINTS_REQUIRED_PRIMARY      = 40;
	private const POINTS_PACK_FAMILY_FIT       = 25;
	private const POINTS_HIERARCHY_FIT         = 10;
	private const POINTS_LPAGERY_FIT           = 10;
	private const POINTS_AFFINITY_SECONDARY    = 15;
	private const POINTS_REQUIRED_SECONDARY    = 20;
	private const POINTS_DISCOURAGED_PRIMARY   = -35;
	private const POINTS_DISCOURAGED_SECONDARY = -15;

	private const TEMPLATE_FAMILY_FIELD = 'template_family';

	/**
	 * Resolves ranked page-template recommendations. Safe: missing profile yields neutral ranking.
	 *
	 * @param array<string, mixed>             $industry_profile Must contain primary_industry_key (string); optional secondary_industry_keys (array).
	 * @param array<string, mixed>|null        $primary_pack    Industry pack (supported_page_families) or null.
	 * @param array<int, array<string, mixed>> $page_templates List of page template definitions (internal_key; optional industry_*, template_family).
	 * @param array<string, mixed>             $options         Optional: subtype_definition (array|null), subtype_extender (Industry_Subtype_Page_Template_Recommendation_Extender|null). When both set, subtype influence is applied after base resolution.
	 * @return Industry_Page_Template_Recommendation_Result
	 */
	public function resolve( array $industry_profile, ?array $primary_pack, array $page_templates, array $options = array() ): Industry_Page_Template_Recommendation_Result {
		$options_for_key = array( 'subtype_key' => $industry_profile['industry_subtype_key'] ?? $options['subtype_key'] ?? '' );
		if ( $this->cache_service !== null && $this->cache_key_builder !== null ) {
			$base_key = $this->cache_key_builder->for_page_template_recommendation( $industry_profile, $page_templates, $options_for_key );
			$cached   = $this->cache_service->get( $base_key );
			if ( is_array( $cached ) && isset( $cached['items'] ) && is_array( $cached['items'] ) ) {
				return new Industry_Page_Template_Recommendation_Result( $cached['items'] );
			}
		}

		$primary   = isset( $industry_profile['primary_industry_key'] ) && is_string( $industry_profile['primary_industry_key'] )
			? trim( $industry_profile['primary_industry_key'] )
			: '';
		$secondary = isset( $industry_profile['secondary_industry_keys'] ) && is_array( $industry_profile['secondary_industry_keys'] )
			? array_values(
				array_filter(
					array_map(
						function ( $v ) {
							return is_string( $v ) ? trim( $v ) : '';
						},
						$industry_profile['secondary_industry_keys']
					)
				)
			)
			: array();

		$items = array();
		foreach ( $page_templates as $template ) {
			if ( ! is_array( $template ) ) {
				continue;
			}
			$template_key = isset( $template[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ) && is_string( $template[ Page_Template_Schema::FIELD_INTERNAL_KEY ] )
				? trim( $template[ Page_Template_Schema::FIELD_INTERNAL_KEY ] )
				: '';
			if ( $template_key === '' ) {
				continue;
			}
			$score         = 0;
			$reasons       = array();
			$source_refs   = array();
			$warnings      = array();
			$hierarchy_fit = '';
			$lpagery_fit   = '';

			if ( $primary === '' ) {
				$items[] = array(
					'page_template_key'    => $template_key,
					'score'                => 0,
					'fit_classification'   => self::FIT_NEUTRAL,
					'explanation_reasons'  => array(),
					'industry_source_refs' => array(),
					'hierarchy_fit'        => '',
					'lpagery_fit'          => '',
					'warning_flags'        => array(),
				);
				continue;
			}

			$pack_families   = $primary_pack !== null && isset( $primary_pack[ Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES ] ) && is_array( $primary_pack[ Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES ] )
				? $primary_pack[ Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES ]
				: array();
			$template_family = isset( $template[ self::TEMPLATE_FAMILY_FIELD ] ) && is_string( $template[ self::TEMPLATE_FAMILY_FIELD ] )
				? trim( $template[ self::TEMPLATE_FAMILY_FIELD ] )
				: '';
			if ( $template_family !== '' && in_array( $template_family, $pack_families, true ) ) {
				$score        += self::POINTS_PACK_FAMILY_FIT;
				$reasons[]     = 'pack_family_fit';
				$source_refs[] = $primary;
			}

			$affinity    = $this->normalize_industry_key_list( $template[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] ?? null );
			$required    = $this->normalize_industry_key_list( $template[ Page_Template_Schema::FIELD_INDUSTRY_REQUIRED ] ?? null );
			$discouraged = $this->normalize_industry_key_list( $template[ Page_Template_Schema::FIELD_INDUSTRY_DISCOURAGED ] ?? null );

			if ( in_array( $primary, $affinity, true ) ) {
				$score        += self::POINTS_AFFINITY_PRIMARY;
				$reasons[]     = 'template_affinity_primary';
				$source_refs[] = $primary;
			}
			if ( in_array( $primary, $required, true ) ) {
				$score        += self::POINTS_REQUIRED_PRIMARY;
				$reasons[]     = 'industry_required_primary';
				$source_refs[] = $primary;
			}
			if ( in_array( $primary, $discouraged, true ) ) {
				$score        += self::POINTS_DISCOURAGED_PRIMARY;
				$reasons[]     = 'industry_discouraged_primary';
				$source_refs[] = $primary;
			}
			$hierarchy_fit = $this->extract_industry_note( $template[ Page_Template_Schema::FIELD_INDUSTRY_HIERARCHY_FIT ] ?? null, $primary );
			if ( $hierarchy_fit !== '' ) {
				$score    += self::POINTS_HIERARCHY_FIT;
				$reasons[] = 'hierarchy_fit';
			}
			$lpagery_fit = $this->extract_industry_note( $template[ Page_Template_Schema::FIELD_INDUSTRY_LPAGERY_FIT ] ?? null, $primary );
			if ( $lpagery_fit !== '' ) {
				$score    += self::POINTS_LPAGERY_FIT;
				$reasons[] = 'lpagery_fit';
			}

			foreach ( $secondary as $sec_key ) {
				if ( $sec_key === '' ) {
					continue;
				}
				if ( in_array( $sec_key, $affinity, true ) ) {
					$score        += self::POINTS_AFFINITY_SECONDARY;
					$reasons[]     = 'template_affinity_secondary';
					$source_refs[] = $sec_key;
				}
				if ( in_array( $sec_key, $required, true ) ) {
					$score        += self::POINTS_REQUIRED_SECONDARY;
					$reasons[]     = 'industry_required_secondary';
					$source_refs[] = $sec_key;
				}
				if ( in_array( $sec_key, $discouraged, true ) ) {
					$score        += self::POINTS_DISCOURAGED_SECONDARY;
					$reasons[]     = 'industry_discouraged_secondary';
					$source_refs[] = $sec_key;
				}
			}

			$fit     = $score >= self::SCORE_RECOMMENDED_MIN
				? self::FIT_RECOMMENDED
				: ( $score <= self::SCORE_DISCOURAGED_MAX ? self::FIT_DISCOURAGED : ( $score > 0 ? self::FIT_ALLOWED_WEAK : self::FIT_NEUTRAL ) );
			$items[] = array(
				'page_template_key'    => $template_key,
				'score'                => $score,
				'fit_classification'   => $fit,
				'explanation_reasons'  => array_values( array_unique( $reasons ) ),
				'industry_source_refs' => array_values( array_unique( $source_refs ) ),
				'hierarchy_fit'        => $hierarchy_fit,
				'lpagery_fit'          => $lpagery_fit,
				'warning_flags'        => $warnings,
			);
		}

		usort(
			$items,
			function ( array $a, array $b ) {
				$score_a = $a['score'] ?? 0;
				$score_b = $b['score'] ?? 0;
				if ( $score_b !== $score_a ) {
					return $score_b <=> $score_a;
				}
				return strcmp( $a['page_template_key'] ?? '', $b['page_template_key'] ?? '' );
			}
		);

		$result      = new Industry_Page_Template_Recommendation_Result( $items );
		$subtype_def = $options['subtype_definition'] ?? null;
		$extender    = $options['subtype_extender'] ?? null;
		if ( is_array( $subtype_def ) && $extender instanceof Industry_Subtype_Page_Template_Recommendation_Extender ) {
			$result = $extender->apply_subtype_influence( $result, $subtype_def, $page_templates );
		}
		if ( $this->cache_service !== null && $this->cache_key_builder !== null ) {
			$base_key = $this->cache_key_builder->for_page_template_recommendation( $industry_profile, $page_templates, $options_for_key );
			$this->cache_service->set( $base_key, $result->to_array() );
		}
		return $result;
	}

	/**
	 * @param mixed $val industry_affinity / industry_required / industry_discouraged (array of keys or map).
	 * @return list<string>
	 */
	private function normalize_industry_key_list( $val ): array {
		if ( ! is_array( $val ) ) {
			return array();
		}
		$out = array();
		foreach ( $val as $k => $v ) {
			if ( is_string( $k ) && trim( $k ) !== '' && preg_match( Page_Template_Schema::INDUSTRY_KEY_PATTERN, $k ) ) {
				$out[] = $k;
			} elseif ( is_string( $v ) && trim( $v ) !== '' && preg_match( Page_Template_Schema::INDUSTRY_KEY_PATTERN, trim( $v ) ) ) {
				$out[] = trim( $v );
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Extract per-industry string note from map or single string.
	 *
	 * @param mixed  $val industry_hierarchy_fit or industry_lpagery_fit value.
	 * @param string $industry_key Primary industry key.
	 * @return string
	 */
	private function extract_industry_note( $val, string $industry_key ): string {
		if ( is_array( $val ) && isset( $val[ $industry_key ] ) && is_string( $val[ $industry_key ] ) ) {
			return trim( $val[ $industry_key ] );
		}
		if ( is_string( $val ) && trim( $val ) !== '' ) {
			return trim( $val );
		}
		return '';
	}
}
