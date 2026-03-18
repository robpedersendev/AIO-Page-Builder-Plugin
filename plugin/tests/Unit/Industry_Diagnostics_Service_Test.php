<?php
/**
 * Unit tests for Industry_Diagnostics_Service: bounded snapshot, no secrets (Prompt 356).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Diagnostics_Service;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Section_Helper_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Page_OnePager_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Diagnostics_Service.php';

final class Industry_Diagnostics_Service_Test extends TestCase {

	public function test_snapshot_without_profile_repository_is_bounded(): void {
		$service  = new Industry_Diagnostics_Service( null, null, null, null, null );
		$snapshot = $service->get_snapshot();
		$this->assertFalse( $snapshot['industry_subsystem_available'] );
		$this->assertSame( '', $snapshot['primary_industry'] );
		$this->assertSame( 'none', $snapshot['profile_readiness'] );
		$this->assertSame( 'inactive', $snapshot['recommendation_mode'] );
		$this->assertArrayHasKey( 'warnings', $snapshot );
		$this->assertArrayHasKey( 'active_pack_refs', $snapshot );
		$this->assertArrayHasKey( 'applied_preset_ref', $snapshot );
		$this->assertArrayHasKey( 'section_overlay_count', $snapshot );
		$this->assertArrayHasKey( 'page_overlay_count', $snapshot );
		$this->assertCount( 10, $snapshot );
	}

	public function test_snapshot_with_empty_profile_has_no_secrets(): void {
		$settings = new Settings_Service();
		$repo     = new Industry_Profile_Repository( $settings );
		$service  = new Industry_Diagnostics_Service( $repo, null, null, null, null );
		$snapshot = $service->get_snapshot();
		$this->assertTrue( $snapshot['industry_subsystem_available'] );
		$this->assertSame( '', $snapshot['primary_industry'] );
		$forbidden = array( 'api_key', 'password', 'secret', 'token', 'credential' );
		foreach ( array_keys( $snapshot ) as $key ) {
			foreach ( $forbidden as $f ) {
				$this->assertStringNotContainsString( $f, strtolower( $key ) );
			}
		}
	}

	public function test_snapshot_with_primary_industry_sets_recommendation_mode(): void {
		$settings = new Settings_Service();
		$repo     = new Industry_Profile_Repository( $settings );
		$repo->set_profile( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor' ) );
		$section_overlay = new Industry_Section_Helper_Overlay_Registry();
		$section_overlay->load( array() );
		$page_overlay = new Industry_Page_OnePager_Overlay_Registry();
		$page_overlay->load( array() );
		$service  = new Industry_Diagnostics_Service( $repo, null, $section_overlay, $page_overlay, null );
		$snapshot = $service->get_snapshot();
		$this->assertSame( 'realtor', $snapshot['primary_industry'] );
		$this->assertSame( 'active', $snapshot['recommendation_mode'] );
		$this->assertContains( 'realtor', $snapshot['active_pack_refs'] );
		$this->assertIsInt( $snapshot['section_overlay_count'] );
		$this->assertIsInt( $snapshot['page_overlay_count'] );
	}
}
