<?php
/**
 * Internal stale-content (asset aging) report screen (Prompt 556). Renders aging report for maintainer triage.
 * Admin-only; read-only; no auto-edit. See industry-asset-aging-scoring-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Asset_Aging_Report_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the stale-content report: summary, by severity, high-impact list, and by-class counts.
 */
final class Industry_Stale_Content_Report_Screen {

	public const SLUG = 'aio-page-builder-industry-stale-content-report';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Stale content report', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Fetches report from aging service.
	 *
	 * @return array<string, mixed>
	 */
	private function get_report(): array {
		if ( ! $this->container instanceof Service_Container || ! $this->container->has( 'industry_asset_aging_report_service' ) ) {
			return array(
				'summary'           => array(
					'total'       => 0,
					'benign'      => 0,
					'advisory'    => 0,
					'high_impact' => 0,
					'by_class'    => array(),
				),
				'items'             => array(),
				'by_class'          => array(),
				'by_severity'       => array(),
				'high_impact_stale' => array(),
				'generated_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
			);
		}
		$service = $this->container->get( 'industry_asset_aging_report_service' );
		if ( ! $service instanceof Industry_Asset_Aging_Report_Service ) {
			return array(
				'summary'           => array(
					'total'       => 0,
					'benign'      => 0,
					'advisory'    => 0,
					'high_impact' => 0,
					'by_class'    => array(),
				),
				'items'             => array(),
				'by_class'          => array(),
				'by_severity'       => array(),
				'high_impact_stale' => array(),
				'generated_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
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
			wp_die( esc_html__( 'You do not have permission to access the Stale Content Report.', 'aio-page-builder' ), 403 );
		}
		$report            = $this->get_report();
		$summary           = isset( $report['summary'] ) && is_array( $report['summary'] ) ? $report['summary'] : array();
		$total             = (int) ( $summary['total'] ?? 0 );
		$benign            = (int) ( $summary['benign'] ?? 0 );
		$advisory          = (int) ( $summary['advisory'] ?? 0 );
		$high_impact       = (int) ( $summary['high_impact'] ?? 0 );
		$by_class          = isset( $summary['by_class'] ) && is_array( $summary['by_class'] ) ? $summary['by_class'] : array();
		$high_impact_stale = isset( $report['high_impact_stale'] ) && is_array( $report['high_impact_stale'] ) ? $report['high_impact_stale'] : array();
		$generated_at      = isset( $report['generated_at'] ) && is_string( $report['generated_at'] ) ? $report['generated_at'] : '';
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-industry-stale-content-report" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'Industry assets scored by file age for maintenance triage. Advisory only; no auto-edit. Use to prioritize content refresh.', 'aio-page-builder' ); ?>
			</p>
			<?php if ( $generated_at !== '' ) : ?>
				<p class="description"><?php esc_html_e( 'Generated:', 'aio-page-builder' ); ?> <?php echo esc_html( $generated_at ); ?></p>
			<?php endif; ?>

			<div class="aio-stale-summary" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1em; margin: 1.5em 0;">
				<section class="aio-stale-widget" aria-labelledby="aio-stale-total-heading">
					<h2 id="aio-stale-total-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Total assets', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) $total ); ?></p>
				</section>
				<section class="aio-stale-widget" aria-labelledby="aio-stale-benign-heading">
					<h2 id="aio-stale-benign-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Benign', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) $benign ); ?></p>
				</section>
				<section class="aio-stale-widget" aria-labelledby="aio-stale-advisory-heading">
					<h2 id="aio-stale-advisory-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Advisory', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) $advisory ); ?></p>
				</section>
				<section class="aio-stale-widget" aria-labelledby="aio-stale-high-heading">
					<h2 id="aio-stale-high-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'High impact stale', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) $high_impact ); ?></p>
				</section>
			</div>

			<?php if ( count( $high_impact_stale ) > 0 ) : ?>
				<section class="aio-stale-high-impact" style="margin-top: 1.5em;" aria-labelledby="aio-stale-high-list-heading">
					<h2 id="aio-stale-high-list-heading"><?php esc_html_e( 'High-impact stale (prioritize review)', 'aio-page-builder' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Asset', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Class', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Days old', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Rationale', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $high_impact_stale, 0, 100 ) as $item ) : ?>
								<?php
								$ref       = isset( $item['asset_ref'] ) && is_string( $item['asset_ref'] ) ? $item['asset_ref'] : '';
								$class     = isset( $item['asset_class'] ) && is_string( $item['asset_class'] ) ? $item['asset_class'] : '';
								$days      = isset( $item['days_old'] ) ? (int) $item['days_old'] : 0;
								$rationale = isset( $item['rationale'] ) && is_string( $item['rationale'] ) ? $item['rationale'] : '';
								?>
								<tr>
									<td><code><?php echo esc_html( $ref ); ?></code></td>
									<td><?php echo esc_html( $class ); ?></td>
									<td><?php echo esc_html( (string) $days ); ?></td>
									<td><?php echo esc_html( $rationale ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php if ( count( $high_impact_stale ) > 100 ) : ?>
						<p class="description"><?php echo esc_html( sprintf( /* translators: %d: total stale items */ __( 'Showing first 100 of %d.', 'aio-page-builder' ), count( $high_impact_stale ) ) ); ?></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $by_class ) ) : ?>
				<section class="aio-stale-by-class" style="margin-top: 1.5em;" aria-labelledby="aio-stale-by-class-heading">
					<h2 id="aio-stale-by-class-heading"><?php esc_html_e( 'Count by asset class', 'aio-page-builder' ); ?></h2>
					<ul style="columns: 2; list-style: disc inside;">
						<?php foreach ( $by_class as $class_name => $count ) : ?>
							<li><?php echo esc_html( $class_name ); ?>: <?php echo esc_html( (string) $count ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<p class="description" style="margin-top: 1.5em;">
				<a href="<?php echo esc_url( Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'author' ) ); ?>" data-aio-ux-action="industry_stale_content_report_back_author" data-aio-ux-section="industry_stale_content_report" data-aio-ux-hub="<?php echo esc_attr( Industry_Profile_Settings_Screen::SLUG ); ?>" data-aio-ux-tab="author"><?php esc_html_e( 'Back to Industry Author Dashboard', 'aio-page-builder' ); ?></a>
			</p>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
