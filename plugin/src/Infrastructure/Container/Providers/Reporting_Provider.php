<?php
/**
 * Registers reporting domain services (install notification; spec §46, §59.12).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../../../Domain/Reporting/Heartbeat/Heartbeat_Scheduler.php';

use AIOPageBuilder\Domain\Reporting\Errors\Reporting_Redaction_Service;
use AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Scheduler;
use AIOPageBuilder\Domain\Reporting\Install\Install_Notification_Service;
use AIOPageBuilder\Domain\Reporting\Install\Install_Notification_Transport_Interface;
use AIOPageBuilder\Domain\Reporting\Install\Wp_Mail_Install_Transport;
use AIOPageBuilder\Domain\Reporting\Logs\Log_Export_Service;
use AIOPageBuilder\Domain\Reporting\UI\Privacy_Settings_State_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers install notification, heartbeat, developer error reporting, and Privacy & Settings screen state builder (spec §46, §49.12, §59.12).
 */
final class Reporting_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'install_notification_transport', function (): Install_Notification_Transport_Interface {
			return new Wp_Mail_Install_Transport();
		} );

		$container->register( 'install_notification_service', function () use ( $container ): Install_Notification_Service {
			$transport = $container->has( 'install_notification_transport' )
				? $container->get( 'install_notification_transport' )
				: null;
			return new Install_Notification_Service( $transport );
		} );

		Heartbeat_Scheduler::register_hook();

		$this->register_developer_error_reporting( $container );

		$logs_dir = __DIR__ . '/../../../Domain/Reporting/Logs';
		require_once $logs_dir . '/Log_Export_Result.php';
		require_once $logs_dir . '/Log_Export_Service.php';

		$container->register( 'log_export_service', function () use ( $container ): Log_Export_Service {
			$redaction = new Reporting_Redaction_Service();
			return new Log_Export_Service(
				$container->get( 'plugin_path_manager' ),
				$redaction,
				$container->has( 'logger' ) ? $container->get( 'logger' ) : null,
				$container->has( 'job_queue_repository' ) ? $container->get( 'job_queue_repository' ) : null,
				$container->has( 'ai_run_repository' ) ? $container->get( 'ai_run_repository' ) : null
			);
		} );

		$container->register( 'privacy_settings_state_builder', function () use ( $container ): Privacy_Settings_State_Builder {
			return new Privacy_Settings_State_Builder( $container->get( 'settings' ), $container );
		} );
	}

	/**
	 * Registers developer error reporting service and dependencies.
	 *
	 * @param Service_Container $container
	 * @return void
	 */
	private function register_developer_error_reporting( Service_Container $container ): void {
		$errors_dir = __DIR__ . '/../../../Domain/Reporting/Errors';
		require_once $errors_dir . '/Developer_Report_Result.php';
		require_once $errors_dir . '/Reporting_Redaction_Service.php';
		require_once $errors_dir . '/Reporting_Eligibility_Evaluator.php';
		require_once $errors_dir . '/Developer_Error_Transport_Interface.php';
		require_once $errors_dir . '/Wp_Mail_Developer_Error_Transport.php';
		require_once $errors_dir . '/Developer_Error_Reporting_Service.php';

		$container->register( 'developer_error_transport', function (): \AIOPageBuilder\Domain\Reporting\Errors\Developer_Error_Transport_Interface {
			return new \AIOPageBuilder\Domain\Reporting\Errors\Wp_Mail_Developer_Error_Transport();
		} );

		$container->register( 'developer_error_reporting_service', function () use ( $container ): \AIOPageBuilder\Domain\Reporting\Errors\Developer_Error_Reporting_Service {
			$transport = $container->has( 'developer_error_transport' )
				? $container->get( 'developer_error_transport' )
				: null;
			return new \AIOPageBuilder\Domain\Reporting\Errors\Developer_Error_Reporting_Service( null, null, $transport );
		} );
	}
}
