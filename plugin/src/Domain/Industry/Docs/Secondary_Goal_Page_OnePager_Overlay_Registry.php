<?php
/**
 * Registry for secondary-goal page one-pager overlays (secondary-goal-page-onepager-overlay-schema.md; Prompt 545).
 * Keyed by primary_goal_key + secondary_goal_key + page_key; applies after primary-goal page overlay.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Secondary-goal page one-pager overlay registry. Read-only after load. Invalid entries skipped.
 */
final class Secondary_Goal_Page_OnePager_Overlay_Registry {

	public const FIELD_PRIMARY_GOAL_KEY   = 'primary_goal_key';
	public const FIELD_SECONDARY_GOAL_KEY = 'secondary_goal_key';
	public const FIELD_PAGE_KEY           = 'page_key';
	public const FIELD_SCOPE              = 'scope';
	public const FIELD_STATUS             = 'status';

	public const SCOPE_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY = 'secondary_goal_page_onepager_overlay';
	public const STATUS_ACTIVE = 'active';

	private const KEY_PATTERN   = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Composite "primary|secondary|page" => overlay. */
	private array $by_composite = array();

	/** @var list<array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Returns built-in secondary-goal page one-pager overlay definitions (Prompt 546).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_overlay_definitions(): array {
		return \AIOPageBuilder\Domain\Industry\Docs\SecondaryGoalPageOnePagerOverlays\Builtin_Secondary_Goal_Page_OnePager_Overlays::get_definitions();
	}

	/**
	 * Loads overlay definitions. Skips invalid or duplicate (primary, secondary, page). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $overlays List of overlay objects.
	 * @return void
	 */
	public function load( array $overlays ): void {
		$this->by_composite = array();
		$this->all          = array();
		foreach ( $overlays as $ov ) {
			if ( ! \is_array( $ov ) ) {
				continue;
			}
			$primary   = isset( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] ) && \is_string( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] )
				? \trim( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] )
				: '';
			$secondary = isset( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] ) && \is_string( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] )
				? \trim( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] )
				: '';
			$page_key  = isset( $ov[ self::FIELD_PAGE_KEY ] ) && \is_string( $ov[ self::FIELD_PAGE_KEY ] )
				? \trim( $ov[ self::FIELD_PAGE_KEY ] )
				: '';
			if ( $primary === '' || $secondary === '' || $page_key === '' || $primary === $secondary ) {
				continue;
			}
			if ( \strlen( $primary ) > self::KEY_MAX_LENGTH || ! \preg_match( self::KEY_PATTERN, $primary ) ) {
				continue;
			}
			if ( \strlen( $secondary ) > self::KEY_MAX_LENGTH || ! \preg_match( self::KEY_PATTERN, $secondary ) ) {
				continue;
			}
			if ( \strlen( $page_key ) > self::KEY_MAX_LENGTH ) {
				continue;
			}
			$scope = isset( $ov[ self::FIELD_SCOPE ] ) && \is_string( $ov[ self::FIELD_SCOPE ] )
				? $ov[ self::FIELD_SCOPE ]
				: '';
			if ( $scope !== self::SCOPE_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY ) {
				continue;
			}
			$status = isset( $ov[ self::FIELD_STATUS ] ) && \is_string( $ov[ self::FIELD_STATUS ] )
				? $ov[ self::FIELD_STATUS ]
				: '';
			if ( $status === '' ) {
				continue;
			}
			$composite = $primary . '|' . $secondary . '|' . $page_key;
			if ( isset( $this->by_composite[ $composite ] ) ) {
				continue;
			}
			$this->by_composite[ $composite ] = $ov;
			$this->all[] = $ov;
		}
	}

	/**
	 * Returns overlay for (primary_goal_key, secondary_goal_key, page_key), or null if not found.
	 *
	 * @param string $primary_goal_key   Primary conversion goal key.
	 * @param string $secondary_goal_key Secondary conversion goal key.
	 * @param string $page_key           Page template internal_key or page family key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $primary_goal_key, string $secondary_goal_key, string $page_key ): ?array {
		$p = \trim( $primary_goal_key );
		$s = \trim( $secondary_goal_key );
		$pg = \trim( $page_key );
		if ( $p === '' || $s === '' || $pg === '' || $p === $s ) {
			return null;
		}
		$composite = $p . '|' . $s . '|' . $pg;
		return $this->by_composite[ $composite ] ?? null;
	}

	/**
	 * Returns overlays for the given (primary_goal, secondary_goal) pair.
	 *
	 * @param string $primary_goal_key   Primary conversion goal key.
	 * @param string $secondary_goal_key Secondary conversion goal key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_primary_secondary( string $primary_goal_key, string $secondary_goal_key ): array {
		$p = \trim( $primary_goal_key );
		$s = \trim( $secondary_goal_key );
		if ( $p === '' || $s === '' || $p === $s ) {
			return array();
		}
		$out = array();
		foreach ( $this->all as $ov ) {
			$pk = isset( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] ) && \is_string( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] )
				? \trim( $ov[ self::FIELD_PRIMARY_GOAL_KEY ] )
				: '';
			$sk = isset( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] ) && \is_string( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] )
				? \trim( $ov[ self::FIELD_SECONDARY_GOAL_KEY ] )
				: '';
			if ( $pk === $p && $sk === $s ) {
				$out[] = $ov;
			}
		}
		return $out;
	}

	/**
	 * Returns all loaded overlays.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}
}
