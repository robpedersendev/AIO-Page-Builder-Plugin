<?php
/**
 * Build Plan list and routing to workspace (spec §31, build-plan-admin-ia-contract.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\BuildPlan;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Actions\Create_Build_Plan_From_AI_Run_Action;
use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Lists Build Plans; with plan_id or id in request, delegates to Build_Plan_Workspace_Screen.
 */
final class Build_Plans_Screen {

	public const SLUG = 'aio-page-builder-build-plans';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Build Plans', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_BUILD_PLANS;
	}

	/**
	 * Renders list or delegates to workspace when plan_id/id present.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		$plan_id = isset( $_GET['plan_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['plan_id'] ) ) : '';
		if ( $plan_id === '' && isset( $_GET['id'] ) ) {
			$plan_id = \sanitize_text_field( \wp_unslash( (string) $_GET['id'] ) );
		}
		if ( $plan_id !== '' ) {
			if ( isset( $_GET[ Create_Build_Plan_From_AI_Run_Action::QUERY_RESULT ] ) ) {
				$bp_flag = \sanitize_key( (string) \wp_unslash( (string) $_GET[ Create_Build_Plan_From_AI_Run_Action::QUERY_RESULT ] ) );
				if ( Create_Build_Plan_From_AI_Run_Action::RESULT_CREATED === $bp_flag ) {
					?>
					<div class="notice notice-success is-dismissible" role="status"><p><?php \esc_html_e( 'Build Plan created from the AI run. Review steps below.', 'aio-page-builder' ); ?></p></div>
					<?php
				}
			}
			$workspace = new Build_Plan_Workspace_Screen( $this->container );
			$workspace->render( $plan_id );
			return;
		}
		$this->render_list( $embed_in_hub );
	}

	private function render_list( bool $embed_in_hub = false ): void {
		$plans = array();
		if ( $this->container && $this->container->has( 'build_plan_repository' ) ) {
			try {
				$repo  = $this->container->get( 'build_plan_repository' );
				$plans = $repo->list_recent( 50, 0 );
			} catch ( \Throwable $e ) {
				$plans = array();
			}
		}
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-build-plans-list" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="aio-build-plans-description"><?php \esc_html_e( 'Review and manage build plans. Open a plan to review steps and items.', 'aio-page-builder' ); ?></p>
			<?php if ( count( $plans ) === 0 ) : ?>
				<p class="aio-admin-notice">
					<?php \esc_html_e( 'No build plans yet.', 'aio-page-builder' ); ?>
					<?php if ( Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) ) : ?>
						<?php
						$ai_runs_url = Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_runs' );
						?>
						<a href="<?php echo \esc_url( $ai_runs_url ); ?>"><?php \esc_html_e( 'Open AI Runs', 'aio-page-builder' ); ?></a>
						<?php \esc_html_e( '— open a completed run, then use “Create Build Plan from this run”.', 'aio-page-builder' ); ?>
					<?php else : ?>
						<?php \esc_html_e( 'Ask an administrator to create a plan from a completed AI run.', 'aio-page-builder' ); ?>
					<?php endif; ?>
				</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Plan', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Plan ID', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Source run', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $plans as $plan ) : ?>
							<?php
							$pid   = (string) ( $plan['plan_id'] ?? $plan['internal_key'] ?? $plan['post_title'] ?? '' );
							$title = (string) ( $plan['plan_title'] ?? $plan['post_title'] ?? $pid );
							?>
							<tr>
								<td><?php echo \esc_html( $title ); ?></td>
								<td><code><?php echo \esc_html( $pid ); ?></code></td>
								<td><?php echo \esc_html( (string) ( $plan['status'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $plan['ai_run_ref'] ?? '' ) ); ?></td>
								<td>
									<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::SLUG . '&plan_id=' . \rawurlencode( $pid ) ) ); ?>"><?php \esc_html_e( 'Open', 'aio-page-builder' ); ?></a>
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
