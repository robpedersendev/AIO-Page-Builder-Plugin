<?php
/**
 * Industry-aware scoring layer for Build Plan generation (industry-build-plan-scoring-contract.md).
 * Enriches normalized output with industry fit metadata; fails safely when profile is missing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Result;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface;

/**
 * Enriches normalized Build Plan draft output with industry recommendation scores and explanation metadata.
 * Additive only; no automatic execution. Safe fallback when industry context is weak or missing.
 */
final class Industry_Build_Plan_Scoring_Service {

	/** Context key for industry profile (array with primary_industry_key, optional secondary_industry_keys). */
	public const CONTEXT_INDUSTRY_PROFILE = 'industry_profile';

	/** Context key for primary industry pack (array or null). */
	public const CONTEXT_INDUSTRY_PRIMARY_PACK = 'industry_primary_pack';

	/** Additive record key: industry source refs. */
	public const RECORD_INDUSTRY_SOURCE_REFS = 'industry_source_refs';

	/** Additive record key: recommendation reason codes. */
	public const RECORD_RECOMMENDATION_REASONS = 'recommendation_reasons';

	/** Additive record key: industry fit score. */
	public const RECORD_INDUSTRY_FIT_SCORE = 'industry_fit_score';

	/** Additive record key: industry warning flags. */
	public const RECORD_INDUSTRY_WARNING_FLAGS = 'industry_warning_flags';

	/** Max page templates to load for resolver (bounded for large libraries). */
	private const PAGE_TEMPLATE_CAP = 500;

	/** @var Industry_Page_Template_Recommendation_Resolver */
	private $page_resolver;

	/** @var Page_Template_Repository_Interface */
	private $page_repo;

	/** @var Industry_Profile_Repository */
	private $profile_repo;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/**
	 * @param Industry_Page_Template_Recommendation_Resolver $page_resolver
	 * @param Page_Template_Repository_Interface              $page_repo
	 * @param Industry_Profile_Repository                    $profile_repo
	 * @param Industry_Pack_Registry|null                   $pack_registry
	 */
	public function __construct(
		Industry_Page_Template_Recommendation_Resolver $page_resolver,
		Page_Template_Repository_Interface $page_repo,
		Industry_Profile_Repository $profile_repo,
		?Industry_Pack_Registry $pack_registry = null
	) {
		$this->page_resolver = $page_resolver;
		$this->page_repo     = $page_repo;
		$this->profile_repo = $profile_repo;
		$this->pack_registry = $pack_registry;
	}

	/**
	 * Enriches normalized output with industry metadata for new_pages_to_create and existing_page_changes.
	 * Returns input unchanged when industry profile is missing or primary_industry_key is empty.
	 *
	 * @param array<string, mixed> $normalized_output Validated Build_Plan_Draft_Schema-shaped output.
	 * @param array<string, mixed> $context          Optional: industry_profile, industry_primary_pack; else resolved from repo/registry.
	 * @return array<string, mixed> Same structure with additive keys on page-related records; or unchanged if no profile.
	 */
	public function enrich_output( array $normalized_output, array $context = array() ): array {
		$profile = $this->resolve_profile( $context );
		$primary_key = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		if ( $primary_key === '' ) {
			return $normalized_output;
		}

		$primary_pack = $this->resolve_primary_pack( $context, $primary_key );
		$page_templates = $this->page_repo->list_all_definitions( self::PAGE_TEMPLATE_CAP, 0 );
		if ( empty( $page_templates ) ) {
			return $normalized_output;
		}

		$recommendation_result = $this->page_resolver->resolve( $profile, $primary_pack, $page_templates, array() );
		$score_by_key = $this->index_result_by_template_key( $recommendation_result );

		$out = $normalized_output;

		$new_pages = isset( $out[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] ) && is_array( $out[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
			? $out[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ]
			: array();
		$out[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] = $this->enrich_page_records( $new_pages, $score_by_key, 'template_key' );
		$out[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] = $this->sort_new_pages_by_fit( $out[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ], $score_by_key );

		$existing = isset( $out[ Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES ] ) && is_array( $out[ Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES ] )
			? $out[ Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES ]
			: array();
		$out[ Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES ] = $this->enrich_page_records( $existing, $score_by_key, 'target_template_key' );

		return $out;
	}

	/**
	 * @param array<string, mixed> $context
	 * @return array<string, mixed>
	 */
	private function resolve_profile( array $context ): array {
		if ( isset( $context[ self::CONTEXT_INDUSTRY_PROFILE ] ) && is_array( $context[ self::CONTEXT_INDUSTRY_PROFILE ] ) ) {
			return $context[ self::CONTEXT_INDUSTRY_PROFILE ];
		}
		return $this->profile_repo->get_profile();
	}

	/**
	 * @param array<string, mixed> $context
	 * @param string               $primary_key
	 * @return array<string, mixed>|null
	 */
	private function resolve_primary_pack( array $context, string $primary_key ): ?array {
		if ( isset( $context[ self::CONTEXT_INDUSTRY_PRIMARY_PACK ] ) && is_array( $context[ self::CONTEXT_INDUSTRY_PRIMARY_PACK ] ) ) {
			return $context[ self::CONTEXT_INDUSTRY_PRIMARY_PACK ];
		}
		if ( $this->pack_registry !== null ) {
			return $this->pack_registry->get( $primary_key );
		}
		return null;
	}

	/**
	 * @param Industry_Page_Template_Recommendation_Result $result
	 * @return array<string, array{score: int, fit_classification: string, explanation_reasons: array, industry_source_refs: array, warning_flags: array}>
	 */
	private function index_result_by_template_key( Industry_Page_Template_Recommendation_Result $result ): array {
		$index = array();
		foreach ( $result->get_items() as $item ) {
			$key = $item['page_template_key'] ?? '';
			if ( $key !== '' ) {
				$index[ $key ] = array(
					'score'                => (int) ( $item['score'] ?? 0 ),
					'fit_classification'  => (string) ( $item['fit_classification'] ?? 'neutral' ),
					'explanation_reasons' => is_array( $item['explanation_reasons'] ?? null ) ? $item['explanation_reasons'] : array(),
					'industry_source_refs' => is_array( $item['industry_source_refs'] ?? null ) ? $item['industry_source_refs'] : array(),
					'warning_flags'       => is_array( $item['warning_flags'] ?? null ) ? $item['warning_flags'] : array(),
				);
			}
		}
		return $index;
	}

	/**
	 * @param array<int, array<string, mixed>> $records
	 * @param array<string, array<string, mixed>> $score_by_key
	 * @param string $template_key_field Record key for template (template_key or target_template_key).
	 * @return array<int, array<string, mixed>>
	 */
	private function enrich_page_records( array $records, array $score_by_key, string $template_key_field ): array {
		$out = array();
		foreach ( $records as $rec ) {
			if ( ! is_array( $rec ) ) {
				$out[] = $rec;
				continue;
			}
			$template_key = isset( $rec[ $template_key_field ] ) && is_string( $rec[ $template_key_field ] )
				? trim( $rec[ $template_key_field ] )
				: '';
			$enriched = $rec;
			if ( $template_key !== '' && isset( $score_by_key[ $template_key ] ) ) {
				$info = $score_by_key[ $template_key ];
				$enriched[ self::RECORD_INDUSTRY_SOURCE_REFS ]    = $info['industry_source_refs'];
				$enriched[ self::RECORD_RECOMMENDATION_REASONS ]   = $info['explanation_reasons'];
				$enriched[ self::RECORD_INDUSTRY_FIT_SCORE ]       = $info['score'];
				$enriched[ self::RECORD_INDUSTRY_WARNING_FLAGS ]   = $info['warning_flags'];
			}
			$out[] = $enriched;
		}
		return $out;
	}

	/**
	 * Sorts new_pages by fit: recommended first, then allowed_weak_fit, neutral, discouraged.
	 *
	 * @param array<int, array<string, mixed>> $records
	 * @param array<string, array<string, mixed>> $score_by_key
	 * @return array<int, array<string, mixed>>
	 */
	private function sort_new_pages_by_fit( array $records, array $score_by_key ): array {
		$order = array(
			Industry_Page_Template_Recommendation_Resolver::FIT_RECOMMENDED     => 0,
			Industry_Page_Template_Recommendation_Resolver::FIT_ALLOWED_WEAK    => 1,
			Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL         => 2,
			Industry_Page_Template_Recommendation_Resolver::FIT_DISCOURAGED       => 3,
		);
		usort( $records, function ( array $a, array $b ) use ( $score_by_key, $order ): int {
			$key_a = isset( $a['template_key'] ) && is_string( $a['template_key'] ) ? trim( $a['template_key'] ) : '';
			$key_b = isset( $b['template_key'] ) && is_string( $b['template_key'] ) ? trim( $b['template_key'] ) : '';
			$fit_a = isset( $score_by_key[ $key_a ]['fit_classification'] ) ? $score_by_key[ $key_a ]['fit_classification'] : Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL;
			$fit_b = isset( $score_by_key[ $key_b ]['fit_classification'] ) ? $score_by_key[ $key_b ]['fit_classification'] : Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL;
			$ord_a = $order[ $fit_a ] ?? 2;
			$ord_b = $order[ $fit_b ] ?? 2;
			if ( $ord_a !== $ord_b ) {
				return $ord_a <=> $ord_b;
			}
			$score_a = isset( $score_by_key[ $key_a ]['score'] ) ? (int) $score_by_key[ $key_a ]['score'] : 0;
			$score_b = isset( $score_by_key[ $key_b ]['score'] ) ? (int) $score_by_key[ $key_b ]['score'] : 0;
			if ( $score_b !== $score_a ) {
				return $score_b <=> $score_a;
			}
			return 0;
		} );
		return $records;
	}
}
