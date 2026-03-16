<?php
/**
 * Internal diff and change-summary for industry pack versions (industry-pack-diff-contract.md; Prompt 418).
 * Read-only; compares two pack definition sets; safe for invalid targets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;

/**
 * Compares two pack states and returns an immutable diff result.
 */
final class Industry_Pack_Diff_Service {

	/** Ref-like fields to compare (scalar or array). */
	private const REF_FIELDS = array(
		Industry_Pack_Schema::FIELD_SUPPORTED_PAGE_FAMILIES,
		Industry_Pack_Schema::FIELD_PREFERRED_SECTION_KEYS,
		Industry_Pack_Schema::FIELD_DISCOURAGED_SECTION_KEYS,
		Industry_Pack_Schema::FIELD_DEFAULT_CTA_PATTERNS,
		Industry_Pack_Schema::FIELD_PREFERRED_CTA_PATTERNS,
		Industry_Pack_Schema::FIELD_DISCOURAGED_CTA_PATTERNS,
		Industry_Pack_Schema::FIELD_REQUIRED_CTA_PATTERNS,
		Industry_Pack_Schema::FIELD_SEO_GUIDANCE_REF,
		Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF,
		Industry_Pack_Schema::FIELD_LPAGERY_RULE_REF,
		Industry_Pack_Schema::FIELD_HELPER_OVERLAY_REFS,
		Industry_Pack_Schema::FIELD_ONE_PAGER_OVERLAY_REFS,
		Industry_Pack_Schema::FIELD_REPLACEMENT_REF,
		Industry_Pack_Schema::FIELD_DEPRECATED_AT,
		Industry_Pack_Schema::FIELD_DEPRECATION_NOTE,
	);

	/**
	 * Diffs two pack definition lists. Safe: invalid or empty input yields empty diff and notes.
	 *
	 * @param array<int, array<string, mixed>> $left_packs  Baseline pack definitions (list).
	 * @param array<int, array<string, mixed>> $right_packs New state pack definitions (list).
	 * @param array<string, mixed>            $options     Optional left_label, right_label (strings).
	 * @return Industry_Pack_Diff_Result
	 */
	public function diff( array $left_packs, array $right_packs, array $options = array() ): Industry_Pack_Diff_Result {
		$compared_at = gmdate( 'Y-m-d\TH:i:s\Z' );
		$left_label  = isset( $options['left_label'] ) && is_string( $options['left_label'] ) ? $options['left_label'] : 'left';
		$right_label = isset( $options['right_label'] ) && is_string( $options['right_label'] ) ? $options['right_label'] : 'right';
		$notes       = array();

		$left_map  = $this->packs_to_map( $left_packs, $notes, 'left' );
		$right_map = $this->packs_to_map( $right_packs, $notes, 'right' );

		$left_keys  = array_keys( $left_map );
		$right_keys = array_keys( $right_map );
		$added      = array_values( array_diff( $right_keys, $left_keys ) );
		$removed    = array_values( array_diff( $left_keys, $right_keys ) );
		$common     = array_intersect( $left_keys, $right_keys );
		$changed    = array();
		foreach ( $common as $key ) {
			$entry = $this->compare_packs( $key, $left_map[ $key ], $right_map[ $key ] );
			if ( $entry !== null ) {
				$changed[] = $entry;
			}
		}

		$impact = $this->impact_level( $added, $removed, $changed );
		$summary = array(
			'added_count'   => count( $added ),
			'removed_count' => count( $removed ),
			'changed_count' => count( $changed ),
			'impact_level'  => $impact,
		);

		return new Industry_Pack_Diff_Result(
			$compared_at,
			$left_label,
			$right_label,
			$added,
			$removed,
			$changed,
			$summary,
			$notes
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $packs
	 * @param list<string>                     $notes
	 * @param string                           $side
	 * @return array<string, array<string, mixed>>
	 */
	private function packs_to_map( array $packs, array &$notes, string $side ): array {
		$map = array();
		foreach ( $packs as $i => $pack ) {
			if ( ! is_array( $pack ) ) {
				$notes[] = "{$side} index {$i}: skipped (not array)";
				continue;
			}
			$key = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				? trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $key === '' ) {
				$notes[] = "{$side} index {$i}: skipped (missing industry_key)";
				continue;
			}
			if ( ! isset( $map[ $key ] ) ) {
				$map[ $key ] = $pack;
			}
		}
		return $map;
	}

	/**
	 * @param string                 $industry_key
	 * @param array<string, mixed>   $left
	 * @param array<string, mixed>   $right
	 * @return array<string, mixed>|null Change entry or null if no change.
	 */
	private function compare_packs( string $industry_key, array $left, array $right ): ?array {
		$status_left  = isset( $left[ Industry_Pack_Schema::FIELD_STATUS ] ) && is_string( $left[ Industry_Pack_Schema::FIELD_STATUS ] )
			? $left[ Industry_Pack_Schema::FIELD_STATUS ]
			: '';
		$status_right = isset( $right[ Industry_Pack_Schema::FIELD_STATUS ] ) && is_string( $right[ Industry_Pack_Schema::FIELD_STATUS ] )
			? $right[ Industry_Pack_Schema::FIELD_STATUS ]
			: '';
		$version_left  = isset( $left[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] ) && is_string( $left[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
			? trim( $left[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
			: '';
		$version_right = isset( $right[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] ) && is_string( $right[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
			? trim( $right[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
			: '';

		$status_change   = ( $status_left !== $status_right ) ? array( 'from' => $status_left, 'to' => $status_right ) : null;
		$version_change  = ( $version_left !== $version_right ) ? array( 'from' => $version_left, 'to' => $version_right ) : null;
		$refs_added      = array();
		$refs_removed    = array();
		$refs_changed    = array();
		$summary_parts   = array();

		foreach ( self::REF_FIELDS as $field ) {
			$v_left  = $left[ $field ] ?? null;
			$v_right = $right[ $field ] ?? null;
			if ( is_array( $v_left ) && is_array( $v_right ) ) {
				$left_set  = $this->normalize_array_ref( $v_left );
				$right_set = $this->normalize_array_ref( $v_right );
				$add = array_diff( $right_set, $left_set );
				$rem = array_diff( $left_set, $right_set );
				if ( $add !== array() ) {
					$refs_added[ $field ] = array_values( $add );
					$summary_parts[] = $field . ' +' . count( $add );
				}
				if ( $rem !== array() ) {
					$refs_removed[ $field ] = array_values( $rem );
					$summary_parts[] = $field . ' -' . count( $rem );
				}
			} else {
				$s_left  = is_scalar( $v_left ) ? (string) $v_left : ( $v_left === null ? '' : json_encode( $v_left ) );
				$s_right = is_scalar( $v_right ) ? (string) $v_right : ( $v_right === null ? '' : json_encode( $v_right ) );
				if ( $s_left !== $s_right ) {
					$refs_changed[ $field ] = array( 'from' => $s_left, 'to' => $s_right );
					$summary_parts[] = $field;
				}
			}
		}

		if ( $status_change === null && $version_change === null && $refs_added === array() && $refs_removed === array() && $refs_changed === array() ) {
			return null;
		}

		$summary_note = implode( '; ', $summary_parts );
		if ( $status_change !== null ) {
			$summary_note = 'status ' . $status_change['from'] . ' → ' . $status_change['to'] . ( $summary_note !== '' ? '; ' . $summary_note : '' );
		}
		if ( $version_change !== null ) {
			$summary_note = 'version ' . $version_change['from'] . ' → ' . $version_change['to'] . ( $summary_note !== '' ? '; ' . $summary_note : '' );
		}

		return array(
			'industry_key'    => $industry_key,
			'status_change'   => $status_change,
			'version_change'  => $version_change,
			'refs_added'      => $refs_added,
			'refs_removed'    => $refs_removed,
			'refs_changed'    => $refs_changed,
			'summary_note'    => $summary_note,
		);
	}

	/**
	 * @param array $arr
	 * @return list<string>
	 */
	private function normalize_array_ref( array $arr ): array {
		$out = array();
		foreach ( $arr as $v ) {
			if ( is_string( $v ) && trim( $v ) !== '' ) {
				$out[] = trim( $v );
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param list<string>                  $added
	 * @param list<string>                  $removed
	 * @param list<array<string, mixed>>     $changed
	 * @return string
	 */
	private function impact_level( array $added, array $removed, array $changed ): string {
		$n = count( $added ) + count( $removed ) + count( $changed );
		if ( $n === 0 ) {
			return 'none';
		}
		if ( count( $removed ) > 0 || count( $added ) > 2 ) {
			return 'high';
		}
		if ( count( $changed ) > 3 || count( $added ) > 0 ) {
			return 'medium';
		}
		return 'low';
	}
}
