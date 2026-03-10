<?php
/**
 * Registers config and settings services (see global-options-schema.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Normalizer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Helper;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Infrastructure\Config\Plugin_Config;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;
use AIOPageBuilder\Infrastructure\Settings\Option_Store;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Registers config (Constants and Versions), settings (global options), option store, and profile store services.
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
		$container->register( 'option_store', function () use ( $container ): Option_Store {
			return new Option_Store( $container->get( 'settings' ) );
		} );
		$container->register( 'profile_normalizer', function (): Profile_Normalizer {
			return new Profile_Normalizer();
		} );
		$container->register( 'profile_store', function () use ( $container ): Profile_Store {
			return new Profile_Store( $container->get( 'settings' ), $container->get( 'profile_normalizer' ) );
		} );
		$container->register( 'profile_snapshot_helper', function (): Profile_Snapshot_Helper {
			return new Profile_Snapshot_Helper();
		} );
	}
}
