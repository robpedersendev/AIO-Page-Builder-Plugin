<?php
/**
 * Registers config and settings services (see global-options-schema.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Plugin_Config;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Registers config (Constants and Versions) and settings (global options) services.
 */
final class Config_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'config', function (): Plugin_Config {
			return new Plugin_Config();
		} );
		$container->register( 'settings', function (): Settings_Service {
			return new Settings_Service();
		} );
	}
}
