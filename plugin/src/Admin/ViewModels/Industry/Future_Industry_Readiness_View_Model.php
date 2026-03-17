<?php
/**
 * View model for Future Industry Readiness screen (Prompt 566). Read-only aggregation of
 * candidate, scaffold, promotion-readiness, and blocker summaries for expansion planning.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\Industry;

defined( 'ABSPATH' ) || exit;

/**
 * DTO for the future-industry readiness dashboard screen.
 */
final class Future_Industry_Readiness_View_Model {

	public const KEY_EXPANSION_BLOCKER_COUNT   = 'expansion_blocker_count';
	public const KEY_SCAFFOLD_INCOMPLETE_COUNT  = 'scaffold_incomplete_count';
	public const KEY_CANDIDATE_READINESS_LABEL  = 'candidate_readiness_label';
	public const KEY_MATURITY_FLOOR_LABEL      = 'maturity_floor_label';
	public const KEY_PROMO_SUMMARY              = 'promotion_readiness_summary';
	public const KEY_SCAFFOLD_SUMMARY           = 'scaffold_summary';
	public const KEY_LINKS                      = 'links';

	/** @var int */
	private int $expansion_blocker_count;
	/** @var int */
	private int $scaffold_incomplete_count;
	/** @var string */
	private string $candidate_readiness_label;
	/** @var string */
	private string $maturity_floor_label;
	/** @var array<string, int> */
	private array $promotion_readiness_summary;
	/** @var array<string, mixed> */
	private array $scaffold_summary;
	/** @var array<string, string> */
	private array $links;

	/**
	 * @param array<string, int> $promotion_readiness_summary Keys: total, scaffold_complete, authored_near_ready, not_near_ready.
	 * @param array<string, mixed> $scaffold_summary Keys: scaffold_count, missing_artifact_count, etc.
	 * @param array<string, string> $links
	 */
	public function __construct(
		int $expansion_blocker_count = 0,
		int $scaffold_incomplete_count = 0,
		string $candidate_readiness_label = '',
		string $maturity_floor_label = '',
		array $promotion_readiness_summary = array(),
		array $scaffold_summary = array(),
		array $links = array()
	) {
		$this->expansion_blocker_count   = $expansion_blocker_count;
		$this->scaffold_incomplete_count = $scaffold_incomplete_count;
		$this->candidate_readiness_label = $candidate_readiness_label;
		$this->maturity_floor_label      = $maturity_floor_label;
		$this->promotion_readiness_summary = $promotion_readiness_summary;
		$this->scaffold_summary          = $scaffold_summary;
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

	/** @return array<string, int> */
	public function get_promotion_readiness_summary(): array {
		return $this->promotion_readiness_summary;
	}

	/** @return array<string, mixed> */
	public function get_scaffold_summary(): array {
		return $this->scaffold_summary;
	}

	/** @return array<string, string> */
	public function get_links(): array {
		return $this->links;
	}

	/** @return array<string, mixed> */
	public function to_array(): array {
		return array(
			self::KEY_EXPANSION_BLOCKER_COUNT  => $this->expansion_blocker_count,
			self::KEY_SCAFFOLD_INCOMPLETE_COUNT => $this->scaffold_incomplete_count,
			self::KEY_CANDIDATE_READINESS_LABEL => $this->candidate_readiness_label,
			self::KEY_MATURITY_FLOOR_LABEL     => $this->maturity_floor_label,
			self::KEY_PROMO_SUMMARY            => $this->promotion_readiness_summary,
			self::KEY_SCAFFOLD_SUMMARY         => $this->scaffold_summary,
			self::KEY_LINKS                    => $this->links,
		);
	}
}
