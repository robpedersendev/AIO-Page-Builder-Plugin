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

	public function __construct(
		AI_Run_Service $run_service,
		AI_Run_Artifact_Service $artifact_service,
		Build_Plan_Generator $plan_generator,
		Build_Plan_Repository $plan_repository
	) {
		$this->run_service       = $run_service;
		$this->artifact_service  = $artifact_service;
		$this->plan_generator    = $plan_generator;
		$this->plan_repository   = $plan_repository;
	}

	/**
	 * Builds a draft Build Plan when the run completed and stores a normalized output artifact.
	 *
	 * @param string   $run_id              AI run internal key.
	 * @param int|null $reuse_plan_post_id When set, replaces the plan definition on this post instead of creating a new post (onboarding shell).
	 * @return Plan_Generation_Result Persisted plan on success.
	 */
	public function create_from_completed_run( string $run_id, ?int $reuse_plan_post_id = null ): Plan_Generation_Result {
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

		$result = $this->plan_generator->generate( $normalized, $run_id, $output_ref, $context );
		if ( ! $result->is_success() || $prev_for_lineage === array() ) {
			return $result;
		}

		$merged = $result->get_plan_payload();
		foreach ( $prev_for_lineage as $k => $v ) {
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
		);
	}
}
