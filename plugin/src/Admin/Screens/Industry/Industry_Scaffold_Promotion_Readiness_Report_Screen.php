<?php
/**
 * Internal scaffold promotion-readiness report screen (Prompt 565). Renders readiness tier and blockers.
 * Admin-only; read-only; no auto-promotion. See industry-scaffold-promotion-readiness-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Promotion_Readiness_Report_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the promotion-readiness report: summary by tier, items with blockers and missing evidence.
 */
final class Industry_Scaffold_Promotion_Readiness_Report_Screen {

	public const SLUG = 'aio-page-builder-industry-scaffold-promotion-readiness-report';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Scaffold promotion readiness', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Fetches report from promotion-readiness service.
	 *
	 * @return array<string, mixed>
	 */
	private function get_report(): array {
		if ( ! $this->container instanceof Service_Container || ! $this->container->has( 'industry_scaffold_promotion_readiness_report_service' ) ) {
			return array(
				'summary'      => array(
					'total'               => 0,
					'scaffold_complete'   => 0,
					'authored_near_ready' => 0,
					'not_near_ready'      => 0,
				),
				'items'        => array(),
				'by_tier'      => array(),
				'generated_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			);
		}
		$service = $this->container->get( 'industry_scaffold_promotion_readiness_report_service' );
		if ( ! $service instanceof Industry_Scaffold_Promotion_Readiness_Report_Service ) {
			return array(
				'summary'      => array(
					'total'               => 0,
					'scaffold_complete'   => 0,
					'authored_near_ready' => 0,
					'not_near_ready'      => 0,
				),
				'items'        => array(),
				'by_tier'      => array(),
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
			wp_die( esc_html__( 'You do not have permission to access the Scaffold promotion readiness report.', 'aio-page-builder' ), 403 );
		}
		$report       = $this->get_report();
		$summary      = isset( $report['summary'] ) && is_array( $report['summary'] ) ? $report['summary'] : array();
		$items        = isset( $report['items'] ) && is_array( $report['items'] ) ? $report['items'] : array();
		$generated_at = isset( $report['generated_at'] ) && is_string( $report['generated_at'] ) ? $report['generated_at'] : '';
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-industry-scaffold-promotion-readiness" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'Readiness of scaffolded assets for promotion. Advisory only; does not replace promotion check or release gate.', 'aio-page-builder' ); ?>
			</p>
			<?php if ( $generated_at !== '' ) : ?>
				<p class="description"><?php esc_html_e( 'Generated:', 'aio-page-builder' ); ?> <?php echo esc_html( $generated_at ); ?></p>
			<?php endif; ?>

			<div class="aio-promo-summary" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1em; margin: 1em 0;">
				<section aria-labelledby="aio-promo-total-heading">
					<h2 id="aio-promo-total-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Total', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) ( $summary['total'] ?? 0 ) ); ?></p>
				</section>
				<section aria-labelledby="aio-promo-complete-heading">
					<h2 id="aio-promo-complete-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Scaffold complete', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) ( $summary['scaffold_complete'] ?? 0 ) ); ?></p>
				</section>
				<section aria-labelledby="aio-promo-near-heading">
					<h2 id="aio-promo-near-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Authored near-ready', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) ( $summary['authored_near_ready'] ?? 0 ) ); ?></p>
				</section>
				<section aria-labelledby="aio-promo-not-heading">
					<h2 id="aio-promo-not-heading" class="aio-widget-title" style="font-size: 1em;"><?php esc_html_e( 'Not near-ready', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) ( $summary['not_near_ready'] ?? 0 ) ); ?></p>
				</section>
			</div>

			<?php if ( count( $items ) > 0 ) : ?>
				<section style="margin-top: 1.5em;" aria-labelledby="aio-promo-items-heading">
					<h2 id="aio-promo-items-heading"><?php esc_html_e( 'Scaffold scopes', 'aio-page-builder' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Ref', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Type', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Score', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Tier', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Blockers', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $items as $row ) : ?>
								<tr>
									<td><code><?php echo esc_html( $row['scaffold_ref'] ?? '' ); ?></code></td>
									<td><?php echo esc_html( $row['scaffold_type'] ?? '' ); ?></td>
									<td><?php echo esc_html( (string) ( $row['readiness_score'] ?? 0 ) ); ?></td>
									<td><?php echo esc_html( $row['readiness_tier'] ?? '' ); ?></td>
									<td><?php echo esc_html( implode( ', ', $row['blockers'] ?? array() ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>

			<p class="description" style="margin-top: 1.5em;">
				<a href="<?php echo esc_url( Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'author' ) ); ?>" data-aio-ux-action="industry_scaffold_promotion_report_back_author" data-aio-ux-section="industry_scaffold_promotion_report" data-aio-ux-hub="<?php echo esc_attr( Industry_Profile_Settings_Screen::SLUG ); ?>" data-aio-ux-tab="author"><?php esc_html_e( 'Back to Industry Author Dashboard', 'aio-page-builder' ); ?></a>
			</p>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
