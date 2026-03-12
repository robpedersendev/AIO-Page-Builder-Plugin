<?php
/**
 * Crawl comparison: prior vs new run summary and page-level changes (spec §24.17, crawler-admin-screen-contract).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Crawler;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders comparison form (select prior/new run) and Session_Comparison_Result summary. No mutating actions; nonce reserved for future.
 */
final class Crawler_Comparison_Screen {

	public const SLUG = 'aio-page-builder-crawler-comparison';

	private const CAPABILITY = 'manage_options';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Crawl Comparison', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return self::CAPABILITY;
	}

	/**
	 * Renders comparison screen: run selectors and optional result table.
	 *
	 * @return void
	 */
	public function render(): void {
		$sessions = array();
		if ( $this->container && $this->container->has( 'crawl_snapshot_service' ) ) {
			$svc      = $this->container->get( 'crawl_snapshot_service' );
			$sessions = $svc->list_sessions( 50 );
		}
		$prior_run_id = isset( $_GET['prior_run_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['prior_run_id'] ) ) : '';
		$new_run_id   = isset( $_GET['new_run_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['new_run_id'] ) ) : '';
		$result       = null;
		if ( $prior_run_id !== '' && $new_run_id !== '' && $this->container && $this->container->has( 'recrawl_comparison_service' ) ) {
			try {
				$comparison = $this->container->get( 'recrawl_comparison_service' );
				$result     = $comparison->compare( $prior_run_id, $new_run_id );
			} catch ( \Throwable $e ) {
				$result = null;
			}
		}
		?>
		<div class="wrap aio-page-builder-screen aio-crawler-comparison" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-crawler-readiness"><?php \esc_html_e( 'Compare two crawl runs to see added, removed, and changed pages. Select prior (baseline) and new run.', 'aio-page-builder' ); ?></p>
			<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" class="aio-crawler-comparison-form">
				<input type="hidden" name="page" value="<?php echo \esc_attr( self::SLUG ); ?>" />
				<p>
					<label for="prior_run_id"><?php \esc_html_e( 'Prior run (baseline):', 'aio-page-builder' ); ?></label>
					<select name="prior_run_id" id="prior_run_id">
						<option value=""><?php \esc_html_e( '— Select —', 'aio-page-builder' ); ?></option>
						<?php foreach ( $sessions as $s ) : ?>
							<option value="<?php echo \esc_attr( (string) ( $s['crawl_run_id'] ?? '' ) ); ?>" <?php selected( $prior_run_id, (string) ( $s['crawl_run_id'] ?? '' ) ); ?>><?php echo \esc_html( (string) ( $s['crawl_run_id'] ?? '' ) ); ?> (<?php echo \esc_html( (string) ( $s['site_host'] ?? '' ) ); ?>)</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label for="new_run_id"><?php \esc_html_e( 'New run:', 'aio-page-builder' ); ?></label>
					<select name="new_run_id" id="new_run_id">
						<option value=""><?php \esc_html_e( '— Select —', 'aio-page-builder' ); ?></option>
						<?php foreach ( $sessions as $s ) : ?>
							<option value="<?php echo \esc_attr( (string) ( $s['crawl_run_id'] ?? '' ) ); ?>" <?php selected( $new_run_id, (string) ( $s['crawl_run_id'] ?? '' ) ); ?>><?php echo \esc_html( (string) ( $s['crawl_run_id'] ?? '' ) ); ?> (<?php echo \esc_html( (string) ( $s['site_host'] ?? '' ) ); ?>)</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<button type="submit" class="button button-primary"><?php \esc_html_e( 'Compare', 'aio-page-builder' ); ?></button>
				</p>
			</form>
			<?php if ( $result !== null ) : ?>
				<h2><?php \esc_html_e( 'Comparison summary', 'aio-page-builder' ); ?></h2>
				<table class="widefat striped">
					<tbody>
						<tr><th scope="row"><?php \esc_html_e( 'Added', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) $result->added_count ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Removed', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) $result->removed_count ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Changed', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) $result->changed_count ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Unchanged', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) $result->unchanged_count ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Reclassified', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) $result->reclassified_count ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Meaningful (prior)', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) $result->meaningful_count_prior ); ?></td></tr>
						<tr><th scope="row"><?php \esc_html_e( 'Meaningful (new)', 'aio-page-builder' ); ?></th><td><?php echo \esc_html( (string) $result->meaningful_count_new ); ?></td></tr>
					</tbody>
				</table>
				<h3><?php \esc_html_e( 'Page changes', 'aio-page-builder' ); ?></h3>
				<?php if ( count( $result->page_changes ) === 0 ) : ?>
					<p><?php \esc_html_e( 'No page changes.', 'aio-page-builder' ); ?></p>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col"><?php \esc_html_e( 'URL', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Category', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Reasons', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $result->page_changes as $change ) : ?>
								<tr>
									<td><code><?php echo \esc_html( $change->url ); ?></code></td>
									<td><?php echo \esc_html( $change->change_category ); ?></td>
									<td><?php echo \esc_html( implode( ', ', $change->reason_codes ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			<?php endif; ?>
			<?php /* Future: nonce for any mutating action. */ ?>
			<p class="aio-crawler-action-placeholder" data-nonce-placeholder="reserved" aria-hidden="true"></p>
		</div>
		<?php
	}
}
