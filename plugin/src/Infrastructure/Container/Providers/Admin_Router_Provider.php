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

/**
 * Placeholder for admin menu/screen routing. Later prompts will replace with real implementation.
 */
final class Admin_Router_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'admin_router', function (): object {
			return new \stdClass();
		} );
	}
}
