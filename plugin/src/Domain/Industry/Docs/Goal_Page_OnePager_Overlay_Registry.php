<?php
/**
 * Registry for conversion-goal page one-pager overlays (conversion-goal-page-onepager-overlay-schema; Prompt 508).
 * Keyed by goal_key + page_key; does not modify base, industry, or subtype page one-pagers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Goal page one-pager overlay registry. Read-only after load. Invalid entries skipped.
 */
final class Goal_Page_OnePager_Overlay_Registry {

	/** Overlay object: conversion goal key. */
	public const FIELD_GOAL_KEY = 'goal_key';

	/** Overlay object: page template internal_key or page family key. */
	public const FIELD_PAGE_KEY = 'page_key';

	/** Overlay object: scope (conversion_goal_page_onepager_overlay). */
	public const FIELD_SCOPE = 'scope';

	/** Overlay object: status. */
	public const FIELD_STATUS = 'status';

	/** Allowed scope for this overlay type. */
	public const SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY = 'conversion_goal_page_onepager_overlay';

	/** Status: active overlays are used at resolution. */
	public const STATUS_ACTIVE = 'active';

	/** Pattern for goal_key (aligned with conversion-goal schema). */
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for keys. */
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Composite key "goal_key|page_key" => overlay. */
	private array $by_composite = array();

	/** @var list<array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Returns built-in goal page one-pager overlay definitions (Prompt 508).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_overlay_definitions(): array {
		return \AIOPageBuilder\Domain\Industry\Docs\GoalPageOnePagerOverlays\Builtin_Goal_Page_OnePager_Overlays::get_definitions();
	}

	/**
	 * Loads overlay definitions. Skips invalid or duplicate (goal_key, page_key). Safe: no throw.
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
			$goal = isset( $ov[ self::FIELD_GOAL_KEY ] ) && is_string( $ov[ self::FIELD_GOAL_KEY ] )
				? trim( $ov[ self::FIELD_GOAL_KEY ] )
				: '';
			$page_key = isset( $ov[ self::FIELD_PAGE_KEY ] ) && is_string( $ov[ self::FIELD_PAGE_KEY ] )
				? trim( $ov[ self::FIELD_PAGE_KEY ] )
				: '';
			if ( $goal === '' || $page_key === '' ) {
				continue;
			}
			if ( strlen( $goal ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $goal ) ) {
				continue;
			}
			if ( strlen( $page_key ) > self::KEY_MAX_LENGTH ) {
				continue;
			}
			$scope = isset( $ov[ self::FIELD_SCOPE ] ) && is_string( $ov[ self::FIELD_SCOPE ] )
				? $ov[ self::FIELD_SCOPE ]
				: '';
			if ( $scope !== self::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY ) {
				continue;
			}
			$status = isset( $ov[ self::FIELD_STATUS ] ) && is_string( $ov[ self::FIELD_STATUS ] )
				? $ov[ self::FIELD_STATUS ]
				: '';
			if ( $status === '' ) {
				continue;
			}
			$composite = $goal . '|' . $page_key;
			if ( isset( $this->by_composite[ $composite ] ) ) {
				continue;
			}
			$this->by_composite[ $composite ] = $ov;
			$this->all[]                     = $ov;
		}
	}

	/**
	 * Returns overlay for (goal_key, page_key), or null if not found.
	 *
	 * @param string $goal_key  Conversion goal key.
	 * @param string $page_key  Page template internal_key or page family key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $goal_key, string $page_key ): ?array {
		$g = trim( $goal_key );
		$p = trim( $page_key );
		$composite = $g . '|' . $p;
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
	 * Returns overlays for the given goal.
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
