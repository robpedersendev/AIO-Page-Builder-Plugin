<?php
/**
 * Registers admin router placeholder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;
use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Router;

/**
 * Registers admin router for named routes and URL generation.
 */
final class Admin_Router_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'admin_router',
			function (): Admin_Router {
				return new Admin_Router();
			}
		);
	}
}
