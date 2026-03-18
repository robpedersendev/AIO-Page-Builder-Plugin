<?php
/**
 * View model for the Future Expansion Readiness dashboard widget (Prompt 563).
 * Read-only summary: expansion blockers, scaffold readiness, candidate readiness, maturity floor.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\Industry;

defined( 'ABSPATH' ) || exit;

/**
 * DTO for the future-expansion readiness widget on the Industry Author Dashboard.
 */
final class Future_Expansion_Readiness_Widget_View_Model {

	public const KEY_EXPANSION_BLOCKER_COUNT   = 'expansion_blocker_count';
	public const KEY_SCAFFOLD_INCOMPLETE_COUNT = 'scaffold_incomplete_count';
	public const KEY_CANDIDATE_READINESS_LABEL = 'candidate_readiness_label';
	public const KEY_MATURITY_FLOOR_LABEL      = 'maturity_floor_label';
	public const KEY_LINKS                     = 'links';

	/** @var int */
	private int $expansion_blocker_count;
	/** @var int */
	private int $scaffold_incomplete_count;
	/** @var string */
	private string $candidate_readiness_label;
	/** @var string */
	private string $maturity_floor_label;
	/** @var array<string, string> */
	private array $links;

	public function __construct(
		int $expansion_blocker_count = 0,
		int $scaffold_incomplete_count = 0,
		string $candidate_readiness_label = '',
		string $maturity_floor_label = '',
		array $links = array()
	) {
		$this->expansion_blocker_count   = $expansion_blocker_count;
		$this->scaffold_incomplete_count = $scaffold_incomplete_count;
		$this->candidate_readiness_label = $candidate_readiness_label;
		$this->maturity_floor_label      = $maturity_floor_label;
		$this->links                     = $links;
	}

	public function get_expansion_blocker_count(): int {
		return $this->expansion_blocker_count;
	}

	public function get_scaffold_incomplete_count(): int {
		return $this->scaffold_incomplete_count;
	}

	public function get_candidate_readiness_label(): string {
		return $this->candidate_readiness_label;
	}

	public function get_maturity_floor_label(): string {
		return $this->maturity_floor_label;
	}

	/** @return array<string, string> */
	public function get_links(): array {
		return $this->links;
	}

	/** @return array<string, mixed> */
	public function to_array(): array {
		return array(
			self::KEY_EXPANSION_BLOCKER_COUNT   => $this->expansion_blocker_count,
			self::KEY_SCAFFOLD_INCOMPLETE_COUNT => $this->scaffold_incomplete_count,
			self::KEY_CANDIDATE_READINESS_LABEL => $this->candidate_readiness_label,
			self::KEY_MATURITY_FLOOR_LABEL      => $this->maturity_floor_label,
			self::KEY_LINKS                     => $this->links,
		);
	}
}
