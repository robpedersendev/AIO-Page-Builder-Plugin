<?php
/**
 * Internal Industry Author Dashboard (Prompt 522). Summary of pack health, completeness, release readiness, gaps.
 * Admin-only; read-only; links to detailed screens. See industry-author-dashboard-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\ViewModels\Industry\Future_Expansion_Readiness_Widget_View_Model;
use AIOPageBuilder\Admin\ViewModels\Industry\Industry_Author_Dashboard_View_Model;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Analyzer;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Completeness_Report_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Completeness_Report_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the Industry Author Dashboard: health, completeness, blockers, coverage gaps, quick links.
 */
final class Industry_Author_Dashboard_Screen {

	public const SLUG = 'aio-page-builder-industry-author-dashboard';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Industry Author Dashboard', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * HTML fragment (leading space) of data-aio-ux-* for author dashboard links.
	 *
	 * @param string $action  Stable trace action id.
	 * @param string $section Section id for grouping.
	 */
	private function author_ux_link_attrs( string $action, string $section = 'industry_author_dashboard' ): string {
		return sprintf(
			' data-aio-ux-action="%s" data-aio-ux-section="%s" data-aio-ux-hub="%s" data-aio-ux-tab="author"',
			\esc_attr( $action ),
			\esc_attr( $section ),
			\esc_attr( Industry_Profile_Settings_Screen::SLUG )
		);
	}

	/**
	 * Builds dashboard view model from health, completeness, and gap services.
	 *
	 * @return Industry_Author_Dashboard_View_Model
	 */
	private function get_view_model(): Industry_Author_Dashboard_View_Model {
		$health_errors   = 0;
		$health_warnings = 0;
		$release_grade   = 0;
		$strong          = 0;
		$minimal         = 0;
		$below_minimal   = 0;
		$pack_count      = 0;
		$subtype_count   = 0;
		$blocker_count   = 0;
		$gap_count       = 0;
		$gap_high        = 0;
		$gap_medium      = 0;
		$gap_low         = 0;

		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_health_check_service' ) ) {
			$health = $this->container->get( 'industry_health_check_service' );
			if ( $health instanceof Industry_Health_Check_Service ) {
				$result          = $health->run();
				$errs            = isset( $result['errors'] ) && is_array( $result['errors'] ) ? $result['errors'] : array();
				$warns           = isset( $result['warnings'] ) && is_array( $result['warnings'] ) ? $result['warnings'] : array();
				$health_errors   = count( $errs );
				$health_warnings = count( $warns );
				$blocker_count  += $health_errors;
			}
		}

		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_pack_completeness_report_service' ) ) {
			$completeness = $this->container->get( 'industry_pack_completeness_report_service' );
			if ( $completeness instanceof Industry_Pack_Completeness_Report_Service ) {
				$report        = $completeness->generate_report( true );
				$summary       = isset( $report['summary'] ) && is_array( $report['summary'] ) ? $report['summary'] : array();
				$release_grade = (int) ( $summary['release_grade_count'] ?? 0 );
				$strong        = (int) ( $summary['strong_count'] ?? 0 );
				$minimal       = (int) ( $summary['minimal_count'] ?? 0 );
				$below_minimal = (int) ( $summary['below_minimal_count'] ?? 0 );
				$pack_count    = (int) ( $summary['pack_count'] ?? 0 );
				$subtype_count = (int) ( $summary['subtype_count'] ?? 0 );
				$results       = isset( $report['pack_results'] ) && is_array( $report['pack_results'] ) ? $report['pack_results'] : array();
				foreach ( $results as $r ) {
					$flags          = isset( $r['blocker_flags'] ) && is_array( $r['blocker_flags'] ) ? $r['blocker_flags'] : array();
					$blocker_count += count( $flags );
				}
			}
		}

		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_coverage_gap_analyzer' ) ) {
			$analyzer = $this->container->get( 'industry_coverage_gap_analyzer' );
			if ( $analyzer instanceof Industry_Coverage_Gap_Analyzer ) {
				$gap_result = $analyzer->analyze( true );
				$gaps       = isset( $gap_result['gaps'] ) && is_array( $gap_result['gaps'] ) ? $gap_result['gaps'] : array();
				$gap_count  = count( $gaps );
				foreach ( $gaps as $g ) {
					$p = isset( $g['priority'] ) && is_string( $g['priority'] ) ? $g['priority'] : '';
					if ( $p === Industry_Coverage_Gap_Analyzer::PRIORITY_HIGH ) {
						++$gap_high;
					} elseif ( $p === Industry_Coverage_Gap_Analyzer::PRIORITY_MEDIUM ) {
						++$gap_medium;
					} else {
						++$gap_low;
					}
				}
			}
		}

		$base  = admin_url( 'admin.php' );
		$links = array(
			'industry_profile'             => $base . '?page=' . Industry_Profile_Settings_Screen::SLUG,
			'health_report'                => $base . '?page=' . Industry_Health_Report_Screen::SLUG,
			'stale_content_report'         => $base . '?page=' . Industry_Stale_Content_Report_Screen::SLUG,
			'pack_family_comparison'       => $base . '?page=' . Industry_Pack_Family_Comparison_Screen::SLUG,
			'future_industry_readiness'    => $base . '?page=' . Future_Industry_Readiness_Screen::SLUG,
			'future_subtype_readiness'     => $base . '?page=' . Future_Subtype_Readiness_Screen::SLUG,
			'maturity_delta_report'        => $base . '?page=' . Industry_Maturity_Delta_Report_Screen::SLUG,
			'drift_report'                 => $base . '?page=' . Industry_Drift_Report_Screen::SLUG,
			'scaffold_promotion_readiness' => $base . '?page=' . Industry_Scaffold_Promotion_Readiness_Report_Screen::SLUG,
			'guided_repair'                => $base . '?page=' . Industry_Guided_Repair_Screen::SLUG,
			'subtype_comparison'           => $base . '?page=' . Industry_Subtype_Comparison_Screen::SLUG,
			'bundle_comparison'            => $base . '?page=' . Industry_Starter_Bundle_Comparison_Screen::SLUG,
			'goal_comparison'              => $base . '?page=' . Conversion_Goal_Comparison_Screen::SLUG,
		);

		return new Industry_Author_Dashboard_View_Model(
			$health_errors,
			$health_warnings,
			$health_errors === 0 && $health_warnings === 0,
			$release_grade,
			$strong,
			$minimal,
			$below_minimal,
			$pack_count,
			$subtype_count,
			$blocker_count,
			$gap_count,
			$gap_high,
			$gap_medium,
			$gap_low,
			$links
		);
	}

	/**
	 * Builds the future-expansion readiness widget view model from completeness, gap, and scaffold data.
	 *
	 * @return Future_Expansion_Readiness_Widget_View_Model
	 */
	private function get_future_expansion_readiness_view_model(): Future_Expansion_Readiness_Widget_View_Model {
		$expansion_blocker_count   = 0;
		$scaffold_incomplete_count = 0;
		$candidate_label           = __( 'Use future-industry evaluation framework', 'aio-page-builder' );
		$maturity_label            = __( 'Stable', 'aio-page-builder' );

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
				$report  = $scaffold->generate_report( array() );
				$results = isset( $report['scaffold_results'] ) && is_array( $report['scaffold_results'] ) ? $report['scaffold_results'] : array();
				foreach ( $results as $r ) {
					$classes = isset( $r['artifact_classes'] ) && is_array( $r['artifact_classes'] ) ? $r['artifact_classes'] : array();
					foreach ( $classes as $state ) {
						if ( $state === 'missing' ) {
							++$scaffold_incomplete_count;
							break;
						}
					}
				}
			}
		}

		$base  = admin_url( 'admin.php' );
		$links = array(
			'pack_family_comparison'    => $base . '?page=' . Industry_Pack_Family_Comparison_Screen::SLUG,
			'future_industry_readiness' => $base . '?page=' . Future_Industry_Readiness_Screen::SLUG,
			'future_subtype_readiness'  => $base . '?page=' . Future_Subtype_Readiness_Screen::SLUG,
		);

		return new Future_Expansion_Readiness_Widget_View_Model(
			$expansion_blocker_count,
			$scaffold_incomplete_count,
			$candidate_label,
			$maturity_label,
			$links
		);
	}

	/**
	 * Renders the dashboard. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access the Industry Author Dashboard.', 'aio-page-builder' ), 403 );
		}
		$vm = $this->get_view_model();
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-industry-author-dashboard" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'Summary of pack health, completeness, release readiness, and coverage gaps. Internal use only; details are on linked screens.', 'aio-page-builder' ); ?>
			</p>

			<div class="aio-dashboard-widgets" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1em; margin-top: 1.5em;">
				<section class="aio-dashboard-widget aio-widget-health" aria-labelledby="aio-widget-health-heading">
					<h2 id="aio-widget-health-heading" class="aio-widget-title"><?php esc_html_e( 'Pack health', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value">
						<?php if ( $vm->is_healthy() ) : ?>
							<span class="aio-status-ok"><?php esc_html_e( 'Healthy', 'aio-page-builder' ); ?></span>
						<?php elseif ( $vm->get_health_error_count() > 0 ) : ?>
							<span class="aio-status-error"><?php echo esc_html( (string) $vm->get_health_error_count() ); ?> <?php esc_html_e( 'errors', 'aio-page-builder' ); ?></span>,
							<?php echo esc_html( (string) $vm->get_health_warning_count() ); ?> <?php esc_html_e( 'warnings', 'aio-page-builder' ); ?>
						<?php else : ?>
							<?php echo esc_html( (string) $vm->get_health_warning_count() ); ?> <?php esc_html_e( 'warnings', 'aio-page-builder' ); ?>
						<?php endif; ?>
					</p>
					<p><a href="<?php echo esc_url( $vm->get_links()['health_report'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_link_health_report', 'aio-widget-health' ); ?>><?php esc_html_e( 'Health Report', 'aio-page-builder' ); ?></a></p>
				</section>

				<section class="aio-dashboard-widget aio-widget-completeness" aria-labelledby="aio-widget-completeness-heading">
					<h2 id="aio-widget-completeness-heading" class="aio-widget-title"><?php esc_html_e( 'Completeness', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value">
						<?php echo esc_html( (string) $vm->get_release_grade_count() ); ?> <?php esc_html_e( 'release-grade', 'aio-page-builder' ); ?>,
						<?php echo esc_html( (string) $vm->get_strong_count() ); ?> <?php esc_html_e( 'strong', 'aio-page-builder' ); ?>,
						<?php echo esc_html( (string) $vm->get_minimal_count() ); ?> <?php esc_html_e( 'minimal', 'aio-page-builder' ); ?>,
						<?php echo esc_html( (string) $vm->get_below_minimal_count() ); ?> <?php esc_html_e( 'below minimal', 'aio-page-builder' ); ?>
					</p>
					<p class="description"><?php echo esc_html( (string) $vm->get_pack_count() ); ?> <?php esc_html_e( 'packs', 'aio-page-builder' ); ?>, <?php echo esc_html( (string) $vm->get_subtype_count() ); ?> <?php esc_html_e( 'subtype scopes', 'aio-page-builder' ); ?></p>
				</section>

				<section class="aio-dashboard-widget aio-widget-blockers" aria-labelledby="aio-widget-blockers-heading">
					<h2 id="aio-widget-blockers-heading" class="aio-widget-title"><?php esc_html_e( 'Release blockers / major', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) $vm->get_blocker_count() ); ?> <?php esc_html_e( 'blocker cues', 'aio-page-builder' ); ?></p>
					<p><a href="<?php echo esc_url( $vm->get_links()['health_report'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_link_health_report', 'aio-widget-blockers' ); ?>><?php esc_html_e( 'Health Report', 'aio-page-builder' ); ?></a></p>
				</section>

				<section class="aio-dashboard-widget aio-widget-gaps" aria-labelledby="aio-widget-gaps-heading">
					<h2 id="aio-widget-gaps-heading" class="aio-widget-title"><?php esc_html_e( 'Coverage gaps', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value"><?php echo esc_html( (string) $vm->get_gap_count() ); ?> <?php esc_html_e( 'total', 'aio-page-builder' ); ?></p>
					<p class="description"><?php echo esc_html( (string) $vm->get_gap_high_count() ); ?> <?php esc_html_e( 'high', 'aio-page-builder' ); ?>, <?php echo esc_html( (string) $vm->get_gap_medium_count() ); ?> <?php esc_html_e( 'medium', 'aio-page-builder' ); ?>, <?php echo esc_html( (string) $vm->get_gap_low_count() ); ?> <?php esc_html_e( 'low', 'aio-page-builder' ); ?></p>
				</section>

				<?php
				$expansion_vm = $this->get_future_expansion_readiness_view_model();
				?>
				<section class="aio-dashboard-widget aio-widget-future-expansion" aria-labelledby="aio-widget-future-expansion-heading">
					<h2 id="aio-widget-future-expansion-heading" class="aio-widget-title"><?php esc_html_e( 'Future expansion readiness', 'aio-page-builder' ); ?></h2>
					<p class="aio-widget-value">
						<?php echo esc_html( (string) $expansion_vm->get_expansion_blocker_count() ); ?> <?php esc_html_e( 'expansion blockers', 'aio-page-builder' ); ?>,
						<?php echo esc_html( (string) $expansion_vm->get_scaffold_incomplete_count() ); ?> <?php esc_html_e( 'scaffold incomplete', 'aio-page-builder' ); ?>
					</p>
					<p class="description"><?php echo esc_html( $expansion_vm->get_candidate_readiness_label() ); ?> · <?php echo esc_html( $expansion_vm->get_maturity_floor_label() ); ?></p>
					<p><a href="<?php echo esc_url( $expansion_vm->get_links()['pack_family_comparison'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_link_pack_family_comparison', 'aio-widget-future-expansion' ); ?>><?php esc_html_e( 'Pack family comparison', 'aio-page-builder' ); ?></a> &middot; <a href="<?php echo esc_url( $expansion_vm->get_links()['future_industry_readiness'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_link_future_industry_readiness', 'aio-widget-future-expansion' ); ?>><?php esc_html_e( 'Future industry readiness', 'aio-page-builder' ); ?></a> &middot; <a href="<?php echo esc_url( $expansion_vm->get_links()['future_subtype_readiness'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_link_future_subtype_readiness', 'aio-widget-future-expansion' ); ?>><?php esc_html_e( 'Future subtype readiness', 'aio-page-builder' ); ?></a></p>
				</section>
			</div>

			<section class="aio-dashboard-links" style="margin-top: 2em;" aria-labelledby="aio-dashboard-links-heading">
				<h2 id="aio-dashboard-links-heading"><?php esc_html_e( 'Quick links', 'aio-page-builder' ); ?></h2>
				<ul>
					<li><a href="<?php echo esc_url( $vm->get_links()['industry_profile'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_industry_profile', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Industry Profile', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['health_report'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_health_report', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Industry Health Report', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['stale_content_report'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_stale_content', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Stale content report', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['pack_family_comparison'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_pack_family', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Pack family comparison', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['future_industry_readiness'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_future_industry', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Future industry readiness', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['future_subtype_readiness'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_future_subtype', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Future subtype readiness', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['maturity_delta_report'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_maturity_delta', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Maturity delta report', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['drift_report'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_drift', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Drift report', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['scaffold_promotion_readiness'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_scaffold_promotion', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Scaffold promotion readiness', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['guided_repair'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_guided_repair', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Guided Repair', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['subtype_comparison'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_subtype_comparison', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Subtype comparison', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['bundle_comparison'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_bundle_comparison', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Bundle comparison', 'aio-page-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( $vm->get_links()['goal_comparison'] ?? '#' ); ?>"<?php echo $this->author_ux_link_attrs( 'industry_author_quick_goal_comparison', 'aio-dashboard-quick-links' ); ?>><?php esc_html_e( 'Conversion goal comparison', 'aio-page-builder' ); ?></a></li>
				</ul>
			</section>

			<p class="description" style="margin-top: 1em;">
				<?php esc_html_e( 'Dashboard reflects state at time of load. For latest validation, run the pre-release pipeline or open Health Report.', 'aio-page-builder' ); ?>
			</p>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
