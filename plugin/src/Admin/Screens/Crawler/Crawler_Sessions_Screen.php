<?php
/**
 * Crawl Sessions admin screen: list of crawl runs and session detail (spec §24.17, crawler-admin-screen-contract).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Crawler;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Lists crawl sessions; with run_id in request, renders session detail (pages for that run).
 */
final class Crawler_Sessions_Screen {

	public const SLUG = 'aio-page-builder-crawler-sessions';

	private const CAPABILITY = 'manage_options';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
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
		$run_id = isset( $_GET['run_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['run_id'] ) ) : '';
		if ( $run_id !== '' ) {
			$this->render_detail( $run_id );
			return;
		}
		$this->render_list();
	}

	private function render_list(): void {
		$sessions = array();
		if ( $this->container && $this->container->has( 'crawl_snapshot_service' ) ) {
			try {
				$svc    = $this->container->get( 'crawl_snapshot_service' );
				$sessions = $svc->list_sessions( 50 );
			} catch ( \Throwable $e ) {
				$sessions = array();
			}
		}
		?>
		<div class="wrap aio-page-builder-screen aio-crawler-sessions" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-crawler-readiness" aria-describedby="aio-crawler-rules"><?php \esc_html_e( 'Crawler runs are scoped to this site only. Sessions below are from the crawl snapshot table.', 'aio-page-builder' ); ?></p>
			<div id="aio-crawler-rules" class="screen-reader-text"><?php \esc_html_e( 'Public-only, normalized URL identity, meaningful-page focus. No arbitrary host input.', 'aio-page-builder' ); ?></div>
			<?php if ( count( $sessions ) === 0 ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'No crawl sessions yet.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Run ID', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Site host', 'aio-page-builder' ); ?></th>
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
								<td><?php echo \esc_html( (string) ( $s['final_status'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['total_discovered'] ?? '0' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['accepted_count'] ?? '0' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['excluded_count'] ?? '0' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['failed_count'] ?? '0' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $s['started_at'] ?? '' ) ); ?></td>
								<td>
									<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::SLUG . '&run_id=' . \rawurlencode( (string) ( $s['crawl_run_id'] ?? '' ) ) ) ); ?>"><?php \esc_html_e( 'View pages', 'aio-page-builder' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<?php /* Future: crawl start/retry button; nonce placeholder. */ ?>
			<p class="aio-crawler-action-placeholder" data-nonce-placeholder="reserved" aria-hidden="true"></p>
		</div>
		<?php
	}

	private function render_detail( string $run_id ): void {
		$detail = new Crawler_Session_Detail_Screen( $this->container );
		$detail->render( $run_id );
	}
}
