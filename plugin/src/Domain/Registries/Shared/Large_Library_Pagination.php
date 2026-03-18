<?php
/**
 * Pagination value object for large-library query results (spec §55.8).
 * Immutable; 1-based page, per_page, total, total_pages, offset.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Shared;

defined( 'ABSPATH' ) || exit;

/**
 * Pagination metadata for directory and filter-result payloads.
 */
final class Large_Library_Pagination {

	/** @var int 1-based page number. */
	private int $page;

	/** @var int Items per page. */
	private int $per_page;

	/** @var int Total matching items (before slicing). */
	private int $total;

	/** @var int Total number of pages. */
	private int $total_pages;

	/** @var int Zero-based offset for the current page. */
	private int $offset;

	private function __construct(
		int $page,
		int $per_page,
		int $total,
		int $total_pages,
		int $offset
	) {
		$this->page        = $page;
		$this->per_page    = $per_page;
		$this->total       = $total;
		$this->total_pages = $total_pages;
		$this->offset      = $offset;
	}

	/**
	 * Builds pagination from 1-based page and per-page size.
	 *
	 * @param int $page     1-based page (minimum 1).
	 * @param int $per_page Items per page (minimum 1).
	 * @param int $total    Total matching items.
	 * @return self
	 */
	public static function from_page_size( int $page, int $per_page, int $total ): self {
		$page        = max( 1, $page );
		$per_page    = max( 1, $per_page );
		$total       = max( 0, $total );
		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;
		$offset      = ( $page - 1 ) * $per_page;
		return new self( $page, $per_page, $total, $total_pages, $offset );
	}

	/**
	 * Builds pagination from zero-based offset and limit (for internal use).
	 *
	 * @param int $offset Zero-based offset.
	 * @param int $limit  Page size.
	 * @param int $total  Total matching items.
	 * @return self
	 */
	public static function from_offset_limit( int $offset, int $limit, int $total ): self {
		$limit       = max( 1, $limit );
		$total       = max( 0, $total );
		$page        = $limit > 0 ? (int) floor( $offset / $limit ) + 1 : 1;
		$total_pages = $limit > 0 ? (int) ceil( $total / $limit ) : 0;
		return new self( $page, $limit, $total, $total_pages, $offset );
	}

	public function get_page(): int {
		return $this->page;
	}

	public function get_per_page(): int {
		return $this->per_page;
	}

	public function get_total(): int {
		return $this->total;
	}

	public function get_total_pages(): int {
		return $this->total_pages;
	}

	public function get_offset(): int {
		return $this->offset;
	}

	/**
	 * Exports to array for JSON/IA payloads.
	 *
	 * @return array{page: int, per_page: int, total: int, total_pages: int, offset: int}
	 */
	public function to_array(): array {
		return array(
			'page'        => $this->page,
			'per_page'    => $this->per_page,
			'total'       => $this->total,
			'total_pages' => $this->total_pages,
			'offset'      => $this->offset,
		);
	}
}
