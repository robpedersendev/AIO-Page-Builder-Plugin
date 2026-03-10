<?php
/**
 * Service provider interface. Implementations register services with the container.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container;

defined( 'ABSPATH' ) || exit;

/**
 * A provider registers one or more services with the container.
 * Used only during bootstrap wiring; no request-driven registration.
 */
interface Service_Provider_Interface {

	/**
	 * Registers services with the container.
	 *
	 * @param Service_Container $container Container to register services with.
	 * @return void
	 */
	public function register( Service_Container $container ): void;
}
