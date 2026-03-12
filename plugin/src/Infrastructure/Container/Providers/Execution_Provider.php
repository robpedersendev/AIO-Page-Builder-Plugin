<?php
/**
 * Registers execution services: dispatcher and single-action executor (spec §40.2, §59.10).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Executor\Execution_Dispatcher;
use AIOPageBuilder\Domain\Execution\Executor\Single_Action_Executor;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers execution_dispatcher and single_action_executor. Depends on build_plan_repository.
 */
final class Execution_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'execution_dispatcher', function (): Execution_Dispatcher {
			return new Execution_Dispatcher();
		} );
		$container->register( 'single_action_executor', function () use ( $container ): Single_Action_Executor {
			return new Single_Action_Executor(
				$container->get( 'execution_dispatcher' ),
				$container->get( 'build_plan_repository' )
			);
		} );
	}
}
