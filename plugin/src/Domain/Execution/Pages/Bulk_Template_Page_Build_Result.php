<?php
/**
 * Stable bulk template new-page build result payload (spec §33.6, §33.7, §33.9, §33.10; Prompt 195).
 *
 * Immutable DTO: plan_id, batch_id, status, job_refs, item_results (per-item status/history),
 * slug_collisions, completed/failed/refused counts, partial_failure, retry_eligible_item_ids, message.
 * Used for bulk Build All / Build Selected observability and retry-safe recovery.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable bulk template page-build result. Convertible to array for API and logging.
 */
final class Bulk_Template_Page_Build_Result {

	public const STATUS_QUEUED   = 'queued';
	public const STATUS_COMPLETED = 'completed';
	public const STATUS_PARTIAL  = 'partial';
	public const STATUS_REFUSED  = 'refused';
	public const STATUS_ERROR    = 'error';

	/** @var string */
	private $plan_id;

	/** @var string */
	private $batch_id;

	/** @var string */
	private $status;

	/** @var array<int, string> */
	private $job_refs;

	/**
	 * Per plan_item_id => array(status, job_ref, post_id, template_key, slug_conflict, failure_reason, retry_eligible).
	 * @var array<string, array<string, mixed>>
	 */
	private $item_results;

	/** @var list<string> Plan item IDs or slugs that had slug conflict (pre-validation or execution). */
	private $slug_collisions;

	/** @var int */
	private $completed_count;

	/** @var int */
	private $failed_count;

	/** @var int */
	private $refused_count;

	/** @var bool */
	private $partial_failure;

	/** @var list<string> Plan item IDs that failed and are retry-eligible (spec §33.10). */
	private $retry_eligible_item_ids;

	/** @var string */
	private $message;

	/** @var array<string, mixed> Optional bulk_template_build_plan snapshot for traceability. */
	private $bulk_plan_snapshot;

	/**
	 * @param string                                    $plan_id
	 * @param string                                    $batch_id
	 * @param string                                    $status
	 * @param array<int, string>                        $job_refs
	 * @param array<string, array<string, mixed>>      $item_results
	 * @param list<string>                             $slug_collisions
	 * @param int                                       $completed_count
	 * @param int                                       $failed_count
	 * @param int                                       $refused_count
	 * @param bool                                      $partial_failure
	 * @param list<string>                             $retry_eligible_item_ids
	 * @param string                                    $message
	 * @param array<string, mixed>                      $bulk_plan_snapshot
	 */
	public function __construct(
		string $plan_id,
		string $batch_id,
		string $status,
		array $job_refs = array(),
		array $item_results = array(),
		array $slug_collisions = array(),
		int $completed_count = 0,
		int $failed_count = 0,
		int $refused_count = 0,
		bool $partial_failure = false,
		array $retry_eligible_item_ids = array(),
		string $message = '',
		array $bulk_plan_snapshot = array()
	) {
		$this->plan_id                  = $plan_id;
		$this->batch_id                 = $batch_id;
		$this->status                   = $status;
		$this->job_refs                 = $job_refs;
		$this->item_results             = $item_results;
		$this->slug_collisions          = $slug_collisions;
		$this->completed_count          = $completed_count;
		$this->failed_count             = $failed_count;
		$this->refused_count            = $refused_count;
		$this->partial_failure          = $partial_failure;
		$this->retry_eligible_item_ids   = $retry_eligible_item_ids;
		$this->message                  = $message;
		$this->bulk_plan_snapshot        = $bulk_plan_snapshot;
	}

	public function get_plan_id(): string {
		return $this->plan_id;
	}

	public function get_batch_id(): string {
		return $this->batch_id;
	}

	public function get_status(): string {
		return $this->status;
	}

	/** @return array<int, string> */
	public function get_job_refs(): array {
		return $this->job_refs;
	}

	/** @return array<string, array<string, mixed>> */
	public function get_item_results(): array {
		return $this->item_results;
	}

	/** @return list<string> */
	public function get_slug_collisions(): array {
		return $this->slug_collisions;
	}

	public function get_completed_count(): int {
		return $this->completed_count;
	}

	public function get_failed_count(): int {
		return $this->failed_count;
	}

	public function get_refused_count(): int {
		return $this->refused_count;
	}

	public function is_partial_failure(): bool {
		return $this->partial_failure;
	}

	/** @return list<string> */
	public function get_retry_eligible_item_ids(): array {
		return $this->retry_eligible_item_ids;
	}

	public function get_message(): string {
		return $this->message;
	}

	/** @return array<string, mixed> */
	public function get_bulk_plan_snapshot(): array {
		return $this->bulk_plan_snapshot;
	}

	/**
	 * Stable payload for API and logging (bulk_template_build_result).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'plan_id'                   => $this->plan_id,
			'batch_id'                  => $this->batch_id,
			'status'                    => $this->status,
			'job_refs'                  => $this->job_refs,
			'item_results'               => $this->item_results,
			'slug_collisions'           => $this->slug_collisions,
			'completed_count'           => $this->completed_count,
			'failed_count'              => $this->failed_count,
			'refused_count'             => $this->refused_count,
			'partial_failure'           => $this->partial_failure,
			'retry_eligible_item_ids'   => $this->retry_eligible_item_ids,
			'message'                   => $this->message,
			'bulk_plan_snapshot'         => $this->bulk_plan_snapshot,
		);
	}

	/**
	 * Returns an example bulk_template_build_result payload for documentation and tests.
	 *
	 * @return array<string, mixed>
	 */
	public static function example_payload(): array {
		return array(
			'plan_id'                   => 'plan_abc123',
			'batch_id'                  => '20250113T143022_4567',
			'status'                    => self::STATUS_PARTIAL,
			'job_refs'                  => array( 'job_item_1_batch', 'job_item_2_batch' ),
			'item_results'               => array(
				'item_1' => array(
					'status'          => 'completed',
					'job_ref'         => 'job_item_1_batch',
					'post_id'         => 101,
					'template_key'    => 'tpl_services_hub',
					'slug_conflict'   => false,
					'failure_reason'  => '',
					'retry_eligible'  => false,
				),
				'item_2' => array(
					'status'          => 'failed',
					'job_ref'         => 'job_item_2_batch',
					'post_id'         => 0,
					'template_key'    => 'tpl_child_detail',
					'slug_conflict'   => false,
					'failure_reason'  => 'Page template not found.',
					'retry_eligible'  => true,
				),
			),
			'slug_collisions'           => array(),
			'completed_count'           => 1,
			'failed_count'              => 1,
			'refused_count'             => 0,
			'partial_failure'           => true,
			'retry_eligible_item_ids'   => array( 'item_2' ),
			'message'                   => '1 completed. 1 failed.',
			'bulk_plan_snapshot'         => array( 'envelope_count' => 2, 'create_page_count' => 2 ),
		);
	}
}
