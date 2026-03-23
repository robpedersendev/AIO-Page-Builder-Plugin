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
use AIOPageBuilder\Domain\AI\Providers\Failover\Failover_Result;
use AIOPageBuilder\Domain\AI\Providers\Failover\Provider_Failover_Service;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Request_Context_Builder;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator;
use AIOPageBuilder\Domain\AI\Planning\Template_Recommendation_Context_Builder;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\AI\Validation\Validation_Report;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Support\Logging\Internal_Debug_Log;

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
		Service_Container $container
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
	}

	/**
	 * Submits a planning request from current onboarding state. Persists run and links to draft.
	 *
	 * @return Planning_Request_Result
	 */
	public function submit(): Planning_Request_Result {
		$draft        = $this->draft_service->get_draft();
		$current_step = $draft['current_step_key'] ?? Onboarding_Step_Keys::WELCOME;

		if ( $current_step !== Onboarding_Step_Keys::SUBMISSION ) {
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

		$prefill     = $this->prefill_service->get_prefill_data( $draft );
		$provider_id = $this->pick_configured_provider_id( $prefill );
		if ( $provider_id === null || $provider_id === '' ) {
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

		// * Spend cap preflight: block run if cap exceeded and override is not enabled.
		$spend_blocked = $this->check_spend_cap_preflight( $provider_id );
		if ( $spend_blocked !== null ) {
			return $spend_blocked;
		}

		$driver = $this->get_driver_for_provider( $provider_id );
		if ( $driver === null ) {
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

		$prompt_pack_ref             = array(
			Input_Artifact_Schema::PROMPT_PACK_REF_INTERNAL_KEY => (string) ( $pack[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' ),
			Input_Artifact_Schema::PROMPT_PACK_REF_VERSION => (string) ( $pack[ Prompt_Pack_Schema::ROOT_VERSION ] ?? '' ),
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
						'max_templates'               => Template_Recommendation_Context_Builder::DEFAULT_MAX_TEMPLATES,
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
			'profile'   => $profile,
			'crawl'     => array(),
			'registry'  => $registry,
			'goal'      => $goal,
			'redaction' => array( Input_Artifact_Schema::REDACTION_APPLIED => false ),
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

		$package       = $package_result->get_normalized_prompt_package();
		$system_prompt = (string) ( $package['system_prompt'] ?? '' );
		$user_message  = (string) ( $package['user_message'] ?? '' );
		$model         = $this->capability_resolver->resolve_default_model_for_planning( $driver, $schema_ref );
		if ( $model === null || $model === '' ) {
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

		$request_id         = 'aio-req-' . uniqid( '', true );
		$normalized_request = $this->request_context_builder->build(
			$request_id,
			$model,
			$system_prompt,
			$user_message,
			array(
				'structured_output_schema_ref' => $schema_ref,
				'max_tokens'                   => 4096,
				'timeout_seconds'              => 120,
			)
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
		$artifacts          = array(
			Artifact_Category_Keys::RAW_PROMPT     => $raw_prompt_capture,
			Artifact_Category_Keys::NORMALIZED_PROMPT_PACKAGE => $package,
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
				$normalized = $validation_report->get_normalized_output();
				$artifacts[ Artifact_Category_Keys::NORMALIZED_OUTPUT ] = $normalized;
				$metadata['completed_at']                               = gmdate( 'Y-m-d\TH:i:s\Z' );
				$post_id = $this->run_service->create_run( $run_id, $metadata, self::RUN_STATUS_COMPLETED, $artifacts );
				$this->connection_test_service->record_last_successful_use( $effective_provider_id, $created_at );
				$this->link_run_to_draft( $draft, $run_id, $post_id );
				// * Record cost_usd against the monthly spend accumulator when pricing data is available.
				$this->maybe_record_run_cost( $effective_provider_id, $response['usage'] ?? null );
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
			Internal_Debug_Log::line(
				sprintf(
					'Spend cap preflight blocked run for provider %s (spent $%.4f of $%.2f cap).',
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
}
