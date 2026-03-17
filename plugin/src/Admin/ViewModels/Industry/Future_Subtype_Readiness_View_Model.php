<?php
/**
 * View model for Future Subtype Readiness screen (Prompt 567). Read-only aggregation of
 * subtype planning, subtype scaffold, promotion-readiness, and blocker summaries.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\Industry;

defined( 'ABSPATH' ) || exit;

/**
 * DTO for the future-subtype readiness dashboard screen.
 */
final class Future_Subtype_Readiness_View_Model {

	public const KEY_SUBTYPE_SCAFFOLD_COUNT   = 'subtype_scaffold_count';
	public const KEY_SUBTYPE_MISSING_COUNT    = 'subtype_missing_count';
	public const KEY_PROMO_SUBTYPE_SUMMARY   = 'promotion_readiness_subtype_summary';
	public const KEY_BLOCKER_COUNT           = 'blocker_count';
	public const KEY_LINKS                   = 'links';

	/** @var int */
	private int $subtype_scaffold_count;
	/** @var int */
	private int $subtype_missing_count;
	/** @var array<string, int> */
	private array $promotion_readiness_subtype_summary;
	/** @var int */
	private int $blocker_count;
	/** @var array<string, string> */
	private array $links;

	/**
	 * @param array<string, int> $promotion_readiness_subtype_summary Keys: total, scaffold_complete, authored_near_ready, not_near_ready (subtype-only).
	 * @param array<string, string> $links
	 */
	public function __construct(
		int $subtype_scaffold_count = 0,
		int $subtype_missing_count = 0,
		array $promotion_readiness_subtype_summary = array(),
		int $blocker_count = 0,
		array $links = array()
	) {
		$this->subtype_scaffold_count = $subtype_scaffold_count;
		$this->subtype_missing_count = $subtype_missing_count;
		$this->promotion_readiness_subtype_summary = $promotion_readiness_subtype_summary;
		$this->blocker_count = $blocker_count;
		$this->links = $links;
	}

	public function get_subtype_scaffold_count(): int {
		return $this->subtype_scaffold_count;
	}

	public function get_subtype_missing_count(): int {
		return $this->subtype_missing_count;
	}

	/** @return array<string, int> */
	public function get_promotion_readiness_subtype_summary(): array {
		return $this->promotion_readiness_subtype_summary;
	}

	public function get_blocker_count(): int {
		return $this->blocker_count;
	}

	/** @return array<string, string> */
	public function get_links(): array {
		return $this->links;
	}

	/** @return array<string, mixed> */
	public function to_array(): array {
		return array(
			self::KEY_SUBTYPE_SCAFFOLD_COUNT => $this->subtype_scaffold_count,
			self::KEY_SUBTYPE_MISSING_COUNT  => $this->subtype_missing_count,
			self::KEY_PROMO_SUBTYPE_SUMMARY  => $this->promotion_readiness_subtype_summary,
			self::KEY_BLOCKER_COUNT          => $this->blocker_count,
			self::KEY_LINKS                 => $this->links,
		);
	}
}
