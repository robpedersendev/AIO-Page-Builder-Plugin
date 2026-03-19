<?php
/**
 * Produces read-only diff between parent (industry) style preset, goal overlay, and combined outcome (Prompt 549).
 * Used by Industry_Style_Layer_Comparison_Screen. No mutation; safe fallback when layers are missing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Goal_Style_Preset_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;

/**
 * Builds comparable style layer summaries and diff rows for parent vs goal vs combined. Read-only.
 */
final class Industry_Style_Layer_Diff_Service {

	/** Result key: parent (industry) preset summary. */
	public const RESULT_PARENT = 'parent';

	/** Result key: subtype preset summary (null when no subtype preset registry or no subtype preset). */
	public const RESULT_SUBTYPE = 'subtype';

	/** Result key: goal overlay summary (null when no goal_key or no overlay for preset). */
	public const RESULT_GOAL = 'goal';

	/** Result key: combined token_values and component_override_refs (parent + goal merged). */
	public const RESULT_COMBINED = 'combined';

	/** Result key: token diff rows (token_key => array with parent, goal, combined, changed). */
	public const RESULT_TOKEN_DIFF_ROWS = 'token_diff_rows';

	/** Result key: component diff rows (ref => array with parent, goal, combined, changed). */
	public const RESULT_COMPONENT_DIFF_ROWS = 'component_diff_rows';

	/** @var Industry_Style_Preset_Registry|null */
	private $preset_registry;

	/** @var Goal_Style_Preset_Overlay_Registry|null */
	private $overlay_registry;

	public function __construct(
		?Industry_Style_Preset_Registry $preset_registry = null,
		?Goal_Style_Preset_Overlay_Registry $overlay_registry = null
	) {
		$this->preset_registry  = $preset_registry;
		$this->overlay_registry = $overlay_registry;
	}

	/**
	 * Builds comparison for one preset: parent, optional goal overlay, combined, and diff rows.
	 *
	 * @param string $preset_key Industry style_preset_key.
	 * @param string $goal_key   Conversion goal key (empty to skip goal layer).
	 * @return array{parent: array<string, mixed>, subtype: array<string, mixed>|null, goal: array<string, mixed>|null, combined: array<string, mixed>, token_diff_rows: list<array<string, mixed>>, component_diff_rows: list<array<string, mixed>>}
	 */
	public function compare( string $preset_key, string $goal_key = '' ): array {
		$parent   = array(
			'preset_key'              => $preset_key,
			'label'                   => '',
			'token_values'            => array(),
			'component_override_refs' => array(),
			'present'                 => false,
		);
		$subtype  = null;
		$goal     = array(
			'goal_preset_key'         => '',
			'goal_key'                => $goal_key,
			'token_values'            => array(),
			'component_override_refs' => array(),
			'present'                 => false,
		);
		$combined = array(
			'token_values'            => array(),
			'component_override_refs' => array(),
		);

		if ( $this->preset_registry !== null && $preset_key !== '' ) {
			$preset = $this->preset_registry->get( trim( $preset_key ) );
			if ( $preset !== null ) {
				$parent['present']                 = true;
				$parent['label']                   = (string) ( $preset[ Industry_Style_Preset_Registry::FIELD_LABEL ] ?? $preset_key );
				$parent['token_values']            = $this->normalize_token_values( $preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] ?? array() );
				$parent['component_override_refs'] = $this->normalize_component_refs( $preset[ Industry_Style_Preset_Registry::FIELD_COMPONENT_OVERRIDE_REFS ] ?? array() );
			}
		}

		$combined['token_values']            = $parent['token_values'];
		$combined['component_override_refs'] = $parent['component_override_refs'];

		if ( $this->overlay_registry !== null && $goal_key !== '' && $parent['present'] ) {
			$overlays  = $this->overlay_registry->get_overlays_for_preset( trim( $preset_key ) );
			$match     = null;
			$goal_trim = trim( $goal_key );
			foreach ( $overlays as $ov ) {
				$gk = isset( $ov[ Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_KEY ] ) && is_string( $ov[ Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_KEY ] )
					? trim( $ov[ Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_KEY ] )
					: '';
				if ( $gk === $goal_trim ) {
					$match = $ov;
					break;
				}
			}
			if ( $match !== null ) {
				$goal['present']                     = true;
				$goal['goal_preset_key']             = (string) ( $match[ Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_PRESET_KEY ] ?? '' );
				$goal['token_values']                = $this->normalize_token_values( $match[ Goal_Style_Preset_Overlay_Registry::FIELD_TOKEN_VALUES ] ?? array() );
				$goal['component_override_refs']     = $this->normalize_component_refs( $match[ Goal_Style_Preset_Overlay_Registry::FIELD_COMPONENT_OVERRIDE_REFS ] ?? array() );
				$combined['token_values']            = array_merge( $parent['token_values'], $goal['token_values'] );
				$combined['component_override_refs'] = array_values( array_unique( array_merge( $parent['component_override_refs'], $goal['component_override_refs'] ) ) );
			}
		}

		$token_diff_rows     = $this->build_token_diff_rows( $parent, $goal, $combined );
		$component_diff_rows = $this->build_component_diff_rows( $parent, $goal, $combined );

		return array(
			self::RESULT_PARENT              => $parent,
			self::RESULT_SUBTYPE             => $subtype,
			self::RESULT_GOAL                => $goal,
			self::RESULT_COMBINED            => $combined,
			self::RESULT_TOKEN_DIFF_ROWS     => $token_diff_rows,
			self::RESULT_COMPONENT_DIFF_ROWS => $component_diff_rows,
		);
	}

	/**
	 * @param array<string, mixed>|null $token_values
	 * @return array<string, string>
	 */
	private function normalize_token_values( $token_values ): array {
		if ( ! is_array( $token_values ) ) {
			return array();
		}
		$out = array();
		foreach ( $token_values as $k => $v ) {
			if ( is_string( $k ) && ( is_string( $v ) || is_numeric( $v ) ) ) {
				$out[ $k ] = (string) $v;
			}
		}
		return $out;
	}

	/**
	 * @param mixed $refs
	 * @return list<string>
	 */
	private function normalize_component_refs( $refs ): array {
		if ( ! is_array( $refs ) ) {
			return array();
		}
		$out = array();
		foreach ( $refs as $ref ) {
			if ( is_string( $ref ) && trim( $ref ) !== '' ) {
				$out[] = trim( $ref );
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param array<string, mixed> $parent
	 * @param array<string, mixed> $goal
	 * @param array<string, mixed> $combined
	 * @return list<array{token_key: string, parent: string, goal: string, combined: string, changed: bool}>
	 */
	private function build_token_diff_rows( array $parent, array $goal, array $combined ): array {
		$all_keys = array_keys( $combined['token_values'] );
		foreach ( array_keys( $parent['token_values'] ) as $k ) {
			if ( ! in_array( $k, $all_keys, true ) ) {
				$all_keys[] = $k;
			}
		}
		foreach ( array_keys( $goal['token_values'] ) as $k ) {
			if ( ! in_array( $k, $all_keys, true ) ) {
				$all_keys[] = $k;
			}
		}
		sort( $all_keys );
		$rows = array();
		foreach ( $all_keys as $token_key ) {
			$p       = $parent['token_values'][ $token_key ] ?? '';
			$g       = $goal['token_values'][ $token_key ] ?? '';
			$c       = $combined['token_values'][ $token_key ] ?? '';
			$changed = ( $p !== $c ) || ( $g !== '' && $g !== $p );
			$rows[]  = array(
				'token_key' => $token_key,
				'parent'    => $p,
				'goal'      => $g,
				'combined'  => $c,
				'changed'   => $changed,
			);
		}
		return $rows;
	}

	/**
	 * @param array<string, mixed> $parent
	 * @param array<string, mixed> $goal
	 * @param array<string, mixed> $combined
	 * @return list<array{ref: string, parent: bool, goal: bool, combined: bool, changed: bool}>
	 */
	private function build_component_diff_rows( array $parent, array $goal, array $combined ): array {
		$all_refs = $combined['component_override_refs'];
		foreach ( $parent['component_override_refs'] as $r ) {
			if ( ! in_array( $r, $all_refs, true ) ) {
				$all_refs[] = $r;
			}
		}
		foreach ( $goal['component_override_refs'] as $r ) {
			if ( ! in_array( $r, $all_refs, true ) ) {
				$all_refs[] = $r;
			}
		}
		sort( $all_refs );
		$rows         = array();
		$parent_set   = array_flip( $parent['component_override_refs'] );
		$goal_set     = array_flip( $goal['component_override_refs'] );
		$combined_set = array_flip( $combined['component_override_refs'] );
		foreach ( $all_refs as $ref ) {
			$p       = isset( $parent_set[ $ref ] );
			$g       = isset( $goal_set[ $ref ] );
			$c       = isset( $combined_set[ $ref ] );
			$changed = $g; // Goal overlay adds or contributes this ref.
			$rows[]  = array(
				'ref'      => $ref,
				'parent'   => $p,
				'goal'     => $g,
				'combined' => $c,
				'changed'  => $changed,
			);
		}
		return $rows;
	}
}
