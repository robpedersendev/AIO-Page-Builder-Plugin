<?php
/**
 * Build Plan list and routing to workspace (spec §31, build-plan-admin-ia-contract.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\BuildPlan;

defined( 'ABSPATH' ) || exit;

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
	public function render(): void {
		$plan_id = isset( $_GET['plan_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['plan_id'] ) ) : '';
		if ( $plan_id === '' && isset( $_GET['id'] ) ) {
			$plan_id = \sanitize_text_field( \wp_unslash( (string) $_GET['id'] ) );
		}
		if ( $plan_id !== '' ) {
			$workspace = new Build_Plan_Workspace_Screen( $this->container );
			$workspace->render( $plan_id );
			return;
		}
		$this->render_list();
	}

	private function render_list(): void {
		$plans = array();
		if ( $this->container && $this->container->has( 'build_plan_repository' ) ) {
			try {
				$repo = $this->container->get( 'build_plan_repository' );
				$plans = $repo->list_recent( 50, 0 );
			} catch ( \Throwable $e ) {
				$plans = array();
			}
		}
		?>
		<div class="wrap aio-page-builder-screen aio-build-plans-list">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-build-plans-description"><?php \esc_html_e( 'Review and manage build plans. Open a plan to review steps and items.', 'aio-page-builder' ); ?></p>
			<?php if ( count( $plans ) === 0 ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'No build plans yet. Create a plan from an AI Run.', 'aio-page-builder' ); ?></p>
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
							$pid = (string) ( $plan['plan_id'] ?? $plan['internal_key'] ?? $plan['post_title'] ?? '' );
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
		</div>
		<?php
	}
}
