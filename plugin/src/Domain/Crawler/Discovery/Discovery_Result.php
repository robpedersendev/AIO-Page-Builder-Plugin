<?php
/**
 * Value object for a single URL discovery outcome (spec §24.8, §24.9; crawler rules contract §7, §11).
 * Machine-readable shape: normalized_url, discovery_source, acceptance_status, rejection_code, dedup_key.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Discovery;

defined( 'ABSPATH' ) || exit;

/**
 * Single candidate URL discovery result: accepted, rejected, or duplicate.
 * Used by URL_Discovery_Service before any HTTP fetch.
 */
final class Discovery_Result {

	/** Acceptance status: candidate is eligible for fetch (same-host, not excluded). */
	public const STATUS_ACCEPTED = 'accepted';

	/** Acceptance status: candidate was filtered out (rejection_code set). */
	public const STATUS_REJECTED = 'rejected';

	/** Acceptance status: same dedup_key already accepted in this run. */
	public const STATUS_DUPLICATE = 'duplicate';

	/** Discovery source: URL came from seed list. */
	public const SOURCE_SEED = 'seed';

	/** Discovery source: URL extracted from HTML or link set (e.g. page body, nav). */
	public const SOURCE_LINK = 'link';

	/** Discovery source: URL from sitemap (future use). */
	public const SOURCE_SITEMAP = 'sitemap';

	/** @var string Normalized same-host URL (after fragment and tracking param removal). */
	public $normalized_url;

	/** @var string Discovery source: SOURCE_SEED, SOURCE_LINK, SOURCE_SITEMAP. */
	public $discovery_source;

	/** @var string One of STATUS_ACCEPTED, STATUS_REJECTED, STATUS_DUPLICATE. */
	public $acceptance_status;

	/** @var string|null Rejection reason code when acceptance_status is rejected; null otherwise. */
	public $rejection_code;

	/** @var string Deterministic key for deduplication (same for same logical page). */
	public $dedup_key;

	/**
	 * Builds an immutable result entry.
	 *
	 * @param string      $normalized_url    Normalized same-host URL.
	 * @param string      $discovery_source  One of SOURCE_*.
	 * @param string      $acceptance_status One of STATUS_*.
	 * @param string|null $rejection_code    Reason code when rejected.
	 * @param string      $dedup_key         Deduplication key.
	 */
	public function __construct(
		string $normalized_url,
		string $discovery_source,
		string $acceptance_status,
		?string $rejection_code,
		string $dedup_key
	) {
		$this->normalized_url    = $normalized_url;
		$this->discovery_source  = $discovery_source;
		$this->acceptance_status = $acceptance_status;
		$this->rejection_code    = $rejection_code;
		$this->dedup_key         = $dedup_key;
	}

	/**
	 * Returns a machine-readable array for logging or aggregation.
	 *
	 * @return array{normalized_url: string, discovery_source: string, acceptance_status: string, rejection_code: string|null, dedup_key: string}
	 */
	public function to_array(): array {
		return array(
			'normalized_url'    => $this->normalized_url,
			'discovery_source'  => $this->discovery_source,
			'acceptance_status' => $this->acceptance_status,
			'rejection_code'    => $this->rejection_code,
			'dedup_key'         => $this->dedup_key,
		);
	}

	/**
	 * Whether this result is accepted (eligible for fetch).
	 *
	 * @return bool
	 */
	public function is_accepted(): bool {
		return $this->acceptance_status === self::STATUS_ACCEPTED;
	}

	/**
	 * Whether this result was rejected (excluded with reason).
	 *
	 * @return bool
	 */
	public function is_rejected(): bool {
		return $this->acceptance_status === self::STATUS_REJECTED;
	}

	/**
	 * Whether this result is a duplicate of an already accepted URL.
	 *
	 * @return bool
	 */
	public function is_duplicate(): bool {
		return $this->acceptance_status === self::STATUS_DUPLICATE;
	}
}
