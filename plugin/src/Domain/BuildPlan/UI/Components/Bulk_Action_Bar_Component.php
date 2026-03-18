<?php
/**
 * Reusable bulk-action control bar (spec §31.6, build-plan-admin-ia-contract §8).
 *
 * Renders apply-to-all-eligible, apply-to-selected, deny-all-eligible, clear-selection.
 * All controls remain disabled when no eligible rows (or no selection for apply-to-selected).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders bulk action bar. Payload: bulk_action_states with per-control enabled/label/count.
 *
 * bulk_action_states shape:
 *   - apply_to_all_eligible: { enabled: bool, label: string, count_eligible: int }
 *   - apply_to_selected: { enabled: bool, label: string, count_selected: int }
 *   - deny_all_eligible: { enabled: bool, label: string, count_eligible: int }
 *   - clear_selection: { enabled: bool, label: string }
 */
final class Bulk_Action_Bar_Component {

	/** @var string Payload key for bulk action states. */
	public const KEY_BULK_ACTION_STATES = 'bulk_action_states';

	/** @var string Control key apply to all eligible. */
	public const CONTROL_APPLY_TO_ALL = 'apply_to_all_eligible';

	/** @var string Control key apply to selected. */
	public const CONTROL_APPLY_TO_SELECTED = 'apply_to_selected';

	/** @var string Control key deny all eligible. */
	public const CONTROL_DENY_ALL = 'deny_all_eligible';

	/** @var string Control key clear selection. */
	public const CONTROL_CLEAR_SELECTION = 'clear_selection';

	/** @var string Control payload key enabled. */
	public const STATE_KEY_ENABLED = 'enabled';

	/** @var string Control payload key label. */
	public const STATE_KEY_LABEL = 'label';

	/** @var string Control payload key count_eligible. */
	public const STATE_KEY_COUNT_ELIGIBLE = 'count_eligible';

	/** @var string Control payload key count_selected. */
	public const STATE_KEY_COUNT_SELECTED = 'count_selected';

	/**
	 * Renders the bulk action bar above the item list.
	 *
	 * @param array<string, mixed> $payload Must contain bulk_action_states (assoc array of control states).
	 * @param string               $bar_id Optional HTML id.
	 * @return void
	 */
	public function render( array $payload, string $bar_id = 'aio-bulk-action-bar' ): void {
		$states = $payload[ self::KEY_BULK_ACTION_STATES ] ?? array();
		if ( ! is_array( $states ) ) {
			$states = array();
		}

		$controls = array(
			self::CONTROL_APPLY_TO_ALL      => array(
				'label_default' => \__( 'Apply to all eligible', 'aio-page-builder' ),
				'count_key'     => self::STATE_KEY_COUNT_ELIGIBLE,
			),
			self::CONTROL_APPLY_TO_SELECTED => array(
				'label_default' => \__( 'Apply to selected', 'aio-page-builder' ),
				'count_key'     => self::STATE_KEY_COUNT_SELECTED,
			),
			self::CONTROL_DENY_ALL          => array(
				'label_default' => \__( 'Deny all eligible', 'aio-page-builder' ),
				'count_key'     => self::STATE_KEY_COUNT_ELIGIBLE,
			),
			self::CONTROL_CLEAR_SELECTION   => array(
				'label_default' => \__( 'Clear selection', 'aio-page-builder' ),
				'count_key'     => null,
			),
		);
		?>
		<div class="aio-bulk-action-bar" id="<?php echo \esc_attr( $bar_id ); ?>">
			<?php
			foreach ( $controls as $control_key => $config ) {
				$state      = isset( $states[ $control_key ] ) && is_array( $states[ $control_key ] ) ? $states[ $control_key ] : array();
				$enabled    = ! empty( $state[ self::STATE_KEY_ENABLED ] );
				$label      = (string) ( $state[ self::STATE_KEY_LABEL ] ?? $config['label_default'] );
				$count_key  = $config['count_key'];
				$count      = $count_key !== null ? (int) ( $state[ $count_key ] ?? 0 ) : 0;
				$aria_label = $label;
				if ( $count_key !== null && $count > 0 ) {
					$aria_label .= ' (' . $count . ')';
				}
				if ( $enabled ) {
					echo '<button type="button" class="button button-secondary aio-bulk-action aio-bulk-' . \esc_attr( $control_key ) . '" data-bulk-action="' . \esc_attr( $control_key ) . '" aria-label="' . \esc_attr( $aria_label ) . '">' . \esc_html( $label );
					if ( $count > 0 ) {
						echo ' <span class="aio-bulk-count">(' . (int) $count . ')</span>';
					}
					echo '</button> ';
				} else {
					echo '<button type="button" class="button button-secondary aio-bulk-action aio-bulk-' . \esc_attr( $control_key ) . '" disabled="disabled" aria-disabled="true" data-bulk-action="' . \esc_attr( $control_key ) . '">' . \esc_html( $label ) . '</button> ';
				}
			}
			?>
		</div>
		<?php
	}
}
