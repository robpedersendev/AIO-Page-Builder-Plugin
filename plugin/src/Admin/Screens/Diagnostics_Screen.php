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
use AIOPageBuilder\Infrastructure\Config\Capabilities;

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
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access diagnostics.', 'aio-page-builder' ), 403 );
		}
		$validator = new Environment_Validator();
		$validator->validate();
		$results = $validator->get_results();
		$passes  = $validator->passes();
		?>
		<div class="wrap aio-page-builder-screen aio-page-builder-diagnostics" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
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
		</div>
		<?php
	}
}
