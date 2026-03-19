<?php
/**
 * Reusable detail drawer/panel component (spec §31.5, build-plan-admin-ia-contract §6–7).
 *
 * Renders right-side or lower detail panel with sections. Content is pluggable
 * via detail_panel_sections; row-level actions can be repeated in the panel.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders detail panel for one selected item. Payload: item_id, sections, row_actions (optional).
 *
 * Detail_panel_sections: array of section arrays, each:
 *   - heading: string
 *   - content: string (HTML, pre-escaped by caller) or content_lines: array of strings
 *   - key (optional): string for CSS/data
 */
final class Detail_Panel_Component {

	/** @var string Payload key for selected item_id. */
	public const KEY_ITEM_ID = 'item_id';

	/** @var string Payload key for sections. */
	public const KEY_SECTIONS = 'sections';

	/** @var string Payload key for row actions in detail. */
	public const KEY_ROW_ACTIONS = 'row_actions';

	/** @var string Section key for heading. */
	public const SECTION_KEY_HEADING = 'heading';

	/** @var string Section key for HTML content. */
	public const SECTION_KEY_CONTENT = 'content';

	/** @var string Section key for content as lines. */
	public const SECTION_KEY_CONTENT_LINES = 'content_lines';

	/** @var string Section key for optional CSS/key. */
	public const SECTION_KEY_KEY = 'key';

	/**
	 * Renders the detail panel. Empty or null item_id can render empty state.
	 *
	 * @param array<string, mixed> $payload item_id, sections, optional row_actions.
	 * @param string               $panel_id Optional HTML id.
	 * @return void
	 */
	public function render( array $payload, string $panel_id = 'aio-detail-panel' ): void {
		$item_id     = (string) ( $payload[ self::KEY_ITEM_ID ] ?? '' );
		$sections    = isset( $payload[ self::KEY_SECTIONS ] ) && is_array( $payload[ self::KEY_SECTIONS ] ) ? $payload[ self::KEY_SECTIONS ] : array();
		$row_actions = isset( $payload[ self::KEY_ROW_ACTIONS ] ) && is_array( $payload[ self::KEY_ROW_ACTIONS ] ) ? $payload[ self::KEY_ROW_ACTIONS ] : array();

		$css_class = 'aio-detail-panel aio-build-plan-detail';
		if ( $item_id === '' ) {
			$css_class .= ' aio-detail-panel-empty';
		}
		?>
		<div class="<?php echo \esc_attr( $css_class ); ?>" id="<?php echo \esc_attr( $panel_id ); ?>" role="complementary" aria-label="<?php \esc_attr_e( 'Item detail', 'aio-page-builder' ); ?>">
			<?php if ( $item_id === '' ) : ?>
				<p class="aio-detail-panel-empty-text"><?php \esc_html_e( 'Select a row to view details.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<div class="aio-detail-panel-inner" data-item-id="<?php echo \esc_attr( $item_id ); ?>">
					<?php $this->render_sections( $sections ); ?>
					<?php if ( ! empty( $row_actions ) ) : ?>
						<div class="aio-detail-panel-actions">
							<h4><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></h4>
							<?php $this->render_detail_actions( $row_actions, $item_id ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders section list.
	 *
	 * @param array<int, array<string, mixed>> $sections
	 * @return void
	 */
	private function render_sections( array $sections ): void {
		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}
			$heading     = (string) ( $section[ self::SECTION_KEY_HEADING ] ?? '' );
			$content     = isset( $section[ self::SECTION_KEY_CONTENT ] ) ? (string) $section[ self::SECTION_KEY_CONTENT ] : '';
			$lines       = isset( $section[ self::SECTION_KEY_CONTENT_LINES ] ) && is_array( $section[ self::SECTION_KEY_CONTENT_LINES ] ) ? $section[ self::SECTION_KEY_CONTENT_LINES ] : array();
			$key         = (string) ( $section[ self::SECTION_KEY_KEY ] ?? '' );
			$block_class = 'aio-detail-section';
			if ( $key !== '' ) {
				$block_class .= ' aio-detail-section-' . \sanitize_html_class( $key );
			}
			?>
			<div class="<?php echo \esc_attr( $block_class ); ?>">
				<?php if ( $heading !== '' ) : ?>
					<h4 class="aio-detail-section-heading"><?php echo \esc_html( $heading ); ?></h4>
				<?php endif; ?>
				<div class="aio-detail-section-body">
					<?php
					if ( $content !== '' ) {
						echo \wp_kses_post( $content );
					} elseif ( ! empty( $lines ) ) {
						echo '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $lines ) ) . '</li></ul>';
					}
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Renders action buttons in detail panel (same shape as row_actions).
	 *
	 * @param array<int, array<string, mixed>> $actions
	 * @param string                           $item_id
	 * @return void
	 */
	private function render_detail_actions( array $actions, string $item_id ): void {
		foreach ( $actions as $action ) {
			$action_id = (string) ( $action['action_id'] ?? '' );
			$label     = (string) ( $action['label'] ?? $action_id );
			$enabled   = ! empty( $action['enabled'] );
			$url       = isset( $action['url'] ) ? (string) $action['url'] : '';
			$css_class = 'aio-detail-action aio-detail-action-' . \sanitize_html_class( $action_id );
			if ( ! $enabled ) {
				echo '<span class="button button-small ' . \esc_attr( $css_class . ' aio-detail-action-disabled' ) . '" aria-disabled="true">' . \esc_html( $label ) . '</span> ';
			} elseif ( $url !== '' ) {
				echo '<a href="' . \esc_url( $url ) . '" class="button button-small ' . \esc_attr( $css_class ) . '" data-item-id="' . \esc_attr( $item_id ) . '" data-action="' . \esc_attr( $action_id ) . '">' . \esc_html( $label ) . '</a> ';
			} else {
				echo '<button type="button" class="button button-small ' . \esc_attr( $css_class ) . '" data-item-id="' . \esc_attr( $item_id ) . '" data-action="' . \esc_attr( $action_id ) . '">' . \esc_html( $label ) . '</button> ';
			}
		}
	}
}
