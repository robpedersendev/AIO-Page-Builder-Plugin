<?php
/**
 * Registry for conversion-goal section-helper overlays (conversion-goal-helper-overlay-schema; Prompt 506).
 * Keyed by goal_key + section_key; does not modify base, industry, or subtype overlays.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Goal section-helper overlay registry. Read-only after load. Invalid entries skipped.
 */
final class Goal_Section_Helper_Overlay_Registry {

	/** Overlay object: conversion goal key. */
	public const FIELD_GOAL_KEY = 'goal_key';

	/** Overlay object: section template internal_key. */
	public const FIELD_SECTION_KEY = 'section_key';

	/** Overlay object: scope (conversion_goal_section_helper_overlay). */
	public const FIELD_SCOPE = 'scope';

	/** Overlay object: status. */
	public const FIELD_STATUS = 'status';

	/** Allowed scope for this overlay type. */
	public const SCOPE_GOAL_SECTION_HELPER_OVERLAY = 'conversion_goal_section_helper_overlay';

	/** Status: active overlays are used at resolution. */
	public const STATUS_ACTIVE = 'active';

	/** Pattern for goal_key (aligned with conversion-goal schema). */
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for keys. */
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Composite key "goal_key|section_key" => overlay. */
	private array $by_composite = array();

	/** @var list<array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Returns built-in goal section-helper overlay definitions (Prompt 506).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_overlay_definitions(): array {
		return \AIOPageBuilder\Domain\Industry\Docs\GoalSectionHelperOverlays\Builtin_Goal_Section_Helper_Overlays::get_definitions();
	}

	/**
	 * Loads overlay definitions. Skips invalid or duplicate (goal_key, section_key). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $overlays List of overlay objects.
	 * @return void
	 */
	public function load( array $overlays ): void {
		$this->by_composite = array();
		$this->all          = array();
		foreach ( $overlays as $ov ) {
			if ( ! is_array( $ov ) ) {
				continue;
			}
			$goal    = isset( $ov[ self::FIELD_GOAL_KEY ] ) && is_string( $ov[ self::FIELD_GOAL_KEY ] )
				? trim( $ov[ self::FIELD_GOAL_KEY ] )
				: '';
			$section = isset( $ov[ self::FIELD_SECTION_KEY ] ) && is_string( $ov[ self::FIELD_SECTION_KEY ] )
				? trim( $ov[ self::FIELD_SECTION_KEY ] )
				: '';
			if ( $goal === '' || $section === '' ) {
				continue;
			}
			if ( strlen( $goal ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $goal ) ) {
				continue;
			}
			if ( strlen( $section ) > self::KEY_MAX_LENGTH ) {
				continue;
			}
			$scope = isset( $ov[ self::FIELD_SCOPE ] ) && is_string( $ov[ self::FIELD_SCOPE ] )
				? $ov[ self::FIELD_SCOPE ]
				: '';
			if ( $scope !== self::SCOPE_GOAL_SECTION_HELPER_OVERLAY ) {
				continue;
			}
			$status = isset( $ov[ self::FIELD_STATUS ] ) && is_string( $ov[ self::FIELD_STATUS ] )
				? $ov[ self::FIELD_STATUS ]
				: '';
			if ( $status === '' ) {
				continue;
			}
			$composite = $goal . '|' . $section;
			if ( isset( $this->by_composite[ $composite ] ) ) {
				continue;
			}
			$this->by_composite[ $composite ] = $ov;
			$this->all[]                      = $ov;
		}
	}

	/**
	 * Returns overlay for (goal_key, section_key), or null if not found.
	 *
	 * @param string $goal_key   Conversion goal key.
	 * @param string $section_key Section template internal_key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $goal_key, string $section_key ): ?array {
		$g         = trim( $goal_key );
		$s         = trim( $section_key );
		$composite = $g . '|' . $s;
		return $this->by_composite[ $composite ] ?? null;
	}

	/**
	 * Returns all loaded overlays.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}

	/**
	 * Returns overlays for the given goal (all section keys for that goal).
	 *
	 * @param string $goal_key Conversion goal key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_goal( string $goal_key ): array {
		$g   = trim( $goal_key );
		$out = array();
		foreach ( $this->all as $ov ) {
			$gk = isset( $ov[ self::FIELD_GOAL_KEY ] ) && is_string( $ov[ self::FIELD_GOAL_KEY ] )
				? trim( $ov[ self::FIELD_GOAL_KEY ] )
				: '';
			if ( $gk === $g ) {
				$out[] = $ov;
			}
		}
		return $out;
	}
}
