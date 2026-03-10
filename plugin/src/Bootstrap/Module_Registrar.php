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
use AIOPageBuilder\Infrastructure\Container\Providers\Admin_Router_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Capability_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Config_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Diagnostics_Provider;

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
	 *
	 * @return void
	 */
	public function register_bootstrap(): void {
		$providers = array(
			new Config_Provider(),
			new Diagnostics_Provider(),
			new Admin_Router_Provider(),
			new Capability_Provider(),
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
