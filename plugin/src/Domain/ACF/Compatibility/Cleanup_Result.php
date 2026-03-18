<?php
/**
 * Structured cleanup analysis result (spec §20.15, §58.4, §58.5).
 * Detection and advice only; no destructive actions.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Cleanup result shape for diagnostics and advisory visibility.
 * Keys are stable; do not rename.
 */
final class Cleanup_Result {

	/** @var list<array{page_ref: string, group_key: string, reason: string}> Assignments no longer in current source. */
	public array $stale_assignments = array();

	/** @var list<array{group_key: string, section_key: string, reason: string}> Groups whose section is deprecated. */
	public array $deprecated_groups = array();

	/** @var list<string> Group keys that can be safely removed (conservative; often empty). */
	public array $safe_to_remove = array();

	/** @var list<array{group_key: string, reason: string}> Groups requiring manual review before removal. */
	public array $requires_manual_review = array();

	/** @var list<string> Human-readable compatibility notes. */
	public array $compatibility_notes = array();

	/**
	 * Returns result as associative array for export/diagnostics.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'stale_assignments'      => $this->stale_assignments,
			'deprecated_groups'      => $this->deprecated_groups,
			'safe_to_remove'         => $this->safe_to_remove,
			'requires_manual_review' => $this->requires_manual_review,
			'compatibility_notes'    => $this->compatibility_notes,
		);
	}
}
