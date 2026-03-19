<?php
/**
 * Registers capability config and runtime inspector for diagnostics.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Bootstrap\Capability_Registrar;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers the canonical capability source of truth and runtime inspector (see capability-matrix.md).
 */
final class Capability_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'capabilities',
			function (): object {
				return new class() {
					/** @return array<int, string> */
					public function get_all(): array {
						return Capabilities::get_all();
					}

					public function role_has_cap( string $role_key, string $cap ): bool {
						return Capability_Registrar::role_has_cap( $role_key, $cap );
					}
				};
			}
		);
	}
}
