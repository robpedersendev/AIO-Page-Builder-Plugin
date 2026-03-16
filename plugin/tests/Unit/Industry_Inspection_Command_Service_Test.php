<?php
/**
 * Unit tests for Industry_Inspection_Command_Service: read-only inspection, bounded output (Prompt 398).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Inspection_Command_Service;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Inspection_Command_Service.php';

// * Tests that call set_profile() require WordPress option stubs (run with phpunit.xml.dist bootstrap).

final class Industry_Inspection_Command_Service_Test extends TestCase {

	public function test_get_profile_summary_without_repo_returns_available_false(): void {
		$service = new Industry_Inspection_Command_Service( null, null, null );
		$summary = $service->get_profile_summary();
		$this->assertFalse( $summary['available'] );
		$this->assertSame( '', $summary['primary_industry_key'] );
		$this->assertSame( 'none', $summary['readiness'] );
		$this->assertArrayHasKey( 'secondary_industry_keys', $summary );
		$this->assertArrayHasKey( 'selected_starter_bundle_key', $summary );
	}

	public function test_get_profile_summary_with_repo_returns_bounded_shape(): void {
		$settings = new Settings_Service();
		$repo = new Industry_Profile_Repository( $settings );
		$repo->set_profile( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor' ) );
		$service = new Industry_Inspection_Command_Service( $repo, null, null );
		$summary = $service->get_profile_summary();
		$this->assertTrue( $summary['available'] );
		$this->assertSame( 'realtor', $summary['primary_industry_key'] );
		$this->assertIsArray( $summary['secondary_industry_keys'] );
		$this->assertSame( 'partial', $summary['readiness'] );
	}

	public function test_get_diagnostics_snapshot_without_service_returns_empty_bounded_shape(): void {
		$service = new Industry_Inspection_Command_Service( null, null, null );
		$snapshot = $service->get_diagnostics_snapshot();
		$this->assertFalse( $snapshot['industry_subsystem_available'] );
		$this->assertSame( 'none', $snapshot['profile_readiness'] );
		$this->assertSame( 'inactive', $snapshot['recommendation_mode'] );
		$this->assertArrayHasKey( 'primary_industry', $snapshot );
		$this->assertArrayHasKey( 'active_pack_refs', $snapshot );
	}

	public function test_get_health_summary_without_service_returns_available_false(): void {
		$service = new Industry_Inspection_Command_Service( null, null, null );
		$summary = $service->get_health_summary();
		$this->assertFalse( $summary['available'] );
		$this->assertSame( 0, $summary['errors_count'] );
		$this->assertSame( 0, $summary['warnings_count'] );
		$this->assertIsArray( $summary['sample_errors'] );
		$this->assertIsArray( $summary['sample_warnings'] );
	}

	public function test_get_recommendation_preview_without_resolvers_returns_bounded_shape(): void {
		$service = new Industry_Inspection_Command_Service( null, null, null, null, null, null, null );
		$preview = $service->get_recommendation_preview( 'realtor', 5, 0 );
		$this->assertSame( 'realtor', $preview['industry_key'] );
		$this->assertSame( array(), $preview['top_template_keys'] );
		$this->assertSame( array(), $preview['top_section_keys'] );
		$this->assertSame( 0, $preview['template_count'] );
		$this->assertSame( 0, $preview['section_count'] );
		$this->assertFalse( $preview['pack_found'] );
	}

	public function test_get_starter_bundles_for_industry_without_registry_returns_empty(): void {
		$service = new Industry_Inspection_Command_Service( null, null, null, null, null, null, null, null );
		$keys = $service->get_starter_bundles_for_industry( 'realtor' );
		$this->assertSame( array(), $keys );
	}

	public function test_inspection_is_read_only_no_mutation(): void {
		$settings = new Settings_Service();
		$repo = new Industry_Profile_Repository( $settings );
		$repo->set_profile( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'plumber' ) );
		$service = new Industry_Inspection_Command_Service( $repo, null, null );
		$first = $service->get_profile_summary();
		$second = $service->get_profile_summary();
		$this->assertSame( $first['primary_industry_key'], $second['primary_industry_key'] );
		$this->assertSame( 'plumber', $first['primary_industry_key'] );
	}
}
