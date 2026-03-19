<?php
/**
 * Structured outcome of page classification (spec §24.5, §24.10–24.12; crawler contract §5, §8).
 * Machine-readable: classification, reason_codes, duplicate_of, meaningful_score_or_flags, retention_decision.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Classification;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable classification result for one fetched page. Used for snapshot storage and planning input selection.
 */
final class Classification_Result {

	/** Primary classification: page is a useful planning input. */
	public const CLASSIFICATION_MEANINGFUL = 'meaningful';

	/** Primary classification: page duplicates another accepted page. */
	public const CLASSIFICATION_DUPLICATE = 'duplicate';

	/** Primary classification: thin, utility, or low-value content. */
	public const CLASSIFICATION_LOW_VALUE = 'low_value';

	/** Primary classification: page shape not supported for classification. */
	public const CLASSIFICATION_UNSUPPORTED = 'unsupported';

	/** Primary classification: excluded after fetch (e.g. noindex, fetch failure). */
	public const CLASSIFICATION_EXCLUDED_AFTER_FETCH = 'excluded_after_fetch';

	/** Retention: keep as planning input. */
	public const RETENTION_RETAIN = 'retain';

	/** Retention: do not use as planning input. */
	public const RETENTION_EXCLUDE = 'exclude';

	/** Retention: keep but flag for review. */
	public const RETENTION_WARN = 'warn';

	/** Reason: has visible H1 and sufficient word count (contract §5). */
	public const REASON_CONTENT_WEIGHT = 'content_weight';

	/** Reason: appears in navigation (contract §5). */
	public const REASON_IN_NAVIGATION = 'in_navigation';

	/** Reason: likely role by URL/title (service, about, contact, etc.). */
	public const REASON_LIKELY_ROLE = 'likely_role';

	/** Reason: linked repeatedly from meaningful pages. */
	public const REASON_LINK_WEIGHT = 'link_weight';

	/** Reason: thin content (no H1 or &lt; 150 words). */
	public const REASON_THIN_CONTENT = 'thin_content';

	/** Reason: duplicate of another page by canonical URL. */
	public const REASON_DUPLICATE_CANONICAL = 'duplicate_canonical';

	/** Reason: duplicate by title + H1 + content hash. */
	public const REASON_DUPLICATE_CONTENT_HASH = 'duplicate_content_hash';

	/** Reason: redirects to already accepted page. */
	public const REASON_DUPLICATE_REDIRECT = 'duplicate_redirect';

	/** Reason: fetch failed or non-HTML. */
	public const REASON_FETCH_FAILED = 'fetch_failed';

	/** Reason: noindex or not indexable. */
	public const REASON_NOT_INDEXABLE = 'not_indexable';

	/** @var string Primary category: CLASSIFICATION_*. */
	public $classification;

	/** @var array<int, string> Explicit reason codes. */
	public $reason_codes;

	/** @var string|null Normalized URL of the page this duplicates; null if not duplicate. */
	public $duplicate_of;

	/** @var array<string, bool|int> Flags used for meaningful decision (e.g. has_h1, word_count, in_nav). */
	public $meaningful_flags;

	/** @var string One of RETENTION_*. */
	public $retention_decision;

	/** @var string|null Content hash for duplicate detection persistence. */
	public $content_hash;

	/**
	 * @param string                  $classification   One of CLASSIFICATION_*.
	 * @param array<int, string>            $reason_codes     Reason codes.
	 * @param string|null             $duplicate_of     Duplicate target URL when classification is duplicate.
	 * @param array<string, bool|int> $meaningful_flags Optional flags (has_h1, word_count, in_nav, etc.).
	 * @param string                  $retention_decision One of RETENTION_*.
	 * @param string|null             $content_hash       Optional content hash for snapshot storage.
	 */
	public function __construct(
		string $classification,
		array $reason_codes,
		?string $duplicate_of,
		array $meaningful_flags,
		string $retention_decision,
		?string $content_hash = null
	) {
		$this->classification     = $classification;
		$this->reason_codes       = $reason_codes;
		$this->duplicate_of       = $duplicate_of;
		$this->meaningful_flags   = $meaningful_flags;
		$this->retention_decision = $retention_decision;
		$this->content_hash       = $content_hash;
	}

	/**
	 * Whether the page is classified as meaningful (retain as planning input).
	 *
	 * @return bool
	 */
	public function is_meaningful(): bool {
		return $this->classification === self::CLASSIFICATION_MEANINGFUL;
	}

	/**
	 * Whether the page is classified as duplicate.
	 *
	 * @return bool
	 */
	public function is_duplicate(): bool {
		return $this->classification === self::CLASSIFICATION_DUPLICATE;
	}

	/**
	 * Whether retention decision is retain.
	 *
	 * @return bool
	 */
	public function is_retain(): bool {
		return $this->retention_decision === self::RETENTION_RETAIN;
	}

	/**
	 * Returns a machine-readable array for logging or snapshot storage.
	 *
	 * @return array{classification: string, reason_codes: array<int, string>, duplicate_of: string|null, retention_decision: string}
	 */
	public function to_array(): array {
		return array(
			'classification'     => $this->classification,
			'reason_codes'       => $this->reason_codes,
			'duplicate_of'       => $this->duplicate_of,
			'meaningful_flags'   => $this->meaningful_flags,
			'retention_decision' => $this->retention_decision,
			'content_hash'       => $this->content_hash,
		);
	}
}
