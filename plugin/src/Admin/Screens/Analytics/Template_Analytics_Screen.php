<?php
/**
 * Template usage and selection analytics dashboard (spec §49.11, §56.1, §59.12, §59.15; Prompt 199).
 * Observational only: usage by family/class, recommendation acceptance, rejection reasons, execution outcomes, rollback frequency, composition usage.
 * No mutation of planner or executor.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Analytics;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Admin\Screens\Logs\Queue_Logs_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Compositions_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Page_Templates_Directory_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders template analytics with date and optional family/class filters; links to authoritative screens.
 */
final class Template_Analytics_Screen {

	public const SLUG = 'aio-page-builder-template-analytics';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Template Analytics', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Renders the template analytics dashboard: filters and summary sections.
	 *
	 * @return void
	 */
	public function render(): void {
		$date_from       = isset( $_GET['date_from'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['date_from'] ) ) : '';
		$date_to         = isset( $_GET['date_to'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['date_to'] ) ) : '';
		$template_family = isset( $_GET['template_family'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['template_family'] ) ) : '';
		$page_class      = isset( $_GET['page_class'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['page_class'] ) ) : '';

		$summary = array(
			'template_usage_trends'           => array(
				'by_family'   => array(),
				'by_class'    => array(),
				'total_items' => 0,
			),
			'recommendation_acceptance'       => array(
				'by_family' => array(),
				'by_class'  => array(),
			),
			'rejection_reasons'               => array(
				'reasons' => array(),
				'total'   => 0,
			),
			'template_family_outcome_summary' => array(
				'by_family'       => array(),
				'total_completed' => 0,
				'total_failed'    => 0,
			),
			'rollback_frequency'              => array(
				'total_rollbacks' => 0,
				'by_month'        => array(),
			),
			'composition_usage'               => array(
				'by_status' => array(),
				'total'     => 0,
			),
		);

		if ( $this->container && $this->container->has( 'template_analytics_service' ) ) {
			try {
				$svc     = $this->container->get( 'template_analytics_service' );
				$summary = $svc->get_analytics_summary(
					$date_from !== '' ? $date_from : null,
					$date_to !== '' ? $date_to : null,
					$template_family !== '' ? $template_family : null,
					$page_class !== '' ? $page_class : null
				);
			} catch ( \Throwable $e ) {
				// Observational; fail gracefully.
			}
		}

		$build_plans_url  = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG );
		$queue_logs_url   = \admin_url( 'admin.php?page=' . Queue_Logs_Screen::SLUG );
		$page_tpl_url     = \admin_url( 'admin.php?page=' . Page_Templates_Directory_Screen::SLUG );
		$compositions_url = \admin_url( 'admin.php?page=' . Compositions_Screen::SLUG );
		?>
		<div class="wrap aio-page-builder-screen aio-template-analytics" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-analytics-intro"><?php \esc_html_e( 'Observational template usage, recommendation success, execution outcomes, and rollback frequency. No changes to plans or execution.', 'aio-page-builder' ); ?></p>
			<p>
				<a href="<?php echo \esc_url( $build_plans_url ); ?>"><?php \esc_html_e( 'Build Plans', 'aio-page-builder' ); ?></a> |
				<a href="<?php echo \esc_url( $queue_logs_url ); ?>"><?php \esc_html_e( 'Queue & Logs', 'aio-page-builder' ); ?></a> |
				<a href="<?php echo \esc_url( $page_tpl_url ); ?>"><?php \esc_html_e( 'Page Templates', 'aio-page-builder' ); ?></a> |
				<a href="<?php echo \esc_url( $compositions_url ); ?>"><?php \esc_html_e( 'Compositions', 'aio-page-builder' ); ?></a>
			</p>

			<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" class="aio-analytics-filter">
				<input type="hidden" name="page" value="<?php echo \esc_attr( self::SLUG ); ?>" />
				<p>
					<label for="aio-date-from"><?php \esc_html_e( 'From date (Y-m-d):', 'aio-page-builder' ); ?></label>
					<input type="date" id="aio-date-from" name="date_from" value="<?php echo \esc_attr( $date_from ); ?>" />
					<label for="aio-date-to"><?php \esc_html_e( 'To date (Y-m-d):', 'aio-page-builder' ); ?></label>
					<input type="date" id="aio-date-to" name="date_to" value="<?php echo \esc_attr( $date_to ); ?>" />
					<label for="aio-template-family"><?php \esc_html_e( 'Template family:', 'aio-page-builder' ); ?></label>
					<input type="text" id="aio-template-family" name="template_family" value="<?php echo \esc_attr( $template_family ); ?>" placeholder="<?php \esc_attr_e( 'optional', 'aio-page-builder' ); ?>" />
					<label for="aio-page-class"><?php \esc_html_e( 'Page class:', 'aio-page-builder' ); ?></label>
					<input type="text" id="aio-page-class" name="page_class" value="<?php echo \esc_attr( $page_class ); ?>" placeholder="<?php \esc_attr_e( 'optional', 'aio-page-builder' ); ?>" />
					<button type="submit" class="button button-primary"><?php \esc_html_e( 'Apply', 'aio-page-builder' ); ?></button>
				</p>
			</form>

			<h2><?php \esc_html_e( 'Template usage by family and class', 'aio-page-builder' ); ?></h2>
			<?php $this->render_usage_trends( $summary['template_usage_trends'] ); ?>

			<h2><?php \esc_html_e( 'Recommendation acceptance by family and class', 'aio-page-builder' ); ?></h2>
			<?php $this->render_recommendation_acceptance( $summary['recommendation_acceptance'] ); ?>

			<h2><?php \esc_html_e( 'Common rejection reasons', 'aio-page-builder' ); ?></h2>
			<?php $this->render_rejection_reasons( $summary['rejection_reasons'] ); ?>

			<h2><?php \esc_html_e( 'Execution outcomes by template family', 'aio-page-builder' ); ?></h2>
			<?php $this->render_family_outcomes( $summary['template_family_outcome_summary'] ); ?>

			<h2><?php \esc_html_e( 'Rollback frequency', 'aio-page-builder' ); ?></h2>
			<?php $this->render_rollback_frequency( $summary['rollback_frequency'] ); ?>

			<h2><?php \esc_html_e( 'Composition usage', 'aio-page-builder' ); ?></h2>
			<?php $this->render_composition_usage( $summary['composition_usage'] ); ?>
		</div>
		<?php
	}

	private function render_usage_trends( array $data ): void {
		$total     = (int) ( $data['total_items'] ?? 0 );
		$by_family = isset( $data['by_family'] ) && is_array( $data['by_family'] ) ? $data['by_family'] : array();
		$by_class  = isset( $data['by_class'] ) && is_array( $data['by_class'] ) ? $data['by_class'] : array();
		?>
		<p><?php echo \esc_html( sprintf( __( 'Total plan items with template context (in range): %d.', 'aio-page-builder' ), $total ) ); ?></p>
		<?php if ( ! empty( $by_family ) ) : ?>
		<h3><?php \esc_html_e( 'By template family', 'aio-page-builder' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Family', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Count', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $by_family as $family => $count ) : ?>
					<tr><td><?php echo \esc_html( (string) $family ); ?></td><td><?php echo \esc_html( (string) $count ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
		<?php if ( ! empty( $by_class ) ) : ?>
		<h3><?php \esc_html_e( 'By page class', 'aio-page-builder' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Class', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Count', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $by_class as $class => $count ) : ?>
					<tr><td><?php echo \esc_html( (string) $class ); ?></td><td><?php echo \esc_html( (string) $count ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
			<?php
		endif;
		if ( empty( $by_family ) && empty( $by_class ) && $total === 0 ) :
			?>
			<p><?php \esc_html_e( 'No template usage data in range.', 'aio-page-builder' ); ?></p>
			<?php
		endif;
	}

	private function render_recommendation_acceptance( array $data ): void {
		$by_family = isset( $data['by_family'] ) && is_array( $data['by_family'] ) ? $data['by_family'] : array();
		$by_class  = isset( $data['by_class'] ) && is_array( $data['by_class'] ) ? $data['by_class'] : array();
		if ( empty( $by_family ) && empty( $by_class ) ) {
			echo '<p>' . \esc_html__( 'No recommendation data in range.', 'aio-page-builder' ) . '</p>';
			return;
		}
		?>
		<h3><?php \esc_html_e( 'By template family', 'aio-page-builder' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Family', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Proposed', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Approved', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Rejected', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Failed', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Completed', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $by_family as $family => $counts ) : ?>
					<tr>
						<td><?php echo \esc_html( (string) $family ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['proposed'] ?? 0 ) ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['approved'] ?? 0 ) ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['rejected'] ?? 0 ) ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['failed'] ?? 0 ) ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['completed'] ?? 0 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<h3><?php \esc_html_e( 'By page class', 'aio-page-builder' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Class', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Proposed', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Approved', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Rejected', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Failed', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Completed', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $by_class as $class => $counts ) : ?>
					<tr>
						<td><?php echo \esc_html( (string) $class ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['proposed'] ?? 0 ) ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['approved'] ?? 0 ) ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['rejected'] ?? 0 ) ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['failed'] ?? 0 ) ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['completed'] ?? 0 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_rejection_reasons( array $data ): void {
		$reasons = isset( $data['reasons'] ) && is_array( $data['reasons'] ) ? $data['reasons'] : array();
		$total   = (int) ( $data['total'] ?? 0 );
		?>
		<p><?php echo \esc_html( sprintf( __( 'Total rejections/failures with recorded reason: %d.', 'aio-page-builder' ), $total ) ); ?></p>
		<?php if ( empty( $reasons ) ) : ?>
			<p><?php \esc_html_e( 'No rejection reasons in range.', 'aio-page-builder' ); ?></p>
		<?php else : ?>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Reason', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Count', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $reasons as $row ) : ?>
					<tr><td><?php echo \esc_html( (string) ( $row['reason'] ?? '' ) ); ?></td><td><?php echo \esc_html( (string) ( $row['count'] ?? 0 ) ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
			<?php
		endif;
	}

	private function render_family_outcomes( array $data ): void {
		$by_family       = isset( $data['by_family'] ) && is_array( $data['by_family'] ) ? $data['by_family'] : array();
		$total_completed = (int) ( $data['total_completed'] ?? 0 );
		$total_failed    = (int) ( $data['total_failed'] ?? 0 );
		?>
		<p><?php echo \esc_html( sprintf( __( 'Execution completed: %1$d; failed: %2$d (create_page, replace_page).', 'aio-page-builder' ), $total_completed, $total_failed ) ); ?></p>
		<?php if ( empty( $by_family ) ) : ?>
			<p><?php \esc_html_e( 'No execution outcome data by family in range.', 'aio-page-builder' ); ?></p>
		<?php else : ?>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Template family', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Completed', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Failed', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $by_family as $family => $counts ) : ?>
					<tr>
						<td><?php echo \esc_html( (string) $family ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['completed'] ?? 0 ) ); ?></td>
						<td><?php echo \esc_html( (string) ( $counts['failed'] ?? 0 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
			<?php
		endif;
	}

	private function render_rollback_frequency( array $data ): void {
		$total    = (int) ( $data['total_rollbacks'] ?? 0 );
		$by_month = isset( $data['by_month'] ) && is_array( $data['by_month'] ) ? $data['by_month'] : array();
		?>
		<p><?php echo \esc_html( sprintf( __( 'Total rollbacks in range: %d.', 'aio-page-builder' ), $total ) ); ?></p>
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
			<p><?php \esc_html_e( 'No rollback jobs in range.', 'aio-page-builder' ); ?></p>
			<?php
		endif;
	}

	private function render_composition_usage( array $data ): void {
		$by_status = isset( $data['by_status'] ) && is_array( $data['by_status'] ) ? $data['by_status'] : array();
		$total     = (int) ( $data['total'] ?? 0 );
		?>
		<p><?php echo \esc_html( sprintf( __( 'Total compositions (inventory): %d.', 'aio-page-builder' ), $total ) ); ?></p>
		<?php if ( ! empty( $by_status ) ) : ?>
		<table class="widefat striped">
			<thead><tr><th><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th><th><?php \esc_html_e( 'Count', 'aio-page-builder' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $by_status as $status => $count ) : ?>
					<tr><td><?php echo \esc_html( (string) $status ); ?></td><td><?php echo \esc_html( (string) $count ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
			<p><?php \esc_html_e( 'No composition data available.', 'aio-page-builder' ); ?></p>
			<?php
		endif;
	}
}
