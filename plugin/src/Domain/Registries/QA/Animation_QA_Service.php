<?php
/**
 * Cross-browser animation and fallback QA for the template library (Prompt 187).
 * Authority: spec §7.7, §51.10, §55.5, §56.6, §59.14; animation-support-and-fallback-contract; template-library-compliance-matrix ANIMATION family.
 * Verifies animation tiers, fallback behavior, and reduced-motion resolution at library scale; produces animation_qa_result and manual checklist.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\QA;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Rendering\Animation\Animation_Fallback_Service;
use AIOPageBuilder\Domain\Rendering\Animation\Animation_Tier_Resolver;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Runs library-wide animation/fallback QA. Machine-checkable: tier/family metadata validity and reduced-motion resolution.
 * Manual checklist covers tier-none layout, reduced-motion behavior in browser, and no broken layout in low-support scenarios.
 */
final class Animation_QA_Service {

	private const LIBRARY_CAP = 2000;

	/** Allowed animation tiers (animation-support-and-fallback-contract §2). */
	private const ALLOWED_TIERS = array( 'none', 'subtle', 'enhanced', 'premium' );

	/** Safe tiers when reduced-motion is applied (contract §5.2). */
	private const REDUCED_MOTION_SAFE_TIERS = array( 'none', 'subtle' );

	private Section_Template_Repository $section_repository;
	private Page_Template_Repository $page_repository;
	private Animation_Fallback_Service $fallback_service;
	private Animation_Tier_Resolver $tier_resolver;

	public function __construct(
		Section_Template_Repository $section_repository,
		Page_Template_Repository $page_repository,
		Animation_Fallback_Service $fallback_service,
		Animation_Tier_Resolver $tier_resolver
	) {
		$this->section_repository = $section_repository;
		$this->page_repository    = $page_repository;
		$this->fallback_service   = $fallback_service;
		$this->tier_resolver      = $tier_resolver;
	}

	/**
	 * Runs full animation/fallback QA; returns result for machine and human reporting.
	 */
	public function run(): Animation_QA_Result {
		$sections = $this->section_repository->list_all_definitions_capped( self::LIBRARY_CAP );
		$pages    = $this->page_repository->list_all_definitions_capped( self::LIBRARY_CAP );

		$violations                  = array();
		$section_by_tier             = array(
			'none'     => 0,
			'subtle'   => 0,
			'enhanced' => 0,
			'premium'  => 0,
		);
		$reduced_motion_capped_count = 0;
		$all_resolve_safe            = true;

		foreach ( $sections as $def ) {
			$this->audit_section( $def, $violations, $section_by_tier );
			$resolved  = $this->tier_resolver->resolve( $def, null, true );
			$effective = $resolved['effective_tier'] ?? 'none';
			if ( in_array( $effective, self::REDUCED_MOTION_SAFE_TIERS, true ) ) {
				$declared = (string) ( $def['animation_tier'] ?? 'none' );
				$declared = in_array( $declared, self::ALLOWED_TIERS, true ) ? $declared : 'none';
				if ( $this->tier_order( $declared ) > $this->tier_order( $effective ) ) {
					++$reduced_motion_capped_count;
				}
			} else {
				$all_resolve_safe = false;
			}
		}

		$page_with_cap = 0;
		foreach ( $pages as $def ) {
			$this->audit_page( $def, $violations );
			$cap = (string) ( $def['animation_tier_cap'] ?? '' );
			if ( $cap !== '' && in_array( $cap, self::ALLOWED_TIERS, true ) ) {
				++$page_with_cap;
			}
		}

		$section_violations = array_filter(
			$violations,
			function ( $v ) {
				return ( $v['scope'] ?? '' ) === 'section';
			}
		);
		$page_violations    = array_filter(
			$violations,
			function ( $v ) {
				return ( $v['scope'] ?? '' ) === 'page';
			}
		);

		$reduced_motion_check_result = array(
			'sections_checked'      => count( $sections ),
			'all_resolve_safe_tier' => $all_resolve_safe,
			'sections_capped_count' => $reduced_motion_capped_count,
		);

		$section_summary = array(
			'audited'    => count( $sections ),
			'by_tier'    => $section_by_tier,
			'violations' => count( $section_violations ),
		);

		$page_summary = array(
			'audited'       => count( $pages ),
			'with_tier_cap' => $page_with_cap,
			'violations'    => count( $page_violations ),
		);

		$manual_qa_checklist = array(
			'Tier none: With animation disabled or tier forced to none, every section and page renders with correct layout and all content visible.',
			'Reduced-motion: With prefers-reduced-motion: reduce, no decorative or non-essential animation runs; content and CTAs remain visible and usable.',
			'No broken layout: In at least one low-support scenario (e.g. animation off), confirm no overflow, overlap, or invisible critical content.',
			'Progressive enhancement: In a full-support scenario, enhanced/premium tiers add motion without removing or hiding content.',
			'Focus and modals: If modal or focus-related animation exists, focus trap and focus return still work when reduced motion is on (Spec §51.10).',
		);

		$passed = empty( $violations ) && $all_resolve_safe;

		return new Animation_QA_Result(
			$passed,
			array_values( $violations ),
			$reduced_motion_check_result,
			$section_summary,
			$page_summary,
			$manual_qa_checklist
		);
	}

	/**
	 * @param array<string, mixed>                                                            $def
	 * @param list<array{scope: string, template_key: string, code: string, message: string}> $violations
	 * @param array<string, int>                                                              $section_by_tier
	 */
	private function audit_section( array $def, array &$violations, array &$section_by_tier ): void {
		$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		if ( $key === '' ) {
			return;
		}

		$tier = (string) ( $def['animation_tier'] ?? 'none' );
		$tier = \sanitize_key( $tier );
		if ( ! in_array( $tier, self::ALLOWED_TIERS, true ) ) {
			$tier         = 'none';
			$violations[] = array(
				'scope'        => 'section',
				'template_key' => $key,
				'code'         => 'invalid_tier',
				'message'      => 'animation_tier must be none, subtle, enhanced, or premium.',
			);
		}
		$section_by_tier[ $tier ] = ( $section_by_tier[ $tier ] ?? 0 ) + 1;

		$families_raw = $def['animation_families'] ?? array();
		if ( is_array( $families_raw ) ) {
			$filtered = $this->fallback_service->filter_allowed_families( $families_raw );
			if ( count( $filtered ) !== count( array_filter( $families_raw, 'is_string' ) ) ) {
				$violations[] = array(
					'scope'        => 'section',
					'template_key' => $key,
					'code'         => 'invalid_family',
					'message'      => 'animation_families must only contain allowed slugs (entrance, hover, scroll, focus, disclosure, stagger, micro).',
				);
			}
		}

		$fallback_tier = $def['animation_fallback_tier'] ?? null;
		if ( $fallback_tier !== null && $fallback_tier !== '' ) {
			$ft = \sanitize_key( (string) $fallback_tier );
			if ( ! in_array( $ft, self::ALLOWED_TIERS, true ) || ( $ft !== 'none' && $ft !== $tier ) ) {
				$violations[] = array(
					'scope'        => 'section',
					'template_key' => $key,
					'code'         => 'fallback_tier_invalid',
					'message'      => 'animation_fallback_tier must be none or match animation_tier.',
				);
			}
		}

		$behavior = (string) ( $def['reduced_motion_behavior'] ?? 'honor' );
		$behavior = \sanitize_key( $behavior );
		if ( ! in_array( $behavior, array( 'honor', 'essential_only' ), true ) ) {
			$violations[] = array(
				'scope'        => 'section',
				'template_key' => $key,
				'code'         => 'invalid_reduced_motion_behavior',
				'message'      => 'reduced_motion_behavior must be honor or essential_only.',
			);
		}
	}

	/**
	 * @param array<string, mixed>                                                            $def
	 * @param list<array{scope: string, template_key: string, code: string, message: string}> $violations
	 */
	private function audit_page( array $def, array &$violations ): void {
		$key = (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		if ( $key === '' ) {
			return;
		}

		$cap = (string) ( $def['animation_tier_cap'] ?? '' );
		if ( $cap !== '' ) {
			$cap = \sanitize_key( $cap );
			if ( ! in_array( $cap, self::ALLOWED_TIERS, true ) ) {
				$violations[] = array(
					'scope'        => 'page',
					'template_key' => $key,
					'code'         => 'invalid_tier_cap',
					'message'      => 'animation_tier_cap must be none, subtle, enhanced, or premium.',
				);
			}
		}

		$allowed = $def['animation_families_allowed'] ?? null;
		if ( is_array( $allowed ) && ! empty( $allowed ) ) {
			$filtered = $this->fallback_service->filter_allowed_families( $allowed );
			if ( count( $filtered ) !== count( array_filter( $allowed, 'is_string' ) ) ) {
				$violations[] = array(
					'scope'        => 'page',
					'template_key' => $key,
					'code'         => 'invalid_families_allowed',
					'message'      => 'animation_families_allowed must only contain allowed family slugs.',
				);
			}
		}
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
}
