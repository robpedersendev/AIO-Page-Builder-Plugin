<?php
/**
 * Registers execution services: dispatcher, single-action executor, bulk executor, queue, create-page job (spec §40.2, §40.3, §42, §59.10, §33.5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Dispatcher;
use AIOPageBuilder\Domain\Execution\Executor\Single_Action_Executor;
use AIOPageBuilder\Domain\Execution\Handlers\Create_Page_Handler;
use AIOPageBuilder\Domain\Execution\Jobs\Create_Page_Job_Service;
use AIOPageBuilder\Domain\Execution\Queue\Bulk_Executor;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Job_Dispatcher;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Queue_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers execution_dispatcher (with create_page handler), single_action_executor, bulk_executor, execution_job_dispatcher, execution_queue_service, create_page_job_service.
 * Depends on build_plan_repository, job_queue_repository, rendering and ACF assignment services.
 */
final class Execution_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'create_page_job_service', function () use ( $container ): Create_Page_Job_Service {
			return new Create_Page_Job_Service(
				$container->get( 'page_template_repository' ),
				$container->get( 'section_template_repository' ),
				$container->get( 'section_render_context_builder' ),
				$container->get( 'section_renderer_base' ),
				$container->get( 'native_block_assembly_pipeline' ),
				$container->get( 'page_instantiation_payload_builder' ),
				$container->get( 'page_instantiator' ),
				$container->get( 'page_field_group_assignment_service' )
			);
		} );
		$container->register( 'execution_dispatcher', function () use ( $container ): Execution_Dispatcher {
			$dispatcher = new Execution_Dispatcher();
			$dispatcher->register_handler(
				Execution_Action_Types::CREATE_PAGE,
				new Create_Page_Handler( $container->get( 'create_page_job_service' ) )
			);
			return $dispatcher;
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
