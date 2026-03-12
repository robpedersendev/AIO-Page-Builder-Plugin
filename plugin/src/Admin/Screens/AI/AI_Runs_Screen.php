<?php
/**
 * AI Runs list and routing to detail (spec §29, §44.7, §44.9). Capability: aio_view_ai_runs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Lists AI runs; with run_id in request, renders run detail (metadata + artifact summaries).
 */
final class AI_Runs_Screen {

	public const SLUG = 'aio-page-builder-ai-runs';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'AI Runs', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_AI_RUNS;
	}

	/**
	 * Renders list of runs or run detail when run_id is present.
	 *
	 * @return void
	 */
	public function render(): void {
		$run_id = isset( $_GET['run_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['run_id'] ) ) : '';
		if ( $run_id !== '' ) {
			$detail = new AI_Run_Detail_Screen( $this->container );
			$detail->render( $run_id );
			return;
		}
		$this->render_list();
	}

	private function render_list(): void {
		$runs = array();
		if ( $this->container && $this->container->has( 'ai_run_repository' ) ) {
			try {
				$repo = $this->container->get( 'ai_run_repository' );
				$runs = $repo->list_recent( 50, 0 );
			} catch ( \Throwable $e ) {
				$runs = array();
			}
		}
		?>
		<div class="wrap aio-page-builder-screen aio-ai-runs" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-ai-runs-description"><?php \esc_html_e( 'Review AI runs and their artifact summaries. Raw prompts and provider responses are restricted.', 'aio-page-builder' ); ?></p>
			<?php if ( count( $runs ) === 0 ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'No AI runs yet.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Run ID', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Model', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Prompt pack', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Created', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $runs as $run ) : ?>
							<?php
							$meta = $run['run_metadata'] ?? array();
							$run_id = (string) ( $run['internal_key'] ?? $run['post_title'] ?? '' );
							?>
							<tr>
								<td><code><?php echo \esc_html( $run_id ); ?></code></td>
								<td><?php echo \esc_html( (string) ( $run['status'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $meta['provider_id'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $meta['model_used'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $meta['prompt_pack_ref'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $meta['created_at'] ?? '' ) ); ?></td>
								<td>
									<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::SLUG . '&run_id=' . \rawurlencode( $run_id ) ) ); ?>"><?php \esc_html_e( 'View details', 'aio-page-builder' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
