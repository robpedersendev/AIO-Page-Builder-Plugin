<?php
/**
 * Industry-aware assistant for the composition builder (Prompt 377, industry-admin-screen-contract).
 * Surfaces recommended sections, fit/warnings for chosen sections, and substitute suggestions. Advisory only; no auto-swap.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Compositions;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Substitute_Suggestion_Engine;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Provides industry-aware section guidance for composition builder: recommended sections, warnings, substitutes.
 */
final class Industry_Composition_Assistant {

	/** @var Industry_Profile_Repository */
	private $profile_repository;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Section_Recommendation_Resolver */
	private $resolver;

	/** @var array<string, array{fit: string, warning_flags: array, purpose_family: string}>|null Cached by-key fit data (purpose_family from section_purpose_family). */
	private $fit_by_key;

	/** @var array<int, string>|null Cached recommended section keys (ranked). */
	private $recommended_keys;

	/** @var Industry_Substitute_Suggestion_Engine|null Optional engine for richer substitute suggestions. */
	private $substitute_engine;

	/** @var \AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Result|null Cached from last build_state when substitute_engine is set. */
	private $last_section_result;

	/** @var array<int, array<string, mixed>>|null Section definitions from last build_state (for engine). */
	private $section_definitions;

	public function __construct(
		Industry_Profile_Repository $profile_repository,
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Section_Recommendation_Resolver $resolver = null,
		?Industry_Substitute_Suggestion_Engine $substitute_engine = null
	) {
		$this->profile_repository  = $profile_repository;
		$this->pack_registry       = $pack_registry;
		$this->resolver            = $resolver ?? new Industry_Section_Recommendation_Resolver();
		$this->substitute_engine   = $substitute_engine;
		$this->last_section_result = null;
		$this->section_definitions = null;
		$this->fit_by_key          = null;
		$this->recommended_keys    = null;
	}

	/**
	 * Builds recommendation state from current profile and the given section list. Call before get_* methods.
	 *
	 * @param array<int, array<string, mixed>> $sections List of section definitions (internal_key; optional purpose_family, industry_*).
	 * @return void
	 */
	public function build_state( array $sections ): void {
		$this->fit_by_key       = null;
		$this->recommended_keys = null;

		$profile = $this->profile_repository->get_profile();
		$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		if ( $primary === '' || empty( $sections ) ) {
			return;
		}

		$primary_pack = null;
		if ( $this->pack_registry !== null ) {
			$primary_pack = $this->pack_registry->get( $primary );
		}

		$result = $this->resolver->resolve( $profile, $primary_pack, $sections, array() );
		if ( $this->substitute_engine !== null ) {
			$this->last_section_result = $result;
		}
		$items = $result->get_items();

		$this->fit_by_key       = array();
		$this->recommended_keys = array();
		foreach ( $items as $item ) {
			$key = $item['section_key'] ?? '';
			if ( $key === '' ) {
				continue;
			}
			$fit            = $item['fit_classification'] ?? Industry_Section_Recommendation_Resolver::FIT_NEUTRAL;
			$warning_flags  = isset( $item['warning_flags'] ) && is_array( $item['warning_flags'] ) ? $item['warning_flags'] : array();
			$purpose_family = '';
			foreach ( $sections as $s ) {
				if ( is_array( $s ) && trim( (string) ( $s[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ) ) === $key ) {
					$purpose_family = isset( $s['section_purpose_family'] ) && is_string( $s['section_purpose_family'] ) ? trim( $s['section_purpose_family'] ) : '';
					break;
				}
			}
			$this->fit_by_key[ $key ] = array(
				'fit'            => $fit,
				'warning_flags'  => $warning_flags,
				'purpose_family' => $purpose_family,
			);
			if ( $fit === Industry_Section_Recommendation_Resolver::FIT_RECOMMENDED ) {
				$this->recommended_keys[] = $key;
			}
		}
	}

	/**
	 * Whether industry guidance is available (profile has primary and state was built with sections).
	 *
	 * @return bool
	 */
	public function has_industry_guidance(): bool {
		return $this->fit_by_key !== null && ! empty( $this->fit_by_key );
	}

	/**
	 * Recommended section keys in rank order (best first). Empty when no guidance.
	 *
	 * @return array<int, string>
	 */
	public function get_recommended_section_keys(): array {
		return $this->recommended_keys !== null ? $this->recommended_keys : array();
	}

	/**
	 * Fit classification for a section key: recommended, allowed_weak_fit, discouraged, neutral.
	 *
	 * @param string $section_key Section internal key.
	 * @return string
	 */
	public function get_fit_for_section( string $section_key ): string {
		if ( $this->fit_by_key === null ) {
			return Industry_Section_Recommendation_Resolver::FIT_NEUTRAL;
		}
		$data = $this->fit_by_key[ $section_key ] ?? null;
		return $data !== null ? $data['fit'] : Industry_Section_Recommendation_Resolver::FIT_NEUTRAL;
	}

	/**
	 * Warning flags for a section key.
	 *
	 * @param string $section_key Section internal key.
	 * @return array<int, string>
	 */
	public function get_warning_flags_for_section( string $section_key ): array {
		if ( $this->fit_by_key === null ) {
			return array();
		}
		$data = $this->fit_by_key[ $section_key ] ?? null;
		return $data !== null ? $data['warning_flags'] : array();
	}

	/**
	 * Substitute section keys: recommended sections that could replace the given one (same purpose_family preferred). Excludes current key.
	 *
	 * @param string $section_key Section internal key.
	 * @param int    $max         Max substitutes to return (default 5).
	 * @return array<int, string>
	 */
	public function get_substitute_section_keys( string $section_key, int $max = 5 ): array {
		if ( $this->recommended_keys === null || $this->fit_by_key === null ) {
			return array();
		}
		$current_family = $this->fit_by_key[ $section_key ]['purpose_family'] ?? '';
		$same_family    = array();
		$other          = array();
		foreach ( $this->recommended_keys as $key ) {
			if ( $key === $section_key ) {
				continue;
			}
			$fam = $this->fit_by_key[ $key ]['purpose_family'] ?? '';
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
	 * Returns structured substitute suggestions for a section (when substitute engine is set and section is discouraged/weak-fit).
	 *
	 * @param string $section_key Section internal key.
	 * @param int    $max         Max suggestions (default 5).
	 * @return array<int, array<string, mixed>> List of Industry_Substitute_Suggestion_Result shapes.
	 */
	public function get_substitute_suggestions_for_section( string $section_key, int $max = 5 ): array {
		if ( $this->substitute_engine === null || $this->last_section_result === null || $this->section_definitions === null ) {
			return array();
		}
		$fit = $this->get_fit_for_section( $section_key );
		return $this->substitute_engine->suggest_section_substitutes(
			$section_key,
			$fit,
			$this->last_section_result,
			$this->section_definitions,
			$max
		);
	}
}
