<?php
/**
 * Template-aware finalization summaries and completion state (spec §59.10, §1.9.9, §1.9.10; Prompt 208).
 *
 * Builds finalization_summary (counts by action/outcome), template_execution_closure_record (trace links
 * to built/replaced pages and template families), run_completion_state (complete|warning|partial|failed),
 * and one-pager retention summary. No mutation; summarizes what happened for support and export.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Finalize;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Execution\Pages\Form_Provider_Dependency_Validator;
use AIOPageBuilder\Domain\FormProvider\Form_Integration_Definitions;

/**
 * Builds template-aware finalization summaries from a plan definition.
 * When Form_Provider_Dependency_Validator is provided, closure records include form_dependency for request-form / form_embed templates (Prompt 230).
 */
final class Template_Finalization_Service {

	/** @var Form_Provider_Dependency_Validator|null */
	private ?Form_Provider_Dependency_Validator $form_provider_dependency_validator;

	public function __construct( ?Form_Provider_Dependency_Validator $form_provider_dependency_validator = null ) {
		$this->form_provider_dependency_validator = $form_provider_dependency_validator;
	}

	/**
	 * Builds finalization summary, template closure record, and run completion state from plan definition.
	 *
	 * @param array<string, mixed>             $definition Full plan definition (after execution; items have status and optional execution_artifact).
	 * @param array<int, array<string, mixed>> $conflicts Optional conflicts from finalization gate (when blocked).
	 * @return Template_Finalization_Result
	 */
	public function build( array $definition, array $conflicts = array() ): Template_Finalization_Result {
		$counts         = $this->count_by_action_and_outcome( $definition );
		$closure_record = $this->build_template_execution_closure_record( $definition );
		$one_pager      = $this->build_one_pager_retention_summary( $definition );
		$run_state      = $this->compute_run_completion_state( $counts, $conflicts );

		$finalization_summary = array(
			'created'                       => $counts['created'],
			'replaced'                      => $counts['replaced'],
			'updated'                       => $counts['updated'],
			'skipped'                       => $counts['skipped'],
			'failed'                        => $counts['failed'],
			'pending'                       => $counts['pending'],
			'published'                     => 0,
			'completed_without_publication' => 0,
			'blocked'                       => count( $conflicts ),
			'denied'                        => $counts['skipped'],
		);

		return new Template_Finalization_Result(
			$finalization_summary,
			$closure_record,
			$run_state,
			$one_pager
		);
	}

	/**
	 * Counts items by action type and outcome (created, replaced, updated, skipped, failed, pending).
	 *
	 * @param array<string, mixed> $definition
	 * @return array{created: int, replaced: int, updated: int, skipped: int, failed: int, pending: int}
	 */
	private function count_by_action_and_outcome( array $definition ): array {
		$out   = array(
			'created'  => 0,
			'replaced' => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'pending'  => 0,
		);
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
				$status    = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
				$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
				if ( $status === Build_Plan_Item_Statuses::COMPLETED ) {
					if ( $item_type === Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE ) {
						++$out['created'];
					} elseif ( $item_type === Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE ) {
						++$out['replaced'];
					} elseif ( in_array( $item_type, array( Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE, Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN, Build_Plan_Item_Schema::ITEM_TYPE_SEO ), true ) ) {
						++$out['updated'];
					}
				} elseif ( $status === Build_Plan_Item_Statuses::REJECTED ) {
					++$out['skipped'];
				} elseif ( $status === Build_Plan_Item_Statuses::FAILED ) {
					++$out['failed'];
				} elseif ( in_array( $status, array( Build_Plan_Item_Statuses::APPROVED, Build_Plan_Item_Statuses::IN_PROGRESS ), true ) ) {
					++$out['pending'];
				} else {
					++$out['pending'];
				}
			}
		}
		return $out;
	}

	/**
	 * Builds template execution closure record: trace links (template_key, template_family, post_id, one_pager ref).
	 *
	 * @param array<string, mixed> $definition
	 * @return array<int, array<string, mixed>>
	 */
	private function build_template_execution_closure_record( array $definition ): array {
		$record = array();
		$steps  = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
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
				$status    = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
				$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
				if ( $status !== Build_Plan_Item_Statuses::COMPLETED ) {
					continue;
				}
				$artifact     = isset( $item['execution_artifact'] ) && is_array( $item['execution_artifact'] ) ? $item['execution_artifact'] : array();
				$payload      = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
				$post_id      = isset( $artifact['target_post_id'] ) && is_numeric( $artifact['target_post_id'] ) ? (int) $artifact['target_post_id'] : ( isset( $artifact['post_id'] ) && is_numeric( $artifact['post_id'] ) ? (int) $artifact['post_id'] : 0 );
				$template_key = isset( $payload['template_key'] ) && is_string( $payload['template_key'] ) ? trim( $payload['template_key'] ) : '';
				if ( $template_key === '' && isset( $payload['target_template_key'] ) && is_string( $payload['target_template_key'] ) ) {
					$template_key = trim( $payload['target_template_key'] );
				}
				if ( $template_key === '' ) {
					$ctx          = isset( $artifact['template_build_execution_result'] ) && is_array( $artifact['template_build_execution_result'] ) ? $artifact['template_build_execution_result'] : ( isset( $artifact['template_replacement_execution_result'] ) && is_array( $artifact['template_replacement_execution_result'] ) ? $artifact['template_replacement_execution_result'] : array() );
					$template_key = isset( $ctx['template_key'] ) && is_string( $ctx['template_key'] ) ? trim( $ctx['template_key'] ) : '';
				}
				$template_family = '';
				if ( isset( $artifact['template_build_execution_result']['template_family'] ) && is_string( $artifact['template_build_execution_result']['template_family'] ) ) {
					$template_family = trim( $artifact['template_build_execution_result']['template_family'] );
				} elseif ( isset( $artifact['template_replacement_execution_result']['template_family'] ) && is_string( $artifact['template_replacement_execution_result']['template_family'] ) ) {
					$template_family = trim( $artifact['template_replacement_execution_result']['template_family'] );
				}
				$one_pager_ref = '';
				if ( isset( $artifact['one_pager_ref'] ) && is_string( $artifact['one_pager_ref'] ) ) {
					$one_pager_ref = trim( $artifact['one_pager_ref'] );
				} elseif ( isset( $artifact['template_build_execution_result']['one_pager_ref'] ) && is_string( $artifact['template_build_execution_result']['one_pager_ref'] ) ) {
					$one_pager_ref = trim( $artifact['template_build_execution_result']['one_pager_ref'] );
				}
				$action_taken    = $item_type === Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE ? 'create' : ( $item_type === Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE ? 'replace' : 'update' );
				$form_dependency = false;
				if ( $template_key !== '' && $this->form_provider_dependency_validator !== null ) {
					$form_dependency = $template_key === Form_Integration_Definitions::REQUEST_PAGE_TEMPLATE_KEY
						|| $this->form_provider_dependency_validator->template_uses_form_sections( $template_key );
				}
				$record[] = array_filter(
					array(
						'plan_item_id'    => (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ),
						'item_type'       => $item_type,
						'action_taken'    => $action_taken,
						'template_key'    => $template_key !== '' ? $template_key : null,
						'template_family' => $template_family !== '' ? $template_family : null,
						'post_id'         => $post_id > 0 ? $post_id : null,
						'one_pager_ref'   => $one_pager_ref !== '' ? $one_pager_ref : null,
						'form_dependency' => $form_dependency ? true : null,
					),
					function ( $v ) {
						return $v !== null && $v !== '';
					}
				);
			}
		}
		return $record;
	}

	/**
	 * Builds one-pager retention summary (template_key => ref or count) for supportability.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	private function build_one_pager_retention_summary( array $definition ): array {
		$by_template = array();
		$steps       = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
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
				if ( ! is_array( $item ) || (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' ) !== Build_Plan_Item_Statuses::COMPLETED ) {
					continue;
				}
				$artifact     = isset( $item['execution_artifact'] ) && is_array( $item['execution_artifact'] ) ? $item['execution_artifact'] : array();
				$template_key = '';
				if ( isset( $artifact['template_build_execution_result']['template_key'] ) && is_string( $artifact['template_build_execution_result']['template_key'] ) ) {
					$template_key = trim( $artifact['template_build_execution_result']['template_key'] );
				} elseif ( isset( $artifact['template_replacement_execution_result']['template_key'] ) && is_string( $artifact['template_replacement_execution_result']['template_key'] ) ) {
					$template_key = trim( $artifact['template_replacement_execution_result']['template_key'] );
				}
				if ( $template_key === '' ) {
					$payload      = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
					$template_key = isset( $payload['template_key'] ) && is_string( $payload['template_key'] ) ? trim( $payload['template_key'] ) : '';
				}
				if ( $template_key !== '' ) {
					if ( ! isset( $by_template[ $template_key ] ) ) {
						$by_template[ $template_key ] = array(
							'count'          => 0,
							'one_pager_refs' => array(),
						);
					}
					++$by_template[ $template_key ]['count'];
					$ref = isset( $artifact['one_pager_ref'] ) && is_string( $artifact['one_pager_ref'] ) ? trim( $artifact['one_pager_ref'] ) : ( isset( $artifact['template_build_execution_result']['one_pager_ref'] ) && is_string( $artifact['template_build_execution_result']['one_pager_ref'] ) ? trim( $artifact['template_build_execution_result']['one_pager_ref'] ) : '' );
					if ( $ref !== '' && ! in_array( $ref, $by_template[ $template_key ]['one_pager_refs'], true ) ) {
						$by_template[ $template_key ]['one_pager_refs'][] = $ref;
					}
				}
			}
		}
		return $by_template;
	}

	/**
	 * Computes run_completion_state: complete, warning, partial, failed.
	 *
	 * @param array{created: int, replaced: int, updated: int, skipped: int, failed: int, pending: int} $counts
	 * @param array<int, array<string, mixed>>                                                          $conflicts
	 * @return string
	 */
	private function compute_run_completion_state( array $counts, array $conflicts ): string {
		if ( count( $conflicts ) > 0 ) {
			return Template_Finalization_Result::RUN_STATE_FAILED;
		}
		$total_done  = $counts['created'] + $counts['replaced'] + $counts['updated'];
		$has_failed  = $counts['failed'] > 0;
		$has_pending = $counts['pending'] > 0;
		if ( $has_failed && $total_done === 0 ) {
			return Template_Finalization_Result::RUN_STATE_FAILED;
		}
		if ( $has_failed ) {
			return $total_done > 0 ? Template_Finalization_Result::RUN_STATE_WARNING : Template_Finalization_Result::RUN_STATE_PARTIAL;
		}
		if ( $has_pending && $total_done > 0 ) {
			return Template_Finalization_Result::RUN_STATE_PARTIAL;
		}
		if ( $total_done > 0 && ! $has_pending ) {
			return Template_Finalization_Result::RUN_STATE_COMPLETE;
		}
		return Template_Finalization_Result::RUN_STATE_PARTIAL;
	}
}
