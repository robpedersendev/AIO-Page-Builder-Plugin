<?php
/**
 * Resolves effective animation tier and families from section/page metadata and reduced-motion (animation-support-and-fallback-contract §2, §7).
 * Metadata-driven; no user-supplied script or tier.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Animation;

defined( 'ABSPATH' ) || exit;

/**
 * Produces stable resolution payload for rendering and asset loading.
 */
final class Animation_Tier_Resolver {

	/** Default tier when not set. */
	public const DEFAULT_TIER = 'none';

	/**
	 * Resolves effective animation tier and families for a section in context.
	 *
	 * @param array<string, mixed>      $section_definition Section template definition (animation_tier, animation_families, reduced_motion_behavior).
	 * @param array<string, mixed>|null $page_template      Optional page template (animation_tier_cap, animation_families_allowed).
	 * @param bool                      $reduced_motion     User prefers reduced motion.
	 * @return array{effective_tier: string, effective_families: list<string>, reduced_motion_applied: bool, resolution_reason: string}
	 */
	public function resolve(
		array $section_definition,
		?array $page_template,
		bool $reduced_motion = false
	): array {
		$reduced_service  = new Reduced_Motion_Service();
		$fallback_service = new Animation_Fallback_Service();

		$declared_tier = (string) ( $section_definition['animation_tier'] ?? self::DEFAULT_TIER );
		$declared_tier = $this->normalize_tier( $declared_tier );
		$families_raw  = $section_definition['animation_families'] ?? array();
		$families      = is_array( $families_raw ) ? $fallback_service->filter_allowed_families( $families_raw ) : array();
		$behavior      = (string) ( $section_definition['reduced_motion_behavior'] ?? Reduced_Motion_Service::BEHAVIOR_HONOR );

		$effective_tier = $declared_tier;
		$reason        = 'section_tier';

		if ( $reduced_motion ) {
			$effective_tier = $reduced_service->apply_to_tier( $declared_tier, true, $behavior );
			$reason         = 'reduced_motion';
		}

		$page_cap   = $page_template !== null ? (string) ( $page_template['animation_tier_cap'] ?? '' ) : '';
		$page_cap   = $this->normalize_tier( $page_cap );
		$page_allow = $page_template !== null && isset( $page_template['animation_families_allowed'] ) && is_array( $page_template['animation_families_allowed'] )
			? $fallback_service->filter_allowed_families( $page_template['animation_families_allowed'] )
			: array();

		if ( $page_cap !== '' && $this->tier_order( $effective_tier ) > $this->tier_order( $page_cap ) ) {
			$effective_tier = $page_cap;
			$reason         = 'page_cap';
		}

		if ( ! empty( $page_allow ) ) {
			$families = array_values( array_intersect( $families, $page_allow ) );
		}

		if ( $effective_tier === 'none' ) {
			$families = array();
		}

		return array(
			'effective_tier'          => $effective_tier,
			'effective_families'      => $families,
			'reduced_motion_applied'  => $reduced_motion,
			'resolution_reason'       => $reason,
		);
	}

	private function normalize_tier( string $tier ): string {
		$t = \sanitize_key( $tier );
		return in_array( $t, array( 'none', 'subtle', 'enhanced', 'premium' ), true ) ? $t : '';
	}

	private function tier_order( string $tier ): int {
		$order = array( 'none' => 0, 'subtle' => 1, 'enhanced' => 2, 'premium' => 3 );
		return $order[ $tier ] ?? 0;
	}
}
