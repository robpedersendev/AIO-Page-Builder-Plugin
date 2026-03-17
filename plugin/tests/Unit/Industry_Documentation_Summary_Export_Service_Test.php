<?php
/**
 * Unit tests for Industry_Documentation_Summary_Export_Service: bounded summary, no secrets (Prompt 458).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Diagnostics_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Documentation_Summary_Export_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Audit_Report_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Diagnostics_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Health_Check_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Section_Override_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Page_Template_Override_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Build_Plan_Item_Override_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Read_Model_Builder.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Override_Audit_Report_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Documentation_Summary_Export_Service.php';

final class Industry_Documentation_Summary_Export_Service_Test extends TestCase {

	protected function tearDown(): void {
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		parent::tearDown();
	}

	public function test_generate_with_no_dependencies_returns_bounded_shape(): void {
		$service = new Industry_Documentation_Summary_Export_Service( null, null, null, null );
		$out = $service->generate();
		$this->assertArrayHasKey( 'generated_at', $out );
		$this->assertArrayHasKey( 'profile_state', $out );
		$this->assertArrayHasKey( 'active_pack_refs', $out );
		$this->assertArrayHasKey( 'override_summary', $out );
		$this->assertArrayHasKey( 'health', $out );
		$this->assertArrayHasKey( 'major_warnings', $out );
		$this->assertSame( 'none', $out['profile_state']['profile_readiness'] );
		$this->assertSame( array(), $out['active_pack_refs'] );
		$this->assertSame( 0, $out['override_summary']['total_count'] );
		$this->assertSame( 0, $out['health']['error_count'] );
		$this->assertSame( 0, $out['health']['warning_count'] );
		$this->assertIsArray( $out['health']['sample_errors'] );
		$this->assertIsArray( $out['health']['sample_warnings'] );
	}

	public function test_generate_includes_no_sensitive_keys(): void {
		$service = new Industry_Documentation_Summary_Export_Service( null, null, null, null );
		$out = $service->generate();
		$forbidden = array( 'api_key', 'password', 'secret', 'token', 'credential', 'raw_content' );
		$keys_str = strtolower( json_encode( array_keys( $out ) ) );
		foreach ( $forbidden as $f ) {
			$this->assertStringNotContainsString( $f, $keys_str );
		}
	}

	public function test_generate_with_diagnostics_populates_profile_and_pack_refs(): void {
		$settings = new Settings_Service();
		$repo = new Industry_Profile_Repository( $settings );
		$repo->set_profile( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor' ) );
		$diagnostics = new Industry_Diagnostics_Service( $repo, null, null, null, null );
		$export = new Industry_Documentation_Summary_Export_Service( $diagnostics, null, null, $repo );
		$out = $export->generate();
		$this->assertSame( 'realtor', $out['profile_state']['primary_industry'] );
		$this->assertContains( 'realtor', $out['active_pack_refs'] );
		$this->assertNotEmpty( $out['generated_at'] );
	}

	public function test_generate_with_health_check_populates_health_and_major_warnings(): void {
		$settings = new Settings_Service();
		$repo = new Industry_Profile_Repository( $settings );
		$repo->set_profile( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'missing_pack' ) );
		$pack_registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry( new \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator() );
		$pack_registry->load( array() );
		$health = new Industry_Health_Check_Service( $repo, $pack_registry, null, null, null, null, null, null, null, null, null );
		$export = new Industry_Documentation_Summary_Export_Service( null, $health, null, null );
		$out = $export->generate();
		$this->assertGreaterThan( 0, $out['health']['error_count'] );
		$this->assertLessThanOrEqual( 5, count( $out['health']['sample_errors'] ) );
		$this->assertNotEmpty( $out['health']['sample_errors'] );
	}

	public function test_generate_with_override_audit_populates_override_summary(): void {
		$override_audit = new Industry_Override_Audit_Report_Service();
		$export = new Industry_Documentation_Summary_Export_Service( null, null, $override_audit, null );
		$out = $export->generate();
		$this->assertArrayHasKey( 'total_count', $out['override_summary'] );
		$this->assertArrayHasKey( 'by_type', $out['override_summary'] );
		$this->assertIsInt( $out['override_summary']['total_count'] );
	}
}
