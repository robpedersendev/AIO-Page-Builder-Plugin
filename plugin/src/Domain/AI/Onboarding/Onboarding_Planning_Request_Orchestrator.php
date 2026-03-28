<?php
/**
 * End-to-end orchestration: onboarding state → prompt pack + input artifact → provider → validation → persist run (spec §49.8, §59.8, §28.11–28.14).
 * Call sites must enforce aio_run_onboarding and aio_run_ai_plans; nonce verification at submission handler.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Budget\Provider_Monthly_Spend_Service;
use AIOPageBuilder\Domain\AI\Budget\Provider_Spend_Cap_Settings;
use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Builder;
use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Schema;
use AIOPageBuilder\Domain\AI\PromptPacks\Normalized_Prompt_Package_Builder;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Registry_Service;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Schema;
use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\Providers\AI_Structured_Response_Guard;
use AIOPageBuilder\Domain\AI\Providers\Failover\Failover_Result;
use AIOPageBuilder\Domain\AI\Routing\AI_Provider_Router_Interface;
use AIOPageBuilder\Domain\AI\Routing\AI_Routing_Task;
use AIOPageBuilder\Domain\AI\Providers\Failover\Provider_Failover_Service;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Request_Context_Builder;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\AI\Planning\Planning_Breadth_Constants;
use AIOPageBuilder\Domain\AI\Planning\Planning_Expand_Pass_Runner;
use AIOPageBuilder\Domain\AI\Planning\Planning_Per_Run_Budget_Estimator;
use AIOPageBuilder\Domain\AI\Planning\Planning_Structured_Output_Limits;
use AIOPageBuilder\Domain\AI\Planning\Planning_Thin_Output_Enrichment_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator;
use AIOPageBuilder\Domain\AI\Planning\Template_Recommendation_Context_Builder;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\AI\Validation\Validation_Report;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Validates onboarding readiness, builds prompt package and input artifact, invokes provider, validates output, persists run.
 * Does not create Build Plans or perform execution.
 */
final class Onboarding_Planning_Request_Orchestrator {

	private const RUN_STATUS_COMPLETED         = 'completed';
	private const RUN_STATUS_FAILED_VALIDATION = 'failed_validation';
	private const RUN_STATUS_FAILED            = 'failed';

	/** @var Onboarding_Draft_Service */
	private Onboarding_Draft_Service $draft_service;

	/** @var Onboarding_Prefill_Service */
	private Onboarding_Prefill_Service $prefill_service;

	/** @var Prompt_Pack_Registry_Service */
	private Prompt_Pack_Registry_Service $prompt_pack_registry;

	/** @var Input_Artifact_Builder */
	private Input_Artifact_Builder $input_artifact_builder;

	/** @var Normalized_Prompt_Package_Builder */
	private Normalized_Prompt_Package_Builder $prompt_package_builder;

	/** @var Provider_Request_Context_Builder */
	private Provider_Request_Context_Builder $request_context_builder;

	/** @var Provider_Capability_Resolver */
	private Provider_Capability_Resolver $capability_resolver;

	/** @var AI_Output_Validator */
	private AI_Output_Validator $validator;

	/** @var AI_Run_Service */
	private AI_Run_Service $run_service;

	/** @var Provider_Connection_Test_Service */
	private Provider_Connection_Test_Service $connection_test_service;

	/** @var Provider_Failover_Service */
	private Provider_Failover_Service $failover_service;

	/** @var Service_Container */
	private Service_Container $container;

	/** @var AI_Provider_Router_Interface */
	private AI_Provider_Router_Interface $provider_router;

	/** @var Planning_Thin_Output_Enrichment_Service|null */
	private ?Planning_Thin_Output_Enrichment_Service $thin_output_enrichment;

	/** @var Planning_Per_Run_Budget_Estimator|null */
	private ?Planning_Per_Run_Budget_Estimator $budget_estimator;

	/** @var Planning_Expand_Pass_Runner|null */
	private ?Planning_Expand_Pass_Runner $expand_runner;

	/**
	 * @param AI_Provider_Router_Interface $provider_router Task-scoped primary provider resolution (spec §25.1).
	 */
	public function __construct(
		Onboarding_Draft_Service $draft_service,
		Onboarding_Prefill_Service $prefill_service,
		Prompt_Pack_Registry_Service $prompt_pack_registry,
		Input_Artifact_Builder $input_artifact_builder,
		Normalized_Prompt_Package_Builder $prompt_package_builder,
		Provider_Request_Context_Builder $request_context_builder,
		Provider_Capability_Resolver $capability_resolver,
		AI_Output_Validator $validator,
		AI_Run_Service $run_service,
		Provider_Connection_Test_Service $connection_test_service,
		Provider_Failover_Service $failover_service,
		Service_Container $container,
		AI_Provider_Router_Interface $provider_router,
		?Planning_Thin_Output_Enrichment_Service $thin_output_enrichment = null,
		?Planning_Per_Run_Budget_Estimator $budget_estimator = null,
		?Planning_Expand_Pass_Runner $expand_runner = null
	) {
		$this->draft_service           = $draft_service;
		$this->prefill_service         = $prefill_service;
		$this->prompt_pack_registry    = $prompt_pack_registry;
		$this->input_artifact_builder  = $input_artifact_builder;
		$this->prompt_package_builder  = $prompt_package_builder;
		$this->request_context_builder = $request_context_builder;
		$this->capability_resolver     = $capability_resolver;
		$this->validator               = $validator;
		$this->run_service             = $run_service;
		$this->connection_test_service = $connection_test_service;
		$this->failover_service        = $failover_service;
		$this->container               = $container;
		$this->provider_router         = $provider_router;
		$this->thin_output_enrichment  = $thin_output_enrichment;
		$this->budget_estimator        = $budget_estimator;
		$this->expand_runner           = $expand_runner;
	}

	/**
	 * Submits a planning request from current onboarding state. Persists run and links to draft.
	 *
	 * @return Planning_Request_Result
	 */
	public function submit(): Planning_Request_Result {
		$draft        = $this->draft_service->get_draft();
		$current_step = $draft['current_step_key'] ?? Onboarding_Step_Keys::WELCOME;
		Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_SUBMIT_ENTER, 'step=' . $current_step );

		if ( $current_step !== Onboarding_Step_Keys::SUBMISSION ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_BLOCKED_NOT_SUBMISSION, 'step=' . $current_step );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_BLOCKED,
				'',
				0,
				__( 'You must be on the Submission step to request a plan.', 'aio-page-builder' ),
				null,
				null,
				'not_on_submission_step'
			);
		}

		if ( ! $this->prefill_service->is_provider_ready() ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_BLOCKED_PROVIDER_NOT_READY, '' );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_BLOCKED,
				'',
				0,
				__( 'Configure an AI provider before submitting.', 'aio-page-builder' ),
				null,
				null,
				'provider_not_ready'
			);
		}

		$prefill   = $this->prefill_service->get_prefill_data( $draft );
		$ctx_block = Onboarding_Planning_Context_Guard::get_blocking_message( $draft, $prefill );
		if ( $ctx_block !== null ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_BLOCKED_PLANNING_CONTEXT, '' );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_BLOCKED,
				'',
				0,
				$ctx_block,
				null,
				null,
				'planning_context_incomplete'
			);
		}
		$picked = $this->pick_configured_provider_id( $prefill );
		if ( $picked === null || $picked === '' ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_BLOCKED_NO_PROVIDER, '' );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_BLOCKED,
				'',
				0,
				__( 'No configured AI provider found.', 'aio-page-builder' ),
				null,
				null,
				'no_configured_provider'
			);
		}

		$route = $this->provider_router->resolve_route(
			AI_Routing_Task::ONBOARDING_PLANNING,
			array( 'preferred_provider_id' => $picked )
		);
		if ( ! $route->is_valid() ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_BLOCKED_NO_PROVIDER, 'reason=invalid_route' );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_BLOCKED,
				'',
				0,
				__( 'AI provider routing is misconfigured.', 'aio-page-builder' ),
				null,
				null,
				'provider_route_invalid'
			);
		}
		$provider_id = $route->get_primary_provider_id();

		// * Spend cap preflight: block run if cap exceeded and override is not enabled.
		$spend_blocked = $this->check_spend_cap_preflight( $provider_id );
		if ( $spend_blocked !== null ) {
			return $spend_blocked;
		}

		$driver = $this->get_driver_for_provider( $provider_id );
		if ( $driver === null ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_BLOCKED_DRIVER_UNAVAILABLE, 'provider_id=' . $provider_id );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_PROVIDER_FAILED,
				'',
				0,
				__( 'The selected AI provider is not available.', 'aio-page-builder' ),
				null,
				array(
					'category'      => 'unsupported_feature',
					'user_message'  => 'Provider not available.',
					'internal_code' => 'unsupported_feature',
					'provider_raw'  => null,
					'retry_posture' => 'no_retry',
				),
				null
			);
		}

		$schema_ref = Build_Plan_Draft_Schema::SCHEMA_REF;
		$pack       = $this->prompt_pack_registry->select_for_planning( $schema_ref, $provider_id );
		if ( $pack === null ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_BLOCKED_NO_PROMPT_PACK, 'schema=' . $schema_ref . ' provider=' . $provider_id );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_BLOCKED,
				'',
				0,
				__( 'No active planning prompt pack found for this provider.', 'aio-page-builder' ),
				null,
				null,
				'no_prompt_pack'
			);
		}

		$prompt_pack_ref = array(
			Input_Artifact_Schema::PROMPT_PACK_REF_INTERNAL_KEY => (string) ( $pack[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' ),
			Input_Artifact_Schema::PROMPT_PACK_REF_VERSION => (string) ( $pack[ Prompt_Pack_Schema::ROOT_VERSION ] ?? '' ),
		);
		Named_Debug_Log::event(
			Named_Debug_Log_Event::ORCHESTRATOR_PROMPT_PACK_SELECTED,
			'internal_key=' . $prompt_pack_ref[ Input_Artifact_Schema::PROMPT_PACK_REF_INTERNAL_KEY ] . ' version=' . $prompt_pack_ref[ Input_Artifact_Schema::PROMPT_PACK_REF_VERSION ]
		);
		$artifact_id                 = 'aio-artifact-' . uniqid( '', true );
		$profile                     = $prefill['profile'] ?? array();
		$goal                        = isset( $draft['goal_or_intent_text'] ) && is_string( $draft['goal_or_intent_text'] ) ? $draft['goal_or_intent_text'] : '';
		$registry                    = array();
		$template_preference_profile = isset( $profile['template_preference_profile'] ) && is_array( $profile['template_preference_profile'] ) ? $profile['template_preference_profile'] : array();
		if ( $this->container->has( 'template_recommendation_context_builder' ) ) {
			$ctx_builder = $this->container->get( 'template_recommendation_context_builder' );
			if ( $ctx_builder instanceof Template_Recommendation_Context_Builder ) {
				$built                                       = $ctx_builder->build(
					array(
						'max_templates'               => Planning_Breadth_Constants::TEMPLATE_RECOMMENDATION_CAP,
						'template_preference_profile' => $template_preference_profile,
					)
				);
				$registry['template_recommendation_context'] = $built['template_recommendation_context'];
				if ( isset( $built['template_preference_profile'] ) && is_array( $built['template_preference_profile'] ) ) {
					$registry['template_preference_profile'] = $built['template_preference_profile'];
				}
			}
		}
		$artifact_options = array(
			'profile'           => $profile,
			'crawl'             => array(),
			'registry'          => $registry,
			'goal'              => $goal,
			'planning_guidance' => $this->prompt_pack_registry->get_planning_guidance_content(),
			'redaction'         => array( Input_Artifact_Schema::REDACTION_APPLIED => false ),
		);
		if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			$industry_repo = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
			if ( $industry_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
				$qp_registry      = $this->container->has( 'industry_question_pack_registry' ) ? $this->container->get( 'industry_question_pack_registry' ) : null;
				$pack_registry    = $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$readiness        = $industry_repo->get_readiness( $pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null, $qp_registry instanceof \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry ? $qp_registry : null );
				$industry_profile = $industry_repo->get_profile();
				$industry_context = array(
					'schema_version'   => '1',
					'industry_profile' => array(
						'primary_industry_key' => $industry_profile[ \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ?? '',
					),
					'readiness'        => $readiness->to_array(),
				);
				if ( $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_SUBTYPE_RESOLVER ) ) {
					$subtype_resolver = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_SUBTYPE_RESOLVER );
					if ( $subtype_resolver instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Subtype_Resolver ) {
						$resolved = $subtype_resolver->resolve_from_profile( $industry_profile );
						if ( ! empty( $resolved['has_valid_subtype'] ) && $resolved['industry_subtype_key'] !== '' && is_array( $resolved['resolved_subtype'] ?? null ) ) {
							$def                                      = $resolved['resolved_subtype'];
							$industry_context['industry_subtype_key'] = $resolved['industry_subtype_key'];
							$industry_context['resolved_subtype_snapshot'] = array(
								'label'   => trim( (string) ( $def[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry::FIELD_LABEL ] ?? '' ) ),
								'summary' => trim( (string) ( $def[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry::FIELD_SUMMARY ] ?? '' ) ),
							);
							$primary                                       = $resolved['primary_industry_key'];
							if ( $primary !== '' && $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ) {
								$bundle_registry = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY );
								if ( $bundle_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ) {
									$bundles = $bundle_registry->get_for_industry( $primary, $resolved['industry_subtype_key'] );
									$refs    = array();
									foreach ( $bundles as $b ) {
										$key = $b[ \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '';
										if ( $key !== '' ) {
											$refs[] = $key;
										}
									}
									if ( $refs !== array() ) {
										$industry_context['subtype_bundle_refs'] = $refs;
									}
								}
							}
						}
					}
				}
				if ( $this->container->has( 'industry_secondary_conversion_goal_resolver' ) ) {
					$goal_resolver = $this->container->get( 'industry_secondary_conversion_goal_resolver' );
					if ( $goal_resolver instanceof \AIOPageBuilder\Domain\Industry\Profile\Secondary_Conversion_Goal_Resolver ) {
						$goals = $goal_resolver->resolve( $industry_profile );
						if ( isset( $goals['primary_goal_key'] ) && is_string( $goals['primary_goal_key'] ) && $goals['primary_goal_key'] !== '' ) {
							$industry_context['primary_goal_key'] = $goals['primary_goal_key'];
						}
						if ( isset( $goals['secondary_goal_key'] ) && is_string( $goals['secondary_goal_key'] ) && $goals['secondary_goal_key'] !== '' ) {
							$industry_context['secondary_goal_key'] = $goals['secondary_goal_key'];
						}
					}
				}
				$artifact_options['industry_context'] = $industry_context;
			}
		}
		$input_artifact = $this->input_artifact_builder->build( $artifact_id, $prompt_pack_ref, $artifact_options );
		if ( $input_artifact === null ) {
			$errors = $this->input_artifact_builder->get_last_validation_errors();
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_INPUT_ARTIFACT_FAILED, 'errors=' . (string) \wp_json_encode( $errors ) );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_BLOCKED,
				'',
				0,
				__( 'Input artifact could not be built. Check your profile and draft.', 'aio-page-builder' ),
				array( 'validation_errors' => $errors ),
				null,
				'input_artifact_failed'
			);
		}
		Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_INPUT_ARTIFACT_BUILT, 'artifact_id=' . $artifact_id );

		$build_options = array();
		if ( $this->container->has( 'industry_prompt_pack_overlay_service' ) ) {
			$overlay_svc = $this->container->get( 'industry_prompt_pack_overlay_service' );
			if ( $overlay_svc instanceof \AIOPageBuilder\Domain\Industry\AI\Industry_Prompt_Pack_Overlay_Service ) {
				$build_options['industry_overlay'] = $overlay_svc->get_overlay_for_artifact( $input_artifact );
			}
		}
		if ( $this->container->has( 'industry_subtype_prompt_pack_overlay_service' ) ) {
			$subtype_overlay_svc = $this->container->get( 'industry_subtype_prompt_pack_overlay_service' );
			if ( $subtype_overlay_svc instanceof \AIOPageBuilder\Domain\Industry\AI\Industry_Subtype_Prompt_Pack_Overlay_Service ) {
				$build_options['subtype_overlay'] = $subtype_overlay_svc->get_overlay_for_artifact( $input_artifact );
			}
		}
		if ( $this->container->has( 'conversion_goal_prompt_pack_overlay_service' ) ) {
			$goal_overlay_svc = $this->container->get( 'conversion_goal_prompt_pack_overlay_service' );
			if ( $goal_overlay_svc instanceof \AIOPageBuilder\Domain\Industry\AI\Conversion_Goal_Prompt_Pack_Overlay_Service ) {
				$build_options['goal_overlay'] = $goal_overlay_svc->get_overlay_for_artifact( $input_artifact );
			}
		}
		$package_result = $this->prompt_package_builder->build( $pack, $input_artifact, $build_options );
		if ( ! $package_result->is_success() || $package_result->get_normalized_prompt_package() === null ) {
			$errors = $package_result->get_validation_errors();
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_PROMPT_PACKAGE_FAILED, 'errors=' . (string) \wp_json_encode( $errors ) );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_BLOCKED,
				'',
				0,
				__( 'Prompt package could not be assembled.', 'aio-page-builder' ),
				array( 'validation_errors' => $errors ),
				null,
				'prompt_package_failed'
			);
		}
		Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_PROMPT_PACKAGE_BUILT, 'pack_key=' . (string) ( $pack[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' ) );

		$package        = $package_result->get_normalized_prompt_package();
		$system_prompt  = (string) ( $package['system_prompt'] ?? '' );
		$user_message   = (string) ( $package['user_message'] ?? '' );
		$model_override = $route->get_primary_model_override();
		$model          = ( $model_override !== null && $model_override !== '' )
			? $model_override
			: $this->capability_resolver->resolve_default_model_for_planning( $driver, $schema_ref );
		if ( $model === null || $model === '' ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_BLOCKED_NO_MODEL, 'provider=' . $provider_id );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_PROVIDER_FAILED,
				'',
				0,
				__( 'No suitable model found for planning.', 'aio-page-builder' ),
				null,
				array(
					'category'      => 'unsupported_feature',
					'user_message'  => 'No model for planning.',
					'internal_code' => 'unsupported_feature',
					'provider_raw'  => null,
					'retry_posture' => 'no_retry',
				),
				null
			);
		}

		if ( $this->budget_estimator !== null ) {
			$upper     = $this->budget_estimator->estimate_full_run_upper_bound_usd( $provider_id, $model, $system_prompt, $user_message );
			$suggested = $this->budget_estimator->get_suggested_planning_budget_usd();
			if ( $upper === null ) {
				Named_Debug_Log::event(
					Named_Debug_Log_Event::ORCHESTRATOR_PER_RUN_BUDGET_UNKNOWN_PRICING,
					'provider=' . $provider_id . ' model=' . $model
				);
			} else {
				Named_Debug_Log::event(
					Named_Debug_Log_Event::ORCHESTRATOR_PLANNING_COST_ESTIMATE,
					'upper_est=' . (string) $upper . ' suggested_target_usd=' . (string) $suggested
				);
			}
		}

		$request_id         = 'aio-req-' . uniqid( '', true );
		$normalized_request = $this->request_context_builder->build(
			$request_id,
			$model,
			$system_prompt,
			$user_message,
			array(
				'structured_output_schema_ref' => $schema_ref,
				'max_tokens'                   => Planning_Structured_Output_Limits::DEFAULT_MAX_OUTPUT_TOKENS,
				'timeout_seconds'              => 180,
			)
		);

		Named_Debug_Log::event(
			Named_Debug_Log_Event::ORCHESTRATOR_PROVIDER_CALL,
			'request_id=' . $request_id . ' provider=' . $provider_id . ' model=' . $model
		);
		$response = $driver->request( $normalized_request );

		$policy          = $this->failover_service->get_policy_for_primary( $provider_id );
		$failover_result = null;

		if ( ! empty( $response['success'] ) ) {
			$failover_result = Failover_Result::primary_success( $provider_id, $model, $policy->to_metadata_snapshot() );
		} else {
			$fallback_bag    = $this->failover_service->try_fallback(
				$policy,
				$provider_id,
				$model,
				$response,
				$normalized_request,
				$schema_ref,
				$this->container
			);
			$response        = $fallback_bag['response'];
			$failover_result = $fallback_bag['result'];
		}

		$response = AI_Structured_Response_Guard::ensure_json_channel_valid( $normalized_request, $response );

		$effective_provider_id = $failover_result->get_effective_provider_id();
		$effective_model       = $failover_result->get_effective_model_used();

		$run_id     = 'aio-run-' . uniqid( '', true );
		$created_at = gmdate( 'Y-m-d\TH:i:s\Z' );
		$metadata   = array(
			'actor'           => (string) ( \get_current_user_id() ),
			'created_at'      => $created_at,
			'provider_id'     => $effective_provider_id,
			'model_used'      => $effective_model,
			'prompt_pack_ref' => $prompt_pack_ref,
			'retry_count'     => 0,
			'request_id'      => $request_id,
		);
		$metadata   = array_merge( $metadata, $failover_result->to_run_metadata() );

		$raw_prompt_capture = $package['raw_prompt_capture_ready'] ?? array(
			'system_prompt' => $system_prompt,
			'user_message'  => $user_message,
		);
		// * Omit input_artifact from normalized package persistence: it duplicates INPUT_SNAPSHOT and can make
		// * wp_json_encode / post meta writes fail silently, leaving raw_prompt + normalized missing in admin.
		$artifacts = array(
			Artifact_Category_Keys::RAW_PROMPT     => $raw_prompt_capture,
			Artifact_Category_Keys::NORMALIZED_PROMPT_PACKAGE => $this->slim_normalized_package_for_persistence( $package ),
			Artifact_Category_Keys::INPUT_SNAPSHOT => $input_artifact,
		);

		if ( ! empty( $response['success'] ) ) {
			$content = isset( $response['structured_payload']['content'] ) && is_string( $response['structured_payload']['content'] )
				? $response['structured_payload']['content']
				: ( is_string( $response['structured_payload'] ?? null ) ? $response['structured_payload'] : '' );
			$artifacts[ Artifact_Category_Keys::RAW_PROVIDER_RESPONSE ] = $response;
			if ( isset( $response['usage'] ) && is_array( $response['usage'] ) ) {
				$artifacts[ Artifact_Category_Keys::USAGE_METADATA ] = $response['usage'];
			}

			$validation_report                                      = $this->validator->validate( $content, $schema_ref );
			$artifacts[ Artifact_Category_Keys::VALIDATION_REPORT ] = $validation_report->to_array();

			if ( $validation_report->allows_build_plan_handoff() ) {
				$normalized  = $validation_report->get_normalized_output();
				$crawl_empty = empty( $artifact_options['crawl'] ?? array() );
				$bundle_refs = array();
				if ( isset( $artifact_options['industry_context'] ) && is_array( $artifact_options['industry_context'] ) ) {
					$ic = $artifact_options['industry_context'];
					if ( isset( $ic['subtype_bundle_refs'] ) && is_array( $ic['subtype_bundle_refs'] ) ) {
						$bundle_refs = $ic['subtype_bundle_refs'];
					}
				}
				$rec_ctx    = isset( $registry['template_recommendation_context'] ) && is_array( $registry['template_recommendation_context'] )
					? $registry['template_recommendation_context']
					: array();
				$enrich_ctx = array(
					'crawl_empty'                     => $crawl_empty,
					'subtype_bundle_refs'             => $bundle_refs,
					'template_recommendation_context' => $rec_ctx,
				);

				if ( $this->thin_output_enrichment !== null ) {
					$pages_before = isset( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] ) && is_array( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
						? count( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
						: 0;
					$normalized   = $this->thin_output_enrichment->enrich( $normalized, $enrich_ctx );
					$pages_after  = isset( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] ) && is_array( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
						? count( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
						: 0;
					if ( $pages_after > $pages_before ) {
						Named_Debug_Log::event(
							Named_Debug_Log_Event::ORCHESTRATOR_THIN_OUTPUT_ENRICHED,
							'before=' . (string) $pages_before . ' after=' . (string) $pages_after . ' crawl_empty=' . ( $crawl_empty ? '1' : '0' )
						);
					}
				}

				$page_count = isset( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] ) && is_array( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
					? count( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
					: 0;
				if ( $this->expand_runner !== null && $page_count < Planning_Breadth_Constants::MIN_NEW_PAGES_TARGET ) {
					$allowed_keys = $this->collect_expand_allowed_template_keys( $registry, $artifact_options );
					if ( $allowed_keys !== array() ) {
						$expand_driver = $this->get_driver_for_provider( $effective_provider_id ) ?? $driver;
						$exp           = $this->expand_runner->maybe_expand( $expand_driver, $effective_provider_id, $effective_model, $normalized, $allowed_keys, $goal );
						$normalized    = $exp['normalized'];
						if ( $exp['usage'] !== null ) {
							$artifacts[ Artifact_Category_Keys::EXPAND_PASS_USAGE ] = $exp['usage'];
							$this->maybe_record_run_cost( $effective_provider_id, $exp['usage'] );
							$pc = isset( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] ) && is_array( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
								? count( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
								: 0;
							Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_EXPAND_PASS_RAN, 'pages=' . (string) $pc );
						}
					}
				}

				if ( $this->thin_output_enrichment !== null ) {
					$pages_before = isset( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] ) && is_array( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
						? count( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
						: 0;
					$normalized   = $this->thin_output_enrichment->enrich( $normalized, $enrich_ctx );
					$pages_after  = isset( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] ) && is_array( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
						? count( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
						: 0;
					if ( $pages_after > $pages_before ) {
						Named_Debug_Log::event(
							Named_Debug_Log_Event::ORCHESTRATOR_THIN_OUTPUT_ENRICHED,
							'before=' . (string) $pages_before . ' after=' . (string) $pages_after . ' phase=post_expand crawl_empty=' . ( $crawl_empty ? '1' : '0' )
						);
					}
				}

				$artifacts[ Artifact_Category_Keys::NORMALIZED_OUTPUT ] = $normalized;
				$metadata['completed_at']                               = gmdate( 'Y-m-d\TH:i:s\Z' );
				$post_id = $this->run_service->create_run( $run_id, $metadata, self::RUN_STATUS_COMPLETED, $artifacts );
				$this->connection_test_service->record_last_successful_use( $effective_provider_id, $created_at );
				$this->link_run_to_draft( $draft, $run_id, $post_id );
				// * Record cost_usd against the monthly spend accumulator when pricing data is available.
				$this->maybe_record_run_cost( $effective_provider_id, $response['usage'] ?? null );
				Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_RUN_COMPLETED, 'run_id=' . $run_id . ' post_id=' . (string) $post_id );
				// * Fires after a successful AI run so snapshot capture and other listeners can react.
				\do_action( 'aio_pb_onboarding_run_completed', $run_id );
				return new Planning_Request_Result(
					true,
					Planning_Request_Result::STATUS_SUCCESS,
					$run_id,
					$post_id,
					__( 'AI plan generated successfully. You can open the run from AI Runs or create a Build Plan.', 'aio-page-builder' ),
					null,
					null,
					null
				);
			}

			$metadata['completed_at'] = gmdate( 'Y-m-d\TH:i:s\Z' );
			$post_id                  = $this->run_service->create_run( $run_id, $metadata, self::RUN_STATUS_FAILED_VALIDATION, $artifacts );
			$this->link_run_to_draft( $draft, $run_id, $post_id );
			Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_RUN_FAILED_VALIDATION, 'run_id=' . $run_id . ' post_id=' . (string) $post_id );
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_VALIDATION_FAILED,
				$run_id,
				$post_id,
				__( 'The AI response could not be validated. Check the run in AI Runs for details.', 'aio-page-builder' ),
				$validation_report->to_array(),
				null,
				null
			);
		}

		$artifacts[ Artifact_Category_Keys::RAW_PROVIDER_RESPONSE ] = $response;
		$normalized_error         = isset( $response['normalized_error'] ) && is_array( $response['normalized_error'] ) ? $response['normalized_error'] : null;
		$metadata['completed_at'] = gmdate( 'Y-m-d\TH:i:s\Z' );
		$post_id                  = $this->run_service->create_run( $run_id, $metadata, self::RUN_STATUS_FAILED, $artifacts );
		$this->link_run_to_draft( $draft, $run_id, $post_id );
		Named_Debug_Log::event( Named_Debug_Log_Event::ORCHESTRATOR_RUN_PROVIDER_FAILED, 'run_id=' . $run_id . ' post_id=' . (string) $post_id );
		$user_msg = $normalized_error['user_message'] ?? __( 'The AI provider returned an error. Check the run in AI Runs.', 'aio-page-builder' );
		return new Planning_Request_Result(
			false,
			Planning_Request_Result::STATUS_PROVIDER_FAILED,
			$run_id,
			$post_id,
			$user_msg,
			null,
			$normalized_error,
			null
		);
	}

	/**
	 * Checks the monthly spend cap for the given provider. Returns a blocked result when
	 * the cap is exceeded and override is not enabled; returns null to indicate pass.
	 *
	 * @param string $provider_id
	 * @return Planning_Request_Result|null
	 */
	private function check_spend_cap_preflight( string $provider_id ): ?Planning_Request_Result {
		if ( ! $this->container->has( 'provider_monthly_spend_service' ) ) {
			return null;
		}
		if ( ! $this->container->has( 'provider_spend_cap_settings' ) ) {
			return null;
		}
		/** @var Provider_Monthly_Spend_Service $spend_service */
		$spend_service = $this->container->get( 'provider_monthly_spend_service' );
		/** @var Provider_Spend_Cap_Settings $cap_settings */
		$cap_settings = $this->container->get( 'provider_spend_cap_settings' );

		if ( ! $cap_settings->has_cap( $provider_id ) ) {
			return null;
		}
		$summary = $spend_service->get_spend_summary( $provider_id );
		if ( $summary['exceeded'] && ! $summary['override_enabled'] ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::ORCHESTRATOR_SPEND_CAP_BLOCKED,
				sprintf(
					'provider=%s spent=%.4f cap=%.2f',
					$provider_id,
					$summary['month_total'],
					$summary['cap']
				)
			);
			return new Planning_Request_Result(
				false,
				Planning_Request_Result::STATUS_BLOCKED,
				'',
				0,
				sprintf(
					/* translators: 1: provider name, 2: amount spent, 3: cap amount */
					__( 'Monthly spend cap for %1$s exceeded ($%2$s of $%3$s). Enable the override in AI Providers settings to allow additional runs.', 'aio-page-builder' ),
					\esc_html( $provider_id ),
					number_format( $summary['month_total'], 4 ),
					number_format( $summary['cap'], 2 )
				),
				null,
				null,
				'spend_cap_exceeded'
			);
		}
		return null;
	}

	/**
	 * Records the cost_usd from a completed run usage struct against the monthly accumulator.
	 * No-op when usage is absent or cost_usd is null.
	 *
	 * @param string     $provider_id
	 * @param array|null $usage
	 * @return void
	 */
	private function maybe_record_run_cost( string $provider_id, ?array $usage ): void {
		if ( ! is_array( $usage ) ) {
			return;
		}
		$cost = $usage['cost_usd'] ?? null;
		if ( ! is_float( $cost ) && ! is_int( $cost ) ) {
			return;
		}
		$cost_float = (float) $cost;
		if ( $cost_float <= 0.0 ) {
			return;
		}
		if ( ! $this->container->has( 'provider_monthly_spend_service' ) ) {
			return;
		}
		/** @var Provider_Monthly_Spend_Service $spend_service */
		$spend_service = $this->container->get( 'provider_monthly_spend_service' );
		$spend_service->record_run_cost( $provider_id, $cost_float );
	}

	/**
	 * @param array<string, mixed> $prefill
	 * @return string|null
	 */
	private function pick_configured_provider_id( array $prefill ): ?string {
		$from_store = $this->prefill_service->get_first_ready_provider_id();
		if ( $from_store !== null && $from_store !== '' ) {
			return $from_store;
		}
		$refs = $prefill['provider_refs'] ?? array();
		foreach ( $refs as $ref ) {
			if ( ! is_array( $ref ) ) {
				continue;
			}
			if ( ( $ref['credential_state'] ?? '' ) === 'configured' && isset( $ref['provider_id'] ) && is_string( $ref['provider_id'] ) ) {
				return $ref['provider_id'];
			}
		}
		return null;
	}

	/**
	 * @param string $provider_id
	 * @return AI_Provider_Interface|null
	 */
	private function get_driver_for_provider( string $provider_id ): ?AI_Provider_Interface {
		if ( $provider_id === 'openai' && $this->container->has( 'openai_provider_driver' ) ) {
			return $this->container->get( 'openai_provider_driver' );
		}
		if ( $provider_id === 'anthropic' && $this->container->has( 'anthropic_provider_driver' ) ) {
			return $this->container->get( 'anthropic_provider_driver' );
		}
		return null;
	}

	/**
	 * Persists a minimal normalized package: omit input_artifact (INPUT_SNAPSHOT) and raw_prompt_capture_ready
	 * (duplicates system/user strings already on this object and in the raw_prompt artifact) to avoid oversized JSON.
	 *
	 * @param array<string, mixed> $package Full normalized package from Normalized_Prompt_Package_Builder.
	 * @return array<string, mixed>
	 */
	private function slim_normalized_package_for_persistence( array $package ): array {
		$ref = isset( $package['prompt_pack_ref'] ) && is_array( $package['prompt_pack_ref'] ) ? $package['prompt_pack_ref'] : array();
		return array(
			'prompt_pack_ref'   => $ref,
			'schema_target_ref' => (string) ( $package['schema_target_ref'] ?? '' ),
			'repair_prompt_ref' => (string) ( $package['repair_prompt_ref'] ?? '' ),
			'input_artifact_id' => (string) ( $package['input_artifact_id'] ?? '' ),
			'system_prompt'     => (string) ( $package['system_prompt'] ?? '' ),
			'user_message'      => (string) ( $package['user_message'] ?? '' ),
		);
	}

	/**
	 * @param array<string, mixed> $draft
	 * @param string               $run_id
	 * @param int                  $run_post_id
	 * @return void
	 */
	private function link_run_to_draft( array $draft, string $run_id, int $run_post_id ): void {
		$draft['last_planning_run_id']      = $run_id;
		$draft['last_planning_run_post_id'] = $run_post_id;
		$this->draft_service->save_draft( $draft );
	}

	/**
	 * Distinct governed template_key values for the expand pass (registry + active starter bundles).
	 *
	 * @param array<string, mixed> $registry         Artifact `registry` slice (includes template_recommendation_context).
	 * @param array<string, mixed> $artifact_options Options passed to Input_Artifact_Builder (industry_context, etc.).
	 * @return list<string>
	 */
	private function collect_expand_allowed_template_keys( array $registry, array $artifact_options ): array {
		$keys = array();
		$rec  = isset( $registry['template_recommendation_context'] ) && is_array( $registry['template_recommendation_context'] )
			? $registry['template_recommendation_context']
			: array();
		foreach ( $rec as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$tk = isset( $entry['template_key'] ) && is_string( $entry['template_key'] ) ? trim( $entry['template_key'] ) : '';
			if ( $tk !== '' ) {
				$keys[] = $tk;
			}
		}
		if ( $this->container->has( Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ) {
			$bundle_reg = $this->container->get( Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY );
			if ( $bundle_reg instanceof Industry_Starter_Bundle_Registry ) {
				$ic   = isset( $artifact_options['industry_context'] ) && is_array( $artifact_options['industry_context'] )
					? $artifact_options['industry_context']
					: array();
				$refs = isset( $ic['subtype_bundle_refs'] ) && is_array( $ic['subtype_bundle_refs'] ) ? $ic['subtype_bundle_refs'] : array();
				foreach ( $refs as $ref ) {
					if ( ! is_string( $ref ) || trim( $ref ) === '' ) {
						continue;
					}
					$bundle = $bundle_reg->get( trim( $ref ) );
					if ( $bundle === null || ( isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ) && (string) $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] !== Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) ) {
						continue;
					}
					$templates = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] ) && is_array( $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] )
						? $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ]
						: array();
					foreach ( $templates as $tk ) {
						if ( ! is_string( $tk ) ) {
							continue;
						}
						$tk = trim( $tk );
						if ( $tk !== '' ) {
							$keys[] = $tk;
						}
					}
				}
			}
		}
		return array_values( array_unique( array_filter( $keys ) ) );
	}
}
