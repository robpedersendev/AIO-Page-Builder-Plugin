<?php
/**
 * Unit tests for Subtype_Goal_Page_OnePager_Overlay_Registry (Prompt 553, 554).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Page_OnePager_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Docs/Subtype_Goal_Page_OnePager_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/SubtypeGoalOverlays/Builtin_Subtype_Goal_Page_OnePager_Overlays.php';

final class Subtype_Goal_Page_OnePager_Overlay_Registry_Test extends TestCase {

	public function test_load_builtin_and_get_returns_overlay(): void {
		$registry = new Subtype_Goal_Page_OnePager_Overlay_Registry();
		$registry->load( Subtype_Goal_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
		$ov = $registry->get( 'realtor_buyer_agent', 'consultations', 'pt_contact_request_01' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'realtor_buyer_agent_consultations_contact', $ov[ Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_OVERLAY_KEY ] );
		$this->assertSame( 'realtor_buyer_agent', $ov[ Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY ] );
		$this->assertSame( 'consultations', $ov[ Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY ] );
		$this->assertSame( 'pt_contact_request_01', $ov[ Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY ] );
	}

	public function test_get_returns_null_when_subtype_goal_or_page_empty(): void {
		$registry = new Subtype_Goal_Page_OnePager_Overlay_Registry();
		$registry->load( Subtype_Goal_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
		$this->assertNull( $registry->get( '', 'consultations', 'pt_contact_request_01' ) );
		$this->assertNull( $registry->get( 'realtor_buyer_agent', '', 'pt_contact_request_01' ) );
		$this->assertNull( $registry->get( 'realtor_buyer_agent', 'consultations', '' ) );
	}

	public function test_get_returns_null_when_no_matching_overlay(): void {
		$registry = new Subtype_Goal_Page_OnePager_Overlay_Registry();
		$registry->load( Subtype_Goal_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
		$this->assertNull( $registry->get( 'unknown_subtype', 'calls', 'pt_contact_request_01' ) );
	}

	public function test_get_for_subtype_goal_returns_matching_overlays(): void {
		$registry = new Subtype_Goal_Page_OnePager_Overlay_Registry();
		$registry->load( Subtype_Goal_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
		$list = $registry->get_for_subtype_goal( 'realtor_buyer_agent', 'consultations' );
		$this->assertGreaterThanOrEqual( 1, count( $list ) );
		$this->assertSame( 'realtor_buyer_agent', $list[0][ Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY ] );
		$this->assertSame( 'consultations', $list[0][ Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY ] );
	}

	public function test_fallback_when_no_combined_overlay_returns_null(): void {
		$registry = new Subtype_Goal_Page_OnePager_Overlay_Registry();
		$registry->load( Subtype_Goal_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
		$this->assertNull( $registry->get( 'realtor_listing_agent', 'consultations', 'pt_contact_request_01' ) );
	}
}
