<?php
/**
 * Result of a demo fixture generation run (spec §56.4, §60.7; Prompt 130).
 * Immutable; carries synthetic marker, domain counts, and seed summary. No secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Fixtures;

defined( 'ABSPATH' ) || exit;

/**
 * Value object for demo seed result. All generated data is explicitly synthetic.
 */
final class Demo_Fixture_Result {

	/** @var bool */
	private bool $success;

	/** @var string */
	private string $message;

	/** @var array<string, int> Domain key => count of generated items. */
	private array $counts;

	/** @var array<string, mixed> Summary payload (no raw secrets); includes seed_result key. */
	private array $summary;

	/** @var bool Always true for demo fixtures. */
	private bool $synthetic;

	public function __construct(
		bool $success,
		string $message,
		array $counts,
		array $summary,
		bool $synthetic = true
	) {
		$this->success   = $success;
		$this->message   = $message;
		$this->counts    = $counts;
		$this->summary   = $summary;
		$this->synthetic = $synthetic;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Counts per domain (registries, profile, crawl_summary, ai_runs, build_plans, logs, export_example).
	 *
	 * @return array<string, int>
	 */
	public function get_counts(): array {
		return $this->counts;
	}

	/**
	 * Summary payload for UI or audit; no secrets. Includes seed_result descriptor.
	 *
	 * @return array<string, mixed>
	 */
	public function get_summary(): array {
		return $this->summary;
	}

	public function is_synthetic(): bool {
		return $this->synthetic;
	}

	/**
	 * Stable payload for logging or API: success, message, synthetic, counts, summary (redacted).
	 *
	 * @return array{success: bool, message: string, synthetic: bool, counts: array<string, int>, summary: array<string, mixed>}
	 */
	public function to_payload(): array {
		return array(
			'success'   => $this->success,
			'message'   => $this->message,
			'synthetic' => $this->synthetic,
			'counts'    => $this->counts,
			'summary'   => $this->summary,
		);
	}
}
