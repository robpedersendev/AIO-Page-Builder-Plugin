<?php
/**
 * Filter result value object for large-library queries (spec §55.8).
 * Holds row summaries, pagination, and optional filter counts for directory IA.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Shared;

defined( 'ABSPATH' ) || exit;

/**
 * Result of a filtered, paginated library query.
 * Row summaries are minimal payloads for directory/state builders; full definitions remain in registry.
 */
final class Large_Library_Filter_Result {

	/** @var array<int, array<string, mixed>> Row summaries (internal_key, name, status, taxonomy fields, etc.). */
	private array $rows;

	/** @var Large_Library_Pagination */
	private Large_Library_Pagination $pagination;

	/** @var array<string, array<string, int>> Filter dimension => value => count (e.g. category => ['hero_intro' => 12]). */
	private array $filter_counts;

	/** @var int Total matching before pagination (same as pagination->get_total()). */
	private int $total_matching;

	/**
	 * @param array<int, array<string, mixed>>  $rows          Row summaries for directory/IA.
	 * @param Large_Library_Pagination          $pagination    Pagination metadata.
	 * @param array<string, array<string, int>> $filter_counts Optional counts by filter dimension.
	 */
	public function __construct(
		array $rows,
		Large_Library_Pagination $pagination,
		array $filter_counts = array(),
		int $total_matching = 0
	) {
		$this->rows           = $rows;
		$this->pagination     = $pagination;
		$this->filter_counts  = $filter_counts;
		$this->total_matching = $total_matching > 0 ? $total_matching : $pagination->get_total();
	}

	public function get_rows(): array {
		return $this->rows;
	}

	public function get_pagination(): Large_Library_Pagination {
		return $this->pagination;
	}

	/**
	 * @return array<string, array<string, int>>
	 */
	public function get_filter_counts(): array {
		return $this->filter_counts;
	}

	public function get_total_matching(): int {
		return $this->total_matching;
	}

	/**
	 * Exports to array for JSON/IA payloads (spec directory IA extension).
	 *
	 * @return array{rows: array<int, array<string, mixed>>, pagination: array{page: int, per_page: int, total: int, total_pages: int, offset: int}, filter_counts: array<string, array<string, int>>, total_matching: int}
	 */
	public function to_array(): array {
		return array(
			'rows'           => $this->rows,
			'pagination'     => $this->pagination->to_array(),
			'filter_counts'  => $this->filter_counts,
			'total_matching' => $this->total_matching,
		);
	}
}
