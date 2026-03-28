<?php
/**
 * Safe admin copy for template-lab / AI lineage on canonical registry detail views (no artifacts or transcripts).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Shared;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Snapshots\Version_Snapshot_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository;

final class Canonical_AI_Lineage_Display_Helper {

	public const TARGET_COMPOSITION = 'composition';

	public const TARGET_PAGE_TEMPLATE = 'page_template';

	public const TARGET_SECTION_TEMPLATE = 'section_template';

	/**
	 * @return array{show: bool, headline: string, lines: list<string>}
	 */
	public static function build_state(
		string $target_kind,
		int $canonical_post_id,
		string $canonical_internal_key,
		array $definition,
		?Version_Snapshot_Repository $snapshots
	): array {
		$has_trace = false;
		if ( $target_kind === self::TARGET_COMPOSITION ) {
			$has_trace = Registry_AI_Provenance_Helper::composition_has_ai_trace( $definition );
		} elseif ( $target_kind === self::TARGET_PAGE_TEMPLATE ) {
			$has_trace = Registry_AI_Provenance_Helper::page_template_has_ai_trace( $definition );
		} elseif ( $target_kind === self::TARGET_SECTION_TEMPLATE ) {
			$has_trace = Registry_AI_Provenance_Helper::section_template_has_ai_trace( $definition );
		}

		$snap_list = array();
		if ( $snapshots instanceof Version_Snapshot_Repository && $canonical_post_id > 0 && $canonical_internal_key !== '' ) {
			$snap_list = $snapshots->list_template_lab_apply_snapshots_for_canonical( $canonical_post_id, $canonical_internal_key, 40 );
		}

		$lines = array();
		if ( $has_trace ) {
			$lines[] = __( 'This record includes AI-assisted provenance. Structured outputs become canonical only after explicit human approval and apply.', 'aio-page-builder' );
		}
		if ( $snap_list !== array() ) {
			$count   = count( $snap_list );
			$lines[] = sprintf(
				/* translators: %d: number of version snapshot records */
				_n(
					'A version snapshot was recorded for this target after an approved template-lab apply (%d entry).',
					'Version snapshots were recorded for this target after approved template-lab applies (%d entries).',
					$count,
					'aio-page-builder'
				),
				$count
			);
			$latest = isset( $snap_list[0][ Version_Snapshot_Schema::FIELD_CREATED_AT ] )
				? (string) $snap_list[0][ Version_Snapshot_Schema::FIELD_CREATED_AT ]
				: '';
			if ( $latest !== '' ) {
				$lines[] = sprintf(
					/* translators: %s: ISO 8601 timestamp */
					__( 'Latest lineage recorded: %s', 'aio-page-builder' ),
					$latest
				);
			}
		}

		$show     = $has_trace || $snap_list !== array();
		$headline = $show ? __( 'Governed AI change trail', 'aio-page-builder' ) : '';

		return array(
			'show'     => $show,
			'headline' => $headline,
			'lines'    => $lines,
		);
	}

	/**
	 * @param array{show: bool, headline: string, lines: list<string>} $state
	 */
	public static function render_notice( array $state ): void {
		if ( empty( $state['show'] ) ) {
			return;
		}
		$headline = (string) ( $state['headline'] ?? '' );
		$lines    = isset( $state['lines'] ) && is_array( $state['lines'] ) ? $state['lines'] : array();
		?>
		<div class="notice notice-info inline aio-canonical-ai-lineage-notice" role="status">
			<?php if ( $headline !== '' ) : ?>
				<p><strong><?php echo \esc_html( $headline ); ?></strong></p>
			<?php endif; ?>
			<?php if ( count( $lines ) > 0 ) : ?>
				<ul style="margin-left:1.25em;list-style:disc;">
					<?php foreach ( $lines as $line ) : ?>
						<li><?php echo \esc_html( (string) $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
