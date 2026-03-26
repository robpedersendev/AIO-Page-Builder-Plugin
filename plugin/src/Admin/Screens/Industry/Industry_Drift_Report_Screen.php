<?php
/**
 * Internal drift report screen (Prompt 562). Renders contract/schema/convention drift for maintainer review.
 * Admin-only; read-only; no auto-fix. See industry-subsystem-drift-detection-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Drift_Report_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the drift report: summary by severity/type, and findings list.
 */
final class Industry_Drift_Report_Screen {

	public const SLUG = 'aio-page-builder-industry-drift-report';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Drift report', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Fetches report from drift service.
	 *
	 * @return array<string, mixed>
	 */
	private function get_report(): array {
		if ( ! $this->container instanceof Service_Container || ! $this->container->has( 'industry_drift_report_service' ) ) {
			return array(
				'summary'      => array(
					'total'   => 0,
					'severe'  => 0,
					'minor'   => 0,
					'by_type' => array(),
				),
				'items'        => array(),
				'by_severity'  => array(),
				'by_type'      => array(),
				'generated_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			);
		}
		$service = $this->container->get( 'industry_drift_report_service' );
		if ( ! $service instanceof Industry_Drift_Report_Service ) {
			return array(
				'summary'      => array(
					'total'   => 0,
					'severe'  => 0,
					'minor'   => 0,
					'by_type' => array(),
				),
				'items'        => array(),
				'by_severity'  => array(),
				'by_type'      => array(),
				'generated_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			);
		}
		return $service->generate_report();
	}

	/**
	 * Renders the report. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access the Drift report.', 'aio-page-builder' ), 403 );
		}
		$report       = $this->get_report();
		$summary      = isset( $report['summary'] ) && is_array( $report['summary'] ) ? $report['summary'] : array();
		$items        = isset( $report['items'] ) && is_array( $report['items'] ) ? $report['items'] : array();
		$generated_at = isset( $report['generated_at'] ) && is_string( $report['generated_at'] ) ? $report['generated_at'] : '';
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-industry-drift-report" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'Contract, schema, and convention drift. Advisory only; no auto-fix. Resolve or waive per maintenance policy.', 'aio-page-builder' ); ?>
			</p>
			<?php if ( $generated_at !== '' ) : ?>
				<p class="description"><?php esc_html_e( 'Generated:', 'aio-page-builder' ); ?> <?php echo esc_html( $generated_at ); ?></p>
			<?php endif; ?>

			<div class="aio-drift-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1em; margin: 1em 0;">
				<section aria-labelledby="aio-drift-total-heading">
					<h2 id="aio-drift-total-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Total findings', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) ( $summary['total'] ?? 0 ) ); ?></p>
				</section>
				<section aria-labelledby="aio-drift-severe-heading">
					<h2 id="aio-drift-severe-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Severe', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) ( $summary['severe'] ?? 0 ) ); ?></p>
				</section>
				<section aria-labelledby="aio-drift-minor-heading">
					<h2 id="aio-drift-minor-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Minor', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) ( $summary['minor'] ?? 0 ) ); ?></p>
				</section>
			</div>

			<?php if ( count( $items ) > 0 ) : ?>
				<section style="margin-top: 1.5em;" aria-labelledby="aio-drift-findings-heading">
					<h2 id="aio-drift-findings-heading"><?php esc_html_e( 'Findings', 'aio-page-builder' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Type', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Severity', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Evidence', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Explanation', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Review path', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $items, 0, 100 ) as $row ) : ?>
								<tr>
									<td><?php echo esc_html( $row['drift_type'] ?? '' ); ?></td>
									<td><?php echo esc_html( $row['severity'] ?? '' ); ?></td>
									<td><code><?php echo esc_html( implode( ', ', $row['evidence_refs'] ?? array() ) ); ?></code></td>
									<td><?php echo esc_html( $row['explanation'] ?? '' ); ?></td>
									<td><?php echo esc_html( $row['suggested_review_path'] ?? '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php if ( count( $items ) > 100 ) : ?>
						<p class="description"><?php echo esc_html( sprintf( /* translators: %d: total drift items */ __( 'Showing first 100 of %d.', 'aio-page-builder' ), count( $items ) ) ); ?></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<p class="description" style="margin-top: 1.5em;">
				<a href="<?php echo esc_url( Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'author' ) ); ?>"><?php esc_html_e( 'Back to Industry Author Dashboard', 'aio-page-builder' ); ?></a>
			</p>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
