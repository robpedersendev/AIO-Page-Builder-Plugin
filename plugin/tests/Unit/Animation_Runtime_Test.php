<?php
/**
 * Unit tests for animation tier resolution, reduced-motion, and fallback (Prompt 175, animation-support-and-fallback-contract).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\Animation\Animation_Fallback_Service;
use AIOPageBuilder\Domain\Rendering\Animation\Animation_Tier_Resolver;
use AIOPageBuilder\Domain\Rendering\Animation\Reduced_Motion_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/Animation/Reduced_Motion_Service.php';
require_once $plugin_root . '/src/Domain/Rendering/Animation/Animation_Fallback_Service.php';
require_once $plugin_root . '/src/Domain/Rendering/Animation/Animation_Tier_Resolver.php';

final class Animation_Runtime_Test extends TestCase {

	public function test_reduced_motion_service_cap_none_when_honor(): void {
		$svc = new Reduced_Motion_Service();
		$this->assertSame( Reduced_Motion_Service::TIER_NONE, $svc->get_effective_tier_cap( true, Reduced_Motion_Service::BEHAVIOR_HONOR ) );
		$this->assertSame( '', $svc->get_effective_tier_cap( false, Reduced_Motion_Service::BEHAVIOR_HONOR ) );
	}

	public function test_reduced_motion_service_cap_subtle_when_essential_only(): void {
		$svc = new Reduced_Motion_Service();
		$this->assertSame( Reduced_Motion_Service::TIER_SUBTLE, $svc->get_effective_tier_cap( true, Reduced_Motion_Service::BEHAVIOR_ESSENTIAL_ONLY ) );
	}

	public function test_reduced_motion_apply_to_tier_downgrades(): void {
		$svc = new Reduced_Motion_Service();
		$this->assertSame( 'none', $svc->apply_to_tier( 'enhanced', true, Reduced_Motion_Service::BEHAVIOR_HONOR ) );
		$this->assertSame( 'subtle', $svc->apply_to_tier( 'enhanced', true, Reduced_Motion_Service::BEHAVIOR_ESSENTIAL_ONLY ) );
		$this->assertSame( 'enhanced', $svc->apply_to_tier( 'enhanced', false, Reduced_Motion_Service::BEHAVIOR_HONOR ) );
	}

	public function test_animation_fallback_service_tier_chain(): void {
		$svc = new Animation_Fallback_Service();
		$this->assertSame( 'enhanced', $svc->get_fallback_tier( 'premium' ) );
		$this->assertSame( 'subtle', $svc->get_fallback_tier( 'enhanced' ) );
		$this->assertSame( 'none', $svc->get_fallback_tier( 'subtle' ) );
		$this->assertNull( $svc->get_fallback_tier( 'none' ) );
	}

	public function test_animation_fallback_resolve_with_support(): void {
		$svc = new Animation_Fallback_Service();
		$this->assertSame( 'subtle', $svc->resolve_with_support( 'enhanced', array( 'none', 'subtle' ) ) );
		$this->assertSame( 'none', $svc->resolve_with_support( 'premium', array() ) );
		$this->assertSame( 'enhanced', $svc->resolve_with_support( 'enhanced', array( 'none', 'subtle', 'enhanced' ) ) );
	}

	public function test_animation_fallback_filter_allowed_families(): void {
		$svc = new Animation_Fallback_Service();
		$out = $svc->filter_allowed_families( array( 'entrance', 'hover', 'invalid', 'scroll' ) );
		$this->assertSame( array( 'entrance', 'hover', 'scroll' ), $out );
	}

	/**
	 * Example animation-resolution payload (real structure): section subtle + entrance/hover, no page cap, no reduced motion.
	 */
	public function test_example_animation_resolution_payload(): void {
		$resolver = new Animation_Tier_Resolver();
		$section  = array(
			'animation_tier'          => 'subtle',
			'animation_families'      => array( 'entrance', 'hover' ),
			'reduced_motion_behavior' => 'honor',
		);
		$resolved = $resolver->resolve( $section, null, false );
		$this->assertSame( 'subtle', $resolved['effective_tier'] );
		$this->assertSame( array( 'entrance', 'hover' ), $resolved['effective_families'] );
		$this->assertFalse( $resolved['reduced_motion_applied'] );
		$this->assertSame( 'section_tier', $resolved['resolution_reason'] );
	}

	/**
	 * Fallback-resolution payload: reduced motion applied → effective tier none; page cap limits tier.
	 */
	public function test_fallback_resolution_payload(): void {
		$resolver = new Animation_Tier_Resolver();
		$section  = array(
			'animation_tier'          => 'enhanced',
			'animation_families'      => array( 'entrance', 'scroll' ),
			'reduced_motion_behavior' => 'honor',
		);
		$resolved = $resolver->resolve( $section, null, true );
		$this->assertSame( 'none', $resolved['effective_tier'] );
		$this->assertSame( array(), $resolved['effective_families'] );
		$this->assertTrue( $resolved['reduced_motion_applied'] );
		$this->assertSame( 'reduced_motion', $resolved['resolution_reason'] );
	}

	public function test_resolver_page_cap_limits_tier(): void {
		$resolver = new Animation_Tier_Resolver();
		$section  = array(
			'animation_tier'     => 'premium',
			'animation_families' => array( 'entrance', 'scroll' ),
		);
		$page     = array( 'animation_tier_cap' => 'enhanced' );
		$resolved = $resolver->resolve( $section, $page, false );
		$this->assertSame( 'enhanced', $resolved['effective_tier'] );
		$this->assertSame( 'page_cap', $resolved['resolution_reason'] );
	}

	public function test_resolver_page_families_allowed_filters(): void {
		$resolver = new Animation_Tier_Resolver();
		$section  = array(
			'animation_tier'     => 'subtle',
			'animation_families' => array( 'entrance', 'hover', 'scroll' ),
		);
		$page     = array( 'animation_families_allowed' => array( 'entrance', 'hover' ) );
		$resolved = $resolver->resolve( $section, $page, false );
		$this->assertSame( array( 'entrance', 'hover' ), $resolved['effective_families'] );
	}

	public function test_resolver_tier_none_yields_no_families(): void {
		$resolver = new Animation_Tier_Resolver();
		$section  = array(
			'animation_tier'     => 'none',
			'animation_families' => array( 'entrance' ),
		);
		$resolved = $resolver->resolve( $section, null, false );
		$this->assertSame( 'none', $resolved['effective_tier'] );
		$this->assertSame( array(), $resolved['effective_families'] );
	}
}
