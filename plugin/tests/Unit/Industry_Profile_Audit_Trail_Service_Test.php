<?php
/**
 * Unit tests for Industry_Profile_Audit_Trail_Service (Prompt 465).
 * Verifies event recording on profile change and bounded timeline output.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Profile_Audit_Trail_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Profile_Audit_Trail_Service.php';

final class Industry_Profile_Audit_Trail_Service_Test extends TestCase {

	private function get_empty_profile(): array {
		return array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY     => '',
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS  => array(),
			Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY    => '',
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => '',
		);
	}

	protected function tearDown(): void {
		\delete_option( Option_Names::INDUSTRY_PROFILE_AUDIT_TRAIL );
		parent::tearDown();
	}

	/** Primary industry change records one event. */
	public function test_record_primary_industry_change(): void {
		$service = new Industry_Profile_Audit_Trail_Service();
		$old     = $this->get_empty_profile();
		$new     = $this->get_empty_profile();
		$new[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] = 'realtor';
		$service->record_profile_change( $old, $new );
		$timeline = $service->get_timeline( 10 );
		$this->assertCount( 1, $timeline );
		$this->assertSame( Industry_Profile_Audit_Trail_Service::EVENT_PRIMARY_INDUSTRY_CHANGED, $timeline[0]['event_type'] );
		$this->assertStringContainsString( 'primary:', $timeline[0]['old_summary'] );
		$this->assertStringContainsString( 'realtor', $timeline[0]['new_summary'] );
		$this->assertArrayHasKey( 'timestamp', $timeline[0] );
	}

	/** No change records no events. */
	public function test_record_no_change_records_nothing(): void {
		$service = new Industry_Profile_Audit_Trail_Service();
		$profile = $this->get_empty_profile();
		$profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] = 'realtor';
		$service->record_profile_change( $profile, $profile );
		$this->assertCount( 0, $service->get_timeline( 10 ) );
	}

	/** get_timeline returns bounded list. */
	public function test_get_timeline_respects_limit(): void {
		$service = new Industry_Profile_Audit_Trail_Service();
		$old     = $this->get_empty_profile();
		for ( $i = 0; $i < 5; $i++ ) {
			$new = $this->get_empty_profile();
			$new[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] = 'pack_' . $i;
			$service->record_profile_change( $old, $new );
			$old = $new;
		}
		$timeline = $service->get_timeline( 3 );
		$this->assertCount( 3, $timeline );
	}
}
