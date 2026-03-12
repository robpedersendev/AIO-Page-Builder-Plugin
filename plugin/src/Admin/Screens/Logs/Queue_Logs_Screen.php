<?php
/**
 * Queue & Logs monitoring screen (spec §49.11, §46.10).
 *
 * Tabs: Queue, Execution Logs, AI Runs, Reporting Logs, Import/Export Logs, Critical Errors.
 * Capability-gated; redacted; row-to-object navigation. Reporting failures visible.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Logs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Domain\Reporting\Logs\Log_Export_Service;
use AIOPageBuilder\Domain\Reporting\UI\Logs_Monitoring_State_Builder;
use AIOPageBuilder\Domain\Reporting\UI\Reporting_Health_Summary_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders Queue & Logs with tabs and filterable views. No mutation actions.
 */
final class Queue_Logs_Screen {

	public const SLUG = 'aio-page-builder-queue-logs';

	public const TAB_QUEUE           = 'queue';
	public const TAB_EXECUTION      = 'execution';
	public const TAB_AI_RUNS        = 'ai_runs';
	public const TAB_REPORTING      = 'reporting';
	public const TAB_IMPORT_EXPORT  = 'import_export';
	public const TAB_CRITICAL       = 'critical';

	private const TABS = array(
		self::TAB_QUEUE          => 'Queue',
		self::TAB_EXECUTION      => 'Execution Logs',
		self::TAB_AI_RUNS        => 'AI Runs',
		self::TAB_REPORTING      => 'Reporting Logs',
		self::TAB_IMPORT_EXPORT  => 'Import/Export Logs',
		self::TAB_CRITICAL       => 'Critical Errors',
	);

	/** @var Service_Container|null */
	private $container;

	private const NONCE_EXPORT_LOGS = 'aio_export_logs';
	private const NONCE_DOWNLOAD_LOG = 'aio_download_log_export';

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
		\add_action( 'admin_post_aio_export_logs', array( $this, 'handle_export_logs' ), 10 );
		\add_action( 'admin_post_aio_download_log_export', array( $this, 'handle_download_log_export' ), 10 );
	}

	public function get_title(): string {
		return __( 'Queue & Logs', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Renders the screen. Capability check is done by WordPress before menu callback.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( $this->get_capability() ) ) {
			return;
		}
		$tab   = $this->get_current_tab();
		$state = $this->build_state();
		$health = ( new Reporting_Health_Summary_Builder() )->build();
		$this->render_header( $tab );
		$this->render_reporting_health( $health );
		$this->render_log_export_section( $state );
		$this->render_tab_nav( $tab );
		$this->render_tab_content( $tab, $state, $health );
	}

	private function get_current_tab(): string {
		$t = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : '';
		return array_key_exists( $t, self::TABS ) ? $t : self::TAB_QUEUE;
	}

	/** @return array<string, mixed> Full state from Logs_Monitoring_State_Builder. */
	private function build_state(): array {
		$job_repo = null;
		$ai_repo  = null;
		if ( $this->container ) {
			if ( $this->container->has( 'job_queue_repository' ) ) {
				$job_repo = $this->container->get( 'job_queue_repository' );
			}
			if ( $this->container->has( 'ai_run_repository' ) ) {
				$ai_repo = $this->container->get( 'ai_run_repository' );
			}
		}
		$builder = new Logs_Monitoring_State_Builder( $job_repo, $ai_repo );
		return $builder->build();
	}

	private function render_header( string $tab ): void {
		?>
		<div class="wrap aio-page-builder-screen aio-queue-logs" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-queue-logs-description"><?php \esc_html_e( 'Monitor queue state, execution logs, reporting delivery, and critical errors. Row links open related plans or runs.', 'aio-page-builder' ); ?></p>
		<?php
	}

	/**
	 * @param array{recent_failures_count: int, last_heartbeat_month: string, reporting_degraded: bool, summary_message: string} $health
	 */
	private function render_reporting_health( array $health ): void {
		$degraded = ! empty( $health['reporting_degraded'] );
		$class    = $degraded ? 'aio-reporting-health aio-reporting-degraded' : 'aio-reporting-health';
		?>
		<div class="<?php echo \esc_attr( $class ); ?>">
			<h2 class="aio-reporting-health-title"><?php \esc_html_e( 'Reporting health', 'aio-page-builder' ); ?></h2>
			<p class="aio-reporting-health-summary"><?php echo \esc_html( $health['summary_message'] ?? '' ); ?></p>
			<?php if ( ! empty( $health['last_heartbeat_month'] ) ) : ?>
				<p class="aio-reporting-health-heartbeat"><?php echo \esc_html( \__( 'Last heartbeat:', 'aio-page-builder' ) . ' ' . (string) $health['last_heartbeat_month'] ); ?></p>
			<?php endif; ?>
			<?php if ( (int) ( $health['recent_failures_count'] ?? 0 ) > 0 ) : ?>
				<p class="aio-reporting-health-failures"><?php echo \esc_html( (string) ( $health['recent_failures_count'] ?? 0 ) . ' ' . \__( 'recent delivery failure(s).', 'aio-page-builder' ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders log export form for authorized users (spec §48.10). Only when aio_export_data.
	 *
	 * @param array<string, mixed> $state Full state including log_export.exportable_log_types.
	 */
	private function render_log_export_section( array $state ): void {
		if ( ! \current_user_can( Capabilities::EXPORT_DATA ) ) {
			return;
		}
		$log_export = $state['log_export'] ?? array();
		$types      = $log_export['exportable_log_types'] ?? array();
		$status     = isset( $_GET['aio_log_export'] ) ? \sanitize_text_field( \wp_unslash( $_GET['aio_log_export'] ) ) : '';
		$file       = isset( $_GET['aio_log_file'] ) ? \sanitize_text_field( \wp_unslash( $_GET['aio_log_file'] ) ) : '';
		if ( $status === 'success' && $file !== '' ) {
			$dl_url = \wp_nonce_url( \add_query_arg( array( 'action' => 'aio_download_log_export', 'file' => $file ), \admin_url( 'admin-post.php' ) ), self::NONCE_DOWNLOAD_LOG );
			echo '<div class="notice notice-success is-dismissible"><p>' . \esc_html__( 'Log export completed.', 'aio-page-builder' ) . ' <a href="' . \esc_url( $dl_url ) . '">' . \esc_html__( 'Download', 'aio-page-builder' ) . '</a></p></div>';
		} elseif ( $status === 'error' ) {
			$msg = isset( $_GET['aio_log_message'] ) ? \sanitize_text_field( \wp_unslash( $_GET['aio_log_message'] ) ) : __( 'Export failed.', 'aio-page-builder' );
			echo '<div class="notice notice-error is-dismissible"><p>' . \esc_html( $msg ) . '</p></div>';
		}
		?>
		<section class="aio-log-export" aria-labelledby="aio-log-export-heading">
			<h2 id="aio-log-export-heading"><?php \esc_html_e( 'Export logs', 'aio-page-builder' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Export selected log categories in structured JSON format. Data is redacted and filtered. Authorized use only.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="aio_export_logs" />
				<?php \wp_nonce_field( self::NONCE_EXPORT_LOGS ); ?>
				<fieldset class="aio-log-export-types">
					<legend><?php \esc_html_e( 'Log types', 'aio-page-builder' ); ?></legend>
					<?php foreach ( $types as $opt ) : ?>
						<label><input type="checkbox" name="log_types[]" value="<?php echo \esc_attr( $opt['value'] ?? '' ); ?>" /> <?php echo \esc_html( $opt['label'] ?? '' ); ?></label><br />
					<?php endforeach; ?>
				</fieldset>
				<p>
					<label for="aio-log-date-from"><?php \esc_html_e( 'Date from (optional)', 'aio-page-builder' ); ?></label>
					<input type="date" id="aio-log-date-from" name="date_from" />
					<label for="aio-log-date-to"><?php \esc_html_e( 'Date to (optional)', 'aio-page-builder' ); ?></label>
					<input type="date" id="aio-log-date-to" name="date_to" />
				</p>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php \esc_html_e( 'Export logs', 'aio-page-builder' ); ?></button>
				</p>
			</form>
		</section>
		<?php
	}

	private function render_tab_nav( string $current ): void {
		$base = \admin_url( 'admin.php?page=' . self::SLUG );
		?>
		<nav class="nav-tab-wrapper aio-queue-logs-tabs" aria-label="<?php \esc_attr_e( 'Queue & Logs tabs', 'aio-page-builder' ); ?>">
			<?php foreach ( self::TABS as $key => $label ) : ?>
				<a href="<?php echo \esc_url( $base . '&tab=' . \rawurlencode( $key ) ); ?>"
					class="nav-tab <?php echo $key === $current ? 'nav-tab-active' : ''; ?>"><?php echo \esc_html( \__( $label, 'aio-page-builder' ) ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/** @param array<string, mixed> $state */
	private function render_tab_content( string $tab, array $state, array $health ): void {
		switch ( $tab ) {
			case self::TAB_QUEUE:
				$this->render_queue_tab( $state['queue'] ?? array() );
				break;
			case self::TAB_EXECUTION:
				$this->render_execution_tab( $state['execution_logs'] ?? array() );
				break;
			case self::TAB_AI_RUNS:
				$this->render_ai_runs_tab( $state['ai_runs'] ?? array() );
				break;
			case self::TAB_REPORTING:
				$this->render_reporting_tab( $state['reporting_logs'] ?? array() );
				break;
			case self::TAB_IMPORT_EXPORT:
				$this->render_import_export_tab( $state['import_export_logs'] ?? array() );
				break;
			case self::TAB_CRITICAL:
				$this->render_critical_tab( $state['critical_errors'] ?? array() );
				break;
			default:
				$this->render_queue_tab( $state['queue'] ?? array() );
		}
		?>
		</div>
		<?php
	}

	/** @param list<array{job_ref: string, job_type: string, queue_status: string, created_at: string, completed_at: string, failure_reason: string, related_plan_id: string}> $rows */
	private function render_queue_tab( array $rows ): void {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php \esc_html_e( 'Job ref', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Type', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Created', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Completed', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Failure reason', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="7"><?php \esc_html_e( 'No queue jobs.', 'aio-page-builder' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><code><?php echo \esc_html( (string) ( $row['job_ref'] ?? '' ) ); ?></code></td>
							<td><?php echo \esc_html( (string) ( $row['job_type'] ?? '' ) ); ?></td>
							<td><span class="aio-badge aio-badge-<?php echo \esc_attr( sanitize_key( (string) ( $row['queue_status'] ?? '' ) ) ); ?>"><?php echo \esc_html( (string) ( $row['queue_status'] ?? '' ) ); ?></span></td>
							<td><?php echo \esc_html( (string) ( $row['created_at'] ?? '' ) ); ?></td>
							<td><?php echo \esc_html( (string) ( $row['completed_at'] ?? '' ) ); ?></td>
							<td><?php echo \esc_html( (string) ( $row['failure_reason'] ?? '' ) ); ?></td>
							<td>
								<?php
								$plan_id = (string) ( $row['related_plan_id'] ?? '' );
								if ( $plan_id !== '' ) {
									echo '<a href="' . \esc_url( \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) ) ) . '">' . \esc_html__( 'Open plan', 'aio-page-builder' ) . '</a>';
								} else {
									echo '—';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/** @param list<array{job_ref: string, job_type: string, queue_status: string, created_at: string, completed_at: string, failure_reason: string, related_plan_id: string}> $rows */
	private function render_execution_tab( array $rows ): void {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php \esc_html_e( 'Job ref', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Type', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Created', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Completed', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Failure reason', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="7"><?php \esc_html_e( 'No execution log entries.', 'aio-page-builder' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><code><?php echo \esc_html( (string) ( $row['job_ref'] ?? '' ) ); ?></code></td>
							<td><?php echo \esc_html( (string) ( $row['job_type'] ?? '' ) ); ?></td>
							<td><span class="aio-badge aio-badge-<?php echo \esc_attr( sanitize_key( (string) ( $row['queue_status'] ?? '' ) ) ); ?>"><?php echo \esc_html( (string) ( $row['queue_status'] ?? '' ) ); ?></span></td>
							<td><?php echo \esc_html( (string) ( $row['created_at'] ?? '' ) ); ?></td>
							<td><?php echo \esc_html( (string) ( $row['completed_at'] ?? '' ) ); ?></td>
							<td><?php echo \esc_html( (string) ( $row['failure_reason'] ?? '' ) ); ?></td>
							<td>
								<?php
								$plan_id = (string) ( $row['related_plan_id'] ?? '' );
								if ( $plan_id !== '' ) {
									echo '<a href="' . \esc_url( \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) ) ) . '">' . \esc_html__( 'Open plan', 'aio-page-builder' ) . '</a>';
								} else {
									echo '—';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/** @param list<array{run_id: string, status: string, created_at: string}> $rows */
	private function render_ai_runs_tab( array $rows ): void {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php \esc_html_e( 'Run ID', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Created', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="4"><?php \esc_html_e( 'No AI runs. Use AI Runs screen for full list.', 'aio-page-builder' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><code><?php echo \esc_html( (string) ( $row['run_id'] ?? '' ) ); ?></code></td>
							<td><span class="aio-badge"><?php echo \esc_html( (string) ( $row['status'] ?? '' ) ); ?></span></td>
							<td><?php echo \esc_html( (string) ( $row['created_at'] ?? '' ) ); ?></td>
							<td><a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . AI_Runs_Screen::SLUG . '&run_id=' . \rawurlencode( (string) ( $row['run_id'] ?? '' ) ) ) ); ?>"><?php \esc_html_e( 'View details', 'aio-page-builder' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<p><a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . AI_Runs_Screen::SLUG ) ); ?>" class="button button-secondary"><?php \esc_html_e( 'Open AI Runs', 'aio-page-builder' ); ?></a></p>
		<?php
	}

	/** @param list<array{event_type: string, dedupe_key: string, attempted_at: string, delivery_status: string, log_reference: string, failure_reason: string}> $rows */
	private function render_reporting_tab( array $rows ): void {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php \esc_html_e( 'Event type', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Attempted at', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Delivery status', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Log reference', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Failure reason', 'aio-page-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="5"><?php \esc_html_e( 'No reporting log entries.', 'aio-page-builder' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo \esc_html( (string) ( $row['event_type'] ?? '' ) ); ?></td>
							<td><?php echo \esc_html( (string) ( $row['attempted_at'] ?? '' ) ); ?></td>
							<td><span class="aio-badge aio-badge-<?php echo \esc_attr( sanitize_key( (string) ( $row['delivery_status'] ?? '' ) ) ); ?>"><?php echo \esc_html( (string) ( $row['delivery_status'] ?? '' ) ); ?></span></td>
							<td><code><?php echo \esc_html( (string) ( $row['log_reference'] ?? '' ) ); ?></code></td>
							<td><?php echo \esc_html( (string) ( $row['failure_reason'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/** @param list<array{id: string, type: string, created_at: string, status: string}> $rows */
	private function render_import_export_tab( array $rows ): void {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php \esc_html_e( 'ID', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Type', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Created', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="4"><?php \esc_html_e( 'No import/export log entries yet.', 'aio-page-builder' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><code><?php echo \esc_html( (string) ( $row['id'] ?? '' ) ); ?></code></td>
							<td><?php echo \esc_html( (string) ( $row['type'] ?? '' ) ); ?></td>
							<td><?php echo \esc_html( (string) ( $row['created_at'] ?? '' ) ); ?></td>
							<td><?php echo \esc_html( (string) ( $row['status'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/** @param list<array{event_type: string, attempted_at: string, delivery_status: string, failure_reason: string, log_reference: string}> $rows */
	private function render_critical_tab( array $rows ): void {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php \esc_html_e( 'Event type', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Attempted at', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Delivery status', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Failure reason', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Log reference', 'aio-page-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="5"><?php \esc_html_e( 'No critical error report failures.', 'aio-page-builder' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo \esc_html( (string) ( $row['event_type'] ?? '' ) ); ?></td>
							<td><?php echo \esc_html( (string) ( $row['attempted_at'] ?? '' ) ); ?></td>
							<td><span class="aio-badge aio-badge-failed"><?php echo \esc_html( (string) ( $row['delivery_status'] ?? '' ) ); ?></span></td>
							<td><?php echo \esc_html( (string) ( $row['failure_reason'] ?? '' ) ); ?></td>
							<td><code><?php echo \esc_html( (string) ( $row['log_reference'] ?? '' ) ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Handles log export request. Nonce and capability checked; redirects with result.
	 *
	 * @return void
	 */
	public function handle_export_logs(): void {
		if ( ! isset( $_POST['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_EXPORT_LOGS ) ) {
			\wp_safe_redirect( $this->logs_url( 'error', __( 'Security check failed.', 'aio-page-builder' ) ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::EXPORT_DATA ) ) {
			\wp_safe_redirect( $this->logs_url( 'error', __( 'You do not have permission to export logs.', 'aio-page-builder' ) ) );
			exit;
		}
		$log_types = array();
		if ( ! empty( $_POST['log_types'] ) && is_array( $_POST['log_types'] ) ) {
			foreach ( $_POST['log_types'] as $t ) {
				$v = \sanitize_text_field( \wp_unslash( $t ) );
				if ( in_array( $v, Log_Export_Service::ALLOWED_LOG_TYPES, true ) ) {
					$log_types[] = $v;
				}
			}
		}
		$filters = array();
		if ( ! empty( $_POST['date_from'] ) && is_string( $_POST['date_from'] ) ) {
			$filters['date_from'] = \sanitize_text_field( \wp_unslash( $_POST['date_from'] ) );
		}
		if ( ! empty( $_POST['date_to'] ) && is_string( $_POST['date_to'] ) ) {
			$filters['date_to'] = \sanitize_text_field( \wp_unslash( $_POST['date_to'] ) );
		}
		$service = $this->get_log_export_service();
		$result  = $service->export( $log_types, $filters );
		if ( $result->is_success() ) {
			\wp_safe_redirect( $this->logs_url( 'success', '', $result->get_export_file_reference() ) );
		} else {
			\wp_safe_redirect( $this->logs_url( 'error', $result->get_message() ) );
		}
		exit;
	}

	/**
	 * Serves log export file download. Nonce and capability checked; filename validated.
	 *
	 * @return void
	 */
	public function handle_download_log_export(): void {
		if ( ! isset( $_GET['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_GET['_wpnonce'] ) ), self::NONCE_DOWNLOAD_LOG ) ) {
			\wp_die( \esc_html__( 'Invalid request.', 'aio-page-builder' ), 403 );
		}
		if ( ! \current_user_can( Capabilities::EXPORT_DATA ) ) {
			\wp_die( \esc_html__( 'You do not have permission to download log exports.', 'aio-page-builder' ), 403 );
		}
		$file = isset( $_GET['file'] ) ? \sanitize_file_name( \wp_unslash( $_GET['file'] ) ) : '';
		if ( $file === '' || preg_match( '#^aio-log-export-\d{8}-\d{6}\.json$#', $file ) !== 1 ) {
			\wp_die( \esc_html__( 'Invalid file.', 'aio-page-builder' ), 400 );
		}
		if ( ! $this->container || ! $this->container->has( 'plugin_path_manager' ) ) {
			\wp_die( \esc_html__( 'Service unavailable.', 'aio-page-builder' ), 503 );
		}
		$path_manager = $this->container->get( 'plugin_path_manager' );
		$exports_dir  = $path_manager->get_exports_dir();
		if ( $exports_dir === '' ) {
			\wp_die( \esc_html__( 'File not found.', 'aio-page-builder' ), 404 );
		}
		$path = rtrim( $exports_dir, '/\\' ) . '/' . $file;
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			\wp_die( \esc_html__( 'File not found.', 'aio-page-builder' ), 404 );
		}
		\header( 'Content-Type: application/json' );
		\header( 'Content-Disposition: attachment; filename="' . \esc_attr( $file ) . '"' );
		\header( 'Content-Length: ' . (string) filesize( $path ) );
		readfile( $path );
		exit;
	}

	private function logs_url( string $status, string $message = '', string $file = '' ): string {
		$args = array( 'page' => self::SLUG, 'aio_log_export' => $status );
		if ( $message !== '' ) {
			$args['aio_log_message'] = $message;
		}
		if ( $file !== '' ) {
			$args['aio_log_file'] = $file;
		}
		return \add_query_arg( $args, \admin_url( 'admin.php' ) );
	}

	private function get_log_export_service(): Log_Export_Service {
		if ( $this->container && $this->container->has( 'log_export_service' ) ) {
			return $this->container->get( 'log_export_service' );
		}
		$path_manager = $this->container && $this->container->has( 'plugin_path_manager' ) ? $this->container->get( 'plugin_path_manager' ) : new \AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager();
		$redaction    = $this->container && $this->container->has( 'reporting_redaction_service' ) ? $this->container->get( 'reporting_redaction_service' ) : new \AIOPageBuilder\Domain\Reporting\Errors\Reporting_Redaction_Service();
		$logger       = $this->container && $this->container->has( 'logger' ) ? $this->container->get( 'logger' ) : null;
		$job_repo     = $this->container && $this->container->has( 'job_queue_repository' ) ? $this->container->get( 'job_queue_repository' ) : null;
		$ai_repo      = $this->container && $this->container->has( 'ai_run_repository' ) ? $this->container->get( 'ai_run_repository' ) : null;
		return new Log_Export_Service( $path_manager, $redaction, $logger, $job_repo, $ai_repo );
	}
}
