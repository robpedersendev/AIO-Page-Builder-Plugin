<?php
/**
 * Internal industry subsystem health and validation report (Prompt 390).
 * Shows missing/invalid refs, active-inactive mismatches, profile selections to unavailable packs/bundles.
 * Admin/support-only; no auto-fix; observational.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders industry health report: errors and warnings grouped by severity.
 */
final class Industry_Health_Report_Screen {

	public const SLUG = 'aio-page-builder-industry-health-report';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Industry Health Report', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Builds state for the report: run health check and group by severity.
	 *
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$errors  = array();
		$warnings = array();
		$service = null;

		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_health_check_service' ) ) {
			$service = $this->container->get( 'industry_health_check_service' );
		}
		if ( $service instanceof Industry_Health_Check_Service ) {
			$result = $service->run();
			$errors  = isset( $result['errors'] ) && is_array( $result['errors'] ) ? $result['errors'] : array();
			$warnings = isset( $result['warnings'] ) && is_array( $result['warnings'] ) ? $result['warnings'] : array();
		}

		return array(
			'errors'   => $errors,
			'warnings' => $warnings,
			'has_errors' => count( $errors ) > 0,
			'has_warnings' => count( $warnings ) > 0,
			'healthy'  => count( $errors ) === 0 && count( $warnings ) === 0,
		);
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access the industry health report.', 'aio-page-builder' ), 403 );
		}
		$state = $this->get_state();
		?>
		<div class="wrap aio-page-builder-screen aio-industry-health-report" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="description">
				<?php \esc_html_e( 'Validation of industry packs, profile selections, starter bundles, and cross-registry refs. Internal use only; no automatic repairs.', 'aio-page-builder' ); ?>
			</p>

			<?php if ( $state['healthy'] ) : ?>
				<div class="notice notice-success inline" style="margin: 1em 0;">
					<p><?php \esc_html_e( 'No issues detected. All checked refs resolve and profile selections are consistent.', 'aio-page-builder' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $state['has_errors'] ) : ?>
				<section class="aio-health-errors" aria-labelledby="aio-health-errors-heading" style="margin: 1.5em 0;">
					<h2 id="aio-health-errors-heading" class="aio-health-section-title"><?php \esc_html_e( 'Errors', 'aio-page-builder' ); ?></h2>
					<p><?php \esc_html_e( 'Missing or invalid refs; should be corrected for full industry behavior.', 'aio-page-builder' ); ?></p>
					<table class="widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php \esc_html_e( 'Object type', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Key', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Issue', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Related refs', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $state['errors'] as $issue ) : ?>
								<tr>
									<td><?php echo \esc_html( $issue['object_type'] ?? '' ); ?></td>
									<td><code><?php echo \esc_html( $issue['key'] ?? '' ); ?></code></td>
									<td><?php echo \esc_html( $issue['issue_summary'] ?? '' ); ?></td>
									<td><?php echo \esc_html( implode( ', ', $issue['related_refs'] ?? array() ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>

			<?php if ( $state['has_warnings'] ) : ?>
				<section class="aio-health-warnings" aria-labelledby="aio-health-warnings-heading" style="margin: 1.5em 0;">
					<h2 id="aio-health-warnings-heading" class="aio-health-section-title"><?php \esc_html_e( 'Warnings', 'aio-page-builder' ); ?></h2>
					<p><?php \esc_html_e( 'Inconsistencies or inactive state; system may fall back to generic behavior.', 'aio-page-builder' ); ?></p>
					<table class="widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php \esc_html_e( 'Object type', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Key', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Issue', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Related refs', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $state['warnings'] as $issue ) : ?>
								<tr>
									<td><?php echo \esc_html( $issue['object_type'] ?? '' ); ?></td>
									<td><code><?php echo \esc_html( $issue['key'] ?? '' ); ?></code></td>
									<td><?php echo \esc_html( $issue['issue_summary'] ?? '' ); ?></td>
									<td><?php echo \esc_html( implode( ', ', $issue['related_refs'] ?? array() ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>
		</div>
		<?php
	}
}
