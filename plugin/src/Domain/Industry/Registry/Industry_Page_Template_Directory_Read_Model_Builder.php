<?php
/**
 * Builds the industry-aware page template directory read model for admin (industry-page-template-recommendation-contract).
 * Supports recommended-only, recommended-plus-weak-fit, and full-library views; invalid industry fails to full/neutral.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Weighted_Recommendation_Engine;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Builds a list of Industry_Page_Template_Directory_Item_View from page templates + industry context. Read-only.
 */
final class Industry_Page_Template_Directory_Read_Model_Builder {

	public const VIEW_RECOMMENDED_ONLY      = 'recommended_only';
	public const VIEW_RECOMMENDED_PLUS_WEAK = 'recommended_plus_weak_fit';
	public const VIEW_FULL_LIBRARY          = 'full_library';

	/** @var Industry_Page_Template_Recommendation_Resolver */
	private Industry_Page_Template_Recommendation_Resolver $resolver;

	/** @var Industry_Weighted_Recommendation_Engine|null */
	private ?Industry_Weighted_Recommendation_Engine $weighted_engine;

	public function __construct(
		?Industry_Page_Template_Recommendation_Resolver $resolver = null,
		?Industry_Weighted_Recommendation_Engine $weighted_engine = null
	) {
		$this->resolver        = $resolver ?? new Industry_Page_Template_Recommendation_Resolver();
		$this->weighted_engine = $weighted_engine;
	}

	/**
	 * Builds the read model: page templates with recommendation metadata, filtered by view mode. Invalid profile/pack → full_library (all templates, neutral).
	 *
	 * @param array<string, mixed>             $industry_profile primary_industry_key, optional secondary_industry_keys.
	 * @param array<string, mixed>|null        $primary_pack     Industry pack or null.
	 * @param array<int, array<string, mixed>> $page_templates List of page template definitions (each with internal_key).
	 * @param string                           $view_mode        One of VIEW_RECOMMENDED_ONLY, VIEW_RECOMMENDED_PLUS_WEAK, VIEW_FULL_LIBRARY.
	 * @return array<int, Industry_Page_Template_Directory_Item_View>
	 */
	public function build(
		array $industry_profile,
		?array $primary_pack,
		array $page_templates,
		string $view_mode = self::VIEW_FULL_LIBRARY
	): array {
		$result = $this->resolver->resolve( $industry_profile, $primary_pack, $page_templates, array() );
		$items  = $result->get_items();
		$by_key = array();
		foreach ( $items as $item ) {
			$key = $item['page_template_key'] ?? '';
			if ( $key === '' ) {
				continue;
			}
			$fit = $item['fit_classification'] ?? Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL;
			if ( $view_mode === self::VIEW_RECOMMENDED_ONLY && $fit !== Industry_Page_Template_Recommendation_Resolver::FIT_RECOMMENDED ) {
				continue;
			}
			if ( $view_mode === self::VIEW_RECOMMENDED_PLUS_WEAK && $fit === Industry_Page_Template_Recommendation_Resolver::FIT_DISCOURAGED ) {
				continue;
			}
			$template_def = array();
			foreach ( $page_templates as $t ) {
				if ( is_array( $t ) && trim( (string) ( $t[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' ) ) === $key ) {
					$template_def = $t;
					break;
				}
			}
			$by_key[ $key ] = new Industry_Page_Template_Directory_Item_View(
				$key,
				$fit,
				(int) ( $item['score'] ?? 0 ),
				isset( $item['explanation_reasons'] ) && is_array( $item['explanation_reasons'] ) ? array_values( $item['explanation_reasons'] ) : array(),
				isset( $item['industry_source_refs'] ) && is_array( $item['industry_source_refs'] ) ? array_values( $item['industry_source_refs'] ) : array(),
				isset( $item['hierarchy_fit'] ) && is_string( $item['hierarchy_fit'] ) ? $item['hierarchy_fit'] : '',
				isset( $item['lpagery_fit'] ) && is_string( $item['lpagery_fit'] ) ? $item['lpagery_fit'] : '',
				isset( $item['warning_flags'] ) && is_array( $item['warning_flags'] ) ? array_values( $item['warning_flags'] ) : array(),
				$template_def
			);
		}
		return array_values( $by_key );
	}

	/**
	 * Builds the read model and weighted results by template key when profile has secondary industries (Prompt 371).
	 *
	 * @param array<string, mixed>             $industry_profile
	 * @param array<string, mixed>|null        $primary_pack
	 * @param array<int, array<string, mixed>> $page_templates
	 * @param string                           $view_mode
	 * @return array{items: array<int, Industry_Page_Template_Directory_Item_View>, weighted_by_key: array<string, array<string, mixed>>}
	 */
	public function build_with_weighted(
		array $industry_profile,
		?array $primary_pack,
		array $page_templates,
		string $view_mode = self::VIEW_FULL_LIBRARY
	): array {
		$items           = $this->build( $industry_profile, $primary_pack, $page_templates, $view_mode );
		$weighted_by_key = array();
		if ( $this->weighted_engine !== null && $this->has_secondary_industries( $industry_profile ) ) {
			$result = $this->resolver->resolve( $industry_profile, $primary_pack, $page_templates, array() );
			foreach ( $result->get_items() as $item ) {
				$key = $item['page_template_key'] ?? '';
				if ( $key !== '' ) {
					$weighted_by_key[ $key ] = $this->weighted_engine->for_template_item( $industry_profile, $item );
				}
			}
		}
		return array(
			'items'           => $items,
			'weighted_by_key' => $weighted_by_key,
		);
	}

	private function has_secondary_industries( array $profile ): bool {
		$secondary = $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ?? $profile['secondary_industry_keys'] ?? array();
		return is_array( $secondary ) && count( $secondary ) > 0;
	}
}
