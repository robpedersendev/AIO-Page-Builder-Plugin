<?php
/**
 * Diagnostics admin screen (spec §44.3, §53.1). Environment and dependency validation summary.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Bootstrap\Environment_Validator;
use AIOPageBuilder\Diagnostics\Environment_Diagnostics_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Renders environment and dependency validation results. No placeholder; real state only.
 */
final class Diagnostics_Screen {

	public const SLUG = 'aio-page-builder-diagnostics';

	private const CAPABILITY = Capabilities::VIEW_SENSITIVE_DIAGNOSTICS;

	public function get_title(): string {
		return __( 'Diagnostics', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return self::CAPABILITY;
	}

	/**
	 * Renders the screen. Runs Environment_Validator and displays results.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access diagnostics.', 'aio-page-builder' ), 403 );
		}
		$validator = new Environment_Validator();
		$validator->validate();
		$results = $validator->get_results();
		$passes  = $validator->passes();
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-page-builder-diagnostics" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php \esc_html_e( 'Environment and dependency checks. Blocking issues must be resolved for activation and key workflows.', 'aio-page-builder' ); ?>
			</p>
			<?php if ( $passes && count( $results ) === 0 ) : ?>
				<div class="notice notice-success inline"><p><?php \esc_html_e( 'All checks passed.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $passes ) : ?>
				<div class="notice notice-info inline"><p><?php \esc_html_e( 'No blocking issues. Warnings or informational items may appear below.', 'aio-page-builder' ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-error inline"><p><?php \esc_html_e( 'One or more blocking issues were found.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<section class="aio-diagnostics-results" style="margin-top: 1.5em;" aria-labelledby="aio-diagnostics-results-heading">
				<h2 id="aio-diagnostics-results-heading"><?php \esc_html_e( 'Validation results', 'aio-page-builder' ); ?></h2>
				<?php if ( count( $results ) > 0 ) : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php \esc_html_e( 'Category', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Severity', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Code', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Message', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Blocking', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $results as $r ) : ?>
								<tr class="<?php echo $r->is_blocking ? 'aio-diagnostics-blocking' : ''; ?>">
									<td><?php echo \esc_html( $r->category ); ?></td>
									<td><?php echo \esc_html( $r->severity ); ?></td>
									<td><code><?php echo \esc_html( $r->code ); ?></code></td>
									<td><?php echo \esc_html( $r->message ); ?></td>
									<td><?php echo $r->is_blocking ? \esc_html__( 'Yes', 'aio-page-builder' ) : \esc_html__( 'No', 'aio-page-builder' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php \esc_html_e( 'No validation results to display.', 'aio-page-builder' ); ?></p>
				<?php endif; ?>
			</section>

			<section class="aio-diagnostics-live-preview" style="margin-top: 2em;" aria-labelledby="aio-diagnostics-live-preview-heading">
				<h2 id="aio-diagnostics-live-preview-heading"><?php \esc_html_e( 'Live preview', 'aio-page-builder' ); ?></h2>
				<?php
				$lp = Environment_Diagnostics_Service::build_live_preview_snapshot();
				?>
				<table class="widefat striped">
					<tbody>
						<tr>
							<th scope="row"><?php \esc_html_e( 'Hardening (opaque tickets + headers)', 'aio-page-builder' ); ?></th>
							<td><?php echo ! empty( $lp['live_preview_hardening_enabled'] ) ? \esc_html__( 'Yes', 'aio-page-builder' ) : \esc_html__( 'No', 'aio-page-builder' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php \esc_html_e( 'Ticket storage probe', 'aio-page-builder' ); ?></th>
							<td><?php echo ! empty( $lp['ticket_storage_healthy'] ) ? \esc_html__( 'OK', 'aio-page-builder' ) : \esc_html__( 'Check failed', 'aio-page-builder' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php \esc_html_e( 'Default shell mode', 'aio-page-builder' ); ?></th>
							<td><code><?php echo \esc_html( isset( $lp['default_shell_mode'] ) ? (string) $lp['default_shell_mode'] : '' ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php \esc_html_e( 'Preview security headers', 'aio-page-builder' ); ?></th>
							<td><?php echo ! empty( $lp['preview_security_headers'] ) ? \esc_html__( 'Enabled', 'aio-page-builder' ) : \esc_html__( 'Unknown', 'aio-page-builder' ); ?></td>
						</tr>
					</tbody>
				</table>
				<p class="description"><?php \esc_html_e( 'Template live preview uses short-lived opaque tickets, session-bound validation, and minimal CSP / frame / referrer policies on the preview route.', 'aio-page-builder' ); ?></p>
			</section>

			<?php
			$tl_tel = \get_option( Option_Names::TEMPLATE_LAB_TELEMETRY_AGGREGATE, array() );
			$tl_c   = is_array( $tl_tel ) && isset( $tl_tel['c'] ) && is_array( $tl_tel['c'] ) ? $tl_tel['c'] : array();
			?>
			<?php if ( $tl_c !== array() ) : ?>
			<section class="aio-diagnostics-template-lab-telemetry" style="margin-top: 2em;" aria-labelledby="aio-diagnostics-tl-tel-heading">
				<h2 id="aio-diagnostics-tl-tel-heading"><?php \esc_html_e( 'Template lab (aggregate counters)', 'aio-page-builder' ); ?></h2>
				<p class="description"><?php \esc_html_e( 'Coarse local counts only; no prompts, transcripts, or provider payloads.', 'aio-page-builder' ); ?></p>
				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Event', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Count', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tl_c as $ek => $cnt ) : ?>
							<tr>
								<td><code><?php echo \esc_html( (string) $ek ); ?></code></td>
								<td><?php echo \esc_html( (string) (int) $cnt ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
			<?php endif; ?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
