<?php
/**
 * Unit tests for heartbeat scheduler (spec §46.4, §53.1, §53.5; Prompt 093).
 *
 * Covers schedule, unschedule (deactivation behavior): after unschedule, no event is next-scheduled.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Scheduler;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Reporting/Heartbeat/Heartbeat_Scheduler.php';

final class Heartbeat_Scheduler_Test extends TestCase {

	protected function tearDown(): void {
		if ( isset( $GLOBALS['_aio_cron_scheduled'] ) && is_array( $GLOBALS['_aio_cron_scheduled'] ) ) {
			unset( $GLOBALS['_aio_cron_scheduled'][ Heartbeat_Scheduler::CRON_HOOK ] );
		}
		parent::tearDown();
	}

	public function test_deactivation_unscheduling_clears_hook(): void {
		Heartbeat_Scheduler::schedule();
		$this->assertTrue(
			\wp_next_scheduled( Heartbeat_Scheduler::CRON_HOOK ),
			'Schedule should register a next run for the heartbeat hook.'
		);

		Heartbeat_Scheduler::unschedule();
		$this->assertFalse(
			\wp_next_scheduled( Heartbeat_Scheduler::CRON_HOOK ),
			'After unschedule, no heartbeat cron should be next-scheduled.'
		);
	}

	public function test_schedule_idempotent(): void {
		Heartbeat_Scheduler::schedule();
		Heartbeat_Scheduler::schedule();
		$this->assertTrue( \wp_next_scheduled( Heartbeat_Scheduler::CRON_HOOK ) );
		Heartbeat_Scheduler::unschedule();
		$this->assertFalse( \wp_next_scheduled( Heartbeat_Scheduler::CRON_HOOK ) );
	}
}
