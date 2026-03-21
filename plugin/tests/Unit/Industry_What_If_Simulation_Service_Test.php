<?php
/**
 * Unit tests for Industry_What_If_Simulation_Service (Prompt 466).
 * Verifies simulated profile build, invalid ref handling, and no persistence.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_What_If_Simulation_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_What_If_Simulation_Service.php';

final class Industry_What_If_Simulation_Service_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$profile = array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor',
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => '',
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => '',
			Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => 'bookings',
		);
		\update_option( Option_Names::INDUSTRY_PROFILE, $profile );
	}

	protected function tearDown(): void {
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		parent::tearDown();
	}

	/** No params: simulated profile equals live; valid when pack registry null (no validation). */
	public function test_run_simulation_no_params_uses_live_profile(): void {
		$settings = new Settings_Service();
		$repo     = new Industry_Profile_Repository( $settings );
		$service  = new Industry_What_If_Simulation_Service( $repo );
		$result   = $service->run_simulation( array() );
		$this->assertTrue( $result['valid'] );
		$this->assertSame( 'realtor', $result['simulated_profile_summary']['primary'] );
		$this->assertSame( array(), $result['invalid_refs'] );
	}

	/** No-goal override: simulated summary has goal empty; live unchanged (Prompt 515 comparison). */
	public function test_run_simulation_no_goal_clears_goal_in_summary(): void {
		$settings = new Settings_Service();
		$repo     = new Industry_Profile_Repository( $settings );
		$service  = new Industry_What_If_Simulation_Service( $repo );
		$result   = $service->run_simulation(
			array(
				Industry_What_If_Simulation_Service::PARAM_ALTERNATE_CONVERSION_GOAL => '',
			)
		);
		$this->assertTrue( $result['valid'] );
		$this->assertSame( '', $result['simulated_profile_summary']['goal'] );
		$this->assertSame( 'bookings', $result['live_profile_summary']['goal'] );
	}

	/** Alternate goal valid: simulated summary reflects goal key (Prompt 515 comparison). */
	public function test_run_simulation_alternate_goal_valid(): void {
		$settings = new Settings_Service();
		$repo     = new Industry_Profile_Repository( $settings );
		$service  = new Industry_What_If_Simulation_Service( $repo );
		$result   = $service->run_simulation(
			array(
				Industry_What_If_Simulation_Service::PARAM_ALTERNATE_CONVERSION_GOAL => 'lead_capture',
			)
		);
		$this->assertTrue( $result['valid'] );
		$this->assertSame( 'lead_capture', $result['simulated_profile_summary']['goal'] );
		$this->assertSame( 'bookings', $result['live_profile_summary']['goal'] );
	}

	/** Invalid goal key (disallowed chars): simulated goal cleared; safe fallback (Prompt 515). */
	public function test_run_simulation_invalid_goal_key_sanitized(): void {
		$settings = new Settings_Service();
		$repo     = new Industry_Profile_Repository( $settings );
		$service  = new Industry_What_If_Simulation_Service( $repo );
		$result   = $service->run_simulation(
			array(
				Industry_What_If_Simulation_Service::PARAM_ALTERNATE_CONVERSION_GOAL => 'invalid.goal!',
			)
		);
		$this->assertTrue( $result['valid'] );
		$this->assertSame( '', $result['simulated_profile_summary']['goal'] );
	}

	/** Invalid primary ref yields valid=false and invalid_refs when pack registry present. */
	public function test_run_simulation_invalid_primary_ref(): void {
		$settings = new Settings_Service();
		$repo     = new Industry_Profile_Repository( $settings );
		$pack_reg = new Industry_Pack_Registry();
		$pack_reg->load( array() );
		$service = new Industry_What_If_Simulation_Service( $repo, $pack_reg );
		$result  = $service->run_simulation(
			array(
				Industry_What_If_Simulation_Service::PARAM_ALTERNATE_PRIMARY => 'nonexistent_pack',
			)
		);
		$this->assertFalse( $result['valid'] );
		$this->assertCount( 1, $result['invalid_refs'] );
		$this->assertSame( 'primary_industry', $result['invalid_refs'][0]['type'] );
		$this->assertSame( 'nonexistent_pack', $result['invalid_refs'][0]['key'] );
	}

	/** Alternate primary provided and pack exists: simulated summary reflects it. */
	public function test_run_simulation_alternate_primary_valid(): void {
		$settings = new Settings_Service();
		$repo     = new Industry_Profile_Repository( $settings );
		$pack_reg = new Industry_Pack_Registry();
		$pack_reg->load(
			array(
				array(
					'industry_key'     => 'realtor',
					'name'             => 'Realtor',
					'summary'          => 'Test pack.',
					'status'           => 'active',
					'version_marker'   => '1',
				),
			)
		);
		$service = new Industry_What_If_Simulation_Service( $repo, $pack_reg );
		$result  = $service->run_simulation(
			array(
				Industry_What_If_Simulation_Service::PARAM_ALTERNATE_PRIMARY => 'realtor',
			)
		);
		$this->assertTrue( $result['valid'] );
		$this->assertSame( 'realtor', $result['simulated_profile_summary']['primary'] );
		$this->assertSame( 'realtor', $result['live_profile_summary']['primary'] );
	}
}
