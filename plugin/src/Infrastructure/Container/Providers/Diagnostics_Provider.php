<?php
/**
 * Registers logger and diagnostics helper (see diagnostics-contract.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;
use AIOPageBuilder\Support\Logging\Error_Record;
use AIOPageBuilder\Support\Logging\Logger_Interface;
use AIOPageBuilder\Support\Logging\Null_Logger;

/**
 * Registers bootstrap logger (Null_Logger) and diagnostics object with message helper.
 */
final class Diagnostics_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'logger', function (): Logger_Interface {
			return new Null_Logger();
		} );

		$container->register( 'diagnostics', function () use ( $container ): object {
			$logger = $container->get( 'logger' );
			return new class( $logger ) {
				/** @var Logger_Interface */
				private $logger;

				public function __construct( Logger_Interface $logger ) {
					$this->logger = $logger;
				}

				public function get_logger(): Logger_Interface {
					return $this->logger;
				}

				/** Returns admin-safe user message from a record (spec §45.3). */
				public function format_user_message( Error_Record $record ): string {
					return $record->get_user_facing_message();
				}
			};
		} );
	}
}
