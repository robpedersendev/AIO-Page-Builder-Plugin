<?php
/**
 * Registers diagnostics bootstrap placeholder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Placeholder for diagnostics/logging bootstrap. Later prompts will replace with real implementation.
 */
final class Diagnostics_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'diagnostics', function (): object {
			return new \stdClass();
		} );
	}
}
