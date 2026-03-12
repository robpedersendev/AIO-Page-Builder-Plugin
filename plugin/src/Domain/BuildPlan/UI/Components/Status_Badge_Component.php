<?php
/**
 * Reusable status badge component (spec §31.8, build-plan-admin-ia-contract §10).
 *
 * Renders a severity-styled badge for plan/step/item status. No business logic;
 * accepts status_badge key and optional label override.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a single status badge. Payload: status_badge (string), optional label (string).
 *
 * Badge keys map to CSS classes: aio-badge-{key}. Standard keys: pending, approved,
 * rejected, in_progress, completed, failed, skipped, not_started, blocked, complete, error.
 */
final class Status_Badge_Component {

	/** @var string Payload key for badge slug. */
	public const KEY_STATUS_BADGE = 'status_badge';

	/** @var string Payload key for optional display label. */
	public const KEY_LABEL = 'label';

	/** @var array<string, string> Default labels for common badge keys. */
	private const DEFAULT_LABELS = array(
		'pending'     => 'Pending',
		'approved'    => 'Approved',
		'rejected'    => 'Rejected',
		'skipped'     => 'Skipped',
		'in_progress' => 'In progress',
		'completed'   => 'Completed',
		'failed'      => 'Failed',
		'not_started' => 'Not started',
		'blocked'     => 'Blocked',
		'complete'    => 'Complete',
		'error'       => 'Error',
	);

	/**
	 * Renders the badge markup.
	 *
	 * @param array<string, mixed> $payload Must contain status_badge; may contain label.
	 * @return void
	 */
	public function render( array $payload ): void {
		$badge_key = (string) ( $payload[ self::KEY_STATUS_BADGE ] ?? '' );
		$label     = isset( $payload[ self::KEY_LABEL ] ) ? (string) $payload[ self::KEY_LABEL ] : ( self::DEFAULT_LABELS[ $badge_key ] ?? $badge_key );
		if ( $badge_key === '' ) {
			$badge_key = 'unknown';
		}
		$css_class = 'aio-status-badge aio-badge-' . \sanitize_html_class( $badge_key );
		?>
		<span class="<?php echo \esc_attr( $css_class ); ?>" role="status"><?php echo \esc_html( $label ?: $badge_key ); ?></span>
		<?php
	}
}
