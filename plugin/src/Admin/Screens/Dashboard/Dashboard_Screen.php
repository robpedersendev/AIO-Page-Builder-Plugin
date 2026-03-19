<?php
/**
 * Dashboard admin screen (spec §49.5): operational overview, readiness cards, activity summaries, quick actions.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Dashboard;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Widgets\Industry_Status_Summary_Widget;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
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
	 * Renders the Dashboard. Capability is enforced by menu registration.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access the dashboard.', 'aio-page-builder' ), 403 );
		}
		$state = $this->get_state();
		?>
		<div class="wrap aio-page-builder-screen aio-dashboard" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php
		$this->render_welcome_or_resume( $state['welcome_state'] );
		$this->render_reporting_disclosure_summary();
		$this->render_quick_actions( $state['quick_actions'] );
		$this->render_industry_summary_widget();
		$this->render_readiness_cards( $state['readiness_cards'] );
		$this->render_last_activity( $state['last_activity_cards'] );
		$this->render_queue_warnings( $state['queue_warning_summary'] );
		$this->render_critical_errors( $state['critical_error_summary'] );
		?>
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
		return new Dashboard_State_Builder( $settings, $crawl, $ai, $plans, $queue );
	}

	/**
	 * Renders a short reporting disclosure summary and link to Privacy, Reporting & Settings (spec §46.11).
	 *
	 * @return void
	 */
	private function render_reporting_disclosure_summary(): void {
		$privacy_url = \add_query_arg( array( 'page' => \AIOPageBuilder\Admin\Screens\Settings\Privacy_Reporting_Settings_Screen::SLUG ), \admin_url( 'admin.php' ) );
		?>
		<div class="aio-dashboard-reporting-summary notice notice-info inline" style="margin: 1em 0;">
			<p>
				<?php \esc_html_e( 'This plugin sends operational reports (installation, heartbeat, error summaries) to an approved destination. No secrets or personal data are included.', 'aio-page-builder' ); ?>
				<a href="<?php echo \esc_url( $privacy_url ); ?>"><?php \esc_html_e( 'Privacy, Reporting & Settings', 'aio-page-builder' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * @param array{is_first_run: bool, is_resume: bool, onboarding_url: string} $welcome
	 * @return void
	 */
	private function render_welcome_or_resume( array $welcome ): void {
		if ( ! $welcome['is_first_run'] && ! $welcome['is_resume'] ) {
			return;
		}
		$message = $welcome['is_first_run']
			? __( 'Welcome. Complete onboarding to set up your profile and AI provider, then run a crawl and create a Build Plan.', 'aio-page-builder' )
			: __( 'Resume onboarding to continue setup.', 'aio-page-builder' );
		?>
		<div class="notice notice-info inline aio-dashboard-welcome" style="margin: 1em 0;">
			<p><?php echo \esc_html( $message ); ?> <a href="<?php echo \esc_url( $welcome['onboarding_url'] ); ?>"><?php \esc_html_e( 'Start / Resume Onboarding', 'aio-page-builder' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * @param list<array{label: string, url: string}> $actions
	 * @return void
	 */
	/**
	 * Renders the industry status summary widget when industry subsystem is loaded (Prompt 410).
	 *
	 * @return void
	 */
	private function render_industry_summary_widget(): void {
		if ( $this->container && $this->container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			Industry_Status_Summary_Widget::render( $this->container );
		}
	}

	/**
	 * @param list<array{label: string, url: string}> $actions
	 * @return void
	 */
	private function render_quick_actions( array $actions ): void {
		if ( count( $actions ) === 0 ) {
			return;
		}
		?>
		<div class="aio-dashboard-quick-actions" style="margin: 1em 0;">
			<h2 class="aio-dashboard-section-title"><?php \esc_html_e( 'Quick actions', 'aio-page-builder' ); ?></h2>
			<ul class="aio-dashboard-action-list" style="list-style: none; display: flex; flex-wrap: wrap; gap: 0.5em;">
				<?php foreach ( $actions as $action ) : ?>
					<li><a href="<?php echo \esc_url( $action['url'] ); ?>" class="button"><?php echo \esc_html( $action['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * @param array{environment: array, dependency: array, provider: array} $cards
	 * @return void
	 */
	private function render_readiness_cards( array $cards ): void {
		?>
		<div class="aio-dashboard-readiness" style="margin: 1em 0;">
			<h2 class="aio-dashboard-section-title"><?php \esc_html_e( 'Readiness', 'aio-page-builder' ); ?></h2>
			<div class="aio-dashboard-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1em;">
				<div class="aio-card aio-readiness-environment <?php echo $cards['environment']['ready'] ? 'ready' : 'not-ready'; ?>" style="border: 1px solid #ccc; padding: 1em;">
					<h3 style="margin-top: 0;"><?php \esc_html_e( 'Environment', 'aio-page-builder' ); ?></h3>
					<p><?php echo \esc_html( $cards['environment']['message'] ); ?></p>
				</div>
				<div class="aio-card aio-readiness-dependency <?php echo $cards['dependency']['ready'] ? 'ready' : 'not-ready'; ?>" style="border: 1px solid #ccc; padding: 1em;">
					<h3 style="margin-top: 0;"><?php \esc_html_e( 'Dependencies', 'aio-page-builder' ); ?></h3>
					<p><?php echo \esc_html( $cards['dependency']['message'] ); ?></p>
				</div>
				<div class="aio-card aio-readiness-provider <?php echo $cards['provider']['ready'] ? 'ready' : 'not-ready'; ?>" style="border: 1px solid #ccc; padding: 1em;">
					<h3 style="margin-top: 0;"><?php \esc_html_e( 'AI Provider', 'aio-page-builder' ); ?></h3>
					<p><?php echo \esc_html( $cards['provider']['message'] ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array{last_crawl: array|null, last_ai_run: array|null, active_build_plans: array} $activity
	 * @return void
	 */
	private function render_last_activity( array $activity ): void {
		?>
		<div class="aio-dashboard-activity" style="margin: 1em 0;">
			<h2 class="aio-dashboard-section-title"><?php \esc_html_e( 'Recent activity', 'aio-page-builder' ); ?></h2>
			<div class="aio-dashboard-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1em;">
				<div class="aio-card" style="border: 1px solid #ccc; padding: 1em;">
					<h3 style="margin-top: 0;"><?php \esc_html_e( 'Last crawl', 'aio-page-builder' ); ?></h3>
					<?php if ( $activity['last_crawl'] !== null ) : ?>
						<p><?php echo \esc_html( $activity['last_crawl']['final_status'] ); ?> — <?php echo \esc_html( (string) $activity['last_crawl']['total_discovered'] ); ?> <?php \esc_html_e( 'pages', 'aio-page-builder' ); ?></p>
						<p><a href="
						<?php
						echo \esc_url(
							\add_query_arg(
								array(
									'page'   => 'aio-page-builder-crawler-sessions',
									'run_id' => $activity['last_crawl']['run_id'],
								),
								\admin_url( 'admin.php' )
							)
						);
						?>
									"><?php \esc_html_e( 'View session', 'aio-page-builder' ); ?></a></p>
					<?php else : ?>
						<p><?php \esc_html_e( 'No crawl yet.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>
				</div>
				<div class="aio-card" style="border: 1px solid #ccc; padding: 1em;">
					<h3 style="margin-top: 0;"><?php \esc_html_e( 'Last AI run', 'aio-page-builder' ); ?></h3>
					<?php if ( $activity['last_ai_run'] !== null ) : ?>
						<p><?php echo \esc_html( $activity['last_ai_run']['status'] ); ?> — <?php echo \esc_html( $activity['last_ai_run']['created_at'] ); ?></p>
						<p><a href="
						<?php
						echo \esc_url(
							\add_query_arg(
								array(
									'page'   => 'aio-page-builder-ai-runs',
									'run_id' => $activity['last_ai_run']['run_id'],
								),
								\admin_url( 'admin.php' )
							)
						);
						?>
									"><?php \esc_html_e( 'View run', 'aio-page-builder' ); ?></a></p>
					<?php else : ?>
						<p><?php \esc_html_e( 'No AI run yet.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>
				</div>
				<div class="aio-card" style="border: 1px solid #ccc; padding: 1em;">
					<h3 style="margin-top: 0;"><?php \esc_html_e( 'Active Build Plans', 'aio-page-builder' ); ?></h3>
					<?php if ( count( $activity['active_build_plans'] ) > 0 ) : ?>
						<ul style="margin: 0; padding-left: 1.2em;">
							<?php foreach ( array_slice( $activity['active_build_plans'], 0, 3 ) as $plan ) : ?>
								<li><a href="
								<?php
								echo \esc_url(
									\add_query_arg(
										array(
											'page'    => 'aio-page-builder-build-plans',
											'plan_id' => $plan['plan_id'],
										),
										\admin_url( 'admin.php' )
									)
								);
								?>
												"><?php echo \esc_html( ( $plan['title'] !== '' && $plan['title'] !== null ) ? $plan['title'] : $plan['plan_id'] ); ?></a> (<?php echo \esc_html( $plan['status'] ); ?>)</li>
							<?php endforeach; ?>
						</ul>
						<p><a href="<?php echo \esc_url( \add_query_arg( array( 'page' => 'aio-page-builder-build-plans' ), \admin_url( 'admin.php' ) ) ); ?>"><?php \esc_html_e( 'View all', 'aio-page-builder' ); ?></a></p>
					<?php else : ?>
						<p><?php \esc_html_e( 'No active plans.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
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
		<div class="aio-dashboard-queue-warning notice notice-warning inline" style="margin: 1em 0;">
			<p><?php echo \esc_html( $summary['message'] ); ?> <a href="<?php echo \esc_url( $summary['queue_logs_url'] ); ?>"><?php \esc_html_e( 'Queue & Logs', 'aio-page-builder' ); ?></a></p>
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
		<div class="aio-dashboard-critical-errors notice notice-error inline" style="margin: 1em 0;">
			<p><strong><?php echo \esc_html( sprintf( __( '%d critical error(s) reported.', 'aio-page-builder' ), $summary['count'] ) ); ?></strong> <a href="<?php echo \esc_url( $summary['logs_url'] ); ?>"><?php \esc_html_e( 'View in Queue & Logs', 'aio-page-builder' ); ?></a></p>
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
