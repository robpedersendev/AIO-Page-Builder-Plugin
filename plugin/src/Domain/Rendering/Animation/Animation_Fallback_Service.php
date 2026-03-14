<?php
/**
 * Deterministic fallback for animation tier/family when support is missing (animation-support-and-fallback-contract §4).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Animation;

defined( 'ABSPATH' ) || exit;

/**
 * Returns fallback tier when a given tier is unsupported or disabled.
 */
final class Animation_Fallback_Service {

	private const TIER_ORDER = array( 'none', 'subtle', 'enhanced', 'premium' );

	private const TIER_FALLBACK = array(
		'premium'  => 'enhanced',
		'enhanced' => 'subtle',
		'subtle'   => 'none',
		'none'     => null,
	);

	private const ALLOWED_FAMILIES = array(
		'entrance', 'hover', 'scroll', 'focus', 'disclosure', 'stagger', 'micro',
	);

	/**
	 * Returns the fallback tier when the given tier is unsupported.
	 *
	 * @param string $tier Current tier.
	 * @return string|null Next lower tier, or null when none.
	 */
	public function get_fallback_tier( string $tier ): ?string {
		$t = \sanitize_key( $tier );
		return self::TIER_FALLBACK[ $t ] ?? null;
	}

	/**
	 * Resolves effective tier given supported tiers in context.
	 *
	 * @param string   $requested_tier  Tier requested.
	 * @param string[] $supported_tiers Tiers supported in current context.
	 * @return string Effective tier.
	 */
	public function resolve_with_support( string $requested_tier, array $supported_tiers = array() ): string {
		if ( empty( $supported_tiers ) ) {
			return 'none';
		}
		$requested = \sanitize_key( $requested_tier );
		$supported = array_flip( array_map( 'sanitize_key', $supported_tiers ) );
		$current   = in_array( $requested, self::TIER_ORDER, true ) ? $requested : 'none';
		while ( $current !== null && ! isset( $supported[ $current ] ) ) {
			$current = $this->get_fallback_tier( $current );
		}
		return $current ?? 'none';
	}

	/**
	 * Filters family slugs against contract (allowed families only).
	 *
	 * @param string[] $families Declared animation_families.
	 * @return list<string>
	 */
	public function filter_allowed_families( array $families ): array {
		$out = array();
		foreach ( $families as $f ) {
			if ( ! is_string( $f ) || $f === '' ) {
				continue;
			}
			$slug = \sanitize_key( $f );
			if ( in_array( $slug, self::ALLOWED_FAMILIES, true ) ) {
				$out[] = $slug;
			}
		}
		return array_values( array_unique( $out ) );
	}

	public function is_none( string $tier ): bool {
		return \sanitize_key( $tier ) === 'none';
	}
}
