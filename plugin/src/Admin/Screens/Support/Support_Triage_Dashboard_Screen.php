<?php
/**
 * Support triage dashboard (spec §49.11, §59.12, §60.7).
 *
 * Aggregate view: reporting health, queue degradation, critical errors, failed AI runs,
 * stale Build Plans, rollback-eligible recent actions, import/export context. Deep links only; no mutation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Support;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Admin\Screens\Logs\Queue_Logs_Screen;
use AIOPageBuilder\Domain\Reporting\UI\Support_Triage_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders support triage dashboard. Permission-gated; redacted; links to authoritative screens.
 */
final class Support_Triage_Dashboard_Screen {

	public const SLUG = 'aio-page-builder-support-triage';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Support Triage', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access the support triage dashboard.', 'aio-page-builder' ), 403 );
		}
		$state = $this->build_state();
		$state = $this->apply_filters( $state );
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-support-triage" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="aio-support-triage-description"><?php \esc_html_e( 'Aggregated view of degraded systems and support next steps. Use links to open the relevant screens.', 'aio-page-builder' ); ?></p>
			<?php $this->render_filter_links( $state ); ?>
			<?php
			$this->render_critical_issues( $state['critical_issues'] );
			$this->render_degraded_systems( $state['degraded_systems'] );
			$this->render_recent_failed_workflows( $state['recent_failed_workflows'] );
			$this->render_stale_plans_and_rollback( $state );
			$this->render_import_export_failures( $state['import_export_failures'] );
			$this->render_recommended_links( $state['recommended_links'] );
			?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	/** @return array<string, mixed> */
	private function build_state(): array {
		$job_repo             = $this->container && $this->container->has( 'job_queue_repository' ) ? $this->container->get( 'job_queue_repository' ) : null;
		$ai_repo              = $this->container && $this->container->has( 'ai_run_repository' ) ? $this->container->get( 'ai_run_repository' ) : null;
		$plan_repo            = $this->container && $this->container->has( 'build_plan_repository' ) ? $this->container->get( 'build_plan_repository' ) : null;
		$industry_diagnostics = $this->container && $this->container->has( 'industry_diagnostics_service' ) ? $this->container->get( 'industry_diagnostics_service' ) : null;
		$builder              = new Support_Triage_State_Builder( $job_repo, $ai_repo, $plan_repo, $industry_diagnostics );
		return $builder->build();
	}

	/**
	 * Optional filter by domain or severity (query args).
	 *
	 * @param array<string, mixed> $state
	 * @return array<string, mixed>
	 */
	private function apply_filters( array $state ): array {
		$domain   = isset( $_GET['domain'] ) ? \sanitize_key( \wp_unslash( $_GET['domain'] ) ) : '';
		$severity = isset( $_GET['severity'] ) ? \sanitize_key( \wp_unslash( $_GET['severity'] ) ) : '';
		if ( $domain !== '' ) {
			$state['critical_issues']         = array_values(
				array_filter(
					$state['critical_issues'],
					function ( $item ) use ( $domain ) {
						return ( $item['domain'] ?? '' ) === $domain;
					}
				)
			);
			$state['degraded_systems']        = array_values(
				array_filter(
					$state['degraded_systems'],
					function ( $item ) use ( $domain ) {
						return ( $item['domain'] ?? '' ) === $domain;
					}
				)
			);
			$state['recent_failed_workflows'] = array_values(
				array_filter(
					$state['recent_failed_workflows'],
					function ( $item ) use ( $domain ) {
						return ( $item['domain'] ?? '' ) === $domain;
					}
				)
			);
		}
		if ( $severity !== '' ) {
			$state['critical_issues'] = array_values(
				array_filter(
					$state['critical_issues'],
					function ( $item ) use ( $severity ) {
						return ( $item['severity'] ?? '' ) === $severity;
					}
				)
			);
		}
		return $state;
	}

	/**
	 * HTML fragment (leading space) of data-aio-ux-* attributes for triage deep links.
	 *
	 * @param string $action  Stable trace action id.
	 * @param string $section Section id for grouping.
	 */
	private function triage_ux_attrs( string $action, string $section ): string {
		return sprintf(
			' data-aio-ux-action="%s" data-aio-ux-section="%s" data-aio-ux-hub="%s" data-aio-ux-tab="triage"',
			\esc_attr( $action ),
			\esc_attr( $section ),
			\esc_attr( Queue_Logs_Screen::SLUG )
		);
	}

	/** @param array<string, mixed> $state */
	private function render_filter_links( array $state ): void {
		$base = Admin_Screen_Hub::tab_url( Queue_Logs_Screen::SLUG, 'triage' );
		?>
		<div class="aio-support-triage-filters">
			<span class="filter-label"><?php \esc_html_e( 'Filter:', 'aio-page-builder' ); ?></span>
			<a href="<?php echo \esc_url( $base ); ?>" data-aio-ux-action="support_triage_filter_all" data-aio-ux-section="support_triage_filters" data-aio-ux-hub="<?php echo \esc_attr( Queue_Logs_Screen::SLUG ); ?>" data-aio-ux-tab="triage"><?php \esc_html_e( 'All', 'aio-page-builder' ); ?></a>
			| <a href="<?php echo \esc_url( \add_query_arg( 'domain', 'queue', $base ) ); ?>" data-aio-ux-action="support_triage_filter_domain_queue" data-aio-ux-section="support_triage_filters" data-aio-ux-hub="<?php echo \esc_attr( Queue_Logs_Screen::SLUG ); ?>" data-aio-ux-tab="triage"><?php \esc_html_e( 'Queue', 'aio-page-builder' ); ?></a>
			| <a href="<?php echo \esc_url( \add_query_arg( 'domain', 'reporting', $base ) ); ?>" data-aio-ux-action="support_triage_filter_domain_reporting" data-aio-ux-section="support_triage_filters" data-aio-ux-hub="<?php echo \esc_attr( Queue_Logs_Screen::SLUG ); ?>" data-aio-ux-tab="triage"><?php \esc_html_e( 'Reporting', 'aio-page-builder' ); ?></a>
			| <a href="<?php echo \esc_url( \add_query_arg( 'domain', 'ai_runs', $base ) ); ?>" data-aio-ux-action="support_triage_filter_domain_ai_runs" data-aio-ux-section="support_triage_filters" data-aio-ux-hub="<?php echo \esc_attr( Queue_Logs_Screen::SLUG ); ?>" data-aio-ux-tab="triage"><?php \esc_html_e( 'AI Runs', 'aio-page-builder' ); ?></a>
			| <a href="<?php echo \esc_url( \add_query_arg( 'severity', 'critical', $base ) ); ?>" data-aio-ux-action="support_triage_filter_severity_critical" data-aio-ux-section="support_triage_filters" data-aio-ux-hub="<?php echo \esc_attr( Queue_Logs_Screen::SLUG ); ?>" data-aio-ux-tab="triage"><?php \esc_html_e( 'Critical only', 'aio-page-builder' ); ?></a>
		</div>
		<?php
	}

	/** @param array<int, array<string, string>> $items */
	private function render_critical_issues( array $items ): void {
		?>
		<section class="aio-support-triage-section aio-critical-issues" style="margin: 1.5em 0;" aria-labelledby="aio-triage-critical-heading">
			<h2 id="aio-triage-critical-heading"><?php \esc_html_e( 'Critical issues', 'aio-page-builder' ); ?></h2>
			<?php if ( empty( $items ) ) : ?>
				<p class="aio-triage-none"><?php \esc_html_e( 'No critical issues.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<ul class="aio-triage-list" style="list-style: none; padding-left: 0;">
					<?php foreach ( $items as $item ) : ?>
						<li class="aio-triage-item aio-severity-<?php echo \esc_attr( \sanitize_key( (string) ( $item['severity'] ?? '' ) ) ); ?>" style="border-left: 4px solid #d63638; padding: 0.5em 0.75em; margin: 0.25em 0; background: #fcf0f1;">
							<strong><?php echo \esc_html( (string) ( $item['title'] ?? '' ) ); ?></strong>
							<p style="margin: 0.25em 0;"><?php echo \esc_html( (string) ( $item['message'] ?? '' ) ); ?></p>
							<p style="margin: 0.25em 0;"><a href="<?php echo \esc_url( (string) ( $item['link_url'] ?? '#' ) ); ?>"<?php echo $this->triage_ux_attrs( 'support_triage_critical_issue_link', 'support_triage_critical' ); ?>><?php echo \esc_html( (string) ( $item['link_label'] ?? '' ) ); ?></a></p>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<?php
	}

	/** @param array<int, array<string, string>> $items */
	private function render_degraded_systems( array $items ): void {
		?>
		<section class="aio-support-triage-section aio-degraded-systems" aria-labelledby="aio-triage-degraded-heading">
			<h2 id="aio-triage-degraded-heading"><?php \esc_html_e( 'Degraded systems', 'aio-page-builder' ); ?></h2>
			<?php if ( empty( $items ) ) : ?>
				<p class="aio-triage-none"><?php \esc_html_e( 'No degraded systems.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<ul class="aio-triage-list">
					<?php foreach ( $items as $item ) : ?>
						<li class="aio-triage-item aio-triage-item--warning">
							<strong><?php echo \esc_html( (string) ( $item['title'] ?? '' ) ); ?></strong> — <?php echo \esc_html( (string) ( $item['message'] ?? '' ) ); ?>
							<a href="<?php echo \esc_url( (string) ( $item['link_url'] ?? '#' ) ); ?>"<?php echo $this->triage_ux_attrs( 'support_triage_degraded_system_link', 'support_triage_degraded' ); ?>><?php echo \esc_html( (string) ( $item['link_label'] ?? '' ) ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<?php
	}

	/** @param array<int, array<string, string>> $items */
	private function render_recent_failed_workflows( array $items ): void {
		?>
		<section class="aio-support-triage-section aio-recent-failed" style="margin: 1.5em 0;" aria-labelledby="aio-triage-failed-heading">
			<h2 id="aio-triage-failed-heading"><?php \esc_html_e( 'Recent failed workflows', 'aio-page-builder' ); ?></h2>
			<?php if ( empty( $items ) ) : ?>
				<p class="aio-triage-none"><?php \esc_html_e( 'No recent failed workflows.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead><tr><th><?php \esc_html_e( 'Domain', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Summary', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Action', 'aio-page-builder' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $items as $item ) : ?>
							<tr>
								<td><?php echo \esc_html( (string) ( $item['domain'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $item['summary'] ?? '' ) ); ?></td>
								<td><a href="<?php echo \esc_url( (string) ( $item['link_url'] ?? '#' ) ); ?>"<?php echo $this->triage_ux_attrs( 'support_triage_failed_workflow_link', 'support_triage_failed_workflows' ); ?>><?php echo \esc_html( (string) ( $item['link_label'] ?? '' ) ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>
		<?php
	}

	/** @param array<string, mixed> $state */
	private function render_stale_plans_and_rollback( array $state ): void {
		$stale_plans = $state['stale_plans'] ?? array();
		$rollback    = $state['rollback_candidates'] ?? array();
		$base        = \admin_url( 'admin.php' );
		?>
		<section class="aio-support-triage-section aio-plans-rollback" aria-labelledby="aio-triage-plans-heading">
			<h2 id="aio-triage-plans-heading"><?php \esc_html_e( 'Plans needing attention & rollback candidates', 'aio-page-builder' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Plans in review or in progress; recent completed jobs that may be rollback-eligible (open plan to confirm).', 'aio-page-builder' ); ?></p>
			<?php if ( ! empty( $stale_plans ) ) : ?>
				<h3 class="aio-triage-subheading"><?php \esc_html_e( 'Plans needing attention', 'aio-page-builder' ); ?></h3>
				<ul class="aio-triage-plans-plain">
					<?php foreach ( $stale_plans as $plan ) : ?>
						<?php $plan_id = (string) ( $plan['plan_id'] ?? '' ); ?>
						<li><a href="<?php echo \esc_url( \add_query_arg( array( 'page' => Build_Plans_Screen::SLUG, 'plan_id' => $plan_id ), $base ) ); ?>"<?php echo $this->triage_ux_attrs( 'support_triage_open_stale_plan', 'support_triage_stale_plans' ); ?>><?php echo \esc_html( (string) ( $plan['title'] ?? $plan_id ) ); ?></a> — <?php echo \esc_html( (string) ( $plan['status'] ?? '' ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( ! empty( $rollback ) ) : ?>
				<h3 class="aio-triage-subheading"><?php \esc_html_e( 'Rollback candidates (recent completed)', 'aio-page-builder' ); ?></h3>
				<table class="wp-list-table widefat fixed striped">
					<thead><tr><th><?php \esc_html_e( 'Job type', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Plan', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Completed', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Action', 'aio-page-builder' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $rollback as $row ) : ?>
							<tr>
								<td><?php echo \esc_html( (string) ( $row['job_type'] ?? '' ) ); ?></td>
								<td><code><?php echo \esc_html( (string) ( $row['plan_id'] ?? '' ) ); ?></code></td>
								<td><?php echo \esc_html( (string) ( $row['completed_at'] ?? '' ) ); ?></td>
								<td><a href="<?php echo \esc_url( (string) ( $row['link_url'] ?? '#' ) ); ?>"<?php echo $this->triage_ux_attrs( 'support_triage_rollback_candidate_link', 'support_triage_rollback' ); ?>><?php echo \esc_html( (string) ( $row['link_label'] ?? '' ) ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<?php if ( empty( $stale_plans ) && empty( $rollback ) ) : ?>
				<p class="aio-triage-none"><?php \esc_html_e( 'No plans needing attention or rollback candidates.', 'aio-page-builder' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
	}

	/** @param array<int, array<string, string>> $items */
	private function render_import_export_failures( array $items ): void {
		?>
		<section class="aio-support-triage-section aio-import-export" aria-labelledby="aio-triage-ie-heading">
			<h2 id="aio-triage-ie-heading"><?php \esc_html_e( 'Import / Export failures', 'aio-page-builder' ); ?></h2>
			<?php if ( empty( $items ) ) : ?>
				<p class="aio-triage-none"><?php \esc_html_e( 'No import/export failures recorded.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<ul class="aio-triage-list">
					<?php foreach ( $items as $item ) : ?>
						<li><?php echo \esc_html( (string) ( $item['message'] ?? '' ) ); ?> <a href="<?php echo \esc_url( (string) ( $item['link_url'] ?? '#' ) ); ?>"<?php echo $this->triage_ux_attrs( 'support_triage_import_export_failure_link', 'support_triage_import_export' ); ?>><?php echo \esc_html( (string) ( $item['link_label'] ?? '' ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<?php
	}

	/** @param array<int, array<string, string>> $items */
	private function render_recommended_links( array $items ): void {
		?>
		<section class="aio-support-triage-section aio-recommended-links" aria-labelledby="aio-triage-links-heading">
			<h2 id="aio-triage-links-heading"><?php \esc_html_e( 'Recommended next steps', 'aio-page-builder' ); ?></h2>
			<ul class="aio-triage-links">
				<?php foreach ( $items as $item ) : ?>
					<li><a href="<?php echo \esc_url( (string) ( $item['url'] ?? '#' ) ); ?>" class="button"<?php echo $this->triage_ux_attrs( 'support_triage_recommended_step_link', 'support_triage_recommended' ); ?>><?php echo \esc_html( (string) ( $item['label'] ?? '' ) ); ?></a> <span class="description"><?php echo \esc_html( (string) ( $item['description'] ?? '' ) ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}
}
