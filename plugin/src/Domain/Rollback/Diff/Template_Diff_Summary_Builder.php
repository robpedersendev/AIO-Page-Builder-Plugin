<?php
/**
 * Builds template-aware diff summary from pre/post snapshots (spec §59.11, §45; Prompt 197).
 *
 * Extracts template metadata from snapshot result_snapshot.template_context and optional
 * intended_template_key from pre state. Produces template_diff_summary for diff/rollback UI
 * without overloading diffs with noise.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Diff;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;

/**
 * Builds template_diff_summary from operational snapshots when template context is present.
 */
final class Template_Diff_Summary_Builder {

	/**
	 * Builds template_diff_summary from pre- and post-change snapshots.
	 * When post_change.result_snapshot contains template_context, includes template_key_after, template_family_after, etc.
	 * When pre_change.state_snapshot contains intended_template_key (replace intent), includes template_key_before.
	 *
	 * @param array<string, mixed> $pre_snapshot  Full pre-change snapshot.
	 * @param array<string, mixed> $post_snapshot Full post-change snapshot.
	 * @return array<string, mixed> template_diff_summary payload; empty keys when no template context.
	 */
	public function build( array $pre_snapshot, array $post_snapshot ): array {
		$pre_state       = $this->extract_pre_state( $pre_snapshot );
		$post_state      = $this->extract_post_state( $post_snapshot );
		$template_after  = isset( $post_state['template_context'] ) && is_array( $post_state['template_context'] ) ? $post_state['template_context'] : array();
		$template_before = array();
		if ( $pre_state !== null && isset( $pre_state['intended_template_key'] ) && is_string( $pre_state['intended_template_key'] ) && trim( $pre_state['intended_template_key'] ) !== '' ) {
			$template_before['template_key'] = trim( $pre_state['intended_template_key'] );
		}

		$template_key_before   = (string) ( $template_before['template_key'] ?? '' );
		$template_key_after    = (string) ( $template_after['template_key'] ?? '' );
		$template_family_after = (string) ( $template_after['template_family'] ?? '' );
		$section_count_after   = isset( $template_after['section_count'] ) && is_numeric( $template_after['section_count'] ) ? (int) $template_after['section_count'] : 0;

		$summary = array(
			'template_key_before'        => $template_key_before,
			'template_key_after'         => $template_key_after,
			'template_family_after'      => $template_family_after,
			'section_count_after'        => $section_count_after,
			'template_structural_change' => $template_key_before !== $template_key_after,
		);

		$rollback_context                     = new Template_Diff_Context(
			$template_key_after !== '' ? $template_key_after : $template_key_before,
			$template_family_after,
			'',
			false,
			'',
			''
		);
		$summary['rollback_template_context'] = $rollback_context->to_array();

		return $summary;
	}

	/**
	 * Returns an example template_diff_summary payload for documentation and tests.
	 *
	 * @return array<string, mixed>
	 */
	public static function example_template_diff_summary_payload(): array {
		return array(
			'template_key_before'        => '',
			'template_key_after'         => 'tpl_services_hub',
			'template_family_after'      => 'services',
			'section_count_after'        => 5,
			'template_structural_change' => true,
			'rollback_template_context'  => Template_Diff_Context::example_rollback_template_context_payload(),
		);
	}

	/**
	 * @param array<string, mixed> $pre_snapshot
	 * @return array<string, mixed>|null
	 */
	private function extract_pre_state( array $pre_snapshot ): ?array {
		$pre = $pre_snapshot[ Operational_Snapshot_Schema::FIELD_PRE_CHANGE ] ?? null;
		if ( ! is_array( $pre ) ) {
			return null;
		}
		$state = $pre['state_snapshot'] ?? null;
		return is_array( $state ) ? $state : null;
	}

	/**
	 * @param array<string, mixed> $post_snapshot
	 * @return array<string, mixed>|null
	 */
	private function extract_post_state( array $post_snapshot ): ?array {
		$post = $post_snapshot[ Operational_Snapshot_Schema::FIELD_POST_CHANGE ] ?? null;
		if ( ! is_array( $post ) ) {
			return null;
		}
		$state = $post['result_snapshot'] ?? null;
		return is_array( $state ) ? $state : null;
	}
}
