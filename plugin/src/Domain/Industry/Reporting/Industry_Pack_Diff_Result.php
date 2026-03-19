<?php
/**
 * Immutable result of Industry_Pack_Diff_Service::diff() (industry-pack-diff-contract.md; Prompt 418).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only diff result: added/removed/changed pack keys and change details.
 */
final class Industry_Pack_Diff_Result {

	/** @var string */
	private string $compared_at;

	/** @var string */
	private string $left_label;

	/** @var string */
	private string $right_label;

	/** @var array<int, string> */
	private array $added;

	/** @var array<int, string> */
	private array $removed;

	/** @var array<int, array<string, mixed>> */
	private array $changed;

	/** @var array{added_count: int, removed_count: int, changed_count: int, impact_level?: string} */
	private array $summary;

	/** @var array<int, string> */
	private array $notes;

	/**
	 * @param string                                                                                 $compared_at ISO 8601.
	 * @param string                                                                                 $left_label Label for baseline.
	 * @param string                                                                                 $right_label Label for new state.
	 * @param array<int, string>                                                                           $added industry_key only in right.
	 * @param array<int, string>                                                                           $removed industry_key only in left.
	 * @param array<int, array<string, mixed>>                                                             $changed Per-pack change entries.
	 * @param array{added_count: int, removed_count: int, changed_count: int, impact_level?: string} $summary
	 * @param array<int, string>                                                                           $notes
	 */
	public function __construct(
		string $compared_at,
		string $left_label,
		string $right_label,
		array $added,
		array $removed,
		array $changed,
		array $summary,
		array $notes = array()
	) {
		$this->compared_at = $compared_at;
		$this->left_label  = $left_label;
		$this->right_label = $right_label;
		$this->added       = $added;
		$this->removed     = $removed;
		$this->changed     = $changed;
		$this->summary     = $summary;
		$this->notes       = $notes;
	}

	public function get_compared_at(): string {
		return $this->compared_at;
	}

	public function get_left_label(): string {
		return $this->left_label;
	}

	public function get_right_label(): string {
		return $this->right_label;
	}

	/** @return array<int, string> */
	public function get_added(): array {
		return $this->added;
	}

	/** @return array<int, string> */
	public function get_removed(): array {
		return $this->removed;
	}

	/** @return array<int, array<string, mixed>> */
	public function get_changed(): array {
		return $this->changed;
	}

	/** @return array{added_count: int, removed_count: int, changed_count: int, impact_level?: string} */
	public function get_summary(): array {
		return $this->summary;
	}

	/** @return array<int, string> */
	public function get_notes(): array {
		return $this->notes;
	}

	/** @return array<string, mixed> */
	public function to_array(): array {
		return array(
			'compared_at' => $this->compared_at,
			'left_label'  => $this->left_label,
			'right_label' => $this->right_label,
			'added'       => $this->added,
			'removed'     => $this->removed,
			'changed'     => $this->changed,
			'summary'     => $this->summary,
			'notes'       => $this->notes,
		);
	}
}
