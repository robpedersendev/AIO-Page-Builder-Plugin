<?php
/**
 * AI Runs list and routing to detail (spec §29, §44.7, §44.9). Capability: aio_view_ai_runs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Pricing\Provider_Pricing_Registry;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Lists AI runs; with run_id in request, renders run detail (metadata + artifact summaries).
 */
final class AI_Runs_Screen {

	public const SLUG = 'aio-page-builder-ai-runs';

	/** Admin hub page slug (tabs: AI Providers, AI Runs, Prompt Experiments). */
	public const HUB_PAGE_SLUG = 'aio-page-builder-ai-workspace';

	/** Query arg: set to {@see ONBOARDING_PLAN_SUCCESS_VALUE} when redirecting from onboarding after a successful planning run. */
	public const QUERY_ONBOARDING_PLAN = 'aio_onboarding_plan';

	/** Value for {@see QUERY_ONBOARDING_PLAN} after onboarding planning success. */
	public const ONBOARDING_PLAN_SUCCESS_VALUE = 'success';

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
	 * @param bool $embed_in_hub When true, outer wrap and list h1 are omitted.
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view AI runs.', 'aio-page-builder' ) );
		}
		$run_id = isset( $_GET['run_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['run_id'] ) ) : '';
		if ( $run_id !== '' ) {
			$detail = new AI_Run_Detail_Screen( $this->container );
			$detail->render( $run_id, $embed_in_hub );
			return;
		}
		$this->render_list( $embed_in_hub );
	}

	/**
	 * After onboarding redirects here with QUERY_ONBOARDING_PLAN, shows the stored user message and clears the transient.
	 *
	 * @return void
	 */
	private function render_onboarding_plan_success_notice_if_needed(): void {
		if ( ! isset( $_GET[ self::QUERY_ONBOARDING_PLAN ] ) ) {
			return;
		}
		$flag = \sanitize_key( (string) \wp_unslash( (string) $_GET[ self::QUERY_ONBOARDING_PLAN ] ) );
		if ( $flag !== self::ONBOARDING_PLAN_SUCCESS_VALUE ) {
			return;
		}
		$transient_key = 'aio_onboarding_planning_result_' . (string) \get_current_user_id();
		$stored        = \get_transient( $transient_key );
		$message       = __( 'Your AI plan was generated successfully. Review this run, then create a Build Plan when you are ready.', 'aio-page-builder' );
		if ( is_array( $stored ) && isset( $stored['user_message'] ) && is_string( $stored['user_message'] ) && $stored['user_message'] !== '' ) {
			$message = $stored['user_message'];
		}
		\delete_transient( $transient_key );
		$build_plans_url = '';
		if ( Capabilities::current_user_can_for_route( Capabilities::ACCESS_PLANS_WORKSPACE ) ) {
			$build_plans_url = Admin_Screen_Hub::tab_url(
				Build_Plans_Screen::SLUG,
				'build_plans',
				array()
			);
		}
		$onboarding_url = Admin_Screen_Hub::tab_url( Onboarding_Screen::SLUG, 'onboarding', array() );
		?>
		<div class="notice notice-success is-dismissible aio-onboarding-plan-success-notice" role="status">
			<p><strong><?php echo \esc_html( $message ); ?></strong></p>
			<p>
				<?php if ( $build_plans_url !== '' ) : ?>
					<a href="<?php echo \esc_url( $build_plans_url ); ?>" class="button button-primary"><?php \esc_html_e( 'Open Build Plans', 'aio-page-builder' ); ?></a>
				<?php endif; ?>
				<a href="<?php echo \esc_url( $onboarding_url ); ?>" class="button<?php echo $build_plans_url === '' ? ' button-primary' : ''; ?>"><?php \esc_html_e( 'Back to onboarding', 'aio-page-builder' ); ?></a>
			</p>
		</div>
		<?php
	}

	private function render_list( bool $embed_in_hub = false ): void {
		$runs            = array();
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
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-ai-runs" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
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
								<td><?php echo \esc_html( AI_Run_Artifact_Service::format_run_metadata_value_for_display( $meta['prompt_pack_ref'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $meta['created_at'] ?? '' ) ); ?></td>
								<td>
									<a href="<?php echo \esc_url( Admin_Screen_Hub::tab_url( self::HUB_PAGE_SLUG, 'ai_runs', array( 'run_id' => $run_id ) ) ); ?>"><?php \esc_html_e( 'View details', 'aio-page-builder' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
