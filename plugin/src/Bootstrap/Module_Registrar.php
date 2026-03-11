<?php
/**
 * Registers bootstrap-level service providers in a stable order.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Blueprints_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Admin_Router_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Capability_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Config_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Diagnostics_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Object_Registration_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Registries_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Repositories_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Storage_Services_Provider;

/**
 * Loads and runs only bootstrap-level providers. Domain providers are registered in later prompts.
 * Registration order is explicit and stable.
 */
final class Module_Registrar {

	/** @var Service_Container */
	private Service_Container $container;

	public function __construct( Service_Container $container ) {
		$this->container = $container;
	}

	/**
	 * Registers all bootstrap providers in order. Call once from Plugin::run().
	 * Config_Provider registers config and settings (see global-options-schema.md).
	 * Diagnostics_Provider registers logger and diagnostics helper (see diagnostics-contract.md).
	 * Admin menu and screen routing are registered separately in Plugin::register_admin_menu().
	 *
	 * @return void
	 */
	public function register_bootstrap(): void {
		$providers = array(
			new Config_Provider(),
			new Diagnostics_Provider(),
			new Admin_Router_Provider(),
			new Capability_Provider(),
			new Object_Registration_Provider(),
			new Repositories_Provider(),
			new ACF_Blueprints_Provider(),
			new Registries_Provider(),
			new Storage_Services_Provider(),
		);
		foreach ( $providers as $provider ) {
			$provider->register( $this->container );
		}
	}

	/** @return Service_Container */
	public function container(): Service_Container {
		return $this->container;
	}
}
