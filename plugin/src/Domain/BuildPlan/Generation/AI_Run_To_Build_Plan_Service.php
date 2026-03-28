<?php
/**
 * Creates a draft Build Plan from a completed AI run normalized output artifact (spec §30.2–30.3).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Generation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\BuildPlan\Build_Plan_Template_Lab_Context_Resolver;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;

/**
 * Loads normalized output for a completed run and persists a plan via {@see Build_Plan_Generator}.
 */
final class AI_Run_To_Build_Plan_Service {

	private AI_Run_Service $run_service;

	private AI_Run_Artifact_Service $artifact_service;

	private Build_Plan_Generator $plan_generator;

	private Build_Plan_Repository $plan_repository;

	private ?Build_Plan_Template_Lab_Context_Resolver $template_lab_context_resolver;

	public function __construct(
		AI_Run_Service $run_service,
		AI_Run_Artifact_Service $artifact_service,
		Build_Plan_Generator $plan_generator,
		Build_Plan_Repository $plan_repository,
		?Build_Plan_Template_Lab_Context_Resolver $template_lab_context_resolver = null
	) {
		$this->run_service                   = $run_service;
		$this->artifact_service              = $artifact_service;
		$this->plan_generator                = $plan_generator;
		$this->plan_repository               = $plan_repository;
		$this->template_lab_context_resolver = $template_lab_context_resolver;
	}

	/**
	 * Builds a draft Build Plan when the run completed and stores a normalized output artifact.
	 *
	 * @param string               $run_id              AI run internal key.
	 * @param int|null             $reuse_plan_post_id When set, replaces the plan definition on this post instead of creating a new post (onboarding shell).
	 * @param array<string, mixed> $options Optional: actor_user_id (int), template_lab_chat_session_id (string) to attach approved template-lab provenance (non-executing).
	 * @return Plan_Generation_Result Persisted plan on success.
	 */
	public function create_from_completed_run( string $run_id, ?int $reuse_plan_post_id = null, array $options = array() ): Plan_Generation_Result {
		$run_id = trim( $run_id );
		if ( $run_id === '' ) {
			return Plan_Generation_Result::failure( array( \__( 'Run ID is required.', 'aio-page-builder' ) ) );
		}

		$run = $this->run_service->get_run_by_id( $run_id );
		if ( $run === null ) {
			return Plan_Generation_Result::failure( array( \__( 'AI run not found.', 'aio-page-builder' ) ) );
		}

		if ( (string) ( $run['status'] ?? '' ) !== 'completed' ) {
			return Plan_Generation_Result::failure(
				array( \__( 'The run must be completed before creating a Build Plan.', 'aio-page-builder' ) )
			);
		}

		$post_id = (int) ( $run['id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return Plan_Generation_Result::failure( array( \__( 'Invalid run record.', 'aio-page-builder' ) ) );
		}

		$normalized = $this->artifact_service->get( $post_id, Artifact_Category_Keys::NORMALIZED_OUTPUT );
		if ( ! is_array( $normalized ) ) {
			return Plan_Generation_Result::failure(
				array( \__( 'This run has no normalized output artifact.', 'aio-page-builder' ) )
			);
		}

		$output_ref = $run_id . ':' . Artifact_Category_Keys::NORMALIZED_OUTPUT;

		$prev_for_lineage = array();
		if ( $reuse_plan_post_id !== null && $reuse_plan_post_id > 0 ) {
			$prev_def = $this->plan_repository->get_plan_definition( $reuse_plan_post_id );
			foreach ( $this->lineage_keys_to_preserve() as $k ) {
				if ( array_key_exists( $k, $prev_def ) ) {
					$prev_for_lineage[ $k ] = $prev_def[ $k ];
				}
			}
		}

		$context = array();
		if ( $reuse_plan_post_id !== null && $reuse_plan_post_id > 0 ) {
			$context['target_post_id'] = $reuse_plan_post_id;
		}

		$actor  = isset( $options['actor_user_id'] ) ? (int) $options['actor_user_id'] : 0;
		$tl_sid = isset( $options['template_lab_chat_session_id'] ) && is_string( $options['template_lab_chat_session_id'] )
			? trim( $options['template_lab_chat_session_id'] )
			: '';
		if ( $tl_sid !== '' ) {
			if ( $this->template_lab_context_resolver === null || $actor <= 0 ) {
				return Plan_Generation_Result::failure(
					array( \__( 'Template-lab session linkage is unavailable for this request.', 'aio-page-builder' ) )
				);
			}
			$tl = $this->template_lab_context_resolver->resolve_for_actor( $actor, $tl_sid );
			if ( $tl['code'] !== Build_Plan_Template_Lab_Context_Resolver::CODE_OK ) {
				return Plan_Generation_Result::failure(
					array( '[aio_tl_link] ' . self::template_lab_link_error_message( $tl['code'] ) )
				);
			}
			$context['template_lab_context'] = $tl['context'];
		}

		$had_template_lab_context = ! empty( $context['template_lab_context'] );

		$result = $this->plan_generator->generate( $normalized, $run_id, $output_ref, $context );
		if ( ! $result->is_success() || $prev_for_lineage === array() ) {
			return $result;
		}

		$merged = $result->get_plan_payload();
		foreach ( $prev_for_lineage as $k => $v ) {
			if ( $had_template_lab_context && $k === Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT ) {
				continue;
			}
			$merged[ $k ] = $v;
		}
		$this->plan_repository->save_plan_definition( $result->get_plan_post_id(), $merged );

		return new Plan_Generation_Result(
			true,
			isset( $merged[ Build_Plan_Schema::KEY_PLAN_ID ] ) ? (string) $merged[ Build_Plan_Schema::KEY_PLAN_ID ] : $result->get_plan_id(),
			$result->get_plan_post_id(),
			$merged,
			$result->get_omitted_report()
		);
	}

	/**
	 * @return array<int, string>
	 */
	private function lineage_keys_to_preserve(): array {
		return array(
			Build_Plan_Schema::KEY_PLAN_LINEAGE_ID,
			Build_Plan_Schema::KEY_PLAN_VERSION_SEQ,
			Build_Plan_Schema::KEY_PLAN_VERSION_LABEL,
			Build_Plan_Schema::KEY_VERSION_PURPOSE_DESCRIPTION,
			Build_Plan_Schema::KEY_ESTIMATED_AI_COST_USD_NOTE,
			Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT,
		);
	}

	private static function template_lab_link_error_message( string $code ): string {
		if ( $code === Build_Plan_Template_Lab_Context_Resolver::CODE_SESSION_MISSING ) {
			return \__( 'The template-lab chat session was not found.', 'aio-page-builder' );
		}
		if ( $code === Build_Plan_Template_Lab_Context_Resolver::CODE_FORBIDDEN ) {
			return \__( 'You cannot link that template-lab session to a build plan.', 'aio-page-builder' );
		}
		if ( $code === Build_Plan_Template_Lab_Context_Resolver::CODE_NOT_APPROVED ) {
			return \__( 'Template-lab snapshots must be approved before they can inform a build plan.', 'aio-page-builder' );
		}
		if ( $code === Build_Plan_Template_Lab_Context_Resolver::CODE_BAD_REF ) {
			return \__( 'The template-lab session snapshot reference is invalid.', 'aio-page-builder' );
		}
		if ( $code === Build_Plan_Template_Lab_Context_Resolver::CODE_FINGERPRINT_MISMATCH ) {
			return \__( 'The template-lab snapshot no longer matches the AI run artifact (regenerate or re-approve).', 'aio-page-builder' );
		}
		if ( $code === Build_Plan_Template_Lab_Context_Resolver::CODE_CANONICAL_NOT_LINKED ) {
			return \__( 'Apply the approved template-lab snapshot to canonical templates before linking it to a build plan.', 'aio-page-builder' );
		}
		return \__( 'Template-lab linkage could not be validated.', 'aio-page-builder' );
	}
}
