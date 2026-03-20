<?php
/**
 * AI Runs list and routing to detail (spec §29, §44.7, §44.9). Capability: aio_view_ai_runs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Pricing\Provider_Pricing_Registry;
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
		$runs         = array();
		$spend_summaries = array();
		if ( $this->container && $this->container->has( 'ai_run_repository' ) ) {
			try {
				$repo = $this->container->get( 'ai_run_repository' );
				$runs = $repo->list_recent( 50, 0 );
			} catch ( \Throwable $e ) {
				$runs = array();
			}
		}
		if ( $this->container && $this->container->has( 'provider_monthly_spend_service' ) && $this->container->has( 'provider_pricing_registry' ) ) {
			try {
				$spend_svc = $this->container->get( 'provider_monthly_spend_service' );
				$registry  = $this->container->get( 'provider_pricing_registry' );
				foreach ( $registry->get_provider_ids() as $provider_id ) {
					$spend_summaries[ $provider_id ] = $spend_svc->get_spend_summary( $provider_id );
				}
			} catch ( \Throwable $e ) {
				$spend_summaries = array();
			}
		}
		?>
		<div class="wrap aio-page-builder-screen aio-ai-runs" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-ai-runs-description"><?php \esc_html_e( 'Review AI runs and their artifact summaries. Raw prompts and provider responses are restricted.', 'aio-page-builder' ); ?></p>
			<?php if ( ! empty( $spend_summaries ) ) : ?>
			<section class="aio-spend-summary" aria-labelledby="aio-spend-summary-heading">
				<h2 id="aio-spend-summary-heading" style="font-size:1em;margin-bottom:0.5em;"><?php \esc_html_e( 'Month-to-date spend by provider', 'aio-page-builder' ); ?></h2>
				<table class="widefat striped" style="max-width:640px;margin-bottom:1.5em;">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Spent (USD)', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Cap (USD)', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $spend_summaries as $provider_id => $summary ) : ?>
						<?php
						$month_total = isset( $summary['month_total'] ) ? (float) $summary['month_total'] : 0.0;
						$cap         = isset( $summary['cap'] ) ? (float) $summary['cap'] : 0.0;
						$has_cap     = ! empty( $summary['has_cap'] );
						$exceeded    = ! empty( $summary['exceeded'] );
						$approaching = ! empty( $summary['approaching'] );
						$pct         = isset( $summary['percent_used'] ) ? (float) $summary['percent_used'] : 0.0;
						if ( $exceeded ) {
							$status_label = \__( 'Cap exceeded', 'aio-page-builder' );
							$status_color = '#dc3232';
						} elseif ( $approaching ) {
							$status_label = sprintf(
								/* translators: %d: integer percent of cap used */
								\__( 'Approaching cap (%d%%)', 'aio-page-builder' ),
								(int) round( $pct * 100 )
							);
							$status_color = '#ffba00';
						} elseif ( $has_cap ) {
							$status_label = sprintf(
								/* translators: %d: integer percent of cap used */
								\__( '%d%% of cap used', 'aio-page-builder' ),
								(int) round( $pct * 100 )
							);
							$status_color = '';
						} else {
							$status_label = \__( 'No cap set', 'aio-page-builder' );
							$status_color = '';
						}
						?>
						<tr>
							<td><?php echo \esc_html( $provider_id ); ?></td>
							<td>$<?php echo \esc_html( number_format( $month_total, 4 ) ); ?></td>
							<td><?php echo $has_cap ? \esc_html( '$' . number_format( $cap, 2 ) ) : \esc_html( \__( '—', 'aio-page-builder' ) ); ?></td>
							<td style="<?php echo $status_color ? 'color:' . \esc_attr( $status_color ) . ';font-weight:600;' : ''; ?>"><?php echo \esc_html( $status_label ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>
			<?php endif; ?>
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
							$meta   = $run['run_metadata'] ?? array();
							$run_id = (string) ( $run['internal_key'] ?? $run['post_title'] ?? '' );
							?>
							<?php
							$is_experiment    = ! empty( $meta['is_experiment'] );
							$experiment_label = $is_experiment
								? ( (string) ( $meta['experiment_variant_label'] ?? $meta['experiment_id'] ?? __( 'Experiment', 'aio-page-builder' ) ) )
								: '';
							?>
						<tr>
							<td><code><?php echo \esc_html( $run_id ); ?></code>
							<?php
							if ( $is_experiment ) :
								?>
								<span class="aio-run-badge" aria-label="<?php esc_attr_e( 'Experiment run', 'aio-page-builder' ); ?>"><?php echo \esc_html( $experiment_label ? $experiment_label : __( 'Experiment', 'aio-page-builder' ) ); ?></span><?php endif; ?></td>
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
