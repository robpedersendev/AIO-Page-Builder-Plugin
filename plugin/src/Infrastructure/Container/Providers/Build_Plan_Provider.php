<?php
/**
 * Registers Build Plan generation and UI state services (spec §30.3, §31).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_Analytics_Service;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Generator;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Item_Generator;
use AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates\Existing_Page_Update_Bulk_Action_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates\Existing_Page_Update_Detail_Builder;
use AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates\Existing_Page_Updates_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\Finalization\Finalization_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\History\History_Rollback_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\Navigation\Navigation_Bulk_Action_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\Navigation\Navigation_Detail_Builder;
use AIOPageBuilder\Domain\BuildPlan\Steps\Navigation\Navigation_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Recommendations\Build_Plan_Template_Explanation_Builder;
use AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation\New_Page_Creation_Bulk_Action_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation\New_Page_Creation_Detail_Builder;
use AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation\New_Page_Creation_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\SEO\SEO_Media_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\Tokens\Tokens_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Stepper_Builder;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_UI_State_Builder;
use AIOPageBuilder\Domain\BuildPlan\UI\New_Page_Template_Recommendation_Builder;
use AIOPageBuilder\Domain\BuildPlan\UI\Step_Workspace_Payload_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers build_plan_item_generator, build_plan_generator, build_plan_stepper_builder, build_plan_ui_state_builder.
 * Depends on Repositories_Provider (build_plan_repository).
 */
final class Build_Plan_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'build_plan_item_generator', function (): Build_Plan_Item_Generator {
			return new Build_Plan_Item_Generator();
		} );
		$container->register( 'build_plan_generator', function () use ( $container ): Build_Plan_Generator {
			return new Build_Plan_Generator(
				$container->get( 'build_plan_repository' ),
				$container->get( 'build_plan_item_generator' )
			);
		} );
		$container->register( 'build_plan_stepper_builder', function (): Build_Plan_Stepper_Builder {
			return new Build_Plan_Stepper_Builder();
		} );
		$container->register( 'build_plan_row_action_resolver', function (): Build_Plan_Row_Action_Resolver {
			return new Build_Plan_Row_Action_Resolver();
		} );
		$container->register( 'build_plan_step_workspace_payload_builder', function () use ( $container ): Step_Workspace_Payload_Builder {
			return new Step_Workspace_Payload_Builder(
				$container->get( 'build_plan_row_action_resolver' )
			);
		} );
		$container->register( 'existing_page_update_detail_builder', function (): Existing_Page_Update_Detail_Builder {
			return new Existing_Page_Update_Detail_Builder();
		} );
		$container->register( 'existing_page_update_bulk_action_service', function () use ( $container ): Existing_Page_Update_Bulk_Action_Service {
			return new Existing_Page_Update_Bulk_Action_Service( $container->get( 'build_plan_repository' ) );
		} );
		$container->register( 'existing_page_updates_ui_service', function () use ( $container ): Existing_Page_Updates_UI_Service {
			return new Existing_Page_Updates_UI_Service(
				$container->get( 'build_plan_row_action_resolver' ),
				$container->get( 'existing_page_update_detail_builder' ),
				$container->get( 'existing_page_update_bulk_action_service' )
			);
		} );
		$container->register( 'build_plan_template_explanation_builder', function () use ( $container ): Build_Plan_Template_Explanation_Builder {
			$ctx_builder = $container->has( 'template_recommendation_context_builder' )
				? $container->get( 'template_recommendation_context_builder' )
				: null;
			return new Build_Plan_Template_Explanation_Builder(
				$container->get( 'page_template_repository' ),
				$ctx_builder
			);
		} );
		$container->register( 'new_page_creation_detail_builder', function () use ( $container ): New_Page_Creation_Detail_Builder {
			$explanation_builder = $container->has( 'build_plan_template_explanation_builder' )
				? $container->get( 'build_plan_template_explanation_builder' )
				: null;
			return new New_Page_Creation_Detail_Builder( $explanation_builder );
		} );
		$container->register( 'new_page_creation_bulk_action_service', function () use ( $container ): New_Page_Creation_Bulk_Action_Service {
			return new New_Page_Creation_Bulk_Action_Service( $container->get( 'build_plan_repository' ) );
		} );
		$container->register( 'new_page_template_recommendation_builder', function () use ( $container ): New_Page_Template_Recommendation_Builder {
			return new New_Page_Template_Recommendation_Builder( $container->get( 'build_plan_template_explanation_builder' ) );
		} );
		$container->register( 'new_page_creation_ui_service', function () use ( $container ): New_Page_Creation_UI_Service {
			return new New_Page_Creation_UI_Service(
				$container->get( 'build_plan_row_action_resolver' ),
				$container->get( 'new_page_creation_detail_builder' ),
				$container->get( 'new_page_creation_bulk_action_service' ),
				$container->get( 'new_page_template_recommendation_builder' )
			);
		} );
		$container->register( 'navigation_detail_builder', function (): Navigation_Detail_Builder {
			return new Navigation_Detail_Builder();
		} );
		$container->register( 'navigation_bulk_action_service', function () use ( $container ): Navigation_Bulk_Action_Service {
			return new Navigation_Bulk_Action_Service( $container->get( 'build_plan_repository' ) );
		} );
		$container->register( 'navigation_step_ui_service', function () use ( $container ): Navigation_Step_UI_Service {
			return new Navigation_Step_UI_Service(
				$container->get( 'build_plan_row_action_resolver' ),
				$container->get( 'navigation_detail_builder' ),
				$container->get( 'navigation_bulk_action_service' )
			);
		} );
		$container->register( 'tokens_step_ui_service', function () use ( $container ): Tokens_Step_UI_Service {
			return new Tokens_Step_UI_Service( $container->get( 'build_plan_row_action_resolver' ) );
		} );
		$container->register( 'seo_media_step_ui_service', function () use ( $container ): SEO_Media_Step_UI_Service {
			return new SEO_Media_Step_UI_Service( $container->get( 'build_plan_row_action_resolver' ) );
		} );
		$container->register( 'finalization_step_ui_service', function (): Finalization_Step_UI_Service {
			return new Finalization_Step_UI_Service();
		} );
		$container->register( 'history_rollback_step_ui_service', function (): History_Rollback_Step_UI_Service {
			return new History_Rollback_Step_UI_Service();
		} );
		$container->register( 'build_plan_ui_state_builder', function () use ( $container ): Build_Plan_UI_State_Builder {
			return new Build_Plan_UI_State_Builder(
				$container->get( 'build_plan_repository' ),
				$container->get( 'build_plan_stepper_builder' ),
				$container->get( 'build_plan_step_workspace_payload_builder' ),
				$container->get( 'existing_page_updates_ui_service' ),
				$container->get( 'new_page_creation_ui_service' ),
				$container->get( 'navigation_step_ui_service' ),
				$container->get( 'tokens_step_ui_service' ),
				$container->get( 'seo_media_step_ui_service' ),
				$container->get( 'finalization_step_ui_service' ),
				$container->get( 'history_rollback_step_ui_service' )
			);
		} );
		$container->register( 'build_plan_analytics_service', function () use ( $container ): Build_Plan_Analytics_Service {
			return new Build_Plan_Analytics_Service( $container->get( 'build_plan_repository' ) );
		} );
	}
}
