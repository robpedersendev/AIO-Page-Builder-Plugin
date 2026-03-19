<?php
/**
 * Registry for subtype-specific section-helper overlays (subtype-section-helper-overlay-schema.md; Prompt 424).
 * Keyed by subtype_key + section_key; does not modify base helper docs or industry overlays.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Subtype section-helper overlay registry. Read-only after load. Invalid entries skipped.
 */
final class Subtype_Section_Helper_Overlay_Registry {

	/** Overlay object: subtype key. */
	public const FIELD_SUBTYPE_KEY = 'subtype_key';

	/** Overlay object: section template internal_key. */
	public const FIELD_SECTION_KEY = 'section_key';

	/** Overlay object: scope (subtype_section_helper_overlay). */
	public const FIELD_SCOPE = 'scope';

	/** Overlay object: status. */
	public const FIELD_STATUS = 'status';

	/** Allowed scope for this overlay type. */
	public const SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY = 'subtype_section_helper_overlay';

	/** Status: active overlays are used at resolution. */
	public const STATUS_ACTIVE = 'active';

	/** Pattern for subtype_key (aligned with industry key pattern). */
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for keys. */
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Composite key "subtype_key|section_key" => overlay. */
	private array $by_composite = array();

	/** @var array<int, array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Returns built-in subtype section-helper overlay definitions (Prompt 425). Override or extend for custom loading.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_overlay_definitions(): array {
		return \AIOPageBuilder\Domain\Industry\Docs\SubtypeSectionHelperOverlays\Builtin_Subtype_Section_Helper_Overlays::get_definitions();
	}

	/**
	 * Loads overlay definitions. Skips invalid or duplicate (subtype_key, section_key). Safe: no throw.
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
			$section = isset( $ov[ self::FIELD_SECTION_KEY ] ) && is_string( $ov[ self::FIELD_SECTION_KEY ] )
				? trim( $ov[ self::FIELD_SECTION_KEY ] )
				: '';
			if ( $subtype === '' || $section === '' ) {
				continue;
			}
			if ( strlen( $subtype ) > self::KEY_MAX_LENGTH || ! preg_match( self::KEY_PATTERN, $subtype ) ) {
				continue;
			}
			if ( strlen( $section ) > self::KEY_MAX_LENGTH ) {
				continue;
			}
			$scope = isset( $ov[ self::FIELD_SCOPE ] ) && is_string( $ov[ self::FIELD_SCOPE ] )
				? $ov[ self::FIELD_SCOPE ]
				: '';
			if ( $scope !== self::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY ) {
				continue;
			}
			$status = isset( $ov[ self::FIELD_STATUS ] ) && is_string( $ov[ self::FIELD_STATUS ] )
				? $ov[ self::FIELD_STATUS ]
				: '';
			if ( $status === '' ) {
				continue;
			}
			$composite = $subtype . '|' . $section;
			if ( isset( $this->by_composite[ $composite ] ) ) {
				continue;
			}
			$this->by_composite[ $composite ] = $ov;
			$this->all[]                      = $ov;
		}
	}

	/**
	 * Returns overlay for (subtype_key, section_key), or null if not found.
	 *
	 * @param string $subtype_key Subtype key.
	 * @param string $section_key Section template internal_key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $subtype_key, string $section_key ): ?array {
		$sub       = trim( $subtype_key );
		$sec       = trim( $section_key );
		$composite = $sub . '|' . $sec;
		return $this->by_composite[ $composite ] ?? null;
	}

	/**
	 * Returns all loaded overlays.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}

	/**
	 * Returns overlays for the given subtype (all section keys for that subtype).
	 *
	 * @param string $subtype_key Subtype key.
	 * @return array<int, array<string, mixed>>
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
