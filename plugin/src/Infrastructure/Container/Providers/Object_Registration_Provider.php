<?php
/**
 * Registers object CPTs on init (spec §9.1, §10). No admin screens or business logic.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Objects\Post_Type_Registrar;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers post_type_registrar service and runs CPT registration on init.
 */
final class Object_Registration_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'post_type_registrar',
			function (): Post_Type_Registrar {
				return new Post_Type_Registrar();
			}
		);

		\add_action(
			'init',
			function () use ( $container ): void {
				$container->get( 'post_type_registrar' )->register();
			},
			20
		);
	}
}
