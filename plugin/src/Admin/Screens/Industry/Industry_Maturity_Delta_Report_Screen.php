<?php
/**
 * Internal maturity delta report screen (Prompt 560). Renders maturity trend report for maintainer review.
 * Admin-only; read-only. See industry-maturity-delta-report-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Maturity_Delta_Report_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the maturity delta report: summary, family deltas, capability deltas; no-baseline fallback.
 */
final class Industry_Maturity_Delta_Report_Screen {

	public const SLUG = 'aio-page-builder-industry-maturity-delta-report';

	/** Option key for stored baseline snapshot (optional). */
	public const OPTION_BASELINE_SNAPSHOT = 'aio_industry_maturity_baseline_snapshot';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Maturity delta report', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Fetches report from maturity delta service (optional baseline from options).
	 *
	 * @return array<string, mixed>
	 */
	private function get_report(): array {
		$baseline          = get_option( self::OPTION_BASELINE_SNAPSHOT, null );
		$baseline_snapshot = is_array( $baseline ) ? $baseline : null;

		if ( ! $this->container instanceof Service_Container || ! $this->container->has( 'industry_maturity_delta_report_service' ) ) {
			return array(
				'summary'           => array(
					'improved'    => 0,
					'stagnated'   => 0,
					'regressed'   => 0,
					'no_baseline' => true,
				),
				'family_deltas'     => array(),
				'capability_deltas' => array(),
				'current_snapshot'  => array(
					'families'         => array(),
					'capability_areas' => array(),
					'captured_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
				),
				'generated_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
			);
		}
		$service = $this->container->get( 'industry_maturity_delta_report_service' );
		if ( ! $service instanceof Industry_Maturity_Delta_Report_Service ) {
			return array(
				'summary'           => array(
					'improved'    => 0,
					'stagnated'   => 0,
					'regressed'   => 0,
					'no_baseline' => true,
				),
				'family_deltas'     => array(),
				'capability_deltas' => array(),
				'current_snapshot'  => array(
					'families'         => array(),
					'capability_areas' => array(),
					'captured_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
				),
				'generated_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
			);
		}
		return $service->generate_report( $baseline_snapshot );
	}

	/**
	 * Renders the report. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access the Maturity delta report.', 'aio-page-builder' ), 403 );
		}
		$report            = $this->get_report();
		$summary           = isset( $report['summary'] ) && is_array( $report['summary'] ) ? $report['summary'] : array();
		$no_baseline       = ! empty( $summary['no_baseline'] );
		$family_deltas     = isset( $report['family_deltas'] ) && is_array( $report['family_deltas'] ) ? $report['family_deltas'] : array();
		$capability_deltas = isset( $report['capability_deltas'] ) && is_array( $report['capability_deltas'] ) ? $report['capability_deltas'] : array();
		$generated_at      = isset( $report['generated_at'] ) && is_string( $report['generated_at'] ) ? $report['generated_at'] : '';
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-industry-maturity-delta-report" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'Compare maturity over time (family completeness and capability areas). Advisory only; no auto-prioritization.', 'aio-page-builder' ); ?>
			</p>
			<?php if ( $generated_at !== '' ) : ?>
				<p class="description"><?php esc_html_e( 'Generated:', 'aio-page-builder' ); ?> <?php echo esc_html( $generated_at ); ?></p>
			<?php endif; ?>

			<?php if ( $no_baseline ) : ?>
				<div class="notice notice-info inline" style="margin: 1em 0;">
					<p><?php esc_html_e( 'No baseline snapshot set. Current state is shown below. Store current_snapshot as baseline (e.g. in option aio_industry_maturity_baseline_snapshot) to compare next time.', 'aio-page-builder' ); ?></p>
				</div>
			<?php else : ?>
				<div class="aio-delta-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1em; margin: 1em 0;">
					<section aria-labelledby="aio-delta-improved-heading">
						<h2 id="aio-delta-improved-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Improved', 'aio-page-builder' ); ?></h2>
						<p class="aio-widget-value"><?php echo esc_html( (string) ( $summary['improved'] ?? 0 ) ); ?></p>
					</section>
					<section aria-labelledby="aio-delta-stagnated-heading">
						<h2 id="aio-delta-stagnated-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Stagnated', 'aio-page-builder' ); ?></h2>
						<p class="aio-widget-value"><?php echo esc_html( (string) ( $summary['stagnated'] ?? 0 ) ); ?></p>
					</section>
					<section aria-labelledby="aio-delta-regressed-heading">
						<h2 id="aio-delta-regressed-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Regressed', 'aio-page-builder' ); ?></h2>
						<p class="aio-widget-value"><?php echo esc_html( (string) ( $summary['regressed'] ?? 0 ) ); ?></p>
					</section>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $family_deltas ) ) : ?>
				<section style="margin-top: 1.5em;" aria-labelledby="aio-delta-families-heading">
					<h2 id="aio-delta-families-heading"><?php esc_html_e( 'Family deltas (completeness)', 'aio-page-builder' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Family', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Band (T1 → T2)', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Total (T1 → T2)', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Trend', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $family_deltas as $row ) : ?>
								<tr>
									<td><code><?php echo esc_html( $row['scope_label'] ?? $row['scope'] ?? '' ); ?></code></td>
									<td><?php echo esc_html( ( $row['band_t1'] ?? '' ) . ' → ' . ( $row['band_t2'] ?? '' ) ); ?></td>
									<td><?php echo esc_html( (string) ( $row['total_t1'] ?? 0 ) . ' → ' . (string) ( $row['total_t2'] ?? 0 ) ); ?></td>
									<td><?php echo esc_html( $row['trend'] ?? '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $capability_deltas ) && ! $no_baseline ) : ?>
				<section style="margin-top: 1.5em;" aria-labelledby="aio-delta-capability-heading">
					<h2 id="aio-delta-capability-heading"><?php esc_html_e( 'Capability area deltas', 'aio-page-builder' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Area', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Level (T1 → T2)', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Trend', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $capability_deltas as $row ) : ?>
								<tr>
									<td><?php echo esc_html( $row['area'] ?? '' ); ?></td>
									<td><?php echo esc_html( ( $row['level_t1'] ?? '' ) . ' → ' . ( $row['level_t2'] ?? '' ) ); ?></td>
									<td><?php echo esc_html( $row['trend'] ?? '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>

			<p class="description" style="margin-top: 1.5em;">
				<a href="<?php echo esc_url( Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'author' ) ); ?>" data-aio-ux-action="industry_maturity_delta_report_back_author" data-aio-ux-section="industry_maturity_delta_report" data-aio-ux-hub="<?php echo esc_attr( Industry_Profile_Settings_Screen::SLUG ); ?>" data-aio-ux-tab="author"><?php esc_html_e( 'Back to Industry Author Dashboard', 'aio-page-builder' ); ?></a>
			</p>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
