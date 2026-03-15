<?php
/**
 * Industry-aware assistant for the create-page-from-template flow (Prompt 376, industry-admin-screen-contract).
 * Surfaces recommended templates, fit/warnings for a chosen template, and substitute suggestions. Advisory only; no auto-apply.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\PageTemplates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Substitute_Suggestion_Engine;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Provides industry-aware template guidance for page creation: recommended first, warnings, substitutes.
 */
final class Industry_Create_Page_Assistant {

	/** @var Industry_Profile_Repository */
	private $profile_repository;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Page_Template_Recommendation_Resolver */
	private $resolver;

	/** @var array<int, array<string, mixed>>|null Cached template list from last build. */
	private $template_definitions;

	/** @var array<string, array{fit: string, warning_flags: array, template_family: string}>|null Cached by-key fit data. */
	private $fit_by_key;

	/** @var list<string>|null Cached recommended keys (ranked). */
	private $recommended_keys;

	/** @var Industry_Substitute_Suggestion_Engine|null Optional engine for richer substitute suggestions. */
	private $substitute_engine;

	/** @var \AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Result|null Cached from last build_state when substitute_engine is set. */
	private $last_template_result;

	public function __construct(
		Industry_Profile_Repository $profile_repository,
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Page_Template_Recommendation_Resolver $resolver = null,
		?Industry_Substitute_Suggestion_Engine $substitute_engine = null
	) {
		$this->profile_repository   = $profile_repository;
		$this->pack_registry        = $pack_registry;
		$this->resolver             = $resolver ?? new Industry_Page_Template_Recommendation_Resolver();
		$this->substitute_engine    = $substitute_engine;
		$this->last_template_result = null;
		$this->template_definitions = null;
		$this->fit_by_key           = null;
		$this->recommended_keys     = null;
	}

	/**
	 * Builds recommendation state from current profile and the given page template list. Call before get_* methods.
	 *
	 * @param array<int, array<string, mixed>> $page_templates List of page template definitions (internal_key, optional template_family).
	 * @return void
	 */
	public function build_state( array $page_templates ): void {
		$this->template_definitions  = $page_templates;
		$this->fit_by_key            = null;
		$this->recommended_keys      = null;
		$this->last_template_result  = null;

		$profile = $this->profile_repository->get_profile();
		$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		if ( $primary === '' || empty( $page_templates ) ) {
			return;
		}

		$primary_pack = null;
		if ( $this->pack_registry !== null ) {
			$primary_pack = $this->pack_registry->get( $primary );
		}

		$result = $this->resolver->resolve( $profile, $primary_pack, $page_templates, array() );
		if ( $this->substitute_engine !== null ) {
			$this->last_template_result = $result;
		}
		$items = $result->get_items();

		$this->fit_by_key = array();
		$this->recommended_keys = array();
		foreach ( $items as $item ) {
			$key = $item['page_template_key'] ?? '';
			if ( $key === '' ) {
				continue;
			}
			$fit = $item['fit_classification'] ?? Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL;
			$warning_flags = isset( $item['warning_flags'] ) && is_array( $item['warning_flags'] ) ? $item['warning_flags'] : array();
			$template_family = '';
			foreach ( $page_templates as $t ) {
				if ( is_array( $t ) && trim( (string) ( $t[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' ) ) === $key ) {
					$template_family = isset( $t['template_family'] ) && is_string( $t['template_family'] ) ? trim( $t['template_family'] ) : '';
					break;
				}
			}
			$this->fit_by_key[ $key ] = array( 'fit' => $fit, 'warning_flags' => $warning_flags, 'template_family' => $template_family );
			if ( $fit === Industry_Page_Template_Recommendation_Resolver::FIT_RECOMMENDED ) {
				$this->recommended_keys[] = $key;
			}
		}
	}

	/**
	 * Whether industry guidance is available (profile has primary and state was built with templates).
	 *
	 * @return bool
	 */
	public function has_industry_guidance(): bool {
		return $this->fit_by_key !== null && ! empty( $this->fit_by_key );
	}

	/**
	 * Recommended template keys in rank order (best first). Empty when no guidance.
	 *
	 * @return list<string>
	 */
	public function get_recommended_template_keys(): array {
		return $this->recommended_keys !== null ? $this->recommended_keys : array();
	}

	/**
	 * Fit classification for a template key: recommended, allowed_weak_fit, discouraged, neutral.
	 *
	 * @param string $template_key Page template internal key.
	 * @return string
	 */
	public function get_fit_for_template( string $template_key ): string {
		if ( $this->fit_by_key === null ) {
			return Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL;
		}
		$data = $this->fit_by_key[ $template_key ] ?? null;
		return $data !== null ? $data['fit'] : Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL;
	}

	/**
	 * Warning flags for a template key (e.g. hierarchy_mismatch, discouraged_for_industry).
	 *
	 * @param string $template_key Page template internal key.
	 * @return list<string>
	 */
	public function get_warning_flags_for_template( string $template_key ): array {
		if ( $this->fit_by_key === null ) {
			return array();
		}
		$data = $this->fit_by_key[ $template_key ] ?? null;
		return $data !== null ? $data['warning_flags'] : array();
	}

	/**
	 * Substitute template keys: recommended templates that could replace the given one (same family preferred, then other recommended). Excludes current key.
	 *
	 * @param string $template_key Page template internal key.
	 * @param int    $max          Max substitutes to return (default 5).
	 * @return list<string>
	 */
	public function get_substitute_template_keys( string $template_key, int $max = 5 ): array {
		if ( $this->recommended_keys === null || $this->fit_by_key === null ) {
			return array();
		}
		$current_family = $this->fit_by_key[ $template_key ]['template_family'] ?? '';
		$same_family = array();
		$other = array();
		foreach ( $this->recommended_keys as $key ) {
			if ( $key === $template_key ) {
				continue;
			}
			$fam = $this->fit_by_key[ $key ]['template_family'] ?? '';
			if ( $fam !== '' && $fam === $current_family ) {
				$same_family[] = $key;
			} else {
				$other[] = $key;
			}
		}
		$merged = array_merge( $same_family, $other );
		return array_slice( $merged, 0, $max );
	}

	/**
	 * Returns structured substitute suggestions for a template (when substitute engine is set and template is discouraged/weak-fit).
	 *
	 * @param string $template_key Page template internal key.
	 * @param int    $max          Max suggestions (default 5).
	 * @return array<int, array<string, mixed>> List of Industry_Substitute_Suggestion_Result shapes.
	 */
	public function get_substitute_suggestions_for_template( string $template_key, int $max = 5 ): array {
		if ( $this->substitute_engine === null || $this->last_template_result === null || $this->template_definitions === null ) {
			return array();
		}
		$fit = $this->get_fit_for_template( $template_key );
		return $this->substitute_engine->suggest_template_substitutes(
			$template_key,
			$fit,
			$this->last_template_result,
			$this->template_definitions,
			$max
		);
	}
}
