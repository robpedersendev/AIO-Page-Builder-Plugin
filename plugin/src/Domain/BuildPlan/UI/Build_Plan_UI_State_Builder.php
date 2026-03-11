<?php
/**
 * Builds UI state payload for the Build Plan shell (spec §31.4, build-plan-admin-ia-contract.md §5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;

/**
 * Consumes plan_id; loads plan via repository; returns structured payload for context rail and shell.
 * No raw repository calls in screen templates; screen uses this payload only.
 */
final class Build_Plan_UI_State_Builder {

	/** @var Build_Plan_Repository */
	private $repository;

	/** @var Build_Plan_Stepper_Builder */
	private $stepper_builder;

	public function __construct( Build_Plan_Repository $repository, Build_Plan_Stepper_Builder $stepper_builder ) {
		$this->repository     = $repository;
		$this->stepper_builder = $stepper_builder;
	}

	/**
	 * Builds full UI state for the workspace shell. Returns null if plan not found.
	 *
	 * @param string $plan_id Plan ID (e.g. UUID or internal_key).
	 * @return array<string, mixed>|null Payload with context_rail, stepper_steps, plan_definition, plan_post_id; null if not found.
	 */
	public function build( string $plan_id ): ?array {
		$plan_id = \sanitize_text_field( $plan_id );
		if ( $plan_id === '' ) {
			return null;
		}
		$record = $this->repository->get_by_key( $plan_id );
		if ( $record === null && is_numeric( $plan_id ) ) {
			$record = $this->repository->get_by_id( (int) $plan_id );
		}
		if ( $record === null ) {
			return null;
		}
		$definition = isset( $record['plan_definition'] ) && is_array( $record['plan_definition'] ) ? $record['plan_definition'] : $record;
		$plan_id_from_def = (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_ID ] ?? $record['internal_key'] ?? $plan_id );
		$stepper_steps   = $this->stepper_builder->build( $definition );
		$context_rail    = $this->build_context_rail( $definition, $stepper_steps );

		return array(
			'plan_id'          => $plan_id_from_def,
			'plan_post_id'     => (int) ( $record['id'] ?? 0 ),
			'plan_definition'  => $definition,
			'context_rail'     => $context_rail,
			'stepper_steps'    => $stepper_steps,
		);
	}

	/**
	 * Context rail fields per IA contract §5.
	 *
	 * @param array<string, mixed> $definition
	 * @param array<int, array<string, mixed>> $stepper_steps
	 * @return array<string, mixed>
	 */
	private function build_context_rail( array $definition, array $stepper_steps ): array {
		$unresolved_by_step = array();
		foreach ( $stepper_steps as $s ) {
			$step_type = (string) ( $s['step_type'] ?? '' );
			$unresolved_by_step[ $step_type ] = (int) ( $s['unresolved_count'] ?? 0 );
		}
		$warnings = isset( $definition[ Build_Plan_Schema::KEY_WARNINGS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_WARNINGS ] )
			? $definition[ Build_Plan_Schema::KEY_WARNINGS ]
			: array();
		$warnings_summary = array_slice( $warnings, 0, 5 );
		if ( count( $warnings ) > 5 ) {
			$warnings_summary[] = array( 'message' => sprintf( __( '+%d more', 'aio-page-builder' ), count( $warnings ) - 5 ) );
		}

		return array(
			'plan_title'             => (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_TITLE ] ?? '' ),
			'plan_id'                => (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ),
			'ai_run_ref'             => (string) ( $definition[ Build_Plan_Schema::KEY_AI_RUN_REF ] ?? '' ),
			'normalized_output_ref'  => (string) ( $definition[ Build_Plan_Schema::KEY_NORMALIZED_OUTPUT_REF ] ?? '' ),
			'plan_status'            => (string) ( $definition[ Build_Plan_Schema::KEY_STATUS ] ?? '' ),
			'site_purpose_summary'    => (string) ( $definition[ Build_Plan_Schema::KEY_SITE_PURPOSE_SUMMARY ] ?? '' ),
			'site_flow_summary'      => (string) ( $definition[ Build_Plan_Schema::KEY_SITE_FLOW_SUMMARY ] ?? '' ),
			'unresolved_counts_by_step' => $unresolved_by_step,
			'warnings_summary'       => $warnings_summary,
		);
	}
}
