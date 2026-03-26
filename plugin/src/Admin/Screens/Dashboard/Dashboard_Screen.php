<?php
/**
 * Dashboard admin screen (spec §49.5): operational overview, readiness cards, activity summaries, quick actions.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Dashboard;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Admin\Dashboard\Dashboard_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the Dashboard as overview and first-run entry point. No mutation actions; deep-links only.
 */
final class Dashboard_Screen {

	public const SLUG = 'aio-page-builder';

	/** Gated by plugin capability; aligned with Industry Author Dashboard (spec §44.3). */
	private const CAPABILITY = Capabilities::VIEW_LOGS;

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Dashboard', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return self::CAPABILITY;
	}

	/**
	 * Capability for add_menu_page() only. Hidden routes use remove_submenu_page(); WordPress
	 * user_can_access_admin_page() then checks the parent menu capability, so it must align
	 * with typical operators (manage_options). Screen bodies still use get_capability().
	 *
	 * @return string
	 */
	public function get_menu_capability(): string {
		return 'manage_options';
	}

	/**
	 * Renders the Dashboard. Enforces VIEW_LOGS here; menu registration uses get_menu_capability().
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access the dashboard.', 'aio-page-builder' ), 403 );
		}
		$state = $this->get_state();
		?>
		<div class="wrap aio-page-builder-screen aio-dashboard" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1 class="aio-dashboard-title"><?php echo \esc_html( $this->get_title() ); ?></h1>
			<style>
				.aio-dashboard-shell { max-width: 1200px; }
				.aio-dash-hero {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
					gap: 1rem;
					margin: 1.25rem 0 1.5rem;
				}
				.aio-dash-hero-card {
					background: linear-gradient(145deg, #f6f7f9 0%, #fff 55%, #eef2f7 100%);
					border: 1px solid #dcdcde;
					border-radius: 10px;
					padding: 1.1rem 1.25rem;
					box-shadow: 0 1px 2px rgba(0,0,0,.04);
				}
				.aio-dash-hero-card h2 {
					margin: 0 0 0.35rem;
					font-size: 0.78rem;
					font-weight: 600;
					text-transform: uppercase;
					letter-spacing: 0.06em;
					color: #646970;
				}
				.aio-dash-hero-stat {
					margin: 0;
					font-size: 1.85rem;
					font-weight: 700;
					line-height: 1.15;
					color: #1d2327;
				}
				.aio-dash-hero-meta { margin: 0.35rem 0 0; font-size: 0.85rem; color: #50575e; }
				.aio-dash-onboard-hero {
					margin: 0 0 1.5rem;
					padding: 1.35rem 1.5rem;
					border-radius: 12px;
					border: 1px solid #c3c4c7;
					background: linear-gradient(110deg, #1e3a5f 0%, #2271b1 42%, #72aee6 100%);
					color: #fff;
					box-shadow: 0 8px 24px rgba(30, 58, 95, 0.25);
				}
				.aio-dash-onboard-hero h2 { margin: 0 0 0.5rem; font-size: 1.35rem; color: #fff; }
				.aio-dash-onboard-hero p { margin: 0 0 1rem; max-width: 52rem; opacity: 0.95; }
				.aio-dash-onboard-hero .button.button-hero {
					background: #fff;
					border-color: #fff;
					color: #1e3a5f;
					font-weight: 600;
				}
				.aio-dash-onboard-resume {
					margin: 0 0 1.5rem;
					padding: 1rem 1.25rem;
					border-radius: 10px;
					border-left: 4px solid #2271b1;
					background: #f0f6fc;
				}
				.aio-dash-strip {
					display: flex;
					flex-wrap: wrap;
					align-items: center;
					gap: 0.75rem 1rem;
					margin: 0 0 1.25rem;
					padding: 0.75rem 1rem;
					background: #fff;
					border: 1px solid #dcdcde;
					border-radius: 8px;
				}
				.aio-dash-pulse {
					margin: 0 0 1.5rem;
					padding: 1rem 1.15rem;
					background: #fcfcfc;
					border: 1px solid #e0e0e0;
					border-radius: 8px;
				}
				.aio-dash-pulse h2 { margin: 0 0 0.65rem; font-size: 1rem; }
				.aio-dash-pulse ul { margin: 0; padding-left: 1.2em; }
				.aio-dash-pulse li { margin: 0.25em 0; }
				.aio-dash-grid {
					display: grid;
					grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
					gap: 1rem;
					margin: 1rem 0 1.5rem;
				}
				.aio-dash-tile {
					display: block;
					padding: 1rem 1.1rem;
					text-decoration: none;
					color: #1d2327;
					border: 1px solid #dcdcde;
					border-radius: 10px;
					background: #fff;
					transition: border-color .15s ease, box-shadow .15s ease;
				}
				.aio-dash-tile:hover, .aio-dash-tile:focus {
					border-color: #2271b1;
					box-shadow: 0 2px 8px rgba(34, 113, 177, 0.12);
					color: #1d2327;
				}
				.aio-dash-tile h3 { margin: 0 0 0.35rem; font-size: 1.02rem; }
				.aio-dash-tile p { margin: 0; font-size: 0.88rem; color: #50575e; line-height: 1.45; }
				.aio-dash-footer-note { font-size: 0.85rem; color: #50575e; margin-top: 1.5rem; }
			</style>
			<div class="aio-dashboard-shell">
		<?php
		$this->render_onboarding_callout( $state['onboarding_callout'] );
		$this->render_metric_hero( $state['overview_metrics'], $state['activity_pulse'] );
		$this->render_readiness_strip( $state['readiness_strip'] );
		$this->render_queue_warnings( $state['queue_warning_summary'] );
		$this->render_critical_errors( $state['critical_error_summary'] );
		$this->render_activity_pulse( $state['activity_pulse'] );
		$this->render_explore_grid( $state['explore_links'] );
		$this->render_footer_links( $state['footer_links'] );
		?>
			</div>
		</div>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$builder = $this->get_state_builder();
		return $builder->build();
	}

	private function get_state_builder(): Dashboard_State_Builder {
		if ( $this->container && $this->container->has( 'dashboard_state_builder' ) ) {
			return $this->container->get( 'dashboard_state_builder' );
		}
		$settings = $this->container && $this->container->has( 'settings' ) ? $this->container->get( 'settings' ) : new \AIOPageBuilder\Infrastructure\Settings\Settings_Service();
		$crawl    = $this->container && $this->container->has( 'crawl_snapshot_service' ) ? $this->container->get( 'crawl_snapshot_service' ) : null;
		$ai       = $this->container && $this->container->has( 'ai_run_repository' ) ? $this->container->get( 'ai_run_repository' ) : null;
		$plans    = $this->container && $this->container->has( 'build_plan_repository' ) ? $this->container->get( 'build_plan_repository' ) : null;
		$queue    = $this->container && $this->container->has( 'job_queue_repository' ) ? $this->container->get( 'job_queue_repository' ) : null;
		$assign   = $this->container && $this->container->has( 'assignment_map_service' ) ? $this->container->get( 'assignment_map_service' ) : null;
		$spend    = $this->container && $this->container->has( 'provider_monthly_spend_service' ) ? $this->container->get( 'provider_monthly_spend_service' ) : null;
		$pricing  = $this->container && $this->container->has( 'provider_pricing_registry' ) ? $this->container->get( 'provider_pricing_registry' ) : null;
		return new Dashboard_State_Builder( $settings, $crawl, $ai, $plans, $queue, $assign, $spend, $pricing );
	}

	/**
	 * @param array{visible: bool, variant: string, headline: string, body: string, cta_label: string, url: string} $callout
	 * @return void
	 */
	private function render_onboarding_callout( array $callout ): void {
		if ( empty( $callout['visible'] ) ) {
			return;
		}
		$variant = (string) ( $callout['variant'] ?? '' );
		if ( $variant === 'hero' ) {
			?>
			<div class="aio-dash-onboard-hero" role="region" aria-label="<?php \esc_attr_e( 'Onboarding', 'aio-page-builder' ); ?>">
				<h2><?php echo \esc_html( (string) ( $callout['headline'] ?? '' ) ); ?></h2>
				<p><?php echo \esc_html( (string) ( $callout['body'] ?? '' ) ); ?></p>
				<a class="button button-hero button-large" href="<?php echo \esc_url( (string) ( $callout['url'] ?? '' ) ); ?>"><?php echo \esc_html( (string) ( $callout['cta_label'] ?? '' ) ); ?></a>
			</div>
			<?php
			return;
		}
		if ( $variant === 'resume' ) {
			?>
			<div class="aio-dash-onboard-resume" role="region">
				<p style="margin:0 0 0.5rem;"><strong><?php echo \esc_html( (string) ( $callout['headline'] ?? '' ) ); ?></strong> — <?php echo \esc_html( (string) ( $callout['body'] ?? '' ) ); ?></p>
				<a class="button button-primary" href="<?php echo \esc_url( (string) ( $callout['url'] ?? '' ) ); ?>"><?php echo \esc_html( (string) ( $callout['cta_label'] ?? '' ) ); ?></a>
			</div>
			<?php
		}
	}

	/**
	 * @param array{built_pages: int, ai_spend_mtd_usd: float, ai_spend_label: string, active_plans: int, provider_ready: bool} $metrics
	 * @param array{ai_hub_url: string, plans_hub_url: string}                                                                  $pulse
	 * @return void
	 */
	private function render_metric_hero( array $metrics, array $pulse ): void {
		$ai_url    = (string) ( $pulse['ai_hub_url'] ?? '' );
		$plans_url = (string) ( $pulse['plans_hub_url'] ?? '' );
		?>
		<div class="aio-dash-hero" role="region" aria-label="<?php \esc_attr_e( 'Overview metrics', 'aio-page-builder' ); ?>">
			<div class="aio-dash-hero-card">
				<h2><?php \esc_html_e( 'Pages with builder assignments', 'aio-page-builder' ); ?></h2>
				<p class="aio-dash-hero-stat"><?php echo \esc_html( (string) (int) ( $metrics['built_pages'] ?? 0 ) ); ?></p>
				<p class="aio-dash-hero-meta"><?php \esc_html_e( 'Distinct pages with a template or composition map from this plugin.', 'aio-page-builder' ); ?></p>
			</div>
			<div class="aio-dash-hero-card">
				<h2><?php \esc_html_e( 'AI spend (this month)', 'aio-page-builder' ); ?></h2>
				<p class="aio-dash-hero-stat"><?php echo \esc_html( (string) ( $metrics['ai_spend_label'] ?? '$0.00 MTD' ) ); ?></p>
				<p class="aio-dash-hero-meta">
					<?php if ( Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) && $ai_url !== '' ) : ?>
						<a href="<?php echo \esc_url( $ai_url ); ?>"><?php \esc_html_e( 'Open AI workspace for runs & detail', 'aio-page-builder' ); ?></a>
					<?php else : ?>
						<?php \esc_html_e( 'Estimated from recorded run costs.', 'aio-page-builder' ); ?>
					<?php endif; ?>
				</p>
			</div>
			<div class="aio-dash-hero-card">
				<h2><?php \esc_html_e( 'Active build plans', 'aio-page-builder' ); ?></h2>
				<p class="aio-dash-hero-stat"><?php echo \esc_html( (string) (int) ( $metrics['active_plans'] ?? 0 ) ); ?></p>
				<p class="aio-dash-hero-meta">
					<?php if ( Capabilities::current_user_can_for_route( Capabilities::VIEW_BUILD_PLANS ) && $plans_url !== '' ) : ?>
						<a href="<?php echo \esc_url( $plans_url ); ?>"><?php \esc_html_e( 'View plans & analytics', 'aio-page-builder' ); ?></a>
					<?php else : ?>
						<?php \esc_html_e( 'In progress, review, or approved.', 'aio-page-builder' ); ?>
					<?php endif; ?>
				</p>
			</div>
			<div class="aio-dash-hero-card">
				<h2><?php \esc_html_e( 'AI provider', 'aio-page-builder' ); ?></h2>
				<p class="aio-dash-hero-stat"><?php echo ! empty( $metrics['provider_ready'] ) ? \esc_html__( 'Connected', 'aio-page-builder' ) : \esc_html__( 'Not set', 'aio-page-builder' ); ?></p>
				<p class="aio-dash-hero-meta"><?php \esc_html_e( 'Credentials status from provider settings.', 'aio-page-builder' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array{all_ready: bool, summary: string, diagnostics_url: string} $strip
	 * @return void
	 */
	private function render_readiness_strip( array $strip ): void {
		$url = (string) ( $strip['diagnostics_url'] ?? '' );
		?>
		<div class="aio-dash-strip">
			<span class="dashicons <?php echo ! empty( $strip['all_ready'] ) ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" style="color:<?php echo ! empty( $strip['all_ready'] ) ? '#00a32a' : '#dba617'; ?>;" aria-hidden="true"></span>
			<span><?php echo \esc_html( (string) ( $strip['summary'] ?? '' ) ); ?></span>
			<?php if ( Capabilities::current_user_can_for_route( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS ) && $url !== '' ) : ?>
				<span><a href="<?php echo \esc_url( $url ); ?>"><?php \esc_html_e( 'Full diagnostics', 'aio-page-builder' ); ?></a></span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param array{last_crawl: array|null, last_ai_run: array|null, active_build_plans: array, plans_hub_url: string, ai_hub_url: string, crawler_url: string} $pulse
	 * @return void
	 */
	private function render_activity_pulse( array $pulse ): void {
		?>
		<div class="aio-dash-pulse">
			<h2><?php \esc_html_e( 'Latest activity', 'aio-page-builder' ); ?></h2>
			<ul>
				<li>
					<?php \esc_html_e( 'Last crawl:', 'aio-page-builder' ); ?>
					<?php
					$crawl = $pulse['last_crawl'] ?? null;
					if ( is_array( $crawl ) && ( $crawl['run_id'] ?? '' ) !== '' ) {
						$sess_url = \add_query_arg(
							array(
								'page'   => 'aio-page-builder-crawler-sessions',
								'run_id' => (string) $crawl['run_id'],
							),
							\admin_url( 'admin.php' )
						);
						echo ' ';
						echo \esc_html( (string) ( $crawl['final_status'] ?? '' ) );
						echo ' — ';
						?>
						<a href="<?php echo \esc_url( $sess_url ); ?>"><?php \esc_html_e( 'Open session', 'aio-page-builder' ); ?></a>
						<?php
					} else {
						echo ' ';
						\esc_html_e( 'none yet', 'aio-page-builder' );
					}
					?>
				</li>
				<li>
					<?php \esc_html_e( 'Last AI run:', 'aio-page-builder' ); ?>
					<?php
					$run = $pulse['last_ai_run'] ?? null;
					if ( is_array( $run ) && ( $run['run_id'] ?? '' ) !== '' && Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) ) {
						$run_url = \add_query_arg(
							array(
								'page'    => 'aio-page-builder-ai-workspace',
								'aio_tab' => 'ai_runs',
								'run_id'  => (string) $run['run_id'],
							),
							\admin_url( 'admin.php' )
						);
						echo ' ';
						echo \esc_html( (string) ( $run['status'] ?? '' ) );
						echo ' — ';
						?>
						<a href="<?php echo \esc_url( $run_url ); ?>"><?php \esc_html_e( 'Open run', 'aio-page-builder' ); ?></a>
						<?php
					} else {
						echo ' ';
						\esc_html_e( 'none yet', 'aio-page-builder' );
					}
					?>
				</li>
				<li>
					<?php
					$plans = isset( $pulse['active_build_plans'] ) && is_array( $pulse['active_build_plans'] ) ? $pulse['active_build_plans'] : array();
					if ( count( $plans ) > 0 && Capabilities::current_user_can_for_route( Capabilities::VIEW_BUILD_PLANS ) ) {
						$first    = $plans[0];
						$plan_key = ( ( $first['title'] ?? '' ) !== '' && ( $first['title'] ?? '' ) !== null ) ? (string) $first['title'] : (string) ( $first['plan_id'] ?? '' );
						$purl     = \add_query_arg(
							array(
								'page'    => 'aio-page-builder-build-plans',
								'plan_id' => (string) ( $first['plan_id'] ?? '' ),
							),
							\admin_url( 'admin.php' )
						);
						\esc_html_e( 'Newest active plan:', 'aio-page-builder' );
						echo ' ';
						?>
						<a href="<?php echo \esc_url( $purl ); ?>"><?php echo \esc_html( $plan_key ); ?></a>
						<?php
					} else {
						\esc_html_e( 'No active build plans in the recent window.', 'aio-page-builder' );
					}
					?>
				</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * @param list<array{title: string, description: string, url: string, capability: string}> $links
	 * @return void
	 */
	private function render_explore_grid( array $links ): void {
		if ( count( $links ) === 0 ) {
			return;
		}
		?>
		<h2 class="title" style="margin-top:0.5rem;"><?php \esc_html_e( 'Go deeper', 'aio-page-builder' ); ?></h2>
		<p class="description"><?php \esc_html_e( 'Jump into the workspace that matches what you need next.', 'aio-page-builder' ); ?></p>
		<div class="aio-dash-grid">
			<?php foreach ( $links as $link ) : ?>
				<a class="aio-dash-tile" href="<?php echo \esc_url( (string) ( $link['url'] ?? '' ) ); ?>">
					<h3><?php echo \esc_html( (string) ( $link['title'] ?? '' ) ); ?></h3>
					<p><?php echo \esc_html( (string) ( $link['description'] ?? '' ) ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param array{privacy_url: string, import_export_url: string} $footer
	 * @return void
	 */
	private function render_footer_links( array $footer ): void {
		$privacy = (string) ( $footer['privacy_url'] ?? '' );
		$ie      = (string) ( $footer['import_export_url'] ?? '' );
		?>
		<p class="aio-dash-footer-note">
			<?php \esc_html_e( 'Operational reporting is disclosed under', 'aio-page-builder' ); ?>
			<?php if ( Capabilities::current_user_can_for_route( Capabilities::ACCESS_SETTINGS_HUB ) && $privacy !== '' ) : ?>
				<a href="<?php echo \esc_url( $privacy ); ?>"><?php \esc_html_e( 'Privacy & reporting', 'aio-page-builder' ); ?></a>
			<?php else : ?>
				<?php \esc_html_e( 'Privacy & reporting (Settings)', 'aio-page-builder' ); ?>
			<?php endif; ?>
			<?php
			if ( ( Capabilities::current_user_can_for_route( Capabilities::EXPORT_DATA ) || Capabilities::current_user_can_for_route( Capabilities::IMPORT_DATA ) ) && $ie !== '' ) :
				?>
				· <a href="<?php echo \esc_url( $ie ); ?>"><?php \esc_html_e( 'Import / Export', 'aio-page-builder' ); ?></a>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * @param array{has_warnings: bool, message: string, queue_logs_url: string} $summary
	 * @return void
	 */
	private function render_queue_warnings( array $summary ): void {
		if ( ! $summary['has_warnings'] ) {
			return;
		}
		?>
		<div class="notice notice-warning inline" style="margin: 1em 0;">
			<p><?php echo \esc_html( $summary['message'] ); ?> <a href="<?php echo \esc_url( $summary['queue_logs_url'] ); ?>"><?php \esc_html_e( 'Operations', 'aio-page-builder' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * @param array{count: int, items: list<array>, logs_url: string} $summary
	 * @return void
	 */
	private function render_critical_errors( array $summary ): void {
		if ( $summary['count'] === 0 ) {
			return;
		}
		?>
		<div class="notice notice-error inline" style="margin: 1em 0;">
			<p><strong><?php echo \esc_html( sprintf( /* translators: %d: number of delivery failures */ __( '%d developer diagnostics delivery failure(s).', 'aio-page-builder' ), $summary['count'] ) ); ?></strong> <a href="<?php echo \esc_url( $summary['logs_url'] ); ?>"><?php \esc_html_e( 'View in Queue & Logs', 'aio-page-builder' ); ?></a></p>
			<?php if ( count( $summary['items'] ) > 0 ) : ?>
				<ul style="margin: 0.5em 0 0 1.2em;">
					<?php foreach ( $summary['items'] as $item ) : ?>
						<li><?php echo \esc_html( (string) ( $item['attempted_at'] ?? '' ) ); ?> — <?php echo \esc_html( (string) ( $item['failure_reason'] ?? '' ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
