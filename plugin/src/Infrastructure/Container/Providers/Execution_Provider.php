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
use AIOPageBuilder\Domain\Execution\Handlers\Apply_Menu_Change_Handler;
use AIOPageBuilder\Domain\Execution\Handlers\Apply_Token_Set_Handler;
use AIOPageBuilder\Domain\Execution\Handlers\Assign_Page_Hierarchy_Handler;
use AIOPageBuilder\Domain\Execution\Handlers\Create_Page_Handler;
use AIOPageBuilder\Domain\Execution\Handlers\Finalize_Plan_Handler;
use AIOPageBuilder\Domain\Execution\Handlers\Replace_Page_Handler;
use AIOPageBuilder\Domain\Execution\Jobs\Create_Page_Job_Service;
use AIOPageBuilder\Domain\Execution\Pages\Form_Provider_Dependency_Validator;
use AIOPageBuilder\Domain\Execution\Pages\Bulk_Template_Page_Build_Service;
use AIOPageBuilder\Domain\Execution\Pages\Template_Page_Build_Service;
use AIOPageBuilder\Domain\Execution\Pages\Template_Page_Replacement_Service;
use AIOPageBuilder\Domain\Execution\Finalize\Template_Finalization_Service;
use AIOPageBuilder\Domain\Execution\Jobs\Finalization_Job_Service;
use AIOPageBuilder\Domain\Execution\Jobs\Menu_Change_Job_Service;
use AIOPageBuilder\Domain\Execution\Jobs\Replace_Page_Job_Service;
use AIOPageBuilder\Domain\Execution\Menus\Template_Menu_Apply_Service;
use AIOPageBuilder\Domain\Execution\Jobs\Token_Set_Job_Service;
use AIOPageBuilder\Domain\Execution\Queue\Bulk_Executor;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Job_Dispatcher;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Queue_Service;
use AIOPageBuilder\Domain\Execution\Queue\Queue_Health_Summary_Builder;
use AIOPageBuilder\Domain\Execution\Queue\Queue_Recovery_Service;
use AIOPageBuilder\Domain\Rollback\Diff\Template_Diff_Summary_Builder;
use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Summarizer_Service;
use AIOPageBuilder\Domain\Rollback\Diffs\Navigation_Diff_Summarizer;
use AIOPageBuilder\Domain\Rollback\Diffs\Page_Diff_Summarizer;
use AIOPageBuilder\Domain\Rollback\Diffs\Token_Diff_Summarizer;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository_Interface;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Service;
use AIOPageBuilder\Domain\Rollback\Snapshots\Post_Change_Result_Builder;
use AIOPageBuilder\Domain\Rollback\Snapshots\Pre_Change_Snapshot_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers execution_dispatcher (with create_page handler), single_action_executor, bulk_executor, execution_job_dispatcher, execution_queue_service, create_page_job_service.
 * Depends on build_plan_repository, job_queue_repository, rendering and ACF assignment services.
 */
final class Execution_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'create_page_job_service',
			function () use ( $container ): Create_Page_Job_Service {
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
			}
		);
		$container->register(
			'form_provider_dependency_validator',
			function () use ( $container ): Form_Provider_Dependency_Validator {
				return new Form_Provider_Dependency_Validator(
					$container->get( 'form_provider_registry' ),
					$container->get( 'page_template_repository' ),
					$container->get( 'section_template_repository' )
				);
			}
		);
		$container->register(
			'template_page_build_service',
			function () use ( $container ): Template_Page_Build_Service {
				return new Template_Page_Build_Service(
					$container->get( 'create_page_job_service' ),
					$container->get( 'page_template_repository' ),
					$container->get( 'form_provider_dependency_validator' )
				);
			}
		);
		$container->register(
			'bulk_template_page_build_service',
			function () use ( $container ): Bulk_Template_Page_Build_Service {
				return new Bulk_Template_Page_Build_Service(
					$container->get( 'build_plan_repository' ),
					$container->get( 'bulk_executor' ),
					$container->get( 'execution_job_dispatcher' )
				);
			}
		);
		$container->register(
			'replace_page_job_service',
			function () use ( $container ): Replace_Page_Job_Service {
				return new Replace_Page_Job_Service(
					$container->get( 'page_template_repository' ),
					$container->get( 'section_template_repository' ),
					$container->get( 'section_render_context_builder' ),
					$container->get( 'section_renderer_base' ),
					$container->get( 'native_block_assembly_pipeline' ),
					$container->get( 'page_instantiation_payload_builder' ),
					$container->get( 'page_instantiator' ),
					$container->get( 'page_field_group_assignment_service' )
				);
			}
		);
		$container->register(
			'template_page_replacement_service',
			function () use ( $container ): Template_Page_Replacement_Service {
				return new Template_Page_Replacement_Service(
					$container->get( 'replace_page_job_service' ),
					$container->get( 'page_template_repository' ),
					$container->get( 'form_provider_dependency_validator' )
				);
			}
		);
		$container->register(
			'menu_change_job_service',
			function (): Menu_Change_Job_Service {
				return new Menu_Change_Job_Service();
			}
		);
		$container->register(
			'template_menu_apply_service',
			function () use ( $container ): Template_Menu_Apply_Service {
				return new Template_Menu_Apply_Service( $container->get( 'menu_change_job_service' ) );
			}
		);
		$container->register(
			'token_set_job_service',
			function (): Token_Set_Job_Service {
				return new Token_Set_Job_Service();
			}
		);
		$container->register(
			'template_finalization_service',
			function () use ( $container ): Template_Finalization_Service {
				$validator = $container->has( 'form_provider_dependency_validator' ) ? $container->get( 'form_provider_dependency_validator' ) : null;
				return new Template_Finalization_Service( $validator );
			}
		);
		$container->register(
			'finalization_job_service',
			function () use ( $container ): Finalization_Job_Service {
				return new Finalization_Job_Service(
					$container->get( 'build_plan_repository' ),
					$container->get( 'template_finalization_service' )
				);
			}
		);
		$container->register(
			'operational_snapshot_repository',
			function (): Operational_Snapshot_Repository {
				return new Operational_Snapshot_Repository();
			}
		);
		$container->register(
			'pre_change_snapshot_builder',
			function (): Pre_Change_Snapshot_Builder {
				return new Pre_Change_Snapshot_Builder();
			}
		);
		$container->register(
			'post_change_result_builder',
			function (): Post_Change_Result_Builder {
				return new Post_Change_Result_Builder();
			}
		);
		$container->register(
			'operational_snapshot_service',
			function () use ( $container ): Operational_Snapshot_Service {
				return new Operational_Snapshot_Service(
					$container->get( 'operational_snapshot_repository' ),
					$container->get( 'pre_change_snapshot_builder' ),
					$container->get( 'post_change_result_builder' )
				);
			}
		);
		$container->register(
			'page_diff_summarizer',
			function (): Page_Diff_Summarizer {
				return new Page_Diff_Summarizer();
			}
		);
		$container->register(
			'navigation_diff_summarizer',
			function (): Navigation_Diff_Summarizer {
				return new Navigation_Diff_Summarizer();
			}
		);
		$container->register(
			'token_diff_summarizer',
			function (): Token_Diff_Summarizer {
				return new Token_Diff_Summarizer();
			}
		);
		$container->register(
			'template_diff_summary_builder',
			function (): Template_Diff_Summary_Builder {
				return new Template_Diff_Summary_Builder();
			}
		);
		$container->register(
			'diff_summarizer_service',
			function () use ( $container ): Diff_Summarizer_Service {
				return new Diff_Summarizer_Service(
					$container->get( 'operational_snapshot_repository' ),
					$container->get( 'page_diff_summarizer' ),
					$container->get( 'navigation_diff_summarizer' ),
					$container->get( 'token_diff_summarizer' ),
					$container->get( 'template_diff_summary_builder' )
				);
			}
		);
		$container->register(
			'execution_dispatcher',
			function () use ( $container ): Execution_Dispatcher {
				$dispatcher = new Execution_Dispatcher();
				$dispatcher->register_handler(
					Execution_Action_Types::CREATE_PAGE,
					new Create_Page_Handler( $container->get( 'template_page_build_service' ) )
				);
				$dispatcher->register_handler(
					Execution_Action_Types::REPLACE_PAGE,
					new Replace_Page_Handler( $container->get( 'template_page_replacement_service' ) )
				);
				$dispatcher->register_handler(
					Execution_Action_Types::UPDATE_MENU,
					new Apply_Menu_Change_Handler( $container->get( 'menu_change_job_service' ), $container->get( 'template_menu_apply_service' ) )
				);
				$dispatcher->register_handler(
					Execution_Action_Types::APPLY_TOKEN_SET,
					new Apply_Token_Set_Handler( $container->get( 'token_set_job_service' ) )
				);
				$dispatcher->register_handler(
					Execution_Action_Types::ASSIGN_PAGE_HIERARCHY,
					new Assign_Page_Hierarchy_Handler()
				);
				$dispatcher->register_handler(
					Execution_Action_Types::FINALIZE_PLAN,
					new Finalize_Plan_Handler( $container->get( 'finalization_job_service' ) )
				);
				return $dispatcher;
			}
		);
		$container->register(
			'single_action_executor',
			function () use ( $container ): Single_Action_Executor {
				$snapshot_service      = $container->get( 'operational_snapshot_service' );
				$snapshot_preflight    = function ( array $envelope ) use ( $snapshot_service ): ?string {
					$result = $snapshot_service->capture_pre_change( $envelope );
					return $result->is_success() ? $result->get_snapshot_id() : null;
				};
				$post_capture_snapshot = function ( array $envelope, array $handler_result ) use ( $snapshot_service ): void {
					$pre_id = isset( $envelope['operational_pre_snapshot_id'] ) && is_string( $envelope['operational_pre_snapshot_id'] ) ? $envelope['operational_pre_snapshot_id'] : ( isset( $envelope['snapshot_ref'] ) && is_string( $envelope['snapshot_ref'] ) ? $envelope['snapshot_ref'] : '' );
					$snapshot_service->capture_post_change( $envelope, $handler_result, $pre_id );
				};
				return new Single_Action_Executor(
					$container->get( 'execution_dispatcher' ),
					$container->get( 'build_plan_repository' ),
					null,
					$snapshot_preflight,
					null,
					null,
					$post_capture_snapshot
				);
			}
		);
		$container->register(
			'bulk_executor',
			function (): Bulk_Executor {
				return new Bulk_Executor();
			}
		);
		$container->register(
			'execution_job_dispatcher',
			function () use ( $container ): Execution_Job_Dispatcher {
				$rollback_executor = $container->has( 'rollback_executor' ) ? $container->get( 'rollback_executor' ) : null;
				return new Execution_Job_Dispatcher(
					$container->get( 'job_queue_repository' ),
					$container->get( 'single_action_executor' ),
					$rollback_executor
				);
			}
		);
		$container->register(
			'execution_queue_service',
			function () use ( $container ): Execution_Queue_Service {
				$snapshot_builder = $container->has( 'industry_approval_snapshot_builder' ) ? $container->get( 'industry_approval_snapshot_builder' ) : null;
				return new Execution_Queue_Service(
					$container->get( 'build_plan_repository' ),
					$container->get( 'bulk_executor' ),
					$container->get( 'execution_job_dispatcher' ),
					$snapshot_builder
				);
			}
		);
		$container->register(
			'queue_health_summary_builder',
			function () use ( $container ): Queue_Health_Summary_Builder {
				$repo = $container->has( 'job_queue_repository' ) ? $container->get( 'job_queue_repository' ) : null;
				return new Queue_Health_Summary_Builder( $repo );
			}
		);
		$container->register(
			'queue_recovery_service',
			function () use ( $container ): Queue_Recovery_Service {
				$logger = $container->has( 'logger' ) ? $container->get( 'logger' ) : null;
				return new Queue_Recovery_Service(
					$container->get( 'job_queue_repository' ),
					$logger
				);
			}
		);
	}
}
