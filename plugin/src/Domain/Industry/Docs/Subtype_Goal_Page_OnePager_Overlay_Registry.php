<?php
/**
 * Registry for combined subtype+goal page one-pager overlays (subtype-goal-doc-overlay-schema.md; Prompt 553, 554).
 * Keyed by subtype_key + goal_key + page_key. Exceptional; applied after subtype and goal layers. Invalid definitions skipped at load.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only registry of combined subtype+goal page one-pager overlay definitions.
 */
final class Subtype_Goal_Page_OnePager_Overlay_Registry {

	/** Overlay object: overlay key. */
	public const FIELD_OVERLAY_KEY = 'overlay_key';

	/** Overlay object: subtype key. */
	public const FIELD_SUBTYPE_KEY = 'subtype_key';

	/** Overlay object: goal key. */
	public const FIELD_GOAL_KEY = 'goal_key';

	/** Overlay object: page template internal_key or page family key. */
	public const FIELD_PAGE_KEY = 'page_key';

	/** Overlay object: scope (subtype_goal_page_onepager_overlay). */
	public const FIELD_SCOPE = 'scope';

	/** Overlay object: status. */
	public const FIELD_STATUS = 'status';

	/** Overlay object: allowed override regions. */
	public const FIELD_ALLOWED_OVERRIDE_REGIONS = 'allowed_override_regions';

	/** Allowed scope for this overlay type. */
	public const SCOPE_SUBTYPE_GOAL_PAGE_ONEPAGER_OVERLAY = 'subtype_goal_page_onepager_overlay';

	/** Status: active overlays are used at resolution. */
	public const STATUS_ACTIVE = 'active';

	/** Allowed goal keys (aligned with conversion-goal schema). */
	private const ALLOWED_GOAL_KEYS = array( 'calls', 'bookings', 'estimates', 'consultations', 'valuations', 'lead_capture' );

	/** Allowed override regions for page overlays. */
	private const ALLOWED_REGIONS = array( 'hierarchy_hints', 'cta_strategy', 'lpagery_seo_notes', 'compliance_cautions', 'additive_blocks', 'structure_notes', 'funnel_notes', 'cta_placement_notes' );

	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LEN = 64;

	/** @var array<string, array<string, mixed>> Composite "subtype|goal|page" => overlay. */
	private array $by_composite = array();

	/** @var array<int, array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Returns built-in combined subtype+goal page one-pager overlay definitions (Prompt 554).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_overlay_definitions(): array {
		return \AIOPageBuilder\Domain\Industry\Docs\SubtypeGoalOverlays\Builtin_Subtype_Goal_Page_OnePager_Overlays::get_definitions();
	}

	/**
	 * Loads overlay definitions. Skips invalid or duplicate. Safe: no throw.
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
			$subtype = isset( $ov[ self::FIELD_SUBTYPE_KEY ] ) && is_string( $ov[ self::FIELD_SUBTYPE_KEY ] )
				? trim( $ov[ self::FIELD_SUBTYPE_KEY ] )
				: '';
			$goal    = isset( $ov[ self::FIELD_GOAL_KEY ] ) && is_string( $ov[ self::FIELD_GOAL_KEY ] )
				? trim( $ov[ self::FIELD_GOAL_KEY ] )
				: '';
			$page    = isset( $ov[ self::FIELD_PAGE_KEY ] ) && is_string( $ov[ self::FIELD_PAGE_KEY ] )
				? trim( $ov[ self::FIELD_PAGE_KEY ] )
				: '';
			if ( $subtype === '' || $goal === '' || $page === '' ) {
				continue;
			}
			if ( strlen( $subtype ) > self::KEY_MAX_LEN || ! preg_match( self::KEY_PATTERN, $subtype ) ) {
				continue;
			}
			if ( ! in_array( $goal, self::ALLOWED_GOAL_KEYS, true ) ) {
				continue;
			}
			if ( strlen( $page ) > self::KEY_MAX_LEN ) {
				continue;
			}
			$scope = isset( $ov[ self::FIELD_SCOPE ] ) && is_string( $ov[ self::FIELD_SCOPE ] )
				? $ov[ self::FIELD_SCOPE ]
				: '';
			if ( $scope !== self::SCOPE_SUBTYPE_GOAL_PAGE_ONEPAGER_OVERLAY ) {
				continue;
			}
			$status = isset( $ov[ self::FIELD_STATUS ] ) && is_string( $ov[ self::FIELD_STATUS ] )
				? $ov[ self::FIELD_STATUS ]
				: '';
			if ( $status !== self::STATUS_ACTIVE ) {
				continue;
			}
			$regions = $ov[ self::FIELD_ALLOWED_OVERRIDE_REGIONS ] ?? null;
			if ( ! is_array( $regions ) || $regions === array() ) {
				continue;
			}
			$valid_regions = true;
			foreach ( $regions as $r ) {
				if ( ! in_array( $r, self::ALLOWED_REGIONS, true ) ) {
					$valid_regions = false;
					break;
				}
			}
			if ( ! $valid_regions ) {
				continue;
			}
			$composite = $subtype . '|' . $goal . '|' . $page;
			if ( isset( $this->by_composite[ $composite ] ) ) {
				continue;
			}
			$this->by_composite[ $composite ] = $ov;
			$this->all[]                      = $ov;
		}
	}

	/**
	 * Returns overlay for (subtype_key, goal_key, page_key), or null if not found.
	 *
	 * @param string $subtype_key Subtype key.
	 * @param string $goal_key    Conversion goal key.
	 * @param string $page_key    Page template internal_key or page family key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $subtype_key, string $goal_key, string $page_key ): ?array {
		$sub  = trim( $subtype_key );
		$g    = trim( $goal_key );
		$page = trim( $page_key );
		if ( $sub === '' || $g === '' || $page === '' ) {
			return null;
		}
		$composite = $sub . '|' . $g . '|' . $page;
		return $this->by_composite[ $composite ] ?? null;
	}

	/**
	 * Returns all loaded overlays for (subtype_key, goal_key).
	 *
	 * @param string $subtype_key Subtype key.
	 * @param string $goal_key    Conversion goal key.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_for_subtype_goal( string $subtype_key, string $goal_key ): array {
		$sub = trim( $subtype_key );
		$g   = trim( $goal_key );
		if ( $sub === '' || $g === '' ) {
			return array();
		}
		$out = array();
		foreach ( $this->all as $ov ) {
			$sk = isset( $ov[ self::FIELD_SUBTYPE_KEY ] ) && is_string( $ov[ self::FIELD_SUBTYPE_KEY ] )
				? trim( $ov[ self::FIELD_SUBTYPE_KEY ] )
				: '';
			$gk = isset( $ov[ self::FIELD_GOAL_KEY ] ) && is_string( $ov[ self::FIELD_GOAL_KEY ] )
				? trim( $ov[ self::FIELD_GOAL_KEY ] )
				: '';
			if ( $sk === $sub && $gk === $g ) {
				$out[] = $ov;
			}
		}
		return $out;
	}

	/**
	 * Returns all loaded overlays.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function list_all(): array {
		return $this->all;
	}
}
