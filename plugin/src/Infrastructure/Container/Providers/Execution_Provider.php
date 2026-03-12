<?php
/**
 * Registers execution services: dispatcher, single-action executor, bulk executor, queue (spec §40.2, §40.3, §42, §59.10).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Executor\Execution_Dispatcher;
use AIOPageBuilder\Domain\Execution\Executor\Single_Action_Executor;
use AIOPageBuilder\Domain\Execution\Queue\Bulk_Executor;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Job_Dispatcher;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Queue_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers execution_dispatcher, single_action_executor, bulk_executor, execution_job_dispatcher, execution_queue_service.
 * Depends on build_plan_repository and job_queue_repository.
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
		$container->register( 'bulk_executor', function (): Bulk_Executor {
			return new Bulk_Executor();
		} );
		$container->register( 'execution_job_dispatcher', function () use ( $container ): Execution_Job_Dispatcher {
			return new Execution_Job_Dispatcher(
				$container->get( 'job_queue_repository' ),
				$container->get( 'single_action_executor' )
			);
		} );
		$container->register( 'execution_queue_service', function () use ( $container ): Execution_Queue_Service {
			return new Execution_Queue_Service(
				$container->get( 'build_plan_repository' ),
				$container->get( 'bulk_executor' ),
				$container->get( 'execution_job_dispatcher' )
			);
		} );
	}
}
