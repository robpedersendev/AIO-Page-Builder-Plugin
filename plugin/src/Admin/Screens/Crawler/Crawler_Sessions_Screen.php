<?php
/**
 * Crawl Sessions admin screen: list of crawl runs and session detail (spec §24.17, crawler-admin-screen-contract).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Crawler;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Lists crawl sessions; with run_id in request, renders session detail (pages for that run).
 * Profile column and profile selector for crawl runs (spec §24, §59.7; Prompt 128).
 */
final class Crawler_Sessions_Screen {

	public const SLUG = 'aio-page-builder-crawler-sessions';

	/** Gated by plugin capability for crawler/diagnostics (spec §44.3). */
	private const CAPABILITY = Capabilities::VIEW_SENSITIVE_DIAGNOSTICS;

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	/**
	 * Returns profile label for display, or raw key if service unavailable.
	 *
	 * @param string $profile_key
	 * @return string
	 */
	private function get_profile_label( string $profile_key ): string {
		if ( $this->container && $this->container->has( 'crawl_profile_service' ) ) {
			try {
				$svc     = $this->container->get( 'crawl_profile_service' );
				$payload = $svc->get_profile_payload( $profile_key );
				return $payload['label'] ?? $profile_key;
			} catch ( \Throwable $e ) {
				return $profile_key;
			}
		}
		return $profile_key;
	}

	public function get_title(): string {
		return __( 'Crawl Sessions', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return self::CAPABILITY;
	}

	/**
	 * Renders list of sessions or session detail when run_id is present.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view crawl sessions.', 'aio-page-builder' ), 403 );
		}
		$this->maybe_handle_actions();
		$run_id = isset( $_GET['run_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['run_id'] ) ) : '';
		if ( $run_id !== '' ) {
			$this->render_detail( $run_id );
			return;
		}
		$this->render_list();
	}

	private function maybe_handle_actions(): void {
		$action = isset( $_POST['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['action'] ) ) : '';
		if ( $action === '' ) {
			return;
		}
		if ( ! \current_user_can( Capabilities::RUN_ONBOARDING ) ) {
			return;
		}
		if ( ! $this->container || ! $this->container->has( 'crawl_enqueue_service' ) ) {
			return;
		}
		$svc = $this->container->get( 'crawl_enqueue_service' );
		if ( $action === 'aio_pb_start_crawl' ) {
			\check_admin_referer( 'aio_pb_start_crawl' );
			$profile_key = isset( $_POST['crawl_profile_key'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['crawl_profile_key'] ) ) : '';
			$out         = $svc->enqueue_start( array( 'crawl_profile_key' => $profile_key ), 'user:' . (string) \get_current_user_id() );
			$this->redirect_with_notice( $out['success'] ? 'success' : 'error', (string) $out['message'] );
		}
		if ( $action === 'aio_pb_retry_crawl' ) {
			$crawl_id = isset( $_POST['crawl_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['crawl_id'] ) ) : '';
			\check_admin_referer( 'aio_pb_retry_crawl_' . $crawl_id );
			$out = $svc->enqueue_retry( $crawl_id, 'user:' . (string) \get_current_user_id() );
			$this->redirect_with_notice( $out['success'] ? 'success' : 'error', (string) $out['message'] );
		}
	}

	private function render_list(): void {
		$sessions = array();
		if ( $this->container && $this->container->has( 'crawl_snapshot_service' ) ) {
			try {
				$svc      = $this->container->get( 'crawl_snapshot_service' );
				$sessions = $svc->list_sessions( 50 );
			} catch ( \Throwable $e ) {
				$sessions = array();
			}
		}
		?>
		<div class="wrap aio-page-builder-screen aio-crawler-sessions" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<?php $this->render_notice(); ?>
			<p class="aio-crawler-readiness" aria-describedby="aio-crawler-rules"><?php \esc_html_e( 'Crawler runs are scoped to this site only. Sessions below are from the crawl snapshot table.', 'aio-page-builder' ); ?></p>
			<div id="aio-crawler-rules" class="screen-reader-text"><?php \esc_html_e( 'Public-only, normalized URL identity, meaningful-page focus. No arbitrary host input.', 'aio-page-builder' ); ?></div>
			<?php
			$profile_options = array();
			if ( $this->container && $this->container->has( 'crawl_profile_service' ) ) {
				try {
					$profile_options = $this->container->get( 'crawl_profile_service' )->list_profiles_for_selection();
				} catch ( \Throwable $e ) {
					$profile_options = array();
				}
			}
			if ( count( $profile_options ) > 0 ) :
				?>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_pb_start_crawl" />
				<?php \wp_nonce_field( 'aio_pb_start_crawl' ); ?>
				<label for="aio-crawl-profile"><?php \esc_html_e( 'Crawl profile:', 'aio-page-builder' ); ?></label>
				<select id="aio-crawl-profile" name="crawl_profile_key" aria-describedby="aio-crawler-profile-desc">
					<?php foreach ( $profile_options as $opt ) : ?>
						<option value="<?php echo \esc_attr( $opt['key'] ); ?>"><?php echo \esc_html( $opt['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="button button-primary"><?php \esc_html_e( 'Start crawl', 'aio-page-builder' ); ?></button>
			</form>
			<p id="aio-crawler-profile-desc" class="screen-reader-text"><?php \esc_html_e( 'Bounded profiles only; no custom unbounded crawls.', 'aio-page-builder' ); ?></p>
			<?php endif; ?>
			<?php if ( count( $sessions ) === 0 ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'No crawl sessions yet.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Run ID', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Site host', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Profile', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Discovered', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Accepted', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Excluded', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Failed', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Started', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $sessions as $s ) : ?>
							<tr>
								<td><code><?php echo \esc_html( (string) ( $s['crawl_run_id'] ?? '' ) ); ?></code></td>
								<td><?php echo \esc_html( (string) ( $s['site_host'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( $this->get_profile_label( (string) ( $s['crawl_profile_key'] ?? '' ) ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['final_status'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['total_discovered'] ?? '0' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['accepted_count'] ?? '0' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['excluded_count'] ?? '0' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['failed_count'] ?? '0' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['started_at'] ?? '' ) ); ?></td>
								<td>
									<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::SLUG . '&run_id=' . \rawurlencode( (string) ( $s['crawl_run_id'] ?? '' ) ) ) ); ?>"><?php \esc_html_e( 'View pages', 'aio-page-builder' ); ?></a>
									<form method="post" action="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" style="display:inline-block;margin-left:6px;">
										<input type="hidden" name="action" value="aio_pb_retry_crawl" />
										<input type="hidden" name="crawl_id" value="<?php echo \esc_attr( (string) ( $s['crawl_run_id'] ?? '' ) ); ?>" />
										<?php \wp_nonce_field( 'aio_pb_retry_crawl_' . (string) ( $s['crawl_run_id'] ?? '' ) ); ?>
										<button type="submit" class="button button-small"><?php \esc_html_e( 'Retry crawl', 'aio-page-builder' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	private function redirect_with_notice( string $status, string $message ): void {
		$url = \add_query_arg(
			array(
				'page'                => self::SLUG,
				'aio_crawl_status'    => $status,
				'aio_crawl_message'   => $message,
			),
			\admin_url( 'admin.php' )
		);
		\wp_safe_redirect( $url );
		exit;
	}

	private function render_notice(): void {
		$message = isset( $_GET['aio_crawl_message'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['aio_crawl_message'] ) ) : '';
		$status  = isset( $_GET['aio_crawl_status'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['aio_crawl_status'] ) ) : '';
		if ( $message === '' || $status === '' ) {
			return;
		}
		$class = $status === 'success' ? 'notice-success' : 'notice-error';
		echo '<div class="notice ' . \esc_attr( $class ) . ' is-dismissible"><p>' . \esc_html( $message ) . '</p></div>';
	}

	private function render_detail( string $run_id ): void {
		$detail = new Crawler_Session_Detail_Screen( $this->container );
		$detail->render( $run_id );
	}
}
