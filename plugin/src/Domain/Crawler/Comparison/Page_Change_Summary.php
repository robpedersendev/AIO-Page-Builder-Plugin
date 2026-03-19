<?php
/**
 * Per-URL change summary for recrawl comparison (spec §24.17).
 * Machine-readable: url, change_category, reason_codes, optional prior/new snapshot keys.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Comparison;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable summary of how one page changed between two crawl sessions.
 */
final class Page_Change_Summary {

	/** Page exists only in the new crawl. */
	public const CATEGORY_ADDED = 'added';

	/** Page existed in prior crawl but not in new. */
	public const CATEGORY_REMOVED = 'removed';

	/** Page exists in both; one or more comparable fields changed. */
	public const CATEGORY_CHANGED = 'changed';

	/** Page exists in both; no material change detected. */
	public const CATEGORY_UNCHANGED = 'unchanged';

	/** Page classification changed (e.g. meaningful <-> low_value or duplicate). */
	public const CATEGORY_RECLASSIFIED = 'reclassified';

	/** Reason: page newly discovered in new crawl. */
	public const REASON_ADDED = 'added';

	/** Reason: page no longer present in new crawl. */
	public const REASON_REMOVED = 'removed';

	/** Reason: title_snapshot changed. */
	public const REASON_TITLE_CHANGED = 'title_changed';

	/** Reason: page_classification changed. */
	public const REASON_CLASSIFICATION_CHANGED = 'classification_changed';

	/** Reason: canonical_url changed. */
	public const REASON_CANONICAL_CHANGED = 'canonical_changed';

	/** Reason: navigation_participation changed. */
	public const REASON_NAV_PARTICIPATION_CHANGED = 'nav_participation_changed';

	/** Reason: content_hash or summary changed. */
	public const REASON_CONTENT_OR_SUMMARY_CHANGED = 'content_or_summary_changed';

	/** @var string Normalized URL (identity). */
	public $url;

	/** @var string One of CATEGORY_*. */
	public $change_category;

	/** @var array<int, string> Explicit reason codes. */
	public $reason_codes;

	/** @var array<string, mixed>|null Key fields from prior run record; null if added. */
	public $prior_snapshot;

	/** @var array<string, mixed>|null Key fields from new run record; null if removed. */
	public $new_snapshot;

	/**
	 * @param string                    $url             Normalized URL.
	 * @param string                    $change_category One of CATEGORY_*.
	 * @param array<int, string>              $reason_codes    Reason codes.
	 * @param array<string, mixed>|null $prior_snapshot Optional prior record excerpt.
	 * @param array<string, mixed>|null $new_snapshot   Optional new record excerpt.
	 */
	public function __construct(
		string $url,
		string $change_category,
		array $reason_codes,
		?array $prior_snapshot = null,
		?array $new_snapshot = null
	) {
		$this->url             = $url;
		$this->change_category = $change_category;
		$this->reason_codes    = $reason_codes;
		$this->prior_snapshot  = $prior_snapshot;
		$this->new_snapshot    = $new_snapshot;
	}

	/**
	 * Returns a machine-readable array for logging or API.
	 *
	 * @return array{url: string, change_category: string, reason_codes: array<int, string>, prior_snapshot: array|null, new_snapshot: array|null}
	 */
	public function to_array(): array {
		return array(
			'url'             => $this->url,
			'change_category' => $this->change_category,
			'reason_codes'    => $this->reason_codes,
			'prior_snapshot'  => $this->prior_snapshot,
			'new_snapshot'    => $this->new_snapshot,
		);
	}
}
