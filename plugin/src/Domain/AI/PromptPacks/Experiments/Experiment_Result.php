<?php
/**
 * Result of a single experiment run (spec §26, §58.3, §59.8, Prompt 121).
 * UI-safe; no secrets. Stable shape for experiment_run_result.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks\Experiments;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object: run identity, status, experiment and variant labels.
 */
final class Experiment_Result {

	/** @var string */
	private string $run_id;

	/** @var int */
	private int $post_id;

	/** @var string */
	private string $status;

	/** @var string */
	private string $experiment_id;

	/** @var string */
	private string $experiment_variant_id;

	/** @var string */
	private string $variant_label;

	/** @var string */
	private string $message;

	public function __construct(
		string $run_id,
		int $post_id,
		string $status,
		string $experiment_id,
		string $experiment_variant_id,
		string $variant_label,
		string $message
	) {
		$this->run_id                = $run_id;
		$this->post_id               = $post_id;
		$this->status                = $status;
		$this->experiment_id         = $experiment_id;
		$this->experiment_variant_id = $experiment_variant_id;
		$this->variant_label         = $variant_label;
		$this->message               = $message;
	}

	public function get_run_id(): string {
		return $this->run_id;
	}

	public function get_post_id(): int {
		return $this->post_id;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function get_experiment_id(): string {
		return $this->experiment_id;
	}

	public function get_experiment_variant_id(): string {
		return $this->experiment_variant_id;
	}

	public function get_variant_label(): string {
		return $this->variant_label;
	}

	public function get_message(): string {
		return $this->message;
	}

	/**
	 * UI-safe summary payload (no secrets).
	 *
	 * @return array{run_id: string, post_id: int, status: string, experiment_id: string, experiment_variant_id: string, variant_label: string, message: string}
	 */
	public function to_array(): array {
		return array(
			'run_id'                => $this->run_id,
			'post_id'               => $this->post_id,
			'status'                => $this->status,
			'experiment_id'         => $this->experiment_id,
			'experiment_variant_id' => $this->experiment_variant_id,
			'variant_label'         => $this->variant_label,
			'message'               => $this->message,
		);
	}
}
