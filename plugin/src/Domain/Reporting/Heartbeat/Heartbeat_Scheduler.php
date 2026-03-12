<?php
/**
 * Schedules and unschedules the monthly heartbeat cron (spec §46.4, §53.1, §53.5).
 *
 * Registers the cron callback; activation schedules daily check; deactivation clears the schedule.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Heartbeat;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/Heartbeat_Service.php';

/**
 * Registers heartbeat cron hook and provides schedule/unschedule for lifecycle.
 */
final class Heartbeat_Scheduler {

	/** Cron hook name. Must be unique and stable for wp_clear_scheduled_hook. */
	public const CRON_HOOK = 'aio_page_builder_heartbeat_run';

	/** Recurrence: daily so we get "first available execution after" the monthly due. */
	private const RECURRENCE = 'daily';

	/**
	 * Registers the cron callback. Call once at plugin load (e.g. from Reporting_Provider).
	 *
	 * @return void
	 */
	public static function register_hook(): void {
		add_action( self::CRON_HOOK, array( self::class, 'run_heartbeat' ) );
	}

	/**
	 * Schedules the daily heartbeat check. Call from activation (Lifecycle_Manager::register_schedules).
	 * Idempotent: does not schedule a second event if one is already scheduled.
	 *
	 * @return void
	 */
	public static function schedule(): void {
		if ( function_exists( 'wp_next_scheduled' ) && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), self::RECURRENCE, self::CRON_HOOK );
		}
	}

	/**
	 * Clears the heartbeat schedule. Call from deactivation (Lifecycle_Manager::unschedule).
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
	}

	/**
	 * Cron callback: runs heartbeat service. Must not throw (WordPress cron).
	 *
	 * @return void
	 */
	public static function run_heartbeat(): void {
		$service = new Heartbeat_Service();
		$service->maybe_send();
	}
}
