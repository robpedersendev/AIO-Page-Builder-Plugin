<?php
/**
 * Build Plan analytics: review trends, common blockers, execution failures, rollback summary (spec §30, §45, §49.11, §59.12; Prompt 129).
 * Observational only; no mutation. Permission-aware; redacted summaries.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\BuildPlan;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders analytics summary with date-range filter and links back to Build Plans.
 */
final class Build_Plan_Analytics_Screen {

	public const SLUG = 'aio-page-builder-build-plan-analytics';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Build Plan Analytics', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_BUILD_PLANS;
	}

	/**
	 * Renders analytics screen: date filter form and trend summaries.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		$date_from = isset( $_GET['date_from'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['date_from'] ) ) : '';
		$date_to   = isset( $_GET['date_to'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['date_to'] ) ) : '';
		$summary   = array(
			'plan_review_trends'         => array(),
			'common_blockers'            => array(),
			'execution_failure_trends'   => array(),
			'rollback_frequency_summary' => array(),
		);
		if ( $this->container && $this->container->has( 'build_plan_analytics_service' ) ) {
			try {
				$svc     = $this->container->get( 'build_plan_analytics_service' );
				$from    = $date_from !== '' ? $date_from : null;
				$to      = $date_to !== '' ? $date_to : null;
				$summary = $svc->get_analytics_summary( $from, $to );
			} catch ( \Throwable $e ) {
				// * Observational screen: keep default summary on service failure.
				unset( $e );
			}
		}
		$build_plans_url = Admin_Screen_Hub::tab_url( Build_Plans_Screen::SLUG, 'build_plans' );
		$ai_runs_url     = Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_runs' );
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-build-plan-analytics" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="aio-analytics-intro"><?php \esc_html_e( 'Observational trends from Build Plan history. No changes to plans or execution.', 'aio-page-builder' ); ?></p>
			<?php if ( Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) ) : ?>
				<p class="description"><?php \esc_html_e( 'To create a new plan, open a completed run under AI Runs and use “Create Build Plan from this run”.', 'aio-page-builder' ); ?> <a href="<?php echo \esc_url( $ai_runs_url ); ?>"><?php \esc_html_e( 'AI Runs', 'aio-page-builder' ); ?></a></p>
			<?php endif; ?>
			<p><a href="<?php echo \esc_url( $build_plans_url ); ?>"><?php \esc_html_e( '&larr; Back to Build Plans', 'aio-page-builder' ); ?></a></p>

			<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" class="aio-analytics-filter">
				<input type="hidden" name="page" value="<?php echo \esc_attr( Build_Plans_Screen::SLUG ); ?>" />
				<input type="hidden" name="<?php echo \esc_attr( Admin_Screen_Hub::QUERY_TAB ); ?>" value="bp_analytics" />
				<p>
					<label for="date_from"><?php \esc_html_e( 'From date (Y-m-d):', 'aio-page-builder' ); ?></label>
					<input type="date" id="date_from" name="date_from" value="<?php echo \esc_attr( $date_from ); ?>" />
					<label for="date_to"><?php \esc_html_e( 'To date (Y-m-d):', 'aio-page-builder' ); ?></label>
					<input type="date" id="date_to" name="date_to" value="<?php echo \esc_attr( $date_to ); ?>" />
					<button type="submit" class="button button-primary"><?php \esc_html_e( 'Apply', 'aio-page-builder' ); ?></button>
				</p>
			</form>

			<h2><?php \esc_html_e( 'Plan review trends', 'aio-page-builder' ); ?></h2>
			<?php $this->render_review_trends( $summary['plan_review_trends'] ); ?>

			<h2><?php \esc_html_e( 'Common blockers', 'aio-page-builder' ); ?></h2>
			<?php $this->render_common_blockers( $summary['common_blockers'] ); ?>

			<h2><?php \esc_html_e( 'Execution failure trends', 'aio-page-builder' ); ?></h2>
			<?php $this->render_failure_trends( $summary['execution_failure_trends'] ); ?>

			<h2><?php \esc_html_e( 'Rollback frequency', 'aio-page-builder' ); ?></h2>
			<?php $this->render_rollback_summary( $summary['rollback_frequency_summary'] ); ?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	private function render_review_trends( array $data ): void {
		$total           = (int) ( $data['total_plans'] ?? 0 );
		$by_status       = isset( $data['by_status'] ) && is_array( $data['by_status'] ) ? $data['by_status'] : array();
		$approval_count  = (int) ( $data['approval_count'] ?? 0 );
		$rejection_count = (int) ( $data['rejection_count'] ?? 0 );
		$approval_rate   = (float) ( $data['approval_rate'] ?? 0 );
		$denial_rate     = (float) ( $data['denial_rate'] ?? 0 );
		?>
		<table class="widefat striped">
			<tbody>
				<tr><th scope="row"><?php \esc_html_e( 'Total plans (in range)', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) $total ); ?></td></tr>
				<tr><th scope="row"><?php \esc_html_e( 'Approved', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) $approval_count ); ?></td></tr>
				<tr><th scope="row"><?php \esc_html_e( 'Rejected', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) $rejection_count ); ?></td></tr>
				<tr><th scope="row"><?php \esc_html_e( 'Approval rate (of reviewed)', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) round( $approval_rate * 100, 2 ) ); ?>%</td></tr>
				<tr><th scope="row"><?php \esc_html_e( 'Denial rate (of reviewed)', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) round( $denial_rate * 100, 2 ) ); ?>%</td></tr>
			</tbody>
		</table>
		<?php if ( ! empty( $by_status ) ) : ?>
		<h3><?php \esc_html_e( 'By status', 'aio-page-builder' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Count', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $by_status as $status => $count ) : ?>
					<tr><td><?php echo \esc_html( (string) $status ); ?></td><td><?php echo \esc_html( (string) $count ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
			<?php
		endif;
	}

	private function render_common_blockers( array $data ): void {
		$blockers       = isset( $data['blockers'] ) && is_array( $data['blockers'] ) ? $data['blockers'] : array();
		$total_rejected = (int) ( $data['total_rejected'] ?? 0 );
		$total_failed   = (int) ( $data['total_failed'] ?? 0 );
		?>
		<p><?php echo \esc_html( sprintf( /* translators: 1: rejected count, 2: failed count */ __( 'Total rejected items: %1$d; total failed items: %2$d.', 'aio-page-builder' ), $total_rejected, $total_failed ) ); ?></p>
		<?php if ( empty( $blockers ) ) : ?>
			<p><?php \esc_html_e( 'No blocker categories in range.', 'aio-page-builder' ); ?></p>
		<?php else : ?>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Category', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Count', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $blockers as $b ) : ?>
					<tr><td><?php echo \esc_html( (string) ( $b['category'] ?? '' ) ); ?></td><td><?php echo \esc_html( (string) ( $b['count'] ?? 0 ) ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
			<?php
		endif;
	}

	private function render_failure_trends( array $data ): void {
		$by_type = isset( $data['failures_by_item_type'] ) && is_array( $data['failures_by_item_type'] ) ? $data['failures_by_item_type'] : array();
		$total   = (int) ( $data['total_failed_items'] ?? 0 );
		?>
		<p><?php echo \esc_html( sprintf( /* translators: %d: failed item count */ __( 'Total failed items: %d.', 'aio-page-builder' ), $total ) ); ?></p>
		<?php if ( empty( $by_type ) ) : ?>
			<p><?php \esc_html_e( 'No failures by type in range.', 'aio-page-builder' ); ?></p>
		<?php else : ?>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Item type', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Count', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $by_type as $type => $count ) : ?>
					<tr><td><?php echo \esc_html( (string) $type ); ?></td><td><?php echo \esc_html( (string) $count ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
			<?php
		endif;
	}

	private function render_rollback_summary( array $data ): void {
		$total    = (int) ( $data['total_rollbacks'] ?? 0 );
		$by_month = isset( $data['by_month'] ) && is_array( $data['by_month'] ) ? $data['by_month'] : array();
		?>
		<p><?php echo \esc_html( sprintf( /* translators: %d: rollback count */ __( 'Total rollbacks in range: %d.', 'aio-page-builder' ), $total ) ); ?></p>
		<?php if ( ! empty( $by_month ) ) : ?>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Month', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Count', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $by_month as $row ) : ?>
					<tr><td><?php echo \esc_html( (string) ( $row['month'] ?? '' ) ); ?></td><td><?php echo \esc_html( (string) ( $row['count'] ?? 0 ) ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
			<p><?php \esc_html_e( 'Rollback data is from plan analytics only; rollback table not queried.', 'aio-page-builder' ); ?></p>
			<?php
		endif;
	}
}
