<?php
/**
 * Contract for rollback handlers (spec §38.5, §41.9).
 *
 * Handlers restore state from pre-change snapshot. No inline mutation from UI.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Execution;

defined( 'ABSPATH' ) || exit;

/**
 * Typed rollback handler: restores target from pre_change state_snapshot.
 */
interface Rollback_Handler_Interface {

	/**
	 * Executes rollback by applying pre-change state to the target. Caller has already revalidated eligibility.
	 *
	 * @param array<string, mixed> $pre_snapshot  Full pre-change snapshot record (root with pre_change.state_snapshot).
	 * @param array<string, mixed> $post_snapshot Full post-change snapshot record (root with post_change.result_snapshot).
	 * @param array<string, mixed> $context       Optional: job_id, actor_ref, plan_id.
	 * @return Rollback_Result
	 */
	public function execute( array $pre_snapshot, array $post_snapshot, array $context = array() ): Rollback_Result;
}
