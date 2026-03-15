<?php
/**
 * Applies multi-industry precedence and conflict detection to recommendation results (Prompt 371).
 * Consumes resolver output (section, page template, or Build Plan item); produces weighted result with conflicts and explanation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;

/**
 * Read-only engine: no resolver calls; takes existing recommendation item and profile, returns weighted result.
 * Single-industry: conflict_results empty, explanation from reasons. Multi-industry: detects warning_worthy conflicts.
 */
final class Industry_Weighted_Recommendation_Engine {

	/** Section reason: primary preferred (pack). */
	private const REASON_PACK_PREFERRED = 'in_pack_preferred';

	/** Section reason: primary discouraged (pack). */
	private const REASON_PACK_DISCOURAGED = 'in_pack_discouraged';

	/** Section reason: section affinity primary. */
	private const REASON_SECTION_AFFINITY_PRIMARY = 'section_affinity_primary';

	/** Section reason: section discouraged primary. */
	private const REASON_SECTION_DISCOURAGED_PRIMARY = 'section_discouraged_primary';

	/** Section reason: section affinity secondary. */
	private const REASON_SECTION_AFFINITY_SECONDARY = 'section_affinity_secondary';

	/** Section reason: section discouraged secondary. */
	private const REASON_SECTION_DISCOURAGED_SECONDARY = 'section_discouraged_secondary';

	/** Template reason: affinity primary. */
	private const REASON_TEMPLATE_AFFINITY_PRIMARY = 'template_affinity_primary';

	/** Template reason: discouraged primary. */
	private const REASON_TEMPLATE_DISCOURAGED_PRIMARY = 'industry_discouraged_primary';

	/** Template reason: affinity secondary. */
	private const REASON_TEMPLATE_AFFINITY_SECONDARY = 'template_affinity_secondary';

	/** Template reason: discouraged secondary. */
	private const REASON_TEMPLATE_DISCOURAGED_SECONDARY = 'industry_discouraged_secondary';

	/**
	 * Builds weighted result for one section recommendation item. Adds conflict when primary and secondary disagree.
	 *
	 * @param array<string, mixed> $profile Industry profile (primary_industry_key, optional secondary_industry_keys).
	 * @param array<string, mixed> $item   Section item (section_key, score, explanation_reasons, industry_source_refs, warning_flags).
	 * @return array<string, mixed> Industry_Weighted_Recommendation_Result shape.
	 */
	public function for_section_item( array $profile, array $item ): array {
		$primary = $this->primary_key( $profile );
		$secondary = $this->secondary_keys( $profile );
		$score = (int) ( $item['score'] ?? 0 );
		$reasons = isset( $item['explanation_reasons'] ) && is_array( $item['explanation_reasons'] )
			? $item['explanation_reasons']
			: array();
		$source_refs = isset( $item['industry_source_refs'] ) && is_array( $item['industry_source_refs'] )
			? array_values( array_filter( array_map( 'strval', $item['industry_source_refs'] ) ) )
			: array();

		$conflicts = array();
		if ( $primary !== '' && count( $secondary ) > 0 ) {
			$primary_positive = $this->section_primary_positive( $reasons );
			$primary_negative = $this->section_primary_negative( $reasons );
			$secondary_positive = $this->section_secondary_positive( $reasons );
			$secondary_negative = $this->section_secondary_negative( $reasons );
			if ( ( $primary_positive && $secondary_negative ) || ( $primary_negative && $secondary_positive ) ) {
				$industries = array_filter( array_merge( array( $primary ), $secondary ) );
				$conflicts[] = Industry_Conflict_Result::create(
					Industry_Conflict_Result::CONFLICT_TYPE_SECTION_FIT,
					$industries,
					Industry_Conflict_Result::RESOLUTION_PRIMARY_WINS,
					$primary_positive && $secondary_negative
						? __( 'Primary industry recommends; secondary discourages. Primary applied.', 'aio-page-builder' )
						: __( 'Primary industry discourages; secondary favors. Primary applied.', 'aio-page-builder' ),
					Industry_Conflict_Result::SEVERITY_WARNING
				);
			}
		}

		$summary = $this->section_explanation_summary( $reasons, $source_refs, $primary, $secondary );
		return Industry_Weighted_Recommendation_Result::create( $score, $source_refs, $conflicts, $summary );
	}

	/**
	 * Builds weighted result for one page template recommendation item.
	 *
	 * @param array<string, mixed> $profile Industry profile.
	 * @param array<string, mixed> $item   Template item (page_template_key, score, explanation_reasons, industry_source_refs, ...).
	 * @return array<string, mixed> Industry_Weighted_Recommendation_Result shape.
	 */
	public function for_template_item( array $profile, array $item ): array {
		$primary = $this->primary_key( $profile );
		$secondary = $this->secondary_keys( $profile );
		$score = (int) ( $item['score'] ?? 0 );
		$reasons = isset( $item['explanation_reasons'] ) && is_array( $item['explanation_reasons'] )
			? $item['explanation_reasons']
			: array();
		$source_refs = isset( $item['industry_source_refs'] ) && is_array( $item['industry_source_refs'] )
			? array_values( array_filter( array_map( 'strval', $item['industry_source_refs'] ) ) )
			: array();

		$conflicts = array();
		if ( $primary !== '' && count( $secondary ) > 0 ) {
			$primary_positive = $this->template_primary_positive( $reasons );
			$primary_negative = $this->template_primary_negative( $reasons );
			$secondary_positive = $this->template_secondary_positive( $reasons );
			$secondary_negative = $this->template_secondary_negative( $reasons );
			if ( ( $primary_positive && $secondary_negative ) || ( $primary_negative && $secondary_positive ) ) {
				$industries = array_filter( array_merge( array( $primary ), $secondary ) );
				$conflicts[] = Industry_Conflict_Result::create(
					Industry_Conflict_Result::CONFLICT_TYPE_TEMPLATE_FIT,
					$industries,
					Industry_Conflict_Result::RESOLUTION_PRIMARY_WINS,
					$primary_positive && $secondary_negative
						? __( 'Primary industry recommends template; secondary discourages. Primary applied.', 'aio-page-builder' )
						: __( 'Primary industry discourages template; secondary favors. Primary applied.', 'aio-page-builder' ),
					Industry_Conflict_Result::SEVERITY_WARNING
				);
			}
		}

		$summary = $this->template_explanation_summary( $reasons, $source_refs, $primary, $secondary );
		return Industry_Weighted_Recommendation_Result::create( $score, $source_refs, $conflicts, $summary );
	}

	/**
	 * Builds weighted result for one Build Plan item using payload industry metadata.
	 *
	 * @param array<string, mixed> $profile       Industry profile.
	 * @param array<string, mixed> $item_payload  Item payload (industry_source_refs, recommendation_reasons, industry_fit_score, industry_warning_flags).
	 * @return array<string, mixed> Industry_Weighted_Recommendation_Result shape.
	 */
	public function for_build_plan_item( array $profile, array $item_payload ): array {
		$primary = $this->primary_key( $profile );
		$secondary = $this->secondary_keys( $profile );
		$score = (int) ( $item_payload['industry_fit_score'] ?? 0 );
		$reasons = isset( $item_payload['recommendation_reasons'] ) && is_array( $item_payload['recommendation_reasons'] )
			? $item_payload['recommendation_reasons']
			: array();
		$source_refs = isset( $item_payload['industry_source_refs'] ) && is_array( $item_payload['industry_source_refs'] )
			? array_values( array_filter( array_map( 'strval', $item_payload['industry_source_refs'] ) ) )
			: array();

		$conflicts = array();
		if ( $primary !== '' && count( $secondary ) > 0 ) {
			$primary_positive = $this->template_primary_positive( $reasons ) || $this->section_primary_positive( $reasons );
			$primary_negative = $this->template_primary_negative( $reasons ) || $this->section_primary_negative( $reasons );
			$secondary_positive = $this->template_secondary_positive( $reasons ) || $this->section_secondary_positive( $reasons );
			$secondary_negative = $this->template_secondary_negative( $reasons ) || $this->section_secondary_negative( $reasons );
			if ( ( $primary_positive && $secondary_negative ) || ( $primary_negative && $secondary_positive ) ) {
				$industries = array_filter( array_merge( array( $primary ), $secondary ) );
				$conflicts[] = Industry_Conflict_Result::create(
					Industry_Conflict_Result::CONFLICT_TYPE_BUILD_PLAN_ITEM,
					$industries,
					Industry_Conflict_Result::RESOLUTION_PRIMARY_WINS,
					__( 'Conflicting industry guidance; primary recommendation applied.', 'aio-page-builder' ),
					Industry_Conflict_Result::SEVERITY_WARNING
				);
			}
		}

		$summary = $this->template_explanation_summary( $reasons, $source_refs, $primary, $secondary );
		return Industry_Weighted_Recommendation_Result::create( $score, $source_refs, $conflicts, $summary );
	}

	private function primary_key( array $profile ): string {
		$key = $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ?? $profile['primary_industry_key'] ?? '';
		return is_string( $key ) ? trim( $key ) : '';
	}

	/**
	 * @return array<int, string>
	 */
	private function secondary_keys( array $profile ): array {
		$keys = $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ?? $profile['secondary_industry_keys'] ?? array();
		if ( ! is_array( $keys ) ) {
			return array();
		}
		return array_values( array_filter( array_map( function ( $v ) {
			return is_string( $v ) ? trim( $v ) : '';
		}, $keys ) ) );
	}

	/**
	 * @param array<int, string> $reasons
	 */
	private function section_primary_positive( array $reasons ): bool {
		$positive = array( self::REASON_PACK_PREFERRED, self::REASON_SECTION_AFFINITY_PRIMARY, 'cta_fit' );
		return count( array_intersect( $reasons, $positive ) ) > 0;
	}

	/**
	 * @param array<int, string> $reasons
	 */
	private function section_primary_negative( array $reasons ): bool {
		return in_array( self::REASON_PACK_DISCOURAGED, $reasons, true ) || in_array( self::REASON_SECTION_DISCOURAGED_PRIMARY, $reasons, true );
	}

	/**
	 * @param array<int, string> $reasons
	 */
	private function section_secondary_positive( array $reasons ): bool {
		return in_array( self::REASON_SECTION_AFFINITY_SECONDARY, $reasons, true );
	}

	/**
	 * @param array<int, string> $reasons
	 */
	private function section_secondary_negative( array $reasons ): bool {
		return in_array( self::REASON_SECTION_DISCOURAGED_SECONDARY, $reasons, true );
	}

	/**
	 * @param array<int, string> $reasons
	 */
	private function template_primary_positive( array $reasons ): bool {
		$positive = array( self::REASON_TEMPLATE_AFFINITY_PRIMARY, 'pack_family_fit', 'industry_required_primary', 'hierarchy_fit', 'lpagery_fit' );
		return count( array_intersect( $reasons, $positive ) ) > 0;
	}

	/**
	 * @param array<int, string> $reasons
	 */
	private function template_primary_negative( array $reasons ): bool {
		return in_array( self::REASON_TEMPLATE_DISCOURAGED_PRIMARY, $reasons, true );
	}

	/**
	 * @param array<int, string> $reasons
	 */
	private function template_secondary_positive( array $reasons ): bool {
		return in_array( self::REASON_TEMPLATE_AFFINITY_SECONDARY, $reasons, true ) || in_array( 'industry_required_secondary', $reasons, true );
	}

	/**
	 * @param array<int, string> $reasons
	 */
	private function template_secondary_negative( array $reasons ): bool {
		return in_array( self::REASON_TEMPLATE_DISCOURAGED_SECONDARY, $reasons, true );
	}

	/**
	 * @param array<int, string> $reasons
	 * @param array<int, string> $source_refs
	 */
	private function section_explanation_summary( array $reasons, array $source_refs, string $primary, array $secondary ): string {
		if ( empty( $reasons ) ) {
			return $primary !== '' ? sprintf( __( 'Industry: %s.', 'aio-page-builder' ), implode( ', ', array_merge( array( $primary ), $secondary ) ) ) : '';
		}
		$parts = array();
		if ( $primary !== '' ) {
			$parts[] = sprintf( __( 'Primary (%s)', 'aio-page-builder' ), $primary );
		}
		if ( ! empty( $secondary ) ) {
			$parts[] = sprintf( __( 'Secondary (%s)', 'aio-page-builder' ), implode( ', ', $secondary ) );
		}
		$prefix = implode( ', ', $parts );
		return $prefix !== '' ? $prefix . ': ' . implode( ', ', $reasons ) : implode( ', ', $reasons );
	}

	/**
	 * @param array<int, string> $reasons
	 * @param array<int, string> $source_refs
	 */
	private function template_explanation_summary( array $reasons, array $source_refs, string $primary, array $secondary ): string {
		return $this->section_explanation_summary( $reasons, $source_refs, $primary, $secondary );
	}
}
