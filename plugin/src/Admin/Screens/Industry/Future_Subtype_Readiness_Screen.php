<?php
/**
 * Internal future-subtype readiness dashboard screen (Prompt 567). Aggregates subtype
 * planning, subtype scaffold, promotion-readiness, and blocker summaries in one place.
 * Admin-only; read-only; no auto-approval or mutation. See subtype planning docs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\ViewModels\Industry\Future_Subtype_Readiness_View_Model;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Completeness_Report_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Promotion_Readiness_Report_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the future-subtype readiness hub: subtype planning summary, subtype scaffold
 * readiness, subtype promotion-readiness summary, likely blockers, and links.
 */
final class Future_Subtype_Readiness_Screen {

	public const SLUG = 'aio-page-builder-industry-future-subtype-readiness';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Future subtype readiness', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Builds view model from scaffold and promotion-readiness services (subtype-filtered).
	 */
	private function get_view_model(): Future_Subtype_Readiness_View_Model {
		$subtype_scaffold_count = 0;
		$subtype_missing_count  = 0;
		$promo_subtype          = array(
			'total'               => 0,
			'scaffold_complete'   => 0,
			'authored_near_ready' => 0,
			'not_near_ready'      => 0,
		);
		$blocker_count          = 0;

		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_scaffold_completeness_report_service' ) ) {
			$scaffold = $this->container->get( 'industry_scaffold_completeness_report_service' );
			if ( $scaffold instanceof Industry_Scaffold_Completeness_Report_Service ) {
				$report  = $scaffold->generate_report( array() );
				$results = isset( $report['scaffold_results'] ) && is_array( $report['scaffold_results'] ) ? $report['scaffold_results'] : array();
				foreach ( $results as $r ) {
					$type = isset( $r['scaffold_type'] ) && is_string( $r['scaffold_type'] ) ? $r['scaffold_type'] : '';
					if ( $type !== 'subtype' ) {
						continue;
					}
					++$subtype_scaffold_count;
					$classes = isset( $r['artifact_classes'] ) && is_array( $r['artifact_classes'] ) ? $r['artifact_classes'] : array();
					foreach ( $classes as $state ) {
						if ( $state === Industry_Scaffold_Completeness_Report_Service::STATE_MISSING ) {
							++$subtype_missing_count;
							break;
						}
					}
				}
			}
		}
		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_scaffold_promotion_readiness_report_service' ) ) {
			$promo = $this->container->get( 'industry_scaffold_promotion_readiness_report_service' );
			if ( $promo instanceof Industry_Scaffold_Promotion_Readiness_Report_Service ) {
				$report = $promo->generate_report();
				$items  = isset( $report['items'] ) && is_array( $report['items'] ) ? $report['items'] : array();
				foreach ( $items as $item ) {
					$type = isset( $item['scaffold_type'] ) && is_string( $item['scaffold_type'] ) ? $item['scaffold_type'] : '';
					if ( $type !== 'subtype' ) {
						continue;
					}
					++$promo_subtype['total'];
					$tier = isset( $item['readiness_tier'] ) && is_string( $item['readiness_tier'] ) ? $item['readiness_tier'] : '';
					if ( $tier === Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_SCAFFOLD_COMPLETE ) {
						++$promo_subtype['scaffold_complete'];
					} elseif ( $tier === Industry_Scaffold_Promotion_Readiness_Report_Service::TIER_AUTHORED_NEAR_READY ) {
						++$promo_subtype['authored_near_ready'];
					} else {
						++$promo_subtype['not_near_ready'];
					}
				}
				$blocker_count = $promo_subtype['not_near_ready'];
			}
		}

		$hub   = Industry_Profile_Settings_Screen::SLUG;
		$links = array(
			'author_dashboard'   => Admin_Screen_Hub::tab_url( $hub, 'author' ),
			'subtype_comparison' => Admin_Screen_Hub::subtab_url( $hub, 'comparisons', 'subtype' ),
			'scaffold_promotion' => Admin_Screen_Hub::subtab_url( $hub, 'reports', 'scaffold' ),
		);

		return new Future_Subtype_Readiness_View_Model(
			$subtype_scaffold_count,
			$subtype_missing_count,
			$promo_subtype,
			$blocker_count,
			$links
		);
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access the Future subtype readiness screen.', 'aio-page-builder' ), 403 );
		}
		$vm    = $this->get_view_model();
		$promo = $vm->get_promotion_readiness_subtype_summary();
		$links = $vm->get_links();
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-future-subtype-readiness" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php esc_html_e( 'Summary of future-subtype readiness: subtype scaffold progress, promotion readiness, and blockers. Internal planning only; read-only.', 'aio-page-builder' ); ?>
			</p>

			<section class="aio-readiness-section" style="margin-top: 1.5em;" aria-labelledby="aio-fsr-planning-heading">
				<h2 id="aio-fsr-planning-heading"><?php esc_html_e( 'Subtype planning summary', 'aio-page-builder' ); ?></h2>
				<p><?php echo esc_html( (string) $vm->get_subtype_scaffold_count() ); ?> <?php esc_html_e( 'subtype scaffold scope(s).', 'aio-page-builder' ); ?></p>
				<p><a href="<?php echo esc_url( $links['subtype_comparison'] ?? '#' ); ?>" data-aio-ux-action="future_subtype_readiness_subtype_comparison" data-aio-ux-section="future_subtype_readiness" data-aio-ux-hub="<?php echo esc_attr( Industry_Profile_Settings_Screen::SLUG ); ?>" data-aio-ux-tab="author"><?php esc_html_e( 'Subtype comparison', 'aio-page-builder' ); ?></a></p>
			</section>

			<section class="aio-readiness-section" style="margin-top: 1.5em;" aria-labelledby="aio-fsr-scaffold-heading">
				<h2 id="aio-fsr-scaffold-heading"><?php esc_html_e( 'Subtype scaffold readiness', 'aio-page-builder' ); ?></h2>
				<p><?php esc_html_e( 'Subtype scaffolds with at least one missing artifact:', 'aio-page-builder' ); ?> <strong><?php echo esc_html( (string) $vm->get_subtype_missing_count() ); ?></strong></p>
			</section>

			<section class="aio-readiness-section" style="margin-top: 1.5em;" aria-labelledby="aio-fsr-promo-heading">
				<h2 id="aio-fsr-promo-heading"><?php esc_html_e( 'Subtype promotion-readiness summary', 'aio-page-builder' ); ?></h2>
				<p><?php esc_html_e( 'Total (subtype only):', 'aio-page-builder' ); ?> <?php echo esc_html( (string) ( $promo['total'] ?? 0 ) ); ?> &middot; <?php esc_html_e( 'Scaffold complete:', 'aio-page-builder' ); ?> <?php echo esc_html( (string) ( $promo['scaffold_complete'] ?? 0 ) ); ?> &middot; <?php esc_html_e( 'Authored near-ready:', 'aio-page-builder' ); ?> <?php echo esc_html( (string) ( $promo['authored_near_ready'] ?? 0 ) ); ?> &middot; <?php esc_html_e( 'Not near-ready:', 'aio-page-builder' ); ?> <?php echo esc_html( (string) ( $promo['not_near_ready'] ?? 0 ) ); ?></p>
				<p><a href="<?php echo esc_url( $links['scaffold_promotion'] ?? '#' ); ?>"><?php esc_html_e( 'Scaffold promotion readiness report', 'aio-page-builder' ); ?></a></p>
			</section>

			<section class="aio-readiness-section" style="margin-top: 1.5em;" aria-labelledby="aio-fsr-blockers-heading">
				<h2 id="aio-fsr-blockers-heading"><?php esc_html_e( 'Likely blockers', 'aio-page-builder' ); ?></h2>
				<p><?php esc_html_e( 'Subtype scopes not near promotion-ready:', 'aio-page-builder' ); ?> <strong><?php echo esc_html( (string) $vm->get_blocker_count() ); ?></strong></p>
			</section>

			<p class="description" style="margin-top: 1.5em;">
				<a href="<?php echo esc_url( $links['author_dashboard'] ?? '#' ); ?>" data-aio-ux-action="future_subtype_readiness_back_author" data-aio-ux-section="future_subtype_readiness" data-aio-ux-hub="<?php echo esc_attr( Industry_Profile_Settings_Screen::SLUG ); ?>" data-aio-ux-tab="author"><?php esc_html_e( 'Back to Industry Author Dashboard', 'aio-page-builder' ); ?></a>
			</p>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
