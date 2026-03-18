<?php
/**
 * Suggests better-fit section or template alternatives for discouraged/weak-fit choices (industry-substitute-suggestion-contract.md).
 * Read-only; uses recommendation data and family/category metadata. Deterministic and explainable.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Proposes substitute sections or templates based on industry recommendation and similarity rules.
 */
final class Industry_Substitute_Suggestion_Engine {

	private const FIT_RECOMMENDED  = 'recommended';
	private const FIT_NEUTRAL      = 'neutral';
	private const FIT_ALLOWED_WEAK = 'allowed_weak_fit';
	private const FIT_DISCOURAGED  = 'discouraged';

	/** Section item key in resolver result. */
	private const SECTION_KEY_FIELD = 'section_key';
	/** Template item key in resolver result. */
	private const TEMPLATE_KEY_FIELD = 'page_template_key';

	/**
	 * Suggests substitute sections when the original is discouraged or weak-fit.
	 *
	 * @param string                                 $original_section_key Section key that is discouraged or weak-fit.
	 * @param string                                 $section_fit          Fit classification for the original (discouraged or allowed_weak_fit to suggest).
	 * @param Industry_Section_Recommendation_Result $result          Resolver result with all section scores/fit.
	 * @param array<int, array<string, mixed>>       $section_definitions  Section definitions (internal_key, section_purpose_family).
	 * @param int                                    $max                  Max suggestions to return (default 5).
	 * @return array<int, array<string, mixed>> List of Industry_Substitute_Suggestion_Result shapes.
	 */
	public function suggest_section_substitutes(
		string $original_section_key,
		string $section_fit,
		Industry_Section_Recommendation_Result $result,
		array $section_definitions,
		int $max = 5
	): array {
		if ( $section_fit === self::FIT_RECOMMENDED || $section_fit === self::FIT_NEUTRAL ) {
			return array();
		}
		$items             = $result->get_items();
		$by_key            = $this->index_section_items_by_key( $items );
		$original_score    = isset( $by_key[ $original_section_key ] ) ? (int) ( $by_key[ $original_section_key ]['score'] ?? 0 ) : 0;
		$original_warnings = isset( $by_key[ $original_section_key ] ) && is_array( $by_key[ $original_section_key ]['warning_flags'] ?? null )
			? $by_key[ $original_section_key ]['warning_flags']
			: array();
		$original_family   = $this->get_section_purpose_family( $original_section_key, $section_definitions );

		$candidates = array();
		foreach ( $items as $item ) {
			$key = $item[ self::SECTION_KEY_FIELD ] ?? '';
			if ( $key === '' || $key === $original_section_key ) {
				continue;
			}
			$fit = $item['fit_classification'] ?? self::FIT_NEUTRAL;
			if ( $fit !== self::FIT_RECOMMENDED ) {
				continue;
			}
			$score        = (int) ( $item['score'] ?? 0 );
			$family       = $this->get_section_purpose_family( $key, $section_definitions );
			$candidates[] = array(
				'key'    => $key,
				'score'  => $score,
				'family' => $family,
			);
		}

		return $this->build_section_suggestions(
			$original_section_key,
			$original_score,
			$original_warnings,
			$original_family,
			$candidates,
			$max
		);
	}

	/**
	 * Suggests substitute page templates when the original is discouraged or weak-fit.
	 *
	 * @param string                                       $original_template_key Template key that is discouraged or weak-fit.
	 * @param string                                       $template_fit          Fit classification for the original.
	 * @param Industry_Page_Template_Recommendation_Result $result          Resolver result with all template scores/fit.
	 * @param array<int, array<string, mixed>>             $template_definitions  Template definitions (internal_key, template_family).
	 * @param int                                          $max                   Max suggestions to return (default 5).
	 * @return array<int, array<string, mixed>> List of Industry_Substitute_Suggestion_Result shapes.
	 */
	public function suggest_template_substitutes(
		string $original_template_key,
		string $template_fit,
		Industry_Page_Template_Recommendation_Result $result,
		array $template_definitions,
		int $max = 5
	): array {
		if ( $template_fit === self::FIT_RECOMMENDED || $template_fit === self::FIT_NEUTRAL ) {
			return array();
		}
		$items             = $result->get_items();
		$by_key            = $this->index_template_items_by_key( $items );
		$original_score    = isset( $by_key[ $original_template_key ] ) ? (int) ( $by_key[ $original_template_key ]['score'] ?? 0 ) : 0;
		$original_warnings = isset( $by_key[ $original_template_key ] ) && is_array( $by_key[ $original_template_key ]['warning_flags'] ?? null )
			? $by_key[ $original_template_key ]['warning_flags']
			: array();
		$original_family   = $this->get_template_family( $original_template_key, $template_definitions );

		$candidates = array();
		foreach ( $items as $item ) {
			$key = $item[ self::TEMPLATE_KEY_FIELD ] ?? '';
			if ( $key === '' || $key === $original_template_key ) {
				continue;
			}
			$fit = $item['fit_classification'] ?? self::FIT_NEUTRAL;
			if ( $fit !== self::FIT_RECOMMENDED ) {
				continue;
			}
			$score        = (int) ( $item['score'] ?? 0 );
			$family       = $this->get_template_family( $key, $template_definitions );
			$candidates[] = array(
				'key'    => $key,
				'score'  => $score,
				'family' => $family,
			);
		}

		return $this->build_template_suggestions(
			$original_template_key,
			$original_score,
			$original_warnings,
			$original_family,
			$candidates,
			$max
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 * @return array<string, array<string, mixed>>
	 */
	private function index_section_items_by_key( array $items ): array {
		$out = array();
		foreach ( $items as $item ) {
			$key = $item[ self::SECTION_KEY_FIELD ] ?? '';
			if ( $key !== '' ) {
				$out[ $key ] = $item;
			}
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 * @return array<string, array<string, mixed>>
	 */
	private function index_template_items_by_key( array $items ): array {
		$out = array();
		foreach ( $items as $item ) {
			$key = $item[ self::TEMPLATE_KEY_FIELD ] ?? '';
			if ( $key !== '' ) {
				$out[ $key ] = $item;
			}
		}
		return $out;
	}

	private function get_section_purpose_family( string $section_key, array $section_definitions ): string {
		foreach ( $section_definitions as $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			$key = isset( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ) && is_string( $def[ Section_Schema::FIELD_INTERNAL_KEY ] )
				? trim( $def[ Section_Schema::FIELD_INTERNAL_KEY ] )
				: '';
			if ( $key === $section_key ) {
				return isset( $def['section_purpose_family'] ) && is_string( $def['section_purpose_family'] ) ? trim( $def['section_purpose_family'] ) : '';
			}
		}
		return '';
	}

	private function get_template_family( string $template_key, array $template_definitions ): string {
		foreach ( $template_definitions as $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			$key = isset( $def['internal_key'] ) && is_string( $def['internal_key'] ) ? trim( $def['internal_key'] ) : '';
			if ( $key === $template_key ) {
				return isset( $def['template_family'] ) && is_string( $def['template_family'] ) ? trim( $def['template_family'] ) : '';
			}
		}
		return '';
	}

	/**
	 * @param array<int, array{key: string, score: int, family: string}> $candidates
	 * @return array<int, array<string, mixed>>
	 */
	private function build_section_suggestions(
		string $original_key,
		int $original_score,
		array $original_warnings,
		string $original_family,
		array $candidates,
		int $max
	): array {
		usort(
			$candidates,
			function ( array $a, array $b ) use ( $original_family ): int {
				$fam_a = $a['family'] !== '' && $a['family'] === $original_family ? 1 : 0;
				$fam_b = $b['family'] !== '' && $b['family'] === $original_family ? 1 : 0;
				if ( $fam_a !== $fam_b ) {
					return $fam_b - $fam_a;
				}
				if ( $a['score'] !== $b['score'] ) {
					return $b['score'] - $a['score'];
				}
				return strcmp( $a['key'], $b['key'] );
			}
		);

		$out = array();
		foreach ( array_slice( $candidates, 0, $max ) as $c ) {
			$reason = ( $original_family !== '' && $c['family'] === $original_family )
				? Industry_Substitute_Suggestion_Result::REASON_SAME_FAMILY_BETTER_FIT
				: Industry_Substitute_Suggestion_Result::REASON_RECOMMENDED_ALTERNATIVE;
			$delta  = $c['score'] - $original_score;
			$out[]  = Industry_Substitute_Suggestion_Result::create(
				$original_key,
				$c['key'],
				$reason,
				$delta,
				$original_warnings
			);
		}
		return $out;
	}

	/**
	 * @param array<int, array{key: string, score: int, family: string}> $candidates
	 * @return array<int, array<string, mixed>>
	 */
	private function build_template_suggestions(
		string $original_key,
		int $original_score,
		array $original_warnings,
		string $original_family,
		array $candidates,
		int $max
	): array {
		usort(
			$candidates,
			function ( array $a, array $b ) use ( $original_family ): int {
				$fam_a = $a['family'] !== '' && $a['family'] === $original_family ? 1 : 0;
				$fam_b = $b['family'] !== '' && $b['family'] === $original_family ? 1 : 0;
				if ( $fam_a !== $fam_b ) {
					return $fam_b - $fam_a;
				}
				if ( $a['score'] !== $b['score'] ) {
					return $b['score'] - $a['score'];
				}
				return strcmp( $a['key'], $b['key'] );
			}
		);

		$out = array();
		foreach ( array_slice( $candidates, 0, $max ) as $c ) {
			$reason = ( $original_family !== '' && $c['family'] === $original_family )
				? Industry_Substitute_Suggestion_Result::REASON_SAME_FAMILY_BETTER_FIT
				: Industry_Substitute_Suggestion_Result::REASON_RECOMMENDED_ALTERNATIVE;
			$delta  = $c['score'] - $original_score;
			$out[]  = Industry_Substitute_Suggestion_Result::create(
				$original_key,
				$c['key'],
				$reason,
				$delta,
				$original_warnings
			);
		}
		return $out;
	}
}
