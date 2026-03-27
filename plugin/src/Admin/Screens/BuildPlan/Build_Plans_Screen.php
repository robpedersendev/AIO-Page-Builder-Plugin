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
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Lists Build Plans; with plan_id or id in request, delegates to Build_Plan_Workspace_Screen.
 */
final class Build_Plans_Screen {

	public const SLUG = 'aio-page-builder-build-plans';

	private Service_Container $container;

	public function __construct( Service_Container $container ) {
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
		$grouped = $this->container->get( 'build_plan_lineage_service' )->list_lineages_with_versions_for_admin();
		$orphans = $this->query_unversioned_build_plan_rows();
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-build-plans-list" data-testid="aio-build-plans-list-screen" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="aio-build-plans-description"><?php \esc_html_e( 'Plans are grouped by lineage (one site strategy split into versions). Open any version to review steps, see what is already applied, and execute remaining items.', 'aio-page-builder' ); ?></p>
			<?php if ( count( $grouped ) === 0 && count( $orphans ) === 0 ) : ?>
				<p class="aio-admin-notice">
					<?php \esc_html_e( 'No build plans yet.', 'aio-page-builder' ); ?>
					<?php if ( Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) ) : ?>
						<?php
						$ai_runs_url = Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_runs' );
						?>
						<a href="<?php echo \esc_url( $ai_runs_url ); ?>"><?php \esc_html_e( 'Open AI Runs', 'aio-page-builder' ); ?></a>
						<?php \esc_html_e( '— open a completed run, then use “Create Build Plan from this run”, or finish onboarding to create a versioned plan.', 'aio-page-builder' ); ?>
					<?php else : ?>
						<?php \esc_html_e( 'Ask an administrator to create a plan from a completed AI run.', 'aio-page-builder' ); ?>
					<?php endif; ?>
				</p>
			<?php else : ?>
				<?php foreach ( $grouped as $group ) : ?>
					<?php
					if ( ! is_array( $group ) ) {
						continue;
					}
					$gtitle = (string) ( $group['display_title'] ?? '' );
					$vers   = isset( $group['versions'] ) && is_array( $group['versions'] ) ? $group['versions'] : array();
					?>
					<h2 class="aio-build-plans-lineage-heading"><?php echo \esc_html( $gtitle !== '' ? $gtitle : __( 'Plan lineage', 'aio-page-builder' ) ); ?></h2>
					<table class="wp-list-table widefat fixed striped aio-build-plans-version-table">
						<thead>
							<tr>
								<th scope="col"><?php \esc_html_e( 'Version', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Plan ID', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Purpose', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Source run', 'aio-page-builder' ); ?></th>
								<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $vers as $row ) : ?>
								<?php
								if ( ! is_array( $row ) ) {
									continue;
								}
								$pid  = (string) ( $row['plan_id'] ?? '' );
								$vlab = (string) ( $row['version_label'] ?? '' );
								$purp = (string) ( $row['version_purpose'] ?? '' );
								$st   = (string) ( $row['plan_status'] ?? '' );
								$run  = (string) ( $row['ai_run_ref'] ?? '' );
								?>
								<tr>
									<td><?php echo \esc_html( $vlab ); ?></td>
									<td><code><?php echo \esc_html( $pid ); ?></code></td>
									<td><?php echo \esc_html( $purp !== '' ? $purp : '—' ); ?></td>
									<td><?php echo \esc_html( $st ); ?></td>
									<td><?php echo \esc_html( $run ); ?></td>
									<td>
										<a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::SLUG . '&plan_id=' . \rawurlencode( $pid ) ) ); ?>"><?php \esc_html_e( 'Open', 'aio-page-builder' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>
				<?php if ( count( $orphans ) > 0 ) : ?>
					<h2 class="aio-build-plans-lineage-heading"><?php \esc_html_e( 'Plans without lineage metadata', 'aio-page-builder' ); ?></h2>
					<p class="description"><?php \esc_html_e( 'Older or imported plans may not show a lineage until they are re-saved with current metadata.', 'aio-page-builder' ); ?></p>
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
							<?php foreach ( $orphans as $plan ) : ?>
								<?php
								$pid   = (string) ( $plan['plan_id'] ?? $plan['internal_key'] ?? '' );
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
			<?php endif; ?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Build plan posts missing lineage meta (legacy imports or pre-versioning data).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function query_unversioned_build_plan_rows(): array {
		if ( ! $this->container->has( 'build_plan_repository' ) ) {
			return array();
		}
		try {
			$repo = $this->container->get( 'build_plan_repository' );
		} catch ( \Throwable $e ) {
			return array();
		}
		$query = new \WP_Query(
			array(
				'post_type'              => Object_Type_Keys::BUILD_PLAN,
				'post_status'            => 'any',
				'posts_per_page'         => 50,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => Build_Plan_Repository::META_PLAN_LINEAGE_ID,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'   => Build_Plan_Repository::META_PLAN_LINEAGE_ID,
						'value' => '',
					),
				),
			)
		);
		$out   = array();
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$def   = $repo->get_plan_definition( $post->ID );
			$out[] = array(
				'plan_id'    => (string) ( $def['plan_id'] ?? '' ),
				'plan_title' => (string) ( $def['plan_title'] ?? $post->post_title ),
				'status'     => (string) ( $def['status'] ?? '' ),
				'ai_run_ref' => (string) ( $def['ai_run_ref'] ?? '' ),
				'post_title' => (string) $post->post_title,
			);
		}
		return $out;
	}
}
