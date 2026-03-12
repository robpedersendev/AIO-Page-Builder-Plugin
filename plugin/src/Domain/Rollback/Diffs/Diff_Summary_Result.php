<?php
/**
 * Result DTO for diff summarization (diff-service-contract.md; spec §41.4–41.7, §59.11).
 *
 * Holds success, no-meaningful-diff flag, contract-shaped diff payload, and optional fallback reason.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Diffs;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of a diff summarizer: contract-shaped diff and metadata.
 */
final class Diff_Summary_Result {

	/** @var bool */
	private bool $success;

	/** @var bool True when no meaningful changes exist (unchanged or incomparable). */
	private bool $no_meaningful_diff;

	/** @var array<string, mixed> Diff result per diff-service-contract root + family_payload. */
	private array $diff;

	/** @var string */
	private string $message;

	/** @var string Optional fallback reason (e.g. snapshot_missing, incompatible_format). */
	private string $fallback_reason;

	private function __construct(
		bool $success,
		bool $no_meaningful_diff,
		array $diff,
		string $message,
		string $fallback_reason = ''
	) {
		$this->success            = $success;
		$this->no_meaningful_diff = $no_meaningful_diff;
		$this->diff               = $diff;
		$this->message            = $message;
		$this->fallback_reason    = $fallback_reason;
	}

	/**
	 * Builds a successful result with a meaningful diff.
	 *
	 * @param array<string, mixed> $diff Contract-shaped diff (diff_id, diff_type, level, target_ref, etc.).
	 * @param string               $message Optional message.
	 * @return self
	 */
	public static function with_diff( array $diff, string $message = '' ): self {
		return new self( true, false, $diff, $message, '' );
	}

	/**
	 * Builds a result indicating no meaningful diff (unchanged or nothing to compare).
	 *
	 * @param array<string, mixed> $diff Minimal contract-shaped diff (e.g. change_count 0, before/after summaries).
	 * @param string               $message Reason (e.g. "No changes detected").
	 * @return self
	 */
	public static function no_meaningful_diff( array $diff, string $message = 'No meaningful diff.' ): self {
		return new self( true, true, $diff, $message, '' );
	}

	/**
	 * Builds a failure result (missing/incomparable snapshots).
	 *
	 * @param array<string, mixed> $diff Minimal diff or empty; before_summary/after_summary placeholders allowed.
	 * @param string               $message Error message.
	 * @param string               $fallback_reason Optional (e.g. snapshot_missing, incompatible_format).
	 * @return self
	 */
	public static function failure( array $diff, string $message, string $fallback_reason = '' ): self {
		return new self( false, false, $diff, $message, $fallback_reason );
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function has_meaningful_diff(): bool {
		return ! $this->no_meaningful_diff;
	}

	/** @return array<string, mixed> */
	public function get_diff(): array {
		return $this->diff;
	}

	public function get_message(): string {
		return $this->message;
	}

	public function get_fallback_reason(): string {
		return $this->fallback_reason;
	}

	/**
	 * Returns the diff payload as array (for API/JSON). Optionally injects fallback_reason into root when set.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$out = $this->diff;
		if ( $this->fallback_reason !== '' ) {
			$out['fallback_reason'] = $this->fallback_reason;
		}
		return $out;
	}
}
