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

	/** @var string Optional payload key for checkbox field name (array field). */
	public const KEY_SELECTION_FIELD_NAME = 'selection_field_name';

	/**
	 * Renders the item list table.
	 *
	 * @param array<string, mixed> $payload Must contain step_list_rows (array) and optionally column_order (array).
	 * @param string|null          $detail_item_id Currently selected item_id for detail panel; null if none.
	 * @param string               $list_id Optional HTML id for the table wrapper.
	 * @return void
	 */
	public function render( array $payload, ?string $detail_item_id = null, string $list_id = 'aio-step-item-list' ): void {
		$rows  = $payload[ self::KEY_STEP_LIST_ROWS ] ?? array();
		$order = $payload[ self::KEY_COLUMN_ORDER ] ?? array();
		$name  = isset( $payload[ self::KEY_SELECTION_FIELD_NAME ] ) && is_string( $payload[ self::KEY_SELECTION_FIELD_NAME ] )
			? trim( $payload[ self::KEY_SELECTION_FIELD_NAME ] )
			: 'selected[]';
		if ( ! is_array( $rows ) ) {
			$rows = array();
		}
		if ( ! is_array( $order ) ) {
			$order = array();
		}

		if ( empty( $rows ) ) {
			return;
		}

		$first_row         = reset( $rows );
		$columns           = ! empty( $order ) ? $order : ( is_array( $first_row ) ? array_keys( (array) ( $first_row[ self::ROW_KEY_SUMMARY_COLUMNS ] ?? array() ) ) : array() );
		$select_all_id     = $list_id . '-select-all';
		$colspan           = 1 + count( $columns ) + 1 + 1; // Select + columns + status + actions.
		$has_group_headers = isset( $first_row['group_label'] ) && (string) $first_row['group_label'] !== '';
		if ( $has_group_headers ) {
			$rows = $this->sort_rows_for_group_headers( $rows );
		}
		?>
		<div class="aio-step-item-list-wrapper" id="<?php echo \esc_attr( $list_id ); ?>">
			<table class="aio-step-item-list widefat striped" role="grid">
				<thead>
					<tr>
						<th class="aio-col-select" scope="col">
							<label for="<?php echo \esc_attr( $select_all_id ); ?>" class="screen-reader-text"><?php \esc_html_e( 'Select all rows', 'aio-page-builder' ); ?></label>
							<input type="checkbox" id="<?php echo \esc_attr( $select_all_id ); ?>" class="aio-row-select-all" aria-label="<?php \esc_attr_e( 'Select all rows', 'aio-page-builder' ); ?>" />
						</th>
						<?php foreach ( $columns as $col_key ) : ?>
							<th scope="col" class="aio-col-<?php echo \esc_attr( \sanitize_html_class( $col_key ) ); ?>"><?php echo \esc_html( $this->column_header_label( $col_key ) ); ?></th>
						<?php endforeach; ?>
						<th scope="col" class="aio-col-status"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
						<th scope="col" class="aio-col-actions"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$prev_group = '';
					foreach ( $rows as $row ) :
						$group_label = $has_group_headers ? (string) ( $row['group_label'] ?? '' ) : '';
						if ( $group_label !== '' && $group_label !== $prev_group ) {
							$prev_group = $group_label;
							?>
							<tr class="aio-group-header" role="row"><td colspan="<?php echo (int) $colspan; ?>" scope="rowgroup"><?php echo \esc_html( $group_label ); ?></td></tr>
							<?php
						}
						$this->render_row( $row, $columns, $detail_item_id, $name );
					endforeach;
					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Sorts rows by group_label then item_id when group headers are used (Prompt 192).
	 *
	 * @param array<int, array<string, mixed>> $rows
	 * @return array<int, array<string, mixed>>
	 */
	private function sort_rows_for_group_headers( array $rows ): array {
		usort(
			$rows,
			function ( array $a, array $b ): int {
				$label_a = (string) ( $a['group_label'] ?? '' );
				$label_b = (string) ( $b['group_label'] ?? '' );
				$cmp     = strcmp( $label_a, $label_b );
				if ( $cmp !== 0 ) {
					return $cmp;
				}
				$id_a = (string) ( $a[ self::ROW_KEY_ITEM_ID ] ?? '' );
				$id_b = (string) ( $b[ self::ROW_KEY_ITEM_ID ] ?? '' );
				return strcmp( $id_a, $id_b );
			}
		);
		return array_values( $rows );
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
			'current_page_title'       => \__( 'Current Page', 'aio-page-builder' ),
			'current_page_url'         => \__( 'URL/Slug', 'aio-page-builder' ),
			'action'                   => \__( 'Suggested Action', 'aio-page-builder' ),
			'target_template'          => \__( 'Target Template', 'aio-page-builder' ),
			'risk_level'               => \__( 'Risk', 'aio-page-builder' ),
			'proposed_page_title'      => \__( 'Proposed Title', 'aio-page-builder' ),
			'proposed_slug'            => \__( 'Slug', 'aio-page-builder' ),
			'purpose'                  => \__( 'Purpose', 'aio-page-builder' ),
			'template_key'             => \__( 'Template', 'aio-page-builder' ),
			'hierarchy_position'       => \__( 'Parent', 'aio-page-builder' ),
			'page_type'                => \__( 'Page Type', 'aio-page-builder' ),
			'confidence'               => \__( 'Confidence', 'aio-page-builder' ),
			'menu_context'             => \__( 'Menu Context', 'aio-page-builder' ),
			'current_menu_name'        => \__( 'Current Menu', 'aio-page-builder' ),
			'proposed_menu_name'       => \__( 'Proposed Menu', 'aio-page-builder' ),
			'diff_summary'             => \__( 'Items', 'aio-page-builder' ),
			'token_group'              => \__( 'Group', 'aio-page-builder' ),
			'token_name'               => \__( 'Token', 'aio-page-builder' ),
			'current_value'            => \__( 'Current', 'aio-page-builder' ),
			'proposed_value'           => \__( 'Proposed', 'aio-page-builder' ),
			'target_page_title_or_url' => \__( 'Target', 'aio-page-builder' ),
			'action_type'              => \__( 'Action Type', 'aio-page-builder' ),
			'current'                  => \__( 'Current', 'aio-page-builder' ),
			'proposed'                 => \__( 'Proposed', 'aio-page-builder' ),
			'event_at'                 => \__( 'Timestamp', 'aio-page-builder' ),
			'scope'                    => \__( 'Object', 'aio-page-builder' ),
			'actor'                    => \__( 'Actor', 'aio-page-builder' ),
			'result'                   => \__( 'Result', 'aio-page-builder' ),
			'rollback'                 => \__( 'Rollback', 'aio-page-builder' ),
		);
		return $labels[ $col_key ] ?? str_replace( '_', ' ', ucfirst( $col_key ) );
	}

	/**
	 * Renders a single table row.
	 *
	 * @param array<string, mixed> $row Row payload (item_id, status_badge, summary_columns, row_actions, is_selected).
	 * @param array<int, string>   $columns Column keys in order.
	 * @param string|null          $detail_item_id Currently selected item_id.
	 * @param string               $selection_field_name Field name for checkbox input (array field, e.g. selected[]).
	 * @return void
	 */
	private function render_row( array $row, array $columns, ?string $detail_item_id, string $selection_field_name ): void {
		$item_id          = (string) ( $row[ self::ROW_KEY_ITEM_ID ] ?? '' );
		$badge            = (string) ( $row[ self::ROW_KEY_STATUS_BADGE ] ?? '' );
		$summary          = isset( $row[ self::ROW_KEY_SUMMARY_COLUMNS ] ) && is_array( $row[ self::ROW_KEY_SUMMARY_COLUMNS ] ) ? $row[ self::ROW_KEY_SUMMARY_COLUMNS ] : array();
		$actions          = isset( $row[ self::ROW_KEY_ROW_ACTIONS ] ) && is_array( $row[ self::ROW_KEY_ROW_ACTIONS ] ) ? $row[ self::ROW_KEY_ROW_ACTIONS ] : array();
		$is_selected      = ! empty( $row[ self::ROW_KEY_IS_SELECTED ] );
		$is_detail_active = $detail_item_id !== null && $detail_item_id === $item_id;
		$badge_component  = new Status_Badge_Component();
		?>
		<tr class="aio-step-item-row <?php echo $is_detail_active ? 'aio-row-detail-active' : ''; ?>" data-item-id="<?php echo \esc_attr( $item_id ); ?>">
			<td class="aio-col-select">
				<input type="checkbox" class="aio-row-select" name="<?php echo \esc_attr( $selection_field_name ); ?>" value="<?php echo \esc_attr( $item_id ); ?>" <?php echo $is_selected ? ' checked="checked"' : ''; ?> aria-label="<?php echo \esc_attr( sprintf( /* translators: %s: plan item id */ __( 'Select item %s', 'aio-page-builder' ), $item_id ) ); ?>" />
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
	 * Renders row action links/buttons/forms. Supports form_post for POST submit (e.g. rollback request).
	 *
	 * @param array<int, array<string, mixed>> $actions Each: action_id, label, enabled, url (optional), form_post (optional), form_action, hidden_fields.
	 * @param string                           $item_id Item id for data attributes.
	 * @return void
	 */
	private function render_row_actions( array $actions, string $item_id ): void {
		if ( empty( $actions ) ) {
			echo '—';
			return;
		}
		$out = array();
		foreach ( $actions as $action ) {
			$action_id   = (string) ( $action['action_id'] ?? '' );
			$label       = (string) ( $action['label'] ?? $action_id );
			$enabled     = ! empty( $action['enabled'] );
			$url         = isset( $action['url'] ) ? (string) $action['url'] : '';
			$form_post   = ! empty( $action['form_post'] );
			$form_action = isset( $action['form_action'] ) ? (string) $action['form_action'] : '';
			$hidden      = isset( $action['hidden_fields'] ) && is_array( $action['hidden_fields'] ) ? $action['hidden_fields'] : array();
			$css_class   = 'aio-row-action aio-row-action-' . \sanitize_html_class( $action_id );
			if ( ! $enabled ) {
				$out[] = '<span class="' . \esc_attr( $css_class . ' aio-row-action-disabled' ) . '" aria-disabled="true">' . \esc_html( $label ) . '</span>';
			} elseif ( $form_post && $form_action !== '' ) {
				$h = '';
				foreach ( $hidden as $name => $value ) {
					$h .= '<input type="hidden" name="' . \esc_attr( (string) $name ) . '" value="' . \esc_attr( (string) $value ) . '" />';
				}
				$out[] = '<form method="post" action="' . \esc_url( $form_action ) . '" class="aio-row-action-form" style="display:inline;">' . $h . '<button type="submit" class="button button-small ' . \esc_attr( $css_class ) . '" data-item-id="' . \esc_attr( $item_id ) . '" data-action="' . \esc_attr( $action_id ) . '">' . \esc_html( $label ) . '</button></form>';
			} elseif ( $url !== '' ) {
				$out[] = '<a href="' . \esc_url( $url ) . '" class="' . \esc_attr( $css_class ) . '" data-item-id="' . \esc_attr( $item_id ) . '" data-action="' . \esc_attr( $action_id ) . '">' . \esc_html( $label ) . '</a>';
			} else {
				$out[] = '<button type="button" class="button button-small ' . \esc_attr( $css_class ) . '" data-item-id="' . \esc_attr( $item_id ) . '" data-action="' . \esc_attr( $action_id ) . '">' . \esc_html( $label ) . '</button>';
			}
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $out entries built with esc_url/esc_attr/esc_html above.
		echo implode( ' ', $out );
	}
}
