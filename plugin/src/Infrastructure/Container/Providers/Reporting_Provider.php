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

use AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Scheduler;
use AIOPageBuilder\Domain\Reporting\Install\Install_Notification_Service;
use AIOPageBuilder\Domain\Reporting\Install\Install_Notification_Transport_Interface;
use AIOPageBuilder\Domain\Reporting\Install\Wp_Mail_Install_Transport;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers install notification service and transport for use by lifecycle or diagnostics.
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
	}
}
