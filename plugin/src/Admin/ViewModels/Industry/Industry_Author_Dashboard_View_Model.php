<?php
/**
 * View model for the Industry Author Dashboard (Prompt 522). Holds aggregated health, completeness, gap, and link data.
 * Read-only; escape on output.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\Industry;

defined( 'ABSPATH' ) || exit;

/**
 * DTO for dashboard widgets: health summary, completeness summary, blocker count, gap counts, links.
 */
final class Industry_Author_Dashboard_View_Model {

	public const KEY_HEALTH_ERROR_COUNT   = 'health_error_count';
	public const KEY_HEALTH_WARNING_COUNT = 'health_warning_count';
	public const KEY_HEALTHY              = 'healthy';
	public const KEY_RELEASE_GRADE_COUNT  = 'release_grade_count';
	public const KEY_STRONG_COUNT         = 'strong_count';
	public const KEY_MINIMAL_COUNT        = 'minimal_count';
	public const KEY_BELOW_MINIMAL_COUNT  = 'below_minimal_count';
	public const KEY_PACK_COUNT           = 'pack_count';
	public const KEY_SUBTYPE_COUNT        = 'subtype_count';
	public const KEY_BLOCKER_COUNT        = 'blocker_count';
	public const KEY_GAP_COUNT            = 'gap_count';
	public const KEY_GAP_HIGH_COUNT       = 'gap_high_count';
	public const KEY_GAP_MEDIUM_COUNT     = 'gap_medium_count';
	public const KEY_GAP_LOW_COUNT        = 'gap_low_count';
	public const KEY_LINKS                = 'links';

	/** @var int */
	private int $health_error_count;
	/** @var int */
	private int $health_warning_count;
	/** @var bool */
	private bool $healthy;
	/** @var int */
	private int $release_grade_count;
	/** @var int */
	private int $strong_count;
	/** @var int */
	private int $minimal_count;
	/** @var int */
	private int $below_minimal_count;
	/** @var int */
	private int $pack_count;
	/** @var int */
	private int $subtype_count;
	/** @var int */
	private int $blocker_count;
	/** @var int */
	private int $gap_count;
	/** @var int */
	private int $gap_high_count;
	/** @var int */
	private int $gap_medium_count;
	/** @var int */
	private int $gap_low_count;
	/** @var array<string, string> */
	private array $links;

	public function __construct(
		int $health_error_count = 0,
		int $health_warning_count = 0,
		bool $healthy = true,
		int $release_grade_count = 0,
		int $strong_count = 0,
		int $minimal_count = 0,
		int $below_minimal_count = 0,
		int $pack_count = 0,
		int $subtype_count = 0,
		int $blocker_count = 0,
		int $gap_count = 0,
		int $gap_high_count = 0,
		int $gap_medium_count = 0,
		int $gap_low_count = 0,
		array $links = array()
	) {
		$this->health_error_count   = $health_error_count;
		$this->health_warning_count = $health_warning_count;
		$this->healthy              = $healthy;
		$this->release_grade_count  = $release_grade_count;
		$this->strong_count         = $strong_count;
		$this->minimal_count        = $minimal_count;
		$this->below_minimal_count  = $below_minimal_count;
		$this->pack_count           = $pack_count;
		$this->subtype_count        = $subtype_count;
		$this->blocker_count        = $blocker_count;
		$this->gap_count            = $gap_count;
		$this->gap_high_count       = $gap_high_count;
		$this->gap_medium_count     = $gap_medium_count;
		$this->gap_low_count        = $gap_low_count;
		$this->links                = $links;
	}

	public function get_health_error_count(): int {
		return $this->health_error_count;
	}

	public function get_health_warning_count(): int {
		return $this->health_warning_count;
	}

	public function is_healthy(): bool {
		return $this->healthy;
	}

	public function get_release_grade_count(): int {
		return $this->release_grade_count;
	}

	public function get_strong_count(): int {
		return $this->strong_count;
	}

	public function get_minimal_count(): int {
		return $this->minimal_count;
	}

	public function get_below_minimal_count(): int {
		return $this->below_minimal_count;
	}

	public function get_pack_count(): int {
		return $this->pack_count;
	}

	public function get_subtype_count(): int {
		return $this->subtype_count;
	}

	public function get_blocker_count(): int {
		return $this->blocker_count;
	}

	public function get_gap_count(): int {
		return $this->gap_count;
	}

	public function get_gap_high_count(): int {
		return $this->gap_high_count;
	}

	public function get_gap_medium_count(): int {
		return $this->gap_medium_count;
	}

	public function get_gap_low_count(): int {
		return $this->gap_low_count;
	}

	/** @return array<string, string> */
	public function get_links(): array {
		return $this->links;
	}

	/** @return array<string, mixed> */
	public function to_array(): array {
		return array(
			self::KEY_HEALTH_ERROR_COUNT   => $this->health_error_count,
			self::KEY_HEALTH_WARNING_COUNT => $this->health_warning_count,
			self::KEY_HEALTHY              => $this->healthy,
			self::KEY_RELEASE_GRADE_COUNT  => $this->release_grade_count,
			self::KEY_STRONG_COUNT         => $this->strong_count,
			self::KEY_MINIMAL_COUNT        => $this->minimal_count,
			self::KEY_BELOW_MINIMAL_COUNT  => $this->below_minimal_count,
			self::KEY_PACK_COUNT           => $this->pack_count,
			self::KEY_SUBTYPE_COUNT        => $this->subtype_count,
			self::KEY_BLOCKER_COUNT        => $this->blocker_count,
			self::KEY_GAP_COUNT            => $this->gap_count,
			self::KEY_GAP_HIGH_COUNT       => $this->gap_high_count,
			self::KEY_GAP_MEDIUM_COUNT     => $this->gap_medium_count,
			self::KEY_GAP_LOW_COUNT        => $this->gap_low_count,
			self::KEY_LINKS                => $this->links,
		);
	}
}
