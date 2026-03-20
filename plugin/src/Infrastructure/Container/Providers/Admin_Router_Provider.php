<?php
/**
 * Registers the admin router service: named admin-page routes and URL generation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Services\Helper_Doc_Url_Resolver;
use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry;
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

		$container->register(
			'helper_doc_url_resolver',
			function () use ( $container ): Helper_Doc_Url_Resolver {
				$router = $container->has( 'admin_router' ) ? $container->get( 'admin_router' ) : new Admin_Router();
				return new Helper_Doc_Url_Resolver( new Documentation_Registry(), $router );
			}
		);
	}
}
