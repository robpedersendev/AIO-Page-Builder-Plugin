<?php
/**
 * Tests for Onboarding_And_Build_Plan_Reset_Service.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Keys;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Telemetry;
use AIOPageBuilder\Domain\Onboarding\Onboarding_And_Build_Plan_Reset_Service;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Domain\Onboarding\Onboarding_And_Build_Plan_Reset_Service
 */
final class Onboarding_And_Build_Plan_Reset_Service_Test extends TestCase {

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_test_options'],
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_wp_deleted_posts'],
			$GLOBALS['_aio_wp_delete_post_return'],
			$GLOBALS['_aio_current_uid'],
			$GLOBALS['_aio_transients']
		);
		parent::tearDown();
	}

	public function test_reset_deletes_build_plans_and_clears_onboarding_options(): void {
		$GLOBALS['_aio_wp_query_posts'] = array(
			(object) array(
				'ID'        => 501,
				'post_type' => Object_Type_Keys::BUILD_PLAN,
			),
			(object) array(
				'ID'        => 502,
				'post_type' => Object_Type_Keys::BUILD_PLAN,
			),
		);
		$GLOBALS['_aio_current_uid']    = 7;
		\set_transient( 'aio_onboarding_planning_result_7', array( 'x' => 1 ), 60 );
		\set_transient( 'aio_onboarding_advance_validation_7', array( 'e' => 1 ), 60 );

		$settings = new Settings_Service();
		$settings->set(
			Option_Names::ONBOARDING_DRAFT,
			array(
				'version'          => Onboarding_Draft_Service::DRAFT_VERSION,
				'current_step_key' => Onboarding_Step_Keys::SUBMISSION,
				'overall_status'   => 'in_progress',
				'step_statuses'    => array(),
			)
		);
		$settings->set(
			Option_Names::ONBOARDING_TELEMETRY_AGGREGATE,
			array(
				'v' => Onboarding_Telemetry::OPTION_SHAPE_VERSION,
				'c' => array( 'draft_save' => 3 ),
			)
		);
		\update_option( Option_Names::PB_ONBOARDING_LAST_SUBMITTED_AT, '2020-01-01T00:00:00+00:00' );

		$draft = new Onboarding_Draft_Service( $settings );
		$svc   = new Onboarding_And_Build_Plan_Reset_Service( $draft, $settings );
		$out   = $svc->reset();

		$this->assertSame( 2, $out['build_plans_deleted'] );
		$this->assertSame( array( 501, 502 ), $GLOBALS['_aio_wp_deleted_posts'] );

		$cleared = $draft->get_draft();
		$this->assertSame( Onboarding_Step_Keys::WELCOME, $cleared['current_step_key'] );

		$tel = $settings->get( Option_Names::ONBOARDING_TELEMETRY_AGGREGATE );
		$this->assertSame( array(), $tel['c'] );
		$this->assertFalse( \get_transient( 'aio_onboarding_planning_result_7' ) );
		$this->assertFalse( \get_transient( 'aio_onboarding_advance_validation_7' ) );
		$this->assertFalse( \get_option( Option_Names::PB_ONBOARDING_LAST_SUBMITTED_AT, false ) );
	}
}
