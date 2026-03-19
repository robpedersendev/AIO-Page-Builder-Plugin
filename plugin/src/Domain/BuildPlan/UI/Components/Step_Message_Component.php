<?php
/**
 * Reusable step/plan/row-level message component (spec §31.8, §31.9, build-plan-admin-ia-contract §10–11).
 *
 * Renders a single message with severity style and plain-language text. Supports
 * plan-level, step-level, and row-level placement. Error payload can include
 * summary, related object, retry eligibility, and optional log reference.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders one message. Payload: severity, message (or summary), optional level, related_object, retry_eligible, log_reference.
 *
 * Step_messages payload shape (for list): array of message arrays, each:
 *   - severity: info | warning | error | success
 *   - message or summary: string (plain-language)
 *   - level (optional): plan | step | row
 *   - related_object (optional): string
 *   - retry_eligible (optional): bool
 *   - log_reference (optional): string (shown only when viewer has permission; caller gates)
 */
final class Step_Message_Component {

	/** @var string Payload key for severity. */
	public const KEY_SEVERITY = 'severity';

	/** @var string Payload key for main message text. */
	public const KEY_MESSAGE = 'message';

	/** @var string Payload key for summary (alias when message not set). */
	public const KEY_SUMMARY = 'summary';

	/** @var string Payload key for level. */
	public const KEY_LEVEL = 'level';

	/** @var string Payload key for related object. */
	public const KEY_RELATED_OBJECT = 'related_object';

	/** @var string Payload key for retry eligibility. */
	public const KEY_RETRY_ELIGIBLE = 'retry_eligible';

	/** @var string Payload key for log reference (gated). */
	public const KEY_LOG_REFERENCE = 'log_reference';

	/** @var array<string> Allowed severity values. */
	public const SEVERITIES = array( 'info', 'warning', 'error', 'success' );

	/**
	 * Renders a single message block.
	 *
	 * @param array<string, mixed> $payload severity, message/summary, optional level, related_object, retry_eligible, log_reference.
	 * @return void
	 */
	public function render( array $payload ): void {
		$severity = (string) ( $payload[ self::KEY_SEVERITY ] ?? 'info' );
		if ( ! in_array( $severity, self::SEVERITIES, true ) ) {
			$severity = 'info';
		}
		$message = (string) ( $payload[ self::KEY_MESSAGE ] ?? $payload[ self::KEY_SUMMARY ] ?? '' );
		$level   = (string) ( $payload[ self::KEY_LEVEL ] ?? 'step' );
		$related = isset( $payload[ self::KEY_RELATED_OBJECT ] ) ? (string) $payload[ self::KEY_RELATED_OBJECT ] : '';
		$retry   = ! empty( $payload[ self::KEY_RETRY_ELIGIBLE ] );
		$log_ref = isset( $payload[ self::KEY_LOG_REFERENCE ] ) ? (string) $payload[ self::KEY_LOG_REFERENCE ] : '';

		$css_class = 'aio-step-message aio-message-' . $severity . ' aio-message-level-' . \sanitize_html_class( $level );
		?>
		<div class="<?php echo \esc_attr( $css_class ); ?>" role="alert">
			<p class="aio-step-message-text"><?php echo \esc_html( $message ); ?></p>
			<?php if ( $related !== '' ) : ?>
				<p class="aio-step-message-related"><?php echo \esc_html( __( 'Related:', 'aio-page-builder' ) . ' ' . $related ); ?></p>
			<?php endif; ?>
			<?php if ( $retry ) : ?>
				<p class="aio-step-message-retry"><?php \esc_html_e( 'You can retry this action.', 'aio-page-builder' ); ?></p>
			<?php endif; ?>
			<?php if ( $log_ref !== '' ) : ?>
				<p class="aio-step-message-log"><a href="<?php echo \esc_url( $log_ref ); ?>"><?php \esc_html_e( 'View log', 'aio-page-builder' ); ?></a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders a list of messages (e.g. step-level notices).
	 *
	 * @param array<int, array<string, mixed>> $messages Array of message payloads.
	 * @return void
	 */
	public function render_list( array $messages ): void {
		foreach ( $messages as $msg ) {
			if ( is_array( $msg ) ) {
				$this->render( $msg );
			}
		}
	}
}
