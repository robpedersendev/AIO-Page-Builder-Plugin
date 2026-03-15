<?php
/**
 * Registry for industry-specific page one-pager overlays (industry-page-onepager-overlay-schema.md).
 * Keyed by industry_key + page_template_key; does not modify base one-pagers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Industry page one-pager overlay registry. Read-only after load. Invalid entries skipped.
 */
final class Industry_Page_OnePager_Overlay_Registry {

	/** Overlay object: industry pack key. */
	public const FIELD_INDUSTRY_KEY = 'industry_key';

	/** Overlay object: page template internal_key. */
	public const FIELD_PAGE_TEMPLATE_KEY = 'page_template_key';

	/** Overlay object: scope (page_onepager_overlay). */
	public const FIELD_SCOPE = 'scope';

	/** Overlay object: status. */
	public const FIELD_STATUS = 'status';

	/** Allowed scope for this overlay type. */
	public const SCOPE_PAGE_ONEPAGER_OVERLAY = 'page_onepager_overlay';

	/** Status: active overlays are used at resolution. */
	public const STATUS_ACTIVE = 'active';

	/** Pattern for industry_key. */
	private const INDUSTRY_KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for keys. */
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Composite key "industry_key|page_template_key" => overlay. */
	private array $by_composite = array();

	/** @var list<array<string, mixed>> All valid overlays in load order. */
	private array $all = array();

	/**
	 * Loads overlay definitions. Skips invalid or duplicate (industry_key, page_template_key). Safe: no throw.
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
			$page_key = isset( $ov[ self::FIELD_PAGE_TEMPLATE_KEY ] ) && is_string( $ov[ self::FIELD_PAGE_TEMPLATE_KEY ] )
				? trim( $ov[ self::FIELD_PAGE_TEMPLATE_KEY ] )
				: '';
			if ( $industry === '' || $page_key === '' ) {
				continue;
			}
			if ( strlen( $industry ) > self::KEY_MAX_LENGTH || ! preg_match( self::INDUSTRY_KEY_PATTERN, $industry ) ) {
				continue;
			}
			if ( strlen( $page_key ) > self::KEY_MAX_LENGTH ) {
				continue;
			}
			$scope = isset( $ov[ self::FIELD_SCOPE ] ) && is_string( $ov[ self::FIELD_SCOPE ] )
				? $ov[ self::FIELD_SCOPE ]
				: '';
			if ( $scope !== self::SCOPE_PAGE_ONEPAGER_OVERLAY ) {
				continue;
			}
			$status = isset( $ov[ self::FIELD_STATUS ] ) && is_string( $ov[ self::FIELD_STATUS ] )
				? $ov[ self::FIELD_STATUS ]
				: '';
			if ( $status === '' ) {
				continue;
			}
			$composite = $industry . '|' . $page_key;
			if ( isset( $this->by_composite[ $composite ] ) ) {
				continue;
			}
			$this->by_composite[ $composite ] = $ov;
			$this->all[]                    = $ov;
		}
	}

	/**
	 * Returns overlay for (industry_key, page_template_key), or null if not found.
	 *
	 * @param string $industry_key     Industry pack key.
	 * @param string $page_template_key Page template internal_key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $industry_key, string $page_template_key ): ?array {
		$i = trim( $industry_key );
		$p = trim( $page_template_key );
		$composite = $i . '|' . $p;
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
	 * Returns overlays for the given industry.
	 *
	 * @param string $industry_key Industry pack key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_industry( string $industry_key ): array {
		$i = trim( $industry_key );
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
