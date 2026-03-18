<?php
/**
 * Crawl session detail: page-level snapshot list for one run (spec §24.17, crawler-admin-screen-contract).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Crawler;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders page records for a single crawl run. No raw HTML bodies; structured summary only.
 * Shown within Crawler_Sessions_Screen; same capability as sessions list (spec §44.3).
 */
final class Crawler_Session_Detail_Screen {

	/** Aligned with Crawler_Sessions_Screen for consistency. */
	private const CAPABILITY = Capabilities::VIEW_SENSITIVE_DIAGNOSTICS;

	/**
	 * Capability required to view this screen (used when rendered via Crawler_Sessions_Screen).
	 *
	 * @return string
	 */
	public function get_capability(): string {
		return self::CAPABILITY;
	}

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	/**
	 * Renders detail view for the given run_id.
	 *
	 * @param string $run_id Crawl run identifier.
	 * @return void
	 */
	public function render( string $run_id ): void {
		$run_id = \sanitize_text_field( $run_id );
		if ( $run_id === '' ) {
			echo '<div class="wrap"><p>' . \esc_html__( 'Invalid run ID.', 'aio-page-builder' ) . '</p></div>';
			return;
		}
		$pages   = array();
		$session = null;
		if ( $this->container && $this->container->has( 'crawl_snapshot_service' ) ) {
			$svc     = $this->container->get( 'crawl_snapshot_service' );
			$pages   = $svc->list_pages_by_run( $run_id, null, 500, 0 );
			$session = $svc->get_session( $run_id );
		}
		$back_url = \admin_url( 'admin.php?page=' . Crawler_Sessions_Screen::SLUG );
		?>
		<div class="wrap aio-page-builder-screen aio-crawler-session-detail">
			<h1><?php echo \esc_html__( 'Crawl Session Detail', 'aio-page-builder' ); ?></h1>
			<p>
				<a href="<?php echo \esc_url( $back_url ); ?>">&larr; <?php \esc_html_e( 'Back to sessions', 'aio-page-builder' ); ?></a>
			</p>
			<p><strong><?php \esc_html_e( 'Run ID:', 'aio-page-builder' ); ?></strong> <code><?php echo \esc_html( $run_id ); ?></code></p>
			<?php if ( $session && ! empty( $session['site_host'] ) ) : ?>
				<p><strong><?php \esc_html_e( 'Site host:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( (string) $session['site_host'] ); ?></p>
				<p><strong><?php \esc_html_e( 'Status:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( (string) ( $session['final_status'] ?? '' ) ); ?></p>
			<?php endif; ?>
			<h2><?php \esc_html_e( 'Page snapshots', 'aio-page-builder' ); ?></h2>
			<?php if ( count( $pages ) === 0 ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'No page records for this run.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'URL', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Title', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Classification', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Nav', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pages as $row ) : ?>
							<tr>
								<td><code><?php echo \esc_html( (string) ( $row['url'] ?? '' ) ); ?></code></td>
								<td><?php echo \esc_html( \wp_trim_words( (string) ( $row['title_snapshot'] ?? '' ), 10 ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $row['page_classification'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $row['navigation_participation'] ?? '0' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $row['crawl_status'] ?? '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
