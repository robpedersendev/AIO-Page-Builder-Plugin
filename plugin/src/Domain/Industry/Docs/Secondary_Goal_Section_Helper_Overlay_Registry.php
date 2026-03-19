<?php
/**
 * Registry for secondary-goal section-helper overlays (secondary-goal-helper-overlay-schema.md; Prompt 543).
 * Keyed by primary_goal_key + secondary_goal_key + section_key; applies after primary-goal overlay.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Secondary-goal section-helper overlay registry. Read-only after load. Invalid entries skipped.
 */
final class Secondary_Goal_Section_Helper_Overlay_Registry {

	public const FIELD_PRIMARY_GOAL_KEY   = 'primary_goal_key';
	public const FIELD_SECONDARY_GOAL_KEY = 'secondary_goal_key';
	public const FIELD_SECTION_KEY        = 'section_key';
	public const FIELD_SCOPE              = 'scope';
	public const FIELD_STATUS             = 'status';

	public const SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY = 'secondary_goal_section_helper_overlay';
	public const STATUS_ACTIVE                               = 'active';

	private const KEY_PATTERN    = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Composite "primary|secondary|section" => overlay. */
	private array $by_composite = array();

	/** @var list<array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Returns built-in secondary-goal section-helper overlay definitions (Prompt 544).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_overlay_definitions(): array {
		return \AIOPageBuilder\Domain\Industry\Docs\SecondaryGoalSectionHelperOverlays\Builtin_Secondary_Goal_Section_Helper_Overlays::get_definitions();
	}

	/**
	 * Loads overlay definitions. Skips invalid or duplicate (primary, secondary, section). Safe: no throw.
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
			$section   = isset( $ov[ self::FIELD_SECTION_KEY ] ) && \is_string( $ov[ self::FIELD_SECTION_KEY ] )
				? \trim( $ov[ self::FIELD_SECTION_KEY ] )
				: '';
			if ( $primary === '' || $secondary === '' || $section === '' || $primary === $secondary ) {
				continue;
			}
			if ( \strlen( $primary ) > self::KEY_MAX_LENGTH || ! \preg_match( self::KEY_PATTERN, $primary ) ) {
				continue;
			}
			if ( \strlen( $secondary ) > self::KEY_MAX_LENGTH || ! \preg_match( self::KEY_PATTERN, $secondary ) ) {
				continue;
			}
			if ( \strlen( $section ) > self::KEY_MAX_LENGTH ) {
				continue;
			}
			$scope = isset( $ov[ self::FIELD_SCOPE ] ) && \is_string( $ov[ self::FIELD_SCOPE ] )
				? $ov[ self::FIELD_SCOPE ]
				: '';
			if ( $scope !== self::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY ) {
				continue;
			}
			$status = isset( $ov[ self::FIELD_STATUS ] ) && \is_string( $ov[ self::FIELD_STATUS ] )
				? $ov[ self::FIELD_STATUS ]
				: '';
			if ( $status === '' ) {
				continue;
			}
			$composite = $primary . '|' . $secondary . '|' . $section;
			if ( isset( $this->by_composite[ $composite ] ) ) {
				continue;
			}
			$this->by_composite[ $composite ] = $ov;
			$this->all[]                      = $ov;
		}
	}

	/**
	 * Returns overlay for (primary_goal_key, secondary_goal_key, section_key), or null if not found.
	 *
	 * @param string $primary_goal_key   Primary conversion goal key.
	 * @param string $secondary_goal_key Secondary conversion goal key.
	 * @param string $section_key        Section template internal_key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $primary_goal_key, string $secondary_goal_key, string $section_key ): ?array {
		$p   = \trim( $primary_goal_key );
		$s   = \trim( $secondary_goal_key );
		$sec = \trim( $section_key );
		if ( $p === '' || $s === '' || $sec === '' || $p === $s ) {
			return null;
		}
		$composite = $p . '|' . $s . '|' . $sec;
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
