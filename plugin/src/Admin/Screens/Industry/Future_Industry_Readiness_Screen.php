<?php
/**
 * Internal future-industry readiness dashboard screen (Prompt 566). Aggregates candidate,
 * scaffold, promotion-readiness, and blocker summaries in one place for expansion planning.
 * Admin-only; read-only; no auto-approval or mutation. See future-industry evaluation docs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\ViewModels\Industry\Future_Industry_Readiness_View_Model;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Analyzer;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Completeness_Report_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Completeness_Report_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Promotion_Readiness_Report_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the future-industry readiness hub: candidate scorecard summary, scaffold readiness,
 * promotion-readiness summary, likely blockers, and links to deeper reports.
 */
final class Future_Industry_Readiness_Screen {

	public const SLUG = 'aio-page-builder-industry-future-readiness';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Future industry readiness', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Builds view model from completeness, gap, scaffold, and promotion-readiness services.
	 */
	private function get_view_model(): Future_Industry_Readiness_View_Model {
		$expansion_blocker_count   = 0;
		$scaffold_incomplete_count = 0;
		$candidate_label           = __( 'Use future-industry evaluation framework', 'aio-page-builder' );
		$maturity_label            = __( 'Stable', 'aio-page-builder' );
		$promo_summary             = array(
			'total'               => 0,
			'scaffold_complete'   => 0,
			'authored_near_ready' => 0,
			'not_near_ready'      => 0,
		);
		$scaffold_summary          = array(
			'scaffold_count'         => 0,
			'missing_artifact_count' => 0,
		);

		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_pack_completeness_report_service' ) ) {
			$completeness = $this->container->get( 'industry_pack_completeness_report_service' );
			if ( $completeness instanceof Industry_Pack_Completeness_Report_Service ) {
				$report  = $completeness->generate_report( true );
				$results = isset( $report['pack_results'] ) && is_array( $report['pack_results'] ) ? $report['pack_results'] : array();
				foreach ( $results as $r ) {
					$flags                    = isset( $r['blocker_flags'] ) && is_array( $r['blocker_flags'] ) ? $r['blocker_flags'] : array();
					$expansion_blocker_count += count( $flags );
				}
			}
		}
		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_coverage_gap_analyzer' ) ) {
			$analyzer = $this->container->get( 'industry_coverage_gap_analyzer' );
			if ( $analyzer instanceof Industry_Coverage_Gap_Analyzer ) {
				$gap_result = $analyzer->analyze( true );
				$gaps       = isset( $gap_result['gaps'] ) && is_array( $gap_result['gaps'] ) ? $gap_result['gaps'] : array();
				foreach ( $gaps as $g ) {
					if ( ( $g['priority'] ?? '' ) === Industry_Coverage_Gap_Analyzer::PRIORITY_HIGH ) {
						++$expansion_blocker_count;
					}
				}
			}
		}
		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_scaffold_completeness_report_service' ) ) {
			$scaffold = $this->container->get( 'industry_scaffold_completeness_report_service' );
			if ( $scaffold instanceof Industry_Scaffold_Completeness_Report_Service ) {
				$report                             = $scaffold->generate_report( array() );
				$results                            = isset( $report['scaffold_results'] ) && is_array( $report['scaffold_results'] ) ? $report['scaffold_results'] : array();
				$scaffold_summary['scaffold_count'] = count( $results );
				$missing                            = 0;
				foreach ( $results as $r ) {
					$classes = isset( $r['artifact_classes'] ) && is_array( $r['artifact_classes'] ) ? $r['artifact_classes'] : array();
					foreach ( $classes as $state ) {
						if ( $state === Industry_Scaffold_Completeness_Report_Service::STATE_MISSING ) {
							++$missing;
							++$scaffold_incomplete_count;
							break;
						}
					}
				}
				$scaffold_summary['missing_artifact_count'] = $missing;
			}
		}
		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_scaffold_promotion_readiness_report_service' ) ) {
			$promo = $this->container->get( 'industry_scaffold_promotion_readiness_report_service' );
			if ( $promo instanceof Industry_Scaffold_Promotion_Readiness_Report_Service ) {
				$report        = $promo->generate_report();
				$sum           = isset( $report['summary'] ) && is_array( $report['summary'] ) ? $report['summary'] : array();
				$promo_summary = array(
					'total'               => (int) ( $sum['total'] ?? 0 ),
					'scaffold_complete'   => (int) ( $sum['scaffold_complete'] ?? 0 ),
					'authored_near_ready' => (int) ( $sum['authored_near_ready'] ?? 0 ),
					'not_near_ready'      => (int) ( $sum['not_near_ready'] ?? 0 ),
				);
			}
		}

		$hub   = Industry_Profile_Settings_Screen::SLUG;
		$links = array(
			'author_dashboard'       => Admin_Screen_Hub::tab_url( $hub, 'author' ),
			'pack_family_comparison' => Admin_Screen_Hub::subtab_url( $hub, 'comparisons', 'pack_family' ),
			'scaffold_promotion'     => Admin_Screen_Hub::subtab_url( $hub, 'reports', 'scaffold' ),
		);

		return new Future_Industry_Readiness_View_Model(
			$expansion_blocker_count,
			$scaffold_incomplete_count,
			$candidate_label,
			$maturity_label,
			$promo_summary,
			$scaffold_summary,
			$links
		);
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access the Future industry readiness screen.', 'aio-page-builder' ), 403 );
		}
		$vm       = $this->get_view_model();
		$promo    = $vm->get_promotion_readiness_summary();
		$scaffold = $vm->get_scaffold_summary();
		$links    = $vm->get_links();
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-future-industry-readiness" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'Summary of future-industry readiness: candidates, scaffold progress, promotion readiness, and blockers. Internal planning only; read-only.', 'aio-page-builder' ); ?>
			</p>

			<section class="aio-readiness-section" style="margin-top: 1.5em;" aria-labelledby="aio-fir-candidate-heading">
				<h2 id="aio-fir-candidate-heading"><?php esc_html_e( 'Candidate scorecard summary', 'aio-page-builder' ); ?></h2>
				<p><strong><?php esc_html_e( 'Readiness:', 'aio-page-builder' ); ?></strong> <?php echo esc_html( $vm->get_candidate_readiness_label() ); ?></p>
				<p><strong><?php esc_html_e( 'Maturity floor:', 'aio-page-builder' ); ?></strong> <?php echo esc_html( $vm->get_maturity_floor_label() ); ?></p>
				<p><a href="<?php echo esc_url( $links['pack_family_comparison'] ?? '#' ); ?>"><?php esc_html_e( 'Pack family comparison', 'aio-page-builder' ); ?></a></p>
			</section>

			<section class="aio-readiness-section" style="margin-top: 1.5em;" aria-labelledby="aio-fir-scaffold-heading">
				<h2 id="aio-fir-scaffold-heading"><?php esc_html_e( 'Scaffold readiness', 'aio-page-builder' ); ?></h2>
				<p><?php echo esc_html( (string) ( $scaffold['scaffold_count'] ?? 0 ) ); ?> <?php esc_html_e( 'scaffold scope(s);', 'aio-page-builder' ); ?> <?php echo esc_html( (string) ( $scaffold['missing_artifact_count'] ?? 0 ) ); ?> <?php esc_html_e( 'with missing artifacts.', 'aio-page-builder' ); ?></p>
				<p><?php esc_html_e( 'Scaffolds with at least one missing artifact:', 'aio-page-builder' ); ?> <?php echo esc_html( (string) $vm->get_scaffold_incomplete_count() ); ?></p>
			</section>

			<section class="aio-readiness-section" style="margin-top: 1.5em;" aria-labelledby="aio-fir-promo-heading">
				<h2 id="aio-fir-promo-heading"><?php esc_html_e( 'Promotion-readiness summary', 'aio-page-builder' ); ?></h2>
				<p><?php esc_html_e( 'Total:', 'aio-page-builder' ); ?> <?php echo esc_html( (string) ( $promo['total'] ?? 0 ) ); ?> &middot; <?php esc_html_e( 'Scaffold complete:', 'aio-page-builder' ); ?> <?php echo esc_html( (string) ( $promo['scaffold_complete'] ?? 0 ) ); ?> &middot; <?php esc_html_e( 'Authored near-ready:', 'aio-page-builder' ); ?> <?php echo esc_html( (string) ( $promo['authored_near_ready'] ?? 0 ) ); ?> &middot; <?php esc_html_e( 'Not near-ready:', 'aio-page-builder' ); ?> <?php echo esc_html( (string) ( $promo['not_near_ready'] ?? 0 ) ); ?></p>
				<p><a href="<?php echo esc_url( $links['scaffold_promotion'] ?? '#' ); ?>"><?php esc_html_e( 'Scaffold promotion readiness report', 'aio-page-builder' ); ?></a></p>
			</section>

			<section class="aio-readiness-section" style="margin-top: 1.5em;" aria-labelledby="aio-fir-blockers-heading">
				<h2 id="aio-fir-blockers-heading"><?php esc_html_e( 'Likely blockers', 'aio-page-builder' ); ?></h2>
				<p><?php esc_html_e( 'Expansion blocker count (completeness blocker flags + high-priority gaps):', 'aio-page-builder' ); ?> <strong><?php echo esc_html( (string) $vm->get_expansion_blocker_count() ); ?></strong></p>
			</section>

			<p class="description" style="margin-top: 1.5em;">
				<a href="<?php echo esc_url( $links['author_dashboard'] ?? '#' ); ?>"><?php esc_html_e( 'Back to Industry Author Dashboard', 'aio-page-builder' ); ?></a>
			</p>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
