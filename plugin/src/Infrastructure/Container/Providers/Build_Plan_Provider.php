<?php
/**
 * Registers Build Plan generation and UI state services (spec §30.3, §31).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_Analytics_Service;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Generator;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Item_Generator;
use AIOPageBuilder\Domain\Industry\AI\Industry_Approval_Snapshot_Builder;
use AIOPageBuilder\Domain\Industry\AI\Build_Plan_Scoring_Interface;
use AIOPageBuilder\Domain\Industry\AI\Industry_Build_Plan_Scoring_Service;
use AIOPageBuilder\Domain\Industry\AI\Industry_Subtype_Build_Plan_Scoring_Service;
use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Planning_Advisor;
use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Compliance_Warning_Resolver;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Weighted_Recommendation_Engine;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Application_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Page_Template_Recommendation_Extender;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Subtype_Resolver;
use AIOPageBuilder\Domain\Registries\Analytics\Template_Analytics_Service;
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
use AIOPageBuilder\Domain\BuildPlan\UI\Existing_Page_Template_Change_Builder;
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
		$container->register(
			'build_plan_item_generator',
			function (): Build_Plan_Item_Generator {
				return new Build_Plan_Item_Generator();
			}
		);
		$container->register(
			'industry_build_plan_scoring_service',
			function () use ( $container ): ?Industry_Build_Plan_Scoring_Service {
				if ( ! $container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) || ! $container->has( 'page_template_repository' ) ) {
					return null;
				}
				$profile_store = $container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
				if ( ! $profile_store instanceof Industry_Profile_Repository ) {
					return null;
				}
				$pack_registry  = $container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY )
				? $container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY )
				: null;
				$is_pack_active = null;
				if ( $container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) ) {
					$toggle         = $container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER );
					$is_pack_active = $toggle !== null && method_exists( $toggle, 'is_pack_active' )
					? static function ( string $key ) use ( $toggle ): bool {
						return $toggle->is_pack_active( $key );
					}
					: null;
				}
				return new Industry_Build_Plan_Scoring_Service(
					new Industry_Page_Template_Recommendation_Resolver(),
					$container->get( 'page_template_repository' ),
					$profile_store,
					$pack_registry instanceof Industry_Pack_Registry ? $pack_registry : null,
					new Industry_Weighted_Recommendation_Engine(),
					$is_pack_active
				);
			}
		);
		$container->register(
			'industry_approval_snapshot_builder',
			function () use ( $container ): ?Industry_Approval_Snapshot_Builder {
				if ( ! $container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
					return null;
				}
				$profile_store = $container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
				if ( ! $profile_store instanceof Industry_Profile_Repository ) {
					return null;
				}
				$pack_registry    = $container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY )
				? $container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY )
				: null;
				$preset_service   = $container->has( 'industry_style_preset_application_service' ) ? $container->get( 'industry_style_preset_application_service' ) : null;
				$lpagery_registry = $container->has( Industry_Packs_Module::CONTAINER_KEY_LPAGERY_RULE_REGISTRY )
				? $container->get( Industry_Packs_Module::CONTAINER_KEY_LPAGERY_RULE_REGISTRY )
				: null;
				$lpagery_advisor  = $lpagery_registry instanceof Industry_LPagery_Rule_Registry
				? new Industry_LPagery_Planning_Advisor( $lpagery_registry )
				: null;
				return new Industry_Approval_Snapshot_Builder(
					$profile_store,
					$pack_registry instanceof Industry_Pack_Registry ? $pack_registry : null,
					$preset_service instanceof Industry_Style_Preset_Application_Service ? $preset_service : null,
					$lpagery_advisor
				);
			}
		);
		$container->register(
			'industry_subtype_build_plan_scoring_service',
			function () use ( $container ): ?Industry_Subtype_Build_Plan_Scoring_Service {
				if ( ! $container->has( 'industry_build_plan_scoring_service' ) || ! $container->has( Industry_Packs_Module::CONTAINER_KEY_SUBTYPE_RESOLVER ) || ! $container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
					return null;
				}
				$inner            = $container->get( 'industry_build_plan_scoring_service' );
				$profile_store    = $container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
				$subtype_resolver = $container->get( Industry_Packs_Module::CONTAINER_KEY_SUBTYPE_RESOLVER );
				if ( ! $inner instanceof Industry_Build_Plan_Scoring_Service || ! $profile_store instanceof Industry_Profile_Repository || ! $subtype_resolver instanceof Industry_Subtype_Resolver ) {
					return null;
				}
				return new Industry_Subtype_Build_Plan_Scoring_Service(
					$inner,
					$subtype_resolver,
					$profile_store,
					new Industry_Subtype_Page_Template_Recommendation_Extender()
				);
			}
		);
		$container->register(
			'build_plan_generator',
			function () use ( $container ): Build_Plan_Generator {
				$scoring = $container->has( 'industry_subtype_build_plan_scoring_service' ) ? $container->get( 'industry_subtype_build_plan_scoring_service' ) : null;
				if ( $scoring === null && $container->has( 'industry_build_plan_scoring_service' ) ) {
					$scoring = $container->get( 'industry_build_plan_scoring_service' );
				}
				return new Build_Plan_Generator(
					$container->get( 'build_plan_repository' ),
					$container->get( 'build_plan_item_generator' ),
					$scoring instanceof Build_Plan_Scoring_Interface ? $scoring : null
				);
			}
		);
		$container->register(
			'build_plan_stepper_builder',
			function (): Build_Plan_Stepper_Builder {
				return new Build_Plan_Stepper_Builder();
			}
		);
		$container->register(
			'build_plan_row_action_resolver',
			function (): Build_Plan_Row_Action_Resolver {
				return new Build_Plan_Row_Action_Resolver();
			}
		);
		$container->register(
			'build_plan_step_workspace_payload_builder',
			function () use ( $container ): Step_Workspace_Payload_Builder {
				return new Step_Workspace_Payload_Builder(
					$container->get( 'build_plan_row_action_resolver' )
				);
			}
		);
		$container->register(
			'existing_page_template_change_builder',
			function () use ( $container ): Existing_Page_Template_Change_Builder {
				return new Existing_Page_Template_Change_Builder( $container->get( 'build_plan_template_explanation_builder' ) );
			}
		);
		$container->register(
			'existing_page_update_detail_builder',
			function () use ( $container ): Existing_Page_Update_Detail_Builder {
				return new Existing_Page_Update_Detail_Builder( $container->get( 'existing_page_template_change_builder' ) );
			}
		);
		$container->register(
			'existing_page_update_bulk_action_service',
			function () use ( $container ): Existing_Page_Update_Bulk_Action_Service {
				return new Existing_Page_Update_Bulk_Action_Service( $container->get( 'build_plan_repository' ) );
			}
		);
		$container->register(
			'existing_page_updates_ui_service',
			function () use ( $container ): Existing_Page_Updates_UI_Service {
				return new Existing_Page_Updates_UI_Service(
					$container->get( 'build_plan_row_action_resolver' ),
					$container->get( 'existing_page_update_detail_builder' ),
					$container->get( 'existing_page_update_bulk_action_service' ),
					$container->get( 'existing_page_template_change_builder' )
				);
			}
		);
		$container->register(
			'build_plan_template_explanation_builder',
			function () use ( $container ): Build_Plan_Template_Explanation_Builder {
				$ctx_builder = $container->has( 'template_recommendation_context_builder' )
				? $container->get( 'template_recommendation_context_builder' )
				: null;
				return new Build_Plan_Template_Explanation_Builder(
					$container->get( 'page_template_repository' ),
					$ctx_builder
				);
			}
		);
		$container->register(
			'new_page_creation_detail_builder',
			function () use ( $container ): New_Page_Creation_Detail_Builder {
				$explanation_builder = $container->has( 'build_plan_template_explanation_builder' )
				? $container->get( 'build_plan_template_explanation_builder' )
				: null;
				return new New_Page_Creation_Detail_Builder( $explanation_builder );
			}
		);
		$container->register(
			'new_page_creation_bulk_action_service',
			function () use ( $container ): New_Page_Creation_Bulk_Action_Service {
				return new New_Page_Creation_Bulk_Action_Service( $container->get( 'build_plan_repository' ) );
			}
		);
		$container->register(
			'new_page_template_recommendation_builder',
			function () use ( $container ): New_Page_Template_Recommendation_Builder {
				$validator = $container->has( 'form_provider_dependency_validator' ) ? $container->get( 'form_provider_dependency_validator' ) : null;
				return new New_Page_Template_Recommendation_Builder( $container->get( 'build_plan_template_explanation_builder' ), $validator );
			}
		);
		$container->register(
			'new_page_creation_ui_service',
			function () use ( $container ): New_Page_Creation_UI_Service {
				$profile_repo     = $container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				$warning_resolver = $container->has( 'industry_compliance_warning_resolver' ) ? $container->get( 'industry_compliance_warning_resolver' ) : null;
				return new New_Page_Creation_UI_Service(
					$container->get( 'build_plan_row_action_resolver' ),
					$container->get( 'new_page_creation_detail_builder' ),
					$container->get( 'new_page_creation_bulk_action_service' ),
					$container->get( 'new_page_template_recommendation_builder' ),
					$profile_repo instanceof Industry_Profile_Repository ? $profile_repo : null,
					$warning_resolver instanceof Industry_Compliance_Warning_Resolver ? $warning_resolver : null
				);
			}
		);
		$container->register(
			'navigation_detail_builder',
			function (): Navigation_Detail_Builder {
				return new Navigation_Detail_Builder();
			}
		);
		$container->register(
			'navigation_bulk_action_service',
			function () use ( $container ): Navigation_Bulk_Action_Service {
				return new Navigation_Bulk_Action_Service( $container->get( 'build_plan_repository' ) );
			}
		);
		$container->register(
			'navigation_step_ui_service',
			function () use ( $container ): Navigation_Step_UI_Service {
				return new Navigation_Step_UI_Service(
					$container->get( 'build_plan_row_action_resolver' ),
					$container->get( 'navigation_detail_builder' ),
					$container->get( 'navigation_bulk_action_service' )
				);
			}
		);
		$container->register(
			'tokens_step_ui_service',
			function () use ( $container ): Tokens_Step_UI_Service {
				return new Tokens_Step_UI_Service( $container->get( 'build_plan_row_action_resolver' ) );
			}
		);
		$container->register(
			'seo_media_step_ui_service',
			function () use ( $container ): SEO_Media_Step_UI_Service {
				return new SEO_Media_Step_UI_Service( $container->get( 'build_plan_row_action_resolver' ) );
			}
		);
		$container->register(
			'finalization_step_ui_service',
			function (): Finalization_Step_UI_Service {
				return new Finalization_Step_UI_Service();
			}
		);
		$container->register(
			'history_rollback_step_ui_service',
			function (): History_Rollback_Step_UI_Service {
				return new History_Rollback_Step_UI_Service();
			}
		);
		$container->register(
			'build_plan_ui_state_builder',
			function () use ( $container ): Build_Plan_UI_State_Builder {
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
			}
		);
		$container->register(
			'build_plan_analytics_service',
			function () use ( $container ): Build_Plan_Analytics_Service {
				$snapshots = $container->has( 'operational_snapshot_repository' ) ? $container->get( 'operational_snapshot_repository' ) : null;
				return new Build_Plan_Analytics_Service(
					$container->get( 'build_plan_repository' ),
					$snapshots
				);
			}
		);
		$analytics_dir = __DIR__ . '/../../../Domain/Registries/Analytics';
		require_once $analytics_dir . '/Template_Analytics_Service.php';
		$container->register(
			'template_analytics_service',
			function () use ( $container ): Template_Analytics_Service {
				return new Template_Analytics_Service(
					$container->get( 'build_plan_repository' ),
					$container->has( 'job_queue_repository' ) ? $container->get( 'job_queue_repository' ) : null,
					$container->has( 'composition_repository' ) ? $container->get( 'composition_repository' ) : null
				);
			}
		);
	}
}
