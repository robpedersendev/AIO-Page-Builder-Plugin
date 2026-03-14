<?php
/**
 * Finalization execution: publish-ready validation, conflict detection, publish/complete, plan state update (spec §37, §40.10, §59.10; Prompt 084, 208).
 *
 * Validates publish readiness; detects conflicts; performs publish transitions for page items
 * with execution_artifact; updates Build Plan to completed with completion summary.
 * Uses Template_Finalization_Service for finalization_summary, template_execution_closure_record,
 * run_completion_state, and one_pager_retention_summary when available.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Finalize\Template_Finalization_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository_Interface;

/**
 * Governed finalization: only publish-ready items; conflict detection blocks when required.
 */
final class Finalization_Job_Service {

	/** Item types that can have a publish transition (page-related). */
	private const PUBLISHABLE_ITEM_TYPES = array(
		Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
		Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE,
	);

	/** @var Build_Plan_Repository_Interface */
	private $plan_repository;

	/** @var Template_Finalization_Service|null */
	private $template_finalization_service;

	public function __construct(
		Build_Plan_Repository_Interface $plan_repository,
		?Template_Finalization_Service $template_finalization_service = null
	) {
		$this->plan_repository = $plan_repository;
		$this->template_finalization_service = $template_finalization_service;
	}

	/**
	 * Runs finalization: validate readiness, detect conflicts, publish where applicable, update plan.
	 *
	 * @param array<string, mixed> $envelope Plan-level envelope (plan_id, actor context; plan_item_id empty).
	 * @return Finalization_Result
	 */
	public function run( array $envelope ): Finalization_Result {
		$plan_id = isset( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] )
			? trim( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] )
			: '';
		if ( $plan_id === '' ) {
			return Finalization_Result::failure( __( 'Missing plan ID.', 'aio-page-builder' ), array( Execution_Action_Contract::ERROR_INVALID_ENVELOPE ) );
		}

		$plan_record = $this->plan_repository->get_by_key( $plan_id );
		if ( $plan_record === null ) {
			return Finalization_Result::failure( __( 'Build Plan not found.', 'aio-page-builder' ), array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ) );
		}
		$plan_post_id = (int) ( $plan_record['id'] ?? 0 );
		if ( $plan_post_id <= 0 ) {
			return Finalization_Result::failure( __( 'Invalid plan record.', 'aio-page-builder' ), array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ) );
		}

		$definition = $this->plan_repository->get_plan_definition( $plan_post_id );
		if ( empty( $definition ) ) {
			return Finalization_Result::failure( __( 'Plan definition not found.', 'aio-page-builder' ), array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ) );
		}

		$plan_status = isset( $definition[ Build_Plan_Schema::KEY_STATUS ] ) && is_string( $definition[ Build_Plan_Schema::KEY_STATUS ] ) ? $definition[ Build_Plan_Schema::KEY_STATUS ] : '';
		if ( ! in_array( $plan_status, array( Build_Plan_Schema::STATUS_APPROVED, Build_Plan_Schema::STATUS_IN_PROGRESS ), true ) ) {
			return Finalization_Result::failure(
				__( 'Plan is not in an executable state for finalization.', 'aio-page-builder' ),
				array( 'plan_not_ready' )
			);
		}

		$counts = $this->count_items_by_outcome( $definition );
		$conflicts = $this->detect_conflicts( $definition );

		$template_result = $this->template_finalization_service !== null
			? $this->template_finalization_service->build( $definition, $conflicts )
			: null;

		if ( ! empty( $conflicts ) ) {
			$summary = array(
				'published'                        => 0,
				'completed_without_publication'    => $counts['completed'],
				'blocked'                          => count( $conflicts ),
				'denied'                           => $counts['rejected'],
				'failed'                           => $counts['failed'],
			);
			$actor_ref = $this->resolve_actor_ref( $envelope );
			$artifacts = array(
				'completion_summary' => $summary,
				'conflicts'         => $conflicts,
				'finalized_at'      => '',
				'actor_ref'         => $actor_ref,
			);
			if ( $template_result !== null ) {
				$artifacts['finalization_summary']              = $template_result->get_finalization_summary();
				$artifacts['template_execution_closure_record'] = $template_result->get_template_execution_closure_record();
				$artifacts['run_completion_state']              = $template_result->get_run_completion_state();
				$artifacts['one_pager_retention_summary']       = $template_result->get_one_pager_retention_summary();
			}
			return Finalization_Result::failure(
				__( 'Conflicts detected; finalization blocked.', 'aio-page-builder' ),
				array( 'conflicts_block' ),
				$artifacts
			);
		}

		$published = $this->publish_ready_pages( $definition );
		$finalized_at = gmdate( 'c' );
		$actor_ref = $this->resolve_actor_ref( $envelope );

		$definition[ Build_Plan_Schema::KEY_STATUS ]      = Build_Plan_Schema::STATUS_COMPLETED;
		$definition[ Build_Plan_Schema::KEY_COMPLETED_AT ] = $finalized_at;
		$definition[ Build_Plan_Schema::KEY_ACTOR_REFS ]  = array( $actor_ref );
		$definition['completion_summary'] = array(
			'published'                        => $published,
			'completed_without_publication'    => max( 0, $counts['completed'] - $published ),
			'blocked'                          => 0,
			'denied'                           => $counts['rejected'],
			'failed'                           => $counts['failed'],
		);

		if ( $template_result !== null ) {
			$definition['finalization_summary']              = $template_result->get_finalization_summary();
			$definition['template_execution_closure_record'] = $template_result->get_template_execution_closure_record();
			$definition['run_completion_state']              = $template_result->get_run_completion_state();
			$definition['one_pager_retention_summary']       = $template_result->get_one_pager_retention_summary();
		}

		if ( ! isset( $definition['finalization_history'] ) || ! is_array( $definition['finalization_history'] ) ) {
			$definition['finalization_history'] = array();
		}
		$history_entry = array(
			'finalized_at' => $finalized_at,
			'actor_ref'    => $actor_ref,
			'completion_summary' => $definition['completion_summary'],
		);
		if ( $template_result !== null ) {
			$history_entry['run_completion_state'] = $template_result->get_run_completion_state();
		}
		$definition['finalization_history'][] = $history_entry;

		$saved = $this->plan_repository->save_plan_definition( $plan_post_id, $definition );
		if ( ! $saved ) {
			return Finalization_Result::failure( __( 'Failed to persist finalization state.', 'aio-page-builder' ), array( 'storage_failed' ) );
		}

		$summary = $definition['completion_summary'];
		$extra = array();
		if ( $template_result !== null ) {
			$extra['finalization_summary']              = $template_result->get_finalization_summary();
			$extra['template_execution_closure_record'] = $template_result->get_template_execution_closure_record();
			$extra['run_completion_state']              = $template_result->get_run_completion_state();
			$extra['one_pager_retention_summary']       = $template_result->get_one_pager_retention_summary();
		}
		return Finalization_Result::success( $finalized_at, $summary, array(), $actor_ref, $extra );
	}

	/**
	 * Counts items by terminal/outcome status across all steps.
	 *
	 * @param array<string, mixed> $definition
	 * @return array{pending: int, approved: int, completed: int, rejected: int, failed: int}
	 */
	private function count_items_by_outcome( array $definition ): array {
		$out = array( 'pending' => 0, 'approved' => 0, 'completed' => 0, 'rejected' => 0, 'failed' => 0 );
		$steps = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		foreach ( $steps as $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}
			$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
				? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
				: array();
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$status = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
				if ( $status === Build_Plan_Item_Statuses::COMPLETED ) {
					++$out['completed'];
				} elseif ( $status === Build_Plan_Item_Statuses::REJECTED ) {
					++$out['rejected'];
				} elseif ( $status === Build_Plan_Item_Statuses::FAILED ) {
					++$out['failed'];
				} elseif ( $status === Build_Plan_Item_Statuses::APPROVED || $status === Build_Plan_Item_Statuses::IN_PROGRESS ) {
					++$out['approved'];
				} else {
					++$out['pending'];
				}
			}
		}
		return $out;
	}

	/**
	 * Detects conflicts that must block finalization (spec §37.4).
	 *
	 * @param array<string, mixed> $definition
	 * @return array<int, array<string, mixed>>
	 */
	private function detect_conflicts( array $definition ): array {
		$conflicts = array();
		$slugs = array();
		$steps = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		foreach ( $steps as $step_index => $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}
			$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
				? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
				: array();
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$status = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
				if ( $status !== Build_Plan_Item_Statuses::COMPLETED ) {
					continue;
				}
				$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
				$slug = isset( $payload['page_slug_candidate'] ) && is_string( $payload['page_slug_candidate'] ) ? trim( $payload['page_slug_candidate'] ) : '';
				if ( $slug === '' && isset( $payload['proposed_slug'] ) && is_string( $payload['proposed_slug'] ) ) {
					$slug = trim( $payload['proposed_slug'] );
				}
				if ( $slug === '' && isset( $payload['target_slug'] ) && is_string( $payload['target_slug'] ) ) {
					$slug = trim( $payload['target_slug'] );
				}
				if ( $slug !== '' ) {
					if ( isset( $slugs[ $slug ] ) ) {
						$conflicts[] = array( 'type' => 'slug_conflict', 'slug' => $slug, 'message' => __( 'Duplicate slug in plan.', 'aio-page-builder' ) );
					}
					$slugs[ $slug ] = true;
				}
			}
		}
		return $conflicts;
	}

	/**
	 * Publishes page posts that have execution_artifact.post_id and are draft. Returns count published.
	 *
	 * @param array<string, mixed> $definition
	 * @return int
	 */
	private function publish_ready_pages( array $definition ): int {
		$published = 0;
		$steps = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		foreach ( $steps as $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}
			$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
				? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
				: array();
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$status = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
				$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
				if ( $status !== Build_Plan_Item_Statuses::COMPLETED || ! in_array( $item_type, self::PUBLISHABLE_ITEM_TYPES, true ) ) {
					continue;
				}
				$artifact = isset( $item['execution_artifact'] ) && is_array( $item['execution_artifact'] ) ? $item['execution_artifact'] : array();
				$post_id = isset( $artifact['post_id'] ) && is_numeric( $artifact['post_id'] ) ? (int) $artifact['post_id'] : ( isset( $artifact['target_post_id'] ) && is_numeric( $artifact['target_post_id'] ) ? (int) $artifact['target_post_id'] : 0 );
				if ( $post_id <= 0 ) {
					continue;
				}
				$post = \get_post( $post_id );
				if ( ! $post instanceof \WP_Post || $post->post_type !== 'page' ) {
					continue;
				}
				if ( $post->post_status === 'publish' ) {
					continue;
				}
				$updated = \wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ), true );
				if ( ! \is_wp_error( $updated ) && $updated > 0 ) {
					++$published;
				}
			}
		}
		return $published;
	}

	private function resolve_actor_ref( array $envelope ): string {
		$actor = $envelope[ Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT ] ?? array();
		if ( ! is_array( $actor ) ) {
			return '';
		}
		$id = $actor[ Execution_Action_Contract::ACTOR_ACTOR_ID ] ?? null;
		if ( is_string( $id ) && $id !== '' ) {
			return $id;
		}
		$user = \wp_get_current_user();
		if ( $user instanceof \WP_User && $user->ID > 0 ) {
			return 'user:' . (string) $user->ID;
		}
		return '';
	}
}
