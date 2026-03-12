<?php
/**
 * Reusable step item list/table component (spec §31.5, build-plan-admin-ia-contract §6–7).
 *
 * Renders a table or grid of step_list_rows. Column definitions are step-specific;
 * row payloads carry summary_columns (key => display value). Row actions are
 * supplied per row and rendered with visibility/enabled from payload.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders overview table/grid from step_list_rows payload. Generic; no step-specific column logic.
 *
 * Payload shape step_list_rows: list of row arrays, each:
 *   - item_id: string
 *   - status: string (item status)
 *   - status_badge: string (badge key for Status_Badge_Component)
 *   - summary_columns: array<string, string> (column_key => display_value, escaped by caller)
 *   - row_actions: array of row_actions (see Individual Action UI; enabled/label per action)
 *   - is_selected: bool (for bulk selection)
 *
 * column_order: array of column keys to render in order (defines table headers).
 */
final class Step_Item_List_Component {

	/** @var string Payload key for list of row arrays. */
	public const KEY_STEP_LIST_ROWS = 'step_list_rows';

	/** @var string Payload key for ordered column keys. */
	public const KEY_COLUMN_ORDER = 'column_order';

	/** @var string Row payload key for item_id. */
	public const ROW_KEY_ITEM_ID = 'item_id';

	/** @var string Row payload key for status. */
	public const ROW_KEY_STATUS = 'status';

	/** @var string Row payload key for status_badge. */
	public const ROW_KEY_STATUS_BADGE = 'status_badge';

	/** @var string Row payload key for summary_columns. */
	public const ROW_KEY_SUMMARY_COLUMNS = 'summary_columns';

	/** @var string Row payload key for row_actions. */
	public const ROW_KEY_ROW_ACTIONS = 'row_actions';

	/** @var string Row payload key for is_selected. */
	public const ROW_KEY_IS_SELECTED = 'is_selected';

	/**
	 * Renders the item list table.
	 *
	 * @param array<string, mixed> $payload Must contain step_list_rows (array) and optionally column_order (array).
	 * @param string|null          $detail_item_id Currently selected item_id for detail panel; null if none.
	 * @param string               $list_id Optional HTML id for the table wrapper.
	 * @return void
	 */
	public function render( array $payload, ?string $detail_item_id = null, string $list_id = 'aio-step-item-list' ): void {
		$rows   = $payload[ self::KEY_STEP_LIST_ROWS ] ?? array();
		$order  = $payload[ self::KEY_COLUMN_ORDER ] ?? array();
		if ( ! is_array( $rows ) ) {
			$rows = array();
		}
		if ( ! is_array( $order ) ) {
			$order = array();
		}

		if ( empty( $rows ) ) {
			return;
		}

		$first_row = reset( $rows );
		$columns   = ! empty( $order ) ? $order : ( is_array( $first_row ) ? array_keys( (array) ( $first_row[ self::ROW_KEY_SUMMARY_COLUMNS ] ?? array() ) ) : array() );
		?>
		<div class="aio-step-item-list-wrapper" id="<?php echo \esc_attr( $list_id ); ?>">
			<table class="aio-step-item-list widefat striped" role="grid">
				<thead>
					<tr>
						<th class="aio-col-select" scope="col">
							<label class="screen-reader-text"><?php \esc_html_e( 'Select', 'aio-page-builder' ); ?></label>
							<input type="checkbox" class="aio-row-select-all" aria-label="<?php \esc_attr_e( 'Select all rows', 'aio-page-builder' ); ?>" />
						</th>
						<?php foreach ( $columns as $col_key ) : ?>
							<th scope="col" class="aio-col-<?php echo \esc_attr( \sanitize_html_class( $col_key ) ); ?>"><?php echo \esc_html( $this->column_header_label( $col_key ) ); ?></th>
						<?php endforeach; ?>
						<th scope="col" class="aio-col-status"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
						<th scope="col" class="aio-col-actions"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<?php $this->render_row( $row, $columns, $detail_item_id ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Returns display label for a column key (step-specific overrides via filter or default humanized).
	 *
	 * @param string $col_key Column key from payload.
	 * @return string
	 */
	private function column_header_label( string $col_key ): string {
		$labels = array(
			'title'                    => \__( 'Title', 'aio-page-builder' ),
			'url'                      => \__( 'URL', 'aio-page-builder' ),
			'action_type'              => \__( 'Action', 'aio-page-builder' ),
			'rationale'                => \__( 'Rationale', 'aio-page-builder' ),
			'risk'                     => \__( 'Risk', 'aio-page-builder' ),
			'current_page_title'       => \__( 'Current page title', 'aio-page-builder' ),
			'current_page_url'         => \__( 'Current URL / slug', 'aio-page-builder' ),
			'action'                   => \__( 'Suggested action', 'aio-page-builder' ),
			'target_template'          => \__( 'Target template', 'aio-page-builder' ),
			'reason'                   => \__( 'Reason', 'aio-page-builder' ),
			'risk_level'               => \__( 'Risk level', 'aio-page-builder' ),
			'proposed_page_title'      => \__( 'Proposed title', 'aio-page-builder' ),
			'proposed_slug'            => \__( 'Proposed slug', 'aio-page-builder' ),
			'purpose'                  => \__( 'Purpose', 'aio-page-builder' ),
			'template_key'             => \__( 'Target template / composition', 'aio-page-builder' ),
			'hierarchy_position'       => \__( 'Hierarchy position', 'aio-page-builder' ),
			'page_type'                => \__( 'Page type', 'aio-page-builder' ),
			'confidence'               => \__( 'Confidence', 'aio-page-builder' ),
			'menu_context'             => \__( 'Navigation context', 'aio-page-builder' ),
			'current_menu_name'        => \__( 'Current menu name', 'aio-page-builder' ),
			'proposed_menu_name'       => \__( 'Proposed menu name', 'aio-page-builder' ),
			'diff_summary'             => \__( 'Differences', 'aio-page-builder' ),
			'token_group'              => \__( 'Token group', 'aio-page-builder' ),
			'token_name'               => \__( 'Token name', 'aio-page-builder' ),
			'proposed_value'           => \__( 'Proposed value', 'aio-page-builder' ),
			'target_page_title_or_url' => \__( 'Target page / URL', 'aio-page-builder' ),
			'storage_path_indicator'   => \__( 'Storage path', 'aio-page-builder' ),
			'event_at'                 => \__( 'When', 'aio-page-builder' ),
			'scope'                    => \__( 'Scope', 'aio-page-builder' ),
			'before_after'             => \__( 'Before / after', 'aio-page-builder' ),
			'rollback_eligible'        => \__( 'Rollback eligible', 'aio-page-builder' ),
		);
		return $labels[ $col_key ] ?? str_replace( '_', ' ', ucfirst( $col_key ) );
	}

	/**
	 * Renders a single table row.
	 *
	 * @param array<string, mixed> $row Row payload (item_id, status_badge, summary_columns, row_actions, is_selected).
	 * @param array<int, string>    $columns Column keys in order.
	 * @param string|null           $detail_item_id Currently selected item_id.
	 * @return void
	 */
	private function render_row( array $row, array $columns, ?string $detail_item_id ): void {
		$item_id    = (string) ( $row[ self::ROW_KEY_ITEM_ID ] ?? '' );
		$badge      = (string) ( $row[ self::ROW_KEY_STATUS_BADGE ] ?? '' );
		$summary    = isset( $row[ self::ROW_KEY_SUMMARY_COLUMNS ] ) && is_array( $row[ self::ROW_KEY_SUMMARY_COLUMNS ] ) ? $row[ self::ROW_KEY_SUMMARY_COLUMNS ] : array();
		$actions    = isset( $row[ self::ROW_KEY_ROW_ACTIONS ] ) && is_array( $row[ self::ROW_KEY_ROW_ACTIONS ] ) ? $row[ self::ROW_KEY_ROW_ACTIONS ] : array();
		$is_selected = ! empty( $row[ self::ROW_KEY_IS_SELECTED ] );
		$is_detail_active = $detail_item_id !== null && $detail_item_id === $item_id;
		$badge_component = new Status_Badge_Component();
		?>
		<tr class="aio-step-item-row <?php echo $is_detail_active ? 'aio-row-detail-active' : ''; ?>" data-item-id="<?php echo \esc_attr( $item_id ); ?>">
			<td class="aio-col-select">
				<input type="checkbox" class="aio-row-select" value="<?php echo \esc_attr( $item_id ); ?>" <?php echo $is_selected ? ' checked="checked"' : ''; ?> aria-label="<?php echo \esc_attr( sprintf( __( 'Select item %s', 'aio-page-builder' ), $item_id ) ); ?>" />
			</td>
			<?php foreach ( $columns as $col_key ) : ?>
				<td class="aio-col-<?php echo \esc_attr( \sanitize_html_class( $col_key ) ); ?>"><?php echo isset( $summary[ $col_key ] ) ? \wp_kses_post( (string) $summary[ $col_key ] ) : '—'; ?></td>
			<?php endforeach; ?>
			<td class="aio-col-status">
				<?php $badge_component->render( array( 'status_badge' => $badge ) ); ?>
			</td>
			<td class="aio-col-actions">
				<?php $this->render_row_actions( $actions, $item_id ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders row action links/buttons. Only actions present in payload are shown; enabled state from payload.
	 *
	 * @param array<int, array<string, mixed>> $actions Each: action_id, label, enabled, url (optional).
	 * @param string                           $item_id Item id for data attributes.
	 * @return void
	 */
	private function render_row_actions( array $actions, string $item_id ): void {
		if ( empty( $actions ) ) {
			echo '—';
			return;
		}
		$links = array();
		foreach ( $actions as $action ) {
			$action_id = (string) ( $action['action_id'] ?? '' );
			$label    = (string) ( $action['label'] ?? $action_id );
			$enabled  = ! empty( $action['enabled'] );
			$url      = isset( $action['url'] ) ? (string) $action['url'] : '';
			$css_class = 'aio-row-action aio-row-action-' . \sanitize_html_class( $action_id );
			if ( ! $enabled ) {
				$links[] = '<span class="' . \esc_attr( $css_class . ' aio-row-action-disabled' ) . '" aria-disabled="true">' . \esc_html( $label ) . '</span>';
			} elseif ( $url !== '' ) {
				$links[] = '<a href="' . \esc_url( $url ) . '" class="' . \esc_attr( $css_class ) . '" data-item-id="' . \esc_attr( $item_id ) . '" data-action="' . \esc_attr( $action_id ) . '">' . \esc_html( $label ) . '</a>';
			} else {
				$links[] = '<button type="button" class="button button-small ' . \esc_attr( $css_class ) . '" data-item-id="' . \esc_attr( $item_id ) . '" data-action="' . \esc_attr( $action_id ) . '">' . \esc_html( $label ) . '</button>';
			}
		}
		echo implode( ' ', array_map( function ( $link ) {
			return $link;
		}, $links ) );
	}
}
