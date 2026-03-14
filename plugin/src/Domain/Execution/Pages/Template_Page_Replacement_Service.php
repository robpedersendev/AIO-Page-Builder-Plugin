<?php
/**
 * Template-driven existing-page replacement with snapshot and traceability (spec §32, §32.7, §32.9, §59.10, §59.11; Prompt 196).
 *
 * Wraps Replace_Page_Job_Service; enriches result with template_family, replacement_trace_record,
 * and template_replacement_execution_result for snapshot-backed audit and rollback compatibility.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Pages;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Jobs\Replace_Page_Job_Service_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Replace_Page_Result;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;

/**
 * Runs existing-page replace/rebuild via job service and produces stable template_replacement_execution_result payload.
 */
final class Template_Page_Replacement_Service implements Replace_Page_Job_Service_Interface {

	/** @var Replace_Page_Job_Service_Interface */
	private $job_service;

	/** @var Page_Template_Repository */
	private $page_template_repository;

	public function __construct(
		Replace_Page_Job_Service_Interface $job_service,
		Page_Template_Repository $page_template_repository
	) {
		$this->job_service             = $job_service;
		$this->page_template_repository = $page_template_repository;
	}

	/**
	 * Runs existing-page replacement/rebuild and enriches result with template_replacement_execution_result and replacement_trace_record.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope (target_reference, snapshot_ref when required).
	 * @return Replace_Page_Result Result with artifacts containing template_replacement_execution_result and replacement_trace_record.
	 */
	public function run( array $envelope ): Replace_Page_Result {
		$result = $this->job_service->run( $envelope );

		$template_key = $result->get_artifacts()['template_key'] ?? '';
		if ( $template_key === '' ) {
			$target = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
				? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
				: array();
			$template_key = $this->resolve_template_key_from_target( $target );
		}

		if ( $result->is_success() ) {
			$template_def = $this->page_template_repository->get_definition_by_key( $template_key );
			$template_family = is_array( $template_def ) && isset( $template_def['template_family'] ) && is_string( $template_def['template_family'] )
				? $template_def['template_family']
				: '';
			$assignment_count = isset( $result->get_artifacts()['assignment_count'] ) && is_numeric( $result->get_artifacts()['assignment_count'] )
				? (int) $result->get_artifacts()['assignment_count']
				: 0;
			$superseded = $result->get_superseded_post_id();
			$trace = $this->build_replacement_trace_record(
				$result->get_target_post_id(),
				$superseded,
				$result->get_snapshot_ref(),
				$template_key
			);
			$build_result = Template_Page_Replacement_Result::success(
				$result->get_target_post_id(),
				$superseded,
				$result->get_snapshot_ref(),
				$template_key,
				$template_family,
				$trace,
				$assignment_count,
				array()
			);
		} else {
			$build_result = Template_Page_Replacement_Result::failure(
				$result->get_message(),
				$result->get_errors(),
				$result->get_snapshot_ref(),
				$template_key
			);
		}

		$artifacts = array_merge( $result->get_artifacts(), array(
			'template_replacement_execution_result' => $build_result->to_array(),
			'replacement_trace_record'               => $build_result->get_replacement_trace_record(),
		) );
		return new Replace_Page_Result(
			$result->is_success(),
			$result->get_target_post_id(),
			$result->get_superseded_post_id(),
			$result->get_snapshot_ref(),
			$result->get_message(),
			$result->get_errors(),
			$artifacts
		);
	}

	/**
	 * Builds replacement_trace_record for audit and rollback (spec §32.9, §59.11).
	 *
	 * @param int    $new_post_id    Target (new or updated) page ID.
	 * @param int    $original_post_id Superseded page ID (0 when rebuild in place).
	 * @param string $snapshot_pre_id Pre-change snapshot ID.
	 * @param string $template_key   Template used.
	 * @return array<string, mixed>
	 */
	private function build_replacement_trace_record( int $new_post_id, int $original_post_id, string $snapshot_pre_id, string $template_key ): array {
		$original = $original_post_id > 0 ? $original_post_id : $new_post_id;
		$archive_status = $original_post_id > 0 ? 'private' : 'in_place';
		return array(
			'original_post_id'  => $original,
			'new_post_id'      => $new_post_id,
			'archive_status'   => $archive_status,
			'template_key'     => $template_key,
			'snapshot_pre_id'  => $snapshot_pre_id,
		);
	}

	private function resolve_template_key_from_target( array $target ): string {
		if ( isset( $target['template_key'] ) && is_string( $target['template_key'] ) && trim( $target['template_key'] ) !== '' ) {
			return trim( $target['template_key'] );
		}
		$ref = $target['template_ref'] ?? null;
		if ( is_array( $ref ) && isset( $ref['type'] ) && (string) $ref['type'] === 'internal_key' && isset( $ref['value'] ) && is_string( $ref['value'] ) ) {
			return trim( $ref['value'] );
		}
		return '';
	}
}
