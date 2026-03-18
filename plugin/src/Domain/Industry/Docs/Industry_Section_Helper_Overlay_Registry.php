<?php
/**
 * Registry for industry-specific section-helper overlays (industry-section-helper-overlay-schema.md).
 * Keyed by industry_key + section_key; does not modify base helper docs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Industry section-helper overlay registry. Read-only after load. Invalid entries skipped.
 */
final class Industry_Section_Helper_Overlay_Registry {

	/** Overlay object: industry pack key. */
	public const FIELD_INDUSTRY_KEY = 'industry_key';

	/** Overlay object: section template internal_key. */
	public const FIELD_SECTION_KEY = 'section_key';

	/** Overlay object: scope (section_helper_overlay). */
	public const FIELD_SCOPE = 'scope';

	/** Overlay object: status. */
	public const FIELD_STATUS = 'status';

	/** Allowed scope for this overlay type. */
	public const SCOPE_SECTION_HELPER_OVERLAY = 'section_helper_overlay';

	/** Status: active overlays are used at resolution. */
	public const STATUS_ACTIVE = 'active';

	/** Pattern for industry_key (aligned with Industry_Pack_Schema). */
	private const INDUSTRY_KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for keys. */
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Composite key "industry_key|section_key" => overlay. */
	private array $by_composite = array();

	/** @var list<array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Returns built-in section-helper overlay definitions from SectionHelperOverlays/*.php (Prompt 353).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_overlay_definitions(): array {
		$dir   = __DIR__ . '/SectionHelperOverlays';
		$files = array(
			$dir . '/overlays-cosmetology-nail.php',
			$dir . '/overlays-realtor.php',
			$dir . '/overlays-plumber.php',
			$dir . '/overlays-disaster-recovery.php',
		);
		$out   = array();
		foreach ( $files as $path ) {
			if ( is_readable( $path ) ) {
				$loaded = require $path;
				if ( is_array( $loaded ) ) {
					foreach ( $loaded as $ov ) {
						if ( is_array( $ov ) ) {
							$out[] = $ov;
						}
					}
				}
			}
		}
		return $out;
	}

	/**
	 * Loads overlay definitions. Skips invalid or duplicate (industry_key, section_key). Safe: no throw.
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
			$industry = isset( $ov[ self::FIELD_INDUSTRY_KEY ] ) && is_string( $ov[ self::FIELD_INDUSTRY_KEY ] )
				? trim( $ov[ self::FIELD_INDUSTRY_KEY ] )
				: '';
			$section  = isset( $ov[ self::FIELD_SECTION_KEY ] ) && is_string( $ov[ self::FIELD_SECTION_KEY ] )
				? trim( $ov[ self::FIELD_SECTION_KEY ] )
				: '';
			if ( $industry === '' || $section === '' ) {
				continue;
			}
			if ( strlen( $industry ) > self::KEY_MAX_LENGTH || ! preg_match( self::INDUSTRY_KEY_PATTERN, $industry ) ) {
				continue;
			}
			if ( strlen( $section ) > self::KEY_MAX_LENGTH ) {
				continue;
			}
			$scope = isset( $ov[ self::FIELD_SCOPE ] ) && is_string( $ov[ self::FIELD_SCOPE ] )
				? $ov[ self::FIELD_SCOPE ]
				: '';
			if ( $scope !== self::SCOPE_SECTION_HELPER_OVERLAY ) {
				continue;
			}
			$status = isset( $ov[ self::FIELD_STATUS ] ) && is_string( $ov[ self::FIELD_STATUS ] )
				? $ov[ self::FIELD_STATUS ]
				: '';
			if ( $status === '' ) {
				continue;
			}
			$composite = $industry . '|' . $section;
			if ( isset( $this->by_composite[ $composite ] ) ) {
				continue;
			}
			$this->by_composite[ $composite ] = $ov;
			$this->all[]                      = $ov;
		}
	}

	/**
	 * Returns overlay for (industry_key, section_key), or null if not found.
	 *
	 * @param string $industry_key Industry pack key.
	 * @param string $section_key   Section template internal_key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $industry_key, string $section_key ): ?array {
		$i         = trim( $industry_key );
		$s         = trim( $section_key );
		$composite = $i . '|' . $s;
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
	 * Returns overlays for the given industry (all section keys for that industry).
	 *
	 * @param string $industry_key Industry pack key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_industry( string $industry_key ): array {
		$i   = trim( $industry_key );
		$out = array();
		foreach ( $this->all as $ov ) {
			$ik = isset( $ov[ self::FIELD_INDUSTRY_KEY ] ) && is_string( $ov[ self::FIELD_INDUSTRY_KEY ] )
				? trim( $ov[ self::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $ik === $i ) {
				$out[] = $ov;
			}
		}
		return $out;
	}
}
