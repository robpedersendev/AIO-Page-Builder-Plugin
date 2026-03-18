<?php
/**
 * Registry for subtype-specific page one-pager overlays (subtype-page-onepager-overlay-schema.md; Prompt 426).
 * Keyed by subtype_key + page_template_key; does not modify base one-pagers or industry overlays.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Subtype page one-pager overlay registry. Read-only after load. Invalid entries skipped.
 */
final class Subtype_Page_OnePager_Overlay_Registry {

	/** Overlay object: subtype key. */
	public const FIELD_SUBTYPE_KEY = 'subtype_key';

	/** Overlay object: page template internal_key. */
	public const FIELD_PAGE_TEMPLATE_KEY = 'page_template_key';

	/** Overlay object: scope (subtype_page_onepager_overlay). */
	public const FIELD_SCOPE = 'scope';

	/** Overlay object: status. */
	public const FIELD_STATUS = 'status';

	/** Allowed scope for this overlay type. */
	public const SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY = 'subtype_page_onepager_overlay';

	/** Status: active overlays are used at resolution. */
	public const STATUS_ACTIVE = 'active';

	/** Pattern for subtype_key (aligned with industry key pattern). */
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for keys. */
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Composite key "subtype_key|page_template_key" => overlay. */
	private array $by_composite = array();

	/** @var list<array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Returns built-in subtype page one-pager overlay definitions (Prompt 427). Override or extend for custom loading.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_overlay_definitions(): array {
		return \AIOPageBuilder\Domain\Industry\Docs\SubtypePageOnePagerOverlays\Builtin_Subtype_Page_OnePager_Overlays::get_definitions();
	}

	/**
	 * Loads overlay definitions. Skips invalid or duplicate (subtype_key, page_template_key). Safe: no throw.
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
			$subtype  = isset( $ov[ self::FIELD_SUBTYPE_KEY ] ) && is_string( $ov[ self::FIELD_SUBTYPE_KEY ] )
				? trim( $ov[ self::FIELD_SUBTYPE_KEY ] )
				: '';
			$page_key = isset( $ov[ self::FIELD_PAGE_TEMPLATE_KEY ] ) && is_string( $ov[ self::FIELD_PAGE_TEMPLATE_KEY ] )
				? trim( $ov[ self::FIELD_PAGE_TEMPLATE_KEY ] )
				: '';
			if ( $subtype === '' || $page_key === '' ) {
				continue;
			}
			if ( strlen( $subtype ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $subtype ) ) {
				continue;
			}
			if ( strlen( $page_key ) > self::KEY_MAX_LENGTH ) {
				continue;
			}
			$scope = isset( $ov[ self::FIELD_SCOPE ] ) && is_string( $ov[ self::FIELD_SCOPE ] )
				? $ov[ self::FIELD_SCOPE ]
				: '';
			if ( $scope !== self::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY ) {
				continue;
			}
			$status = isset( $ov[ self::FIELD_STATUS ] ) && is_string( $ov[ self::FIELD_STATUS ] )
				? $ov[ self::FIELD_STATUS ]
				: '';
			if ( $status === '' ) {
				continue;
			}
			$composite = $subtype . '|' . $page_key;
			if ( isset( $this->by_composite[ $composite ] ) ) {
				continue;
			}
			$this->by_composite[ $composite ] = $ov;
			$this->all[]                      = $ov;
		}
	}

	/**
	 * Returns overlay for (subtype_key, page_template_key), or null if not found.
	 *
	 * @param string $subtype_key      Subtype key.
	 * @param string $page_template_key Page template internal_key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $subtype_key, string $page_template_key ): ?array {
		$sub       = trim( $subtype_key );
		$p         = trim( $page_template_key );
		$composite = $sub . '|' . $p;
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
	 * Returns overlays for the given subtype.
	 *
	 * @param string $subtype_key Subtype key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_subtype( string $subtype_key ): array {
		$sub = trim( $subtype_key );
		$out = array();
		foreach ( $this->all as $ov ) {
			$sk = isset( $ov[ self::FIELD_SUBTYPE_KEY ] ) && is_string( $ov[ self::FIELD_SUBTYPE_KEY ] )
				? trim( $ov[ self::FIELD_SUBTYPE_KEY ] )
				: '';
			if ( $sk === $sub ) {
				$out[] = $ov;
			}
		}
		return $out;
	}
}
