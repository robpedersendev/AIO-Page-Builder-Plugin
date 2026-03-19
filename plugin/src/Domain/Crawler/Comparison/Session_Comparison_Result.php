<?php
/**
 * Session-level recrawl comparison result (spec §24.17).
 * Machine-readable: prior/new run ids, counts, list of page change summaries.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Comparison;

defined( 'ABSPATH' ) || exit;

/**
 * Result of comparing two crawl sessions. Used for diagnostics and planning refresh input.
 */
final class Session_Comparison_Result {

	/** @var string Prior crawl run id. */
	public $prior_run_id;

	/** @var string New crawl run id. */
	public $new_run_id;

	/** @var int Count of pages only in new run. */
	public $added_count;

	/** @var int Count of pages only in prior run. */
	public $removed_count;

	/** @var int Count of pages in both with material changes (including reclassified). */
	public $changed_count;

	/** @var int Count of pages in both with no material change. */
	public $unchanged_count;

	/** @var int Count of pages whose classification changed. */
	public $reclassified_count;

	/** @var int Count of meaningful pages in prior run (classification = meaningful). */
	public $meaningful_count_prior;

	/** @var int Count of meaningful pages in new run. */
	public $meaningful_count_new;

	/** @var array<int, Page_Change_Summary> */
	public $page_changes;

	/**
	 * @param string                    $prior_run_id         Prior crawl run id.
	 * @param string                    $new_run_id           New crawl run id.
	 * @param int                       $added_count          Pages only in new.
	 * @param int                       $removed_count         Pages only in prior.
	 * @param int                       $changed_count         Pages in both with changes.
	 * @param int                       $unchanged_count       Pages in both unchanged.
	 * @param int                       $reclassified_count   Pages with classification change.
	 * @param int                       $meaningful_count_prior Meaningful pages in prior.
	 * @param int                       $meaningful_count_new  Meaningful pages in new.
	 * @param array<int, Page_Change_Summary> $page_changes      Per-page change summaries.
	 */
	public function __construct(
		string $prior_run_id,
		string $new_run_id,
		int $added_count,
		int $removed_count,
		int $changed_count,
		int $unchanged_count,
		int $reclassified_count,
		int $meaningful_count_prior,
		int $meaningful_count_new,
		array $page_changes
	) {
		$this->prior_run_id           = $prior_run_id;
		$this->new_run_id             = $new_run_id;
		$this->added_count            = $added_count;
		$this->removed_count          = $removed_count;
		$this->changed_count          = $changed_count;
		$this->unchanged_count        = $unchanged_count;
		$this->reclassified_count     = $reclassified_count;
		$this->meaningful_count_prior = $meaningful_count_prior;
		$this->meaningful_count_new   = $meaningful_count_new;
		$this->page_changes           = $page_changes;
	}

	/**
	 * Returns a machine-readable array for logging or API.
	 *
	 * @return array{prior_run_id: string, new_run_id: string, added_count: int, removed_count: int, changed_count: int, unchanged_count: int, reclassified_count: int, meaningful_count_prior: int, meaningful_count_new: int, page_changes: array}
	 */
	public function to_array(): array {
		return array(
			'prior_run_id'           => $this->prior_run_id,
			'new_run_id'             => $this->new_run_id,
			'added_count'            => $this->added_count,
			'removed_count'          => $this->removed_count,
			'changed_count'          => $this->changed_count,
			'unchanged_count'        => $this->unchanged_count,
			'reclassified_count'     => $this->reclassified_count,
			'meaningful_count_prior' => $this->meaningful_count_prior,
			'meaningful_count_new'   => $this->meaningful_count_new,
			'page_changes'           => array_map(
				function ( Page_Change_Summary $p ) {
					return $p->to_array(); },
				$this->page_changes
			),
		);
	}
}
