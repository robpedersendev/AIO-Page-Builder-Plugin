<?php
/**
 * Bulk new-page build orchestration with dependency ordering and slug validation (spec §33.6, §33.7, §33.8, §33.10, §42; Prompt 195).
 *
 * Builds dependency-ordered create_page envelopes, validates slug collisions (within-batch and existing),
 * enqueues jobs, optionally runs immediately, and returns Bulk_Template_Page_Build_Result with per-item
 * status and retry-safe metadata. Parent-first ordering is provided by Bulk_Executor (depends_on_item_ids).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Pages;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Executor\Plan_State_For_Execution_Interface;
use AIOPageBuilder\Domain\Execution\Queue\Bulk_Executor;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Job_Dispatcher;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Job_Result;

/**
 * Orchestrates bulk new-page creation: dependency ordering, slug validation, enqueue, optional run, result aggregation.
 */
final class Bulk_Template_Page_Build_Service {

	/** @var Plan_State_For_Execution_Interface */
	private $plan_state;

	/** @var Bulk_Executor */
	private $bulk_executor;

	/** @var Execution_Job_Dispatcher */
	private $job_dispatcher;

	public function __construct(
		Plan_State_For_Execution_Interface $plan_state,
		Bulk_Executor $bulk_executor,
		Execution_Job_Dispatcher $job_dispatcher
	) {
		$this->plan_state    = $plan_state;
		$this->bulk_executor = $bulk_executor;
		$this->job_dispatcher = $job_dispatcher;
	}

	/**
	 * Runs bulk new-page build: ordered envelopes, slug validation, enqueue, optional immediate run.
	 *
	 * @param string               $plan_id       Plan ID (internal key).
	 * @param array<int, string>|null $item_ids    Plan item IDs to include; null = all eligible (Build All).
	 * @param array<string, mixed> $actor_context Actor context for envelopes.
	 * @param array<string, mixed> $options       run_immediately (bool), priority (int), batch_id (string).
	 * @return Bulk_Template_Page_Build_Result
	 */
	public function run_bulk_new_pages(
		string $plan_id,
		?array $item_ids,
		array $actor_context,
		array $options = array()
	): Bulk_Template_Page_Build_Result {
		$batch_id = isset( $options['batch_id'] ) && is_string( $options['batch_id'] ) ? $options['batch_id'] : $this->generate_batch_id();
		$plan_record = $this->plan_state->get_by_key( $plan_id );
		if ( $plan_record === null ) {
			return $this->error_result( $plan_id, $batch_id, __( 'Plan not found.', 'aio-page-builder' ) );
		}
		$plan_post_id = (int) ( $plan_record['id'] ?? 0 );
		$definition   = $this->plan_state->get_plan_definition( $plan_post_id );
		if ( empty( $definition ) ) {
			return $this->error_result( $plan_id, $batch_id, __( 'Plan definition not found.', 'aio-page-builder' ) );
		}

		$envelopes = $this->bulk_executor->build_ordered_envelopes( $plan_id, $definition, $item_ids, $actor_context, $batch_id );
		$create_page_envelopes = array();
		foreach ( $envelopes as $env ) {
			$action_type = isset( $env[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) ? $env[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] : '';
			if ( $action_type === Execution_Action_Types::CREATE_PAGE ) {
				$create_page_envelopes[] = $env;
			}
		}

		if ( empty( $create_page_envelopes ) ) {
			return $this->error_result( $plan_id, $batch_id, __( 'No eligible new-page actions to execute.', 'aio-page-builder' ), array( 'envelope_count' => count( $envelopes ) ) );
		}

		$slug_validation = $this->validate_slugs( $create_page_envelopes );
		$to_enqueue = array();
		$slug_collision_item_ids = array();
		$item_results_pre = array();
		foreach ( $create_page_envelopes as $env ) {
			$plan_item_id = isset( $env[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) && is_string( $env[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) ? $env[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] : '';
			if ( $plan_item_id !== '' && isset( $slug_validation['conflict_item_ids'][ $plan_item_id ] ) ) {
				$slug_collision_item_ids[] = $plan_item_id;
				$item_results_pre[ $plan_item_id ] = array(
					'status'          => Execution_Job_Result::STATUS_REFUSED,
					'job_ref'         => '',
					'post_id'         => 0,
					'template_key'    => (string) ( $env[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]['template_key'] ?? '' ),
					'slug_conflict'   => true,
					'failure_reason'  => __( 'Slug conflict: duplicate or existing page.', 'aio-page-builder' ),
					'retry_eligible'   => false,
				);
				continue;
			}
			$to_enqueue[] = $env;
		}

		$slug_collisions = array_merge( $slug_validation['duplicate_slugs'], $slug_validation['existing_slugs'] );
		foreach ( $slug_collision_item_ids as $id ) {
			if ( ! in_array( $id, $slug_collisions, true ) ) {
				$slug_collisions[] = $id;
			}
		}

		$actor_ref = $this->actor_ref_from_context( $actor_context );
		$priority  = isset( $options['priority'] ) && is_numeric( $options['priority'] ) ? (int) $options['priority'] : 0;
		$job_refs  = array();
		if ( ! empty( $to_enqueue ) ) {
			$job_refs = $this->job_dispatcher->enqueue_batch( $to_enqueue, $actor_ref, $priority, $batch_id );
		}

		$run_immediately = ! empty( $options['run_immediately'] );
		if ( $run_immediately && ! empty( $job_refs ) ) {
			$results = $this->job_dispatcher->process_batch( $job_refs );
			return $this->aggregate_to_bulk_result( $plan_id, $batch_id, $job_refs, $results, $item_results_pre, $slug_collisions );
		}

		$total = count( $create_page_envelopes );
		$refused_pre = count( $slug_collision_item_ids );
		$queued = count( $job_refs );
		$message = $refused_pre > 0
			? sprintf( __( '%d queued, %d blocked by slug conflict.', 'aio-page-builder' ), $queued, $refused_pre )
			: sprintf( __( '%d actions queued.', 'aio-page-builder' ), $queued );

		return new Bulk_Template_Page_Build_Result(
			$plan_id,
			$batch_id,
			Bulk_Template_Page_Build_Result::STATUS_QUEUED,
			$job_refs,
			$item_results_pre,
			$slug_collisions,
			0,
			0,
			$refused_pre,
			$refused_pre > 0,
			array(),
			$message,
			array(
				'envelope_count'      => $total,
				'create_page_count'   => $total,
				'queued_count'        => $queued,
				'slug_conflict_count' => $refused_pre,
			)
		);
	}

	/**
	 * Validates proposed slugs: within-batch duplicates and existing pages (spec §33.8).
	 *
	 * @param array<int, array<string, mixed>> $envelopes Create_page envelopes.
	 * @return array{duplicate_slugs: list<string>, existing_slugs: list<string>, conflict_item_ids: array<string, true>}
	 */
	private function validate_slugs( array $envelopes ): array {
		$slug_to_item_ids = array();
		$existing_slugs = array();
		$conflict_item_ids = array();

		foreach ( $envelopes as $env ) {
			$target = isset( $env[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $env[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
				? $env[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
				: array();
			$slug = isset( $target['proposed_slug'] ) && is_string( $target['proposed_slug'] ) ? trim( $target['proposed_slug'] ) : '';
			if ( $slug === '' && isset( $target['proposed_page_title'] ) && is_string( $target['proposed_page_title'] ) ) {
				$slug = \sanitize_title( trim( $target['proposed_page_title'] ) );
			}
			if ( $slug === '' ) {
				continue;
			}
			$plan_item_id = isset( $env[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) && is_string( $env[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) ? $env[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] : '';
			$slug_to_item_ids[ $slug ] = $slug_to_item_ids[ $slug ] ?? array();
			$slug_to_item_ids[ $slug ][] = $plan_item_id;
		}

		foreach ( $slug_to_item_ids as $slug => $ids ) {
			$ids = array_filter( array_unique( $ids ) );
			if ( count( $ids ) > 1 ) {
				foreach ( $ids as $id ) {
					$conflict_item_ids[ $id ] = true;
				}
			}
		}

		if ( function_exists( 'get_page_by_path' ) ) {
			foreach ( array_keys( $slug_to_item_ids ) as $slug ) {
				$page = \get_page_by_path( $slug, OBJECT, 'page' );
				if ( $page instanceof \WP_Post ) {
					$existing_slugs[] = $slug;
					foreach ( $slug_to_item_ids[ $slug ] as $id ) {
						$conflict_item_ids[ $id ] = true;
					}
				}
			}
		}

		$duplicate_slugs = array();
		foreach ( $slug_to_item_ids as $slug => $ids ) {
			if ( count( array_unique( $ids ) ) > 1 ) {
				$duplicate_slugs[] = $slug;
			}
		}

		return array(
			'duplicate_slugs'   => $duplicate_slugs,
			'existing_slugs'    => $existing_slugs,
			'conflict_item_ids' => $conflict_item_ids,
		);
	}

	/**
	 * Aggregates job results and pre-filled item results into Bulk_Template_Page_Build_Result.
	 *
	 * @param string                          $plan_id
	 * @param string                          $batch_id
	 * @param array<int, string>              $job_refs
	 * @param array<int, Execution_Job_Result> $results
	 * @param array<string, array<string, mixed>> $item_results_pre
	 * @param list<string>                    $slug_collisions
	 * @return Bulk_Template_Page_Build_Result
	 */
	private function aggregate_to_bulk_result(
		string $plan_id,
		string $batch_id,
		array $job_refs,
		array $results,
		array $item_results_pre,
		array $slug_collisions
	): Bulk_Template_Page_Build_Result {
		$item_results = $item_results_pre;
		$completed = 0;
		$failed = 0;
		$refused = 0;
		$retry_eligible_item_ids = array();

		foreach ( $results as $r ) {
			$plan_item_id = $r->get_plan_item_id();
			$status = $r->get_status();
			$summary = $r->get_result_summary();
			$artifacts = isset( $summary['artifacts'] ) && is_array( $summary['artifacts'] ) ? $summary['artifacts'] : array();
			$post_id = isset( $artifacts['post_id'] ) && is_numeric( $artifacts['post_id'] ) ? (int) $artifacts['post_id'] : 0;
			$template_key = isset( $artifacts['template_key'] ) && is_string( $artifacts['template_key'] ) ? $artifacts['template_key'] : '';

			$item_results[ $plan_item_id ] = array(
				'status'          => $status,
				'job_ref'         => $r->get_job_ref(),
				'post_id'         => $post_id,
				'template_key'    => $template_key,
				'slug_conflict'   => false,
				'failure_reason'  => $r->get_failure_reason(),
				'retry_eligible'  => $r->is_retry_eligible(),
			);

			if ( $status === Execution_Job_Result::STATUS_COMPLETED ) {
				++$completed;
			} elseif ( $status === Execution_Job_Result::STATUS_REFUSED ) {
				++$refused;
			} else {
				++$failed;
				if ( $r->is_retry_eligible() ) {
					$retry_eligible_item_ids[] = $plan_item_id;
				}
			}
		}

		$total = $completed + $failed + $refused + count( $item_results_pre );
		$refused += count( $item_results_pre );
		$partial_failure = $failed > 0 || $refused > 0;
		$overall_status = $refused === $total ? Bulk_Template_Page_Build_Result::STATUS_REFUSED
			: ( $completed === $total ? Bulk_Template_Page_Build_Result::STATUS_COMPLETED : Bulk_Template_Page_Build_Result::STATUS_PARTIAL );

		$message_parts = array();
		if ( $completed > 0 ) {
			$message_parts[] = sprintf( __( '%d completed.', 'aio-page-builder' ), $completed );
		}
		if ( $failed > 0 ) {
			$message_parts[] = sprintf( __( '%d failed.', 'aio-page-builder' ), $failed );
		}
		if ( $refused > 0 ) {
			$message_parts[] = sprintf( __( '%d refused.', 'aio-page-builder' ), $refused );
		}
		$message = implode( ' ', $message_parts ) !== '' ? implode( ' ', $message_parts ) : __( 'No results.', 'aio-page-builder' );

		return new Bulk_Template_Page_Build_Result(
			$plan_id,
			$batch_id,
			$overall_status,
			$job_refs,
			$item_results,
			$slug_collisions,
			$completed,
			$failed,
			$refused,
			$partial_failure,
			$retry_eligible_item_ids,
			$message,
			array(
				'envelope_count'   => count( $job_refs ) + count( $item_results_pre ),
				'create_page_count' => count( $item_results ),
			)
		);
	}

	private function error_result( string $plan_id, string $batch_id, string $message, array $snapshot = array() ): Bulk_Template_Page_Build_Result {
		return new Bulk_Template_Page_Build_Result(
			$plan_id,
			$batch_id,
			Bulk_Template_Page_Build_Result::STATUS_ERROR,
			array(),
			array(),
			array(),
			0,
			0,
			0,
			false,
			array(),
			$message,
			$snapshot
		);
	}

	private function actor_ref_from_context( array $actor_context ): string {
		$type = isset( $actor_context['actor_type'] ) && is_string( $actor_context['actor_type'] ) ? $actor_context['actor_type'] : 'user';
		$id   = isset( $actor_context['actor_id'] ) && is_string( $actor_context['actor_id'] ) ? $actor_context['actor_id'] : '';
		return $type . ':' . ( $id !== '' ? $id : '0' );
	}

	private function generate_batch_id(): string {
		return gmdate( 'Ymd\THis' ) . '_' . wp_rand( 1000, 9999 );
	}
}
