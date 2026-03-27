<?php
/**
 * Reduced-motion preference handling (animation-support-and-fallback-contract §5).
 * When user prefers reduced motion, effective tier is capped to none or essential_only (subtle for focus only).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Animation;

defined( 'ABSPATH' ) || exit;

/**
 * Server-side reduced-motion policy: no script injection; metadata-driven.
 */
final class Reduced_Motion_Service {

	public const BEHAVIOR_HONOR          = 'honor';
	public const BEHAVIOR_ESSENTIAL_ONLY = 'essential_only';
	public const TIER_NONE               = 'none';
	public const TIER_SUBTLE             = 'subtle';

	/**
	 * Returns the effective tier cap when reduced-motion preference is active.
	 *
	 * @param bool   $reduced_motion_preference User prefers reduced motion.
	 * @param string $section_behavior         honor | essential_only.
	 * @return string Tier slug to cap at: none or subtle.
	 */
	public function get_effective_tier_cap( bool $reduced_motion_preference, string $section_behavior = self::BEHAVIOR_HONOR ): string {
		if ( ! $reduced_motion_preference ) {
			return '';
		}
		$behavior = \sanitize_key( $section_behavior );
		if ( $behavior === self::BEHAVIOR_ESSENTIAL_ONLY ) {
			return self::TIER_SUBTLE;
		}
		return self::TIER_NONE;
	}

	/**
	 * Applies reduced-motion cap to a section's declared tier.
	 *
	 * @param string $declared_tier             Section animation_tier.
	 * @param bool   $reduced_motion_preference Whether user prefers reduced motion.
	 * @param string $section_behavior         Section reduced_motion_behavior.
	 * @return string Effective tier (never higher than cap).
	 */
	public function apply_to_tier( string $declared_tier, bool $reduced_motion_preference, string $section_behavior = self::BEHAVIOR_HONOR ): string {
		if ( ! $reduced_motion_preference ) {
			return $this->normalize_tier( $declared_tier );
		}
		$cap  = $this->get_effective_tier_cap( true, $section_behavior );
		$tier = $this->normalize_tier( $declared_tier );
		return $this->min_tier( $tier, $cap );
	}

	private function tier_order( string $tier ): int {
		$order = array(
			'none'     => 0,
			'subtle'   => 1,
			'enhanced' => 2,
			'premium'  => 3,
		);
		return $order[ $tier ] ?? 0;
	}

	private function min_tier( string $a, string $b ): string {
		return $this->tier_order( $a ) <= $this->tier_order( $b ) ? $a : $b;
	}

	private function normalize_tier( string $tier ): string {
		$t = \sanitize_key( $tier );
		return in_array( $t, array( 'none', 'subtle', 'enhanced', 'premium' ), true ) ? $t : 'none';
	}
}
