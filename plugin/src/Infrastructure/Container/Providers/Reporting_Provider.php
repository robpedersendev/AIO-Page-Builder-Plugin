<?php
/**
 * Registers reporting domain services (install notification; spec §46, §59.12).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

// * Manual requires: production bootstrap does not use Composer autoload; interfaces/results before classes that implement or type-hint them.
$aio_pb_reporting_heartbeat_dir = __DIR__ . '/../../../Domain/Reporting/Heartbeat';
require_once $aio_pb_reporting_heartbeat_dir . '/Heartbeat_Transport_Interface.php';
require_once $aio_pb_reporting_heartbeat_dir . '/Heartbeat_Health_Provider_Interface.php';
require_once $aio_pb_reporting_heartbeat_dir . '/Heartbeat_Result.php';
require_once $aio_pb_reporting_heartbeat_dir . '/Heartbeat_Service.php';
require_once $aio_pb_reporting_heartbeat_dir . '/Heartbeat_Scheduler.php';
require_once $aio_pb_reporting_heartbeat_dir . '/Default_Heartbeat_Health_Provider.php';
require_once $aio_pb_reporting_heartbeat_dir . '/Wp_Mail_Heartbeat_Transport.php';
require_once __DIR__ . '/../../../Domain/Reporting/Payloads/Template_Library_Report_Summary_Builder.php';

$aio_pb_reporting_install_dir = __DIR__ . '/../../../Domain/Reporting/Install';
require_once $aio_pb_reporting_install_dir . '/Install_Notification_Transport_Interface.php';
require_once $aio_pb_reporting_install_dir . '/Wp_Mail_Install_Transport.php';
require_once $aio_pb_reporting_install_dir . '/Install_Notification_Service.php';

use AIOPageBuilder\Domain\Reporting\Errors\Reporting_Redaction_Service;
use AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Service;
use AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Scheduler;
use AIOPageBuilder\Domain\Reporting\Install\Install_Notification_Service;
use AIOPageBuilder\Domain\Reporting\Install\Install_Notification_Transport_Interface;
use AIOPageBuilder\Domain\Reporting\Install\Wp_Mail_Install_Transport;
use AIOPageBuilder\Domain\Reporting\Logs\Log_Export_Service;
use AIOPageBuilder\Domain\Reporting\Payloads\Template_Library_Report_Summary_Builder;
use AIOPageBuilder\Domain\Reporting\UI\Privacy_Settings_State_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers install notification, heartbeat, developer error reporting, and Privacy & Settings screen state builder (spec §46, §49.12, §59.12).
 */
final class Reporting_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'install_notification_transport',
			function (): Install_Notification_Transport_Interface {
				return new Wp_Mail_Install_Transport();
			}
		);

		$this->register_template_library_report_summary_builder( $container );

		$container->register(
			'install_notification_service',
			function () use ( $container ): Install_Notification_Service {
				$transport       = $container->has( 'install_notification_transport' )
				? $container->get( 'install_notification_transport' )
				: null;
				$summary_builder = $container->has( 'template_library_report_summary_builder' )
				? $container->get( 'template_library_report_summary_builder' )
				: null;
				return new Install_Notification_Service( $transport, $summary_builder );
			}
		);

		$this->register_heartbeat_service( $container );

		Heartbeat_Scheduler::register_hook();

		$this->register_developer_error_reporting( $container );

		$logs_dir = __DIR__ . '/../../../Domain/Reporting/Logs';
		require_once $logs_dir . '/Log_Export_Result.php';
		require_once $logs_dir . '/Log_Export_Service.php';

		$container->register(
			'log_export_service',
			function () use ( $container ): Log_Export_Service {
				$redaction = new Reporting_Redaction_Service();
				return new Log_Export_Service(
					$container->get( 'plugin_path_manager' ),
					$redaction,
					$container->has( 'logger' ) ? $container->get( 'logger' ) : null,
					$container->has( 'job_queue_repository' ) ? $container->get( 'job_queue_repository' ) : null,
					$container->has( 'ai_run_repository' ) ? $container->get( 'ai_run_repository' ) : null
				);
			}
		);

		$container->register(
			'privacy_settings_state_builder',
			function () use ( $container ): Privacy_Settings_State_Builder {
				return new Privacy_Settings_State_Builder( $container->get( 'settings' ), $container );
			}
		);
	}

	/**
	 * Registers template_library_report_summary_builder for install, heartbeat, and error report enrichment (Prompt 214).
	 *
	 * @param Service_Container $container
	 * @return void
	 */
	private function register_template_library_report_summary_builder( Service_Container $container ): void {
		$container->register(
			'template_library_report_summary_builder',
			function () use ( $container ): Template_Library_Report_Summary_Builder {
				$section_repo = $container->has( 'section_template_repository' ) ? $container->get( 'section_template_repository' ) : null;
				$page_repo    = $container->has( 'page_template_repository' ) ? $container->get( 'page_template_repository' ) : null;
				$comp_repo    = $container->has( 'composition_repository' ) ? $container->get( 'composition_repository' ) : null;
				$appendices   = $container->has( 'section_inventory_appendix_generator' ) && $container->has( 'page_template_inventory_appendix_generator' );
				return new Template_Library_Report_Summary_Builder( $section_repo, $page_repo, $comp_repo, $appendices, null );
			}
		);
	}

	/**
	 * Registers heartbeat_service and filter for cron callback (Prompt 214).
	 *
	 * @param Service_Container $container
	 * @return void
	 */
	private function register_heartbeat_service( Service_Container $container ): void {
		$container->register(
			'heartbeat_transport',
			function (): \AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Transport_Interface {
				return new \AIOPageBuilder\Domain\Reporting\Heartbeat\Wp_Mail_Heartbeat_Transport();
			}
		);
		$container->register(
			'heartbeat_health_provider',
			function (): \AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Health_Provider_Interface {
				return new \AIOPageBuilder\Domain\Reporting\Heartbeat\Default_Heartbeat_Health_Provider();
			}
		);
		$container->register(
			'heartbeat_service',
			function () use ( $container ): Heartbeat_Service {
				$transport = $container->get( 'heartbeat_transport' );
				$health    = $container->get( 'heartbeat_health_provider' );
				$summary   = $container->has( 'template_library_report_summary_builder' ) ? $container->get( 'template_library_report_summary_builder' ) : null;
				return new Heartbeat_Service( $transport, $health, $summary );
			}
		);
		add_filter(
			'aio_page_builder_heartbeat_service',
			function () use ( $container ): ?Heartbeat_Service {
				return $container->get( 'heartbeat_service' );
			},
			10,
			0
		);
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

		$container->register(
			'developer_error_transport',
			function (): \AIOPageBuilder\Domain\Reporting\Errors\Developer_Error_Transport_Interface {
				return new \AIOPageBuilder\Domain\Reporting\Errors\Wp_Mail_Developer_Error_Transport();
			}
		);

		$container->register(
			'developer_error_reporting_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Reporting\Errors\Developer_Error_Reporting_Service {
				$transport       = $container->has( 'developer_error_transport' )
				? $container->get( 'developer_error_transport' )
				: null;
				$summary_builder = $container->has( 'template_library_report_summary_builder' )
				? $container->get( 'template_library_report_summary_builder' )
				: null;
				return new \AIOPageBuilder\Domain\Reporting\Errors\Developer_Error_Reporting_Service( null, null, $transport, $summary_builder );
			}
		);
	}
}
