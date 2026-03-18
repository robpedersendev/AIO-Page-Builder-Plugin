<?php
/**
 * Compares two crawl sessions and produces structured change results (spec §24.17).
 * Identifies added, removed, changed, unchanged, and reclassified pages. No AI or execution.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Comparison;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;

/**
 * Compares prior and new crawl snapshots by normalized URL; produces Session_Comparison_Result.
 */
final class Recrawl_Comparison_Service {

	/** Classification value for meaningful pages (must match Classification_Result). */
	private const CLASSIFICATION_MEANINGFUL = 'meaningful';

	/** @var Crawl_Snapshot_Service */
	private $snapshot_service;

	public function __construct( Crawl_Snapshot_Service $snapshot_service ) {
		$this->snapshot_service = $snapshot_service;
	}

	/**
	 * Compares two crawl sessions. Prior = baseline, new = latest run.
	 *
	 * @param string $prior_run_id Prior (baseline) crawl run id.
	 * @param string $new_run_id   New crawl run id.
	 * @return Session_Comparison_Result
	 */
	public function compare( string $prior_run_id, string $new_run_id ): Session_Comparison_Result {
		$prior_pages  = $this->index_by_url( $this->snapshot_service->list_pages_by_run( $prior_run_id ) );
		$new_pages    = $this->index_by_url( $this->snapshot_service->list_pages_by_run( $new_run_id ) );
		$prior_urls   = array_keys( $prior_pages );
		$new_urls     = array_keys( $new_pages );
		$added_urls   = array_diff( $new_urls, $prior_urls );
		$removed_urls = array_diff( $prior_urls, $new_urls );
		$common_urls  = array_intersect( $prior_urls, $new_urls );

		$meaningful_count_prior = $this->count_meaningful( $prior_pages );
		$meaningful_count_new   = $this->count_meaningful( $new_pages );

		$page_changes = array();
		foreach ( $added_urls as $url ) {
			$page_changes[] = new Page_Change_Summary(
				$url,
				Page_Change_Summary::CATEGORY_ADDED,
				array( Page_Change_Summary::REASON_ADDED ),
				null,
				$this->snapshot_excerpt( $new_pages[ $url ] )
			);
		}
		foreach ( $removed_urls as $url ) {
			$page_changes[] = new Page_Change_Summary(
				$url,
				Page_Change_Summary::CATEGORY_REMOVED,
				array( Page_Change_Summary::REASON_REMOVED ),
				$this->snapshot_excerpt( $prior_pages[ $url ] ),
				null
			);
		}

		$changed_count      = 0;
		$unchanged_count    = 0;
		$reclassified_count = 0;

		foreach ( $common_urls as $url ) {
			$prior         = $prior_pages[ $url ];
			$new           = $new_pages[ $url ];
			$reasons       = $this->diff_reasons( $prior, $new );
			$class_changed = in_array( Page_Change_Summary::REASON_CLASSIFICATION_CHANGED, $reasons, true );
			if ( $class_changed ) {
				++$reclassified_count;
			}
			if ( count( $reasons ) > 0 ) {
				++$changed_count;
				$category       = $class_changed ? Page_Change_Summary::CATEGORY_RECLASSIFIED : Page_Change_Summary::CATEGORY_CHANGED;
				$page_changes[] = new Page_Change_Summary(
					$url,
					$category,
					$reasons,
					$this->snapshot_excerpt( $prior ),
					$this->snapshot_excerpt( $new )
				);
			} else {
				++$unchanged_count;
				$page_changes[] = new Page_Change_Summary(
					$url,
					Page_Change_Summary::CATEGORY_UNCHANGED,
					array(),
					$this->snapshot_excerpt( $prior ),
					$this->snapshot_excerpt( $new )
				);
			}
		}

		return new Session_Comparison_Result(
			$prior_run_id,
			$new_run_id,
			count( $added_urls ),
			count( $removed_urls ),
			$changed_count,
			$unchanged_count,
			$reclassified_count,
			$meaningful_count_prior,
			$meaningful_count_new,
			$page_changes
		);
	}

	/**
	 * @param array<string, array<string, mixed>> $index
	 * @return int
	 */
	private function count_meaningful( array $index ): int {
		$n = 0;
		foreach ( $index as $row ) {
			if ( ( $row['page_classification'] ?? '' ) === self::CLASSIFICATION_MEANINGFUL ) {
				++$n;
			}
		}
		return $n;
	}

	/**
	 * @param list<array<string, mixed>> $pages
	 * @return array<string, array<string, mixed>> Keyed by url.
	 */
	private function index_by_url( array $pages ): array {
		$out = array();
		foreach ( $pages as $row ) {
			$url = $row['url'] ?? '';
			if ( $url !== '' ) {
				$out[ $url ] = $row;
			}
		}
		return $out;
	}

	/**
	 * Returns reason codes for differences between two page records.
	 *
	 * @param array<string, mixed> $prior
	 * @param array<string, mixed> $new
	 * @return list<string>
	 */
	private function diff_reasons( array $prior, array $new ): array {
		$reasons     = array();
		$title_prior = $prior['title_snapshot'] ?? null;
		$title_new   = $new['title_snapshot'] ?? null;
		if ( (string) $title_prior !== (string) $title_new ) {
			$reasons[] = Page_Change_Summary::REASON_TITLE_CHANGED;
		}
		$class_prior = $prior['page_classification'] ?? null;
		$class_new   = $new['page_classification'] ?? null;
		if ( (string) $class_prior !== (string) $class_new ) {
			$reasons[] = Page_Change_Summary::REASON_CLASSIFICATION_CHANGED;
		}
		$canon_prior = $prior['canonical_url'] ?? null;
		$canon_new   = $new['canonical_url'] ?? null;
		if ( (string) $canon_prior !== (string) $canon_new ) {
			$reasons[] = Page_Change_Summary::REASON_CANONICAL_CHANGED;
		}
		$nav_prior = (int) ( $prior['navigation_participation'] ?? 0 );
		$nav_new   = (int) ( $new['navigation_participation'] ?? 0 );
		if ( $nav_prior !== $nav_new ) {
			$reasons[] = Page_Change_Summary::REASON_NAV_PARTICIPATION_CHANGED;
		}
		$hash_prior = $prior['content_hash'] ?? null;
		$hash_new   = $new['content_hash'] ?? null;
		if ( (string) $hash_prior !== (string) $hash_new ) {
			$reasons[] = Page_Change_Summary::REASON_CONTENT_OR_SUMMARY_CHANGED;
		}
		return $reasons;
	}

	/**
	 * Returns a bounded excerpt of a page record for comparison output (no raw blobs).
	 *
	 * @param array<string, mixed> $record
	 * @return array<string, mixed>
	 */
	private function snapshot_excerpt( array $record ): array {
		return array(
			'url'                      => $record['url'] ?? null,
			'title_snapshot'           => $record['title_snapshot'] ?? null,
			'page_classification'      => $record['page_classification'] ?? null,
			'canonical_url'            => $record['canonical_url'] ?? null,
			'navigation_participation' => $record['navigation_participation'] ?? null,
			'content_hash'             => $record['content_hash'] ?? null,
		);
	}
}
