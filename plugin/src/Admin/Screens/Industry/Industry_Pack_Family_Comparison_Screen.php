<?php
/**
 * Internal pack family comparison screen (Prompt 558). Compares launch-industry families,
 * subtype families, and candidate hooks side by side. Read-only; no mutation.
 * See industry-pack-family-comparison-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Analyzer;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Completeness_Report_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders a bounded comparison of pack families using completeness, gap, and maturity-related data.
 */
final class Industry_Pack_Family_Comparison_Screen {

	public const SLUG = 'aio-page-builder-industry-pack-family-comparison';

	/** Max rows to keep the view bounded. */
	private const MAX_ROWS = 100;

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Pack family comparison', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Builds comparison rows from completeness report and gap analyzer. One row per pack or subtype scope.
	 *
	 * @return array{rows: list<array{pack_key: string, subtype_key: string, scope_label: string, band: string, total: int, gap_count: int, blocker_count: int}>, generated_at: string, dashboard_url: string}
	 */
	private function get_state(): array {
		$rows          = array();
		$by_scope_gaps = array();

		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_coverage_gap_analyzer' ) ) {
			$analyzer = $this->container->get( 'industry_coverage_gap_analyzer' );
			if ( $analyzer instanceof Industry_Coverage_Gap_Analyzer ) {
				$gap_result    = $analyzer->analyze( true );
				$by_scope_gaps = isset( $gap_result['by_scope'] ) && is_array( $gap_result['by_scope'] ) ? $gap_result['by_scope'] : array();
			}
		}

		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_pack_completeness_report_service' ) ) {
			$completeness = $this->container->get( 'industry_pack_completeness_report_service' );
			if ( $completeness instanceof Industry_Pack_Completeness_Report_Service ) {
				$report       = $completeness->generate_report( true );
				$pack_results = isset( $report['pack_results'] ) && is_array( $report['pack_results'] ) ? $report['pack_results'] : array();
				foreach ( array_slice( $pack_results, 0, self::MAX_ROWS ) as $r ) {
					$pack_key      = isset( $r['pack_key'] ) && is_string( $r['pack_key'] ) ? $r['pack_key'] : '';
					$subtype_key   = isset( $r['subtype_key'] ) && is_string( $r['subtype_key'] ) ? $r['subtype_key'] : '';
					$scope         = $subtype_key !== '' ? $pack_key . '|' . $subtype_key : $pack_key;
					$gap_count     = count( $by_scope_gaps[ $scope ] ?? array() );
					$blocker_flags = isset( $r['blocker_flags'] ) && is_array( $r['blocker_flags'] ) ? $r['blocker_flags'] : array();
					$scope_label   = $pack_key;
					if ( $subtype_key !== '' ) {
						$scope_label .= ' → ' . $subtype_key;
					}
					$rows[] = array(
						'pack_key'      => $pack_key,
						'subtype_key'   => $subtype_key,
						'scope_label'   => $scope_label,
						'band'          => isset( $r['band'] ) && is_string( $r['band'] ) ? $r['band'] : '',
						'total'         => isset( $r['total'] ) ? (int) $r['total'] : 0,
						'gap_count'     => $gap_count,
						'blocker_count' => count( $blocker_flags ),
					);
				}
			}
		}

		return array(
			'rows'          => $rows,
			'generated_at'  => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'dashboard_url' => Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'author' ),
		);
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access the Pack family comparison screen.', 'aio-page-builder' ), 403 );
		}
		$state = $this->get_state();
		$rows  = $state['rows'];
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-industry-pack-family-comparison" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'Compare launch-industry and subtype families by completeness band, coverage gaps, and blocker count. Internal only; read-only. See industry-pack-family-comparison-contract.md.', 'aio-page-builder' ); ?>
			</p>
			<?php if ( isset( $state['generated_at'] ) && $state['generated_at'] !== '' ) : ?>
				<p class="description"><?php esc_html_e( 'Generated:', 'aio-page-builder' ); ?> <?php echo esc_html( $state['generated_at'] ); ?></p>
			<?php endif; ?>

			<?php if ( count( $rows ) === 0 ) : ?>
				<div class="notice notice-info inline" style="margin: 1em 0;">
					<p><?php esc_html_e( 'No pack or subtype families to compare. Ensure active packs are registered and completeness report is available.', 'aio-page-builder' ); ?></p>
					<p><a href="<?php echo esc_url( $state['dashboard_url'] ?? '#' ); ?>" data-aio-ux-action="industry_pack_family_open_author_dashboard" data-aio-ux-section="industry_pack_family_empty" data-aio-ux-hub="<?php echo esc_attr( Industry_Profile_Settings_Screen::SLUG ); ?>" data-aio-ux-tab="author"><?php esc_html_e( 'Industry Author Dashboard', 'aio-page-builder' ); ?></a></p>
				</div>
				<?php return; ?>
			<?php endif; ?>

			<table class="widefat striped" style="margin-top: 1em;">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Family (pack → subtype)', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Completeness band', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Total score', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Coverage gaps', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Blockers', 'aio-page-builder' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><code><?php echo esc_html( $row['scope_label'] ); ?></code></td>
							<td><?php echo esc_html( $row['band'] ); ?></td>
							<td><?php echo esc_html( (string) $row['total'] ); ?></td>
							<td><?php echo esc_html( (string) $row['gap_count'] ); ?></td>
							<td><?php echo esc_html( (string) $row['blocker_count'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class="description" style="margin-top: 1.5em;">
				<a href="<?php echo esc_url( $state['dashboard_url'] ?? '#' ); ?>" data-aio-ux-action="industry_pack_family_back_author_dashboard" data-aio-ux-section="industry_pack_family_footer" data-aio-ux-hub="<?php echo esc_attr( Industry_Profile_Settings_Screen::SLUG ); ?>" data-aio-ux-tab="author"><?php esc_html_e( 'Back to Industry Author Dashboard', 'aio-page-builder' ); ?></a>
			</p>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
