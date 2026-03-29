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
		<?php else : ?>
		<div class="aio-build-plans-list aio-build-plans-list--embedded" data-testid="aio-build-plans-list-screen" role="region" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
		<?php endif; ?>
			<div class="aio-bp-list-intro">
				<p class="aio-bp-list-intro__text"><?php \esc_html_e( 'Plans are grouped by lineage (one site strategy, multiple versions). Open a version to review steps, approvals, and execution.', 'aio-page-builder' ); ?></p>
			</div>
			<?php if ( count( $grouped ) === 0 && count( $orphans ) === 0 ) : ?>
				<div class="aio-bp-empty-card" role="status">
					<p class="aio-bp-empty-card__title"><?php \esc_html_e( 'No build plans yet', 'aio-page-builder' ); ?></p>
					<p class="aio-bp-empty-card__hint">
					<?php if ( Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) ) : ?>
						<?php
						$ai_runs_url = Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_runs' );
						?>
						<a class="button button-primary" href="<?php echo \esc_url( $ai_runs_url ); ?>"><?php \esc_html_e( 'Open AI Runs', 'aio-page-builder' ); ?></a>
						<span class="aio-bp-empty-card__hint-rest"><?php \esc_html_e( 'Create a plan from a completed run, or finish onboarding.', 'aio-page-builder' ); ?></span>
					<?php else : ?>
						<?php \esc_html_e( 'Ask an administrator to create a plan from a completed AI run.', 'aio-page-builder' ); ?>
					<?php endif; ?>
					</p>
				</div>
			<?php else : ?>
				<div class="aio-bp-lineage-stack">
				<?php
				$lineage_idx = 0;
				foreach ( $grouped as $group ) :
					if ( ! is_array( $group ) ) {
						continue;
					}
					$gtitle      = (string) ( $group['display_title'] ?? '' );
					$vers        = isset( $group['versions'] ) && is_array( $group['versions'] ) ? $group['versions'] : array();
					$head        = $gtitle !== '' ? $gtitle : __( 'Plan lineage', 'aio-page-builder' );
					$lineage_lid = 'aio-bp-lineage-' . (string) $lineage_idx;
					++$lineage_idx;
					?>
					<section class="aio-bp-lineage-card" aria-labelledby="<?php echo \esc_attr( $lineage_lid ); ?>">
						<header class="aio-bp-lineage-card__head">
							<h2 class="aio-bp-lineage-card__title" id="<?php echo \esc_attr( $lineage_lid ); ?>" title="<?php echo \esc_attr( $head ); ?>"><?php echo \esc_html( $this->truncate_list_label( $head, 96 ) ); ?></h2>
							<span class="aio-bp-lineage-card__count"><?php echo \esc_html( \sprintf( /* translators: %d: version count */ \_n( '%d version', '%d versions', \count( $vers ), 'aio-page-builder' ), \count( $vers ) ) ); ?></span>
						</header>
						<ul class="aio-bp-version-list">
							<?php foreach ( $vers as $row ) : ?>
								<?php
								if ( ! is_array( $row ) ) {
									continue;
								}
								$pid     = (string) ( $row['plan_id'] ?? '' );
								$post_id = (int) ( $row['post_id'] ?? 0 );
								$open    = $this->open_plan_workspace_tab_url( $pid, $post_id );
								$vlab    = (string) ( $row['version_label'] ?? '' );
								$purp    = (string) ( $row['version_purpose'] ?? '' );
								$st      = (string) ( $row['plan_status'] ?? '' );
								$run     = (string) ( $row['ai_run_ref'] ?? '' );
								?>
								<li class="aio-bp-version-card">
									<div class="aio-bp-version-card__lead">
										<span class="aio-bp-pill aio-bp-pill--version"><?php echo \esc_html( $vlab !== '' ? $vlab : __( 'Version', 'aio-page-builder' ) ); ?></span>
										<div class="aio-bp-kv">
											<span class="aio-bp-kv__k"><?php \esc_html_e( 'Plan ID', 'aio-page-builder' ); ?></span>
											<code class="aio-bp-mono" title="<?php echo \esc_attr( $pid !== '' ? $pid : ( $post_id > 0 ? 'post:' . (string) $post_id : '' ) ); ?>"><?php echo \esc_html( $pid !== '' ? $this->truncate_list_label( $pid, 44 ) : ( $post_id > 0 ? '#' . (string) $post_id : '—' ) ); ?></code>
										</div>
									</div>
									<div class="aio-bp-version-card__body">
										<div class="aio-bp-kv aio-bp-kv--block">
											<span class="aio-bp-kv__k"><?php \esc_html_e( 'Purpose', 'aio-page-builder' ); ?></span>
											<span class="aio-bp-kv__v" title="<?php echo \esc_attr( $purp ); ?>"><?php echo \esc_html( $purp !== '' ? $this->truncate_list_label( $purp, 120 ) : '—' ); ?></span>
										</div>
										<div class="aio-bp-kv aio-bp-kv--inline">
											<span class="aio-bp-kv__k"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></span>
											<?php if ( $st !== '' ) : ?>
												<span class="<?php echo \esc_attr( $this->status_pill_class( $st ) ); ?>"><?php echo \esc_html( $st ); ?></span>
											<?php else : ?>
												<span class="aio-bp-muted"><?php \esc_html_e( 'Not set', 'aio-page-builder' ); ?></span>
											<?php endif; ?>
										</div>
										<div class="aio-bp-kv aio-bp-kv--block">
											<span class="aio-bp-kv__k"><?php \esc_html_e( 'Source run', 'aio-page-builder' ); ?></span>
											<code class="aio-bp-mono aio-bp-mono--sm" title="<?php echo \esc_attr( $run ); ?>"><?php echo \esc_html( $run !== '' ? $this->truncate_list_label( $run, 36 ) : '—' ); ?></code>
										</div>
									</div>
									<div class="aio-bp-version-card__action">
										<?php if ( $open !== '' ) : ?>
											<a class="button button-primary" href="<?php echo \esc_url( $open ); ?>"><?php \esc_html_e( 'Open plan', 'aio-page-builder' ); ?></a>
										<?php else : ?>
											<span class="aio-bp-incomplete" role="note"><?php \esc_html_e( 'Plan ID missing — re-save or repair from AI run.', 'aio-page-builder' ); ?></span>
										<?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endforeach; ?>
				</div>
				<?php if ( count( $orphans ) > 0 ) : ?>
					<section class="aio-bp-orphans-section" aria-labelledby="aio-bp-orphans-heading">
						<header class="aio-bp-orphans-section__head">
							<h2 class="aio-bp-orphans-section__title" id="aio-bp-orphans-heading"><?php \esc_html_e( 'Plans without lineage', 'aio-page-builder' ); ?></h2>
							<p class="aio-bp-orphans-section__hint"><?php \esc_html_e( 'Older or imported posts may lack lineage until re-saved.', 'aio-page-builder' ); ?></p>
						</header>
						<ul class="aio-bp-orphan-list">
							<?php foreach ( $orphans as $plan ) : ?>
								<?php
								$pid     = (string) ( $plan['plan_id'] ?? $plan['internal_key'] ?? '' );
								$post_id = (int) ( $plan['post_id'] ?? 0 );
								$open    = $this->open_plan_workspace_tab_url( $pid, $post_id );
								$title   = (string) ( $plan['plan_title'] ?? $plan['post_title'] ?? $pid );
								$st      = (string) ( $plan['status'] ?? '' );
								$run     = (string) ( $plan['ai_run_ref'] ?? '' );
								?>
								<li class="aio-bp-orphan-card">
									<div class="aio-bp-orphan-card__summary">
										<p class="aio-bp-orphan-card__title" title="<?php echo \esc_attr( $title ); ?>"><?php echo \esc_html( $this->truncate_list_label( $title, 160 ) ); ?></p>
										<div class="aio-bp-orphan-card__chips">
											<?php if ( $st !== '' ) : ?>
												<span class="<?php echo \esc_attr( $this->status_pill_class( $st ) ); ?>"><?php echo \esc_html( $st ); ?></span>
											<?php endif; ?>
										</div>
									</div>
									<dl class="aio-bp-orphan-card__dl">
										<div class="aio-bp-orphan-card__dl-row">
											<dt><?php \esc_html_e( 'Plan ID', 'aio-page-builder' ); ?></dt>
											<dd><code class="aio-bp-mono" title="<?php echo \esc_attr( $pid ); ?>"><?php echo \esc_html( $pid !== '' ? $this->truncate_list_label( $pid, 48 ) : '—' ); ?></code></dd>
										</div>
										<div class="aio-bp-orphan-card__dl-row">
											<dt><?php \esc_html_e( 'Source run', 'aio-page-builder' ); ?></dt>
											<dd><code class="aio-bp-mono aio-bp-mono--sm" title="<?php echo \esc_attr( $run ); ?>"><?php echo \esc_html( $run !== '' ? $this->truncate_list_label( $run, 40 ) : '—' ); ?></code></dd>
										</div>
									</dl>
									<div class="aio-bp-orphan-card__action">
										<?php if ( $open !== '' ) : ?>
											<a class="button button-primary" href="<?php echo \esc_url( $open ); ?>"><?php \esc_html_e( 'Open plan', 'aio-page-builder' ); ?></a>
										<?php else : ?>
											<span class="aio-bp-incomplete"><?php \esc_html_e( 'No plan ID', 'aio-page-builder' ); ?></span>
										<?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Truncates long labels for card layout; full string should be passed via title= on the element.
	 *
	 * @param string $text Raw text.
	 * @param int    $max  Max characters (approximate for multibyte).
	 * @return string
	 */
	/**
	 * Hub URL to open a plan workspace (stable plan_id preferred; post id when definition omits plan_id).
	 *
	 * @param string $plan_id From plan definition internal key when present.
	 * @param int    $post_id Build plan post ID when $plan_id is empty.
	 * @return string Empty when no usable identifier.
	 */
	private function open_plan_workspace_tab_url( string $plan_id, int $post_id = 0 ): string {
		$extra = array();
		if ( $plan_id !== '' ) {
			$extra['plan_id'] = $plan_id;
		} elseif ( $post_id > 0 ) {
			$extra['id'] = (string) $post_id;
		}
		if ( $extra === array() ) {
			return '';
		}
		return Admin_Screen_Hub::tab_url( self::SLUG, 'build_plans', $extra );
	}

	private function truncate_list_label( string $text, int $max ): string {
		$text = trim( $text );
		if ( $text === '' || $max < 4 ) {
			return $text;
		}
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $text ) <= $max ) {
				return $text;
			}
			return mb_substr( $text, 0, $max - 1 ) . '…';
		}
		if ( strlen( $text ) <= $max ) {
			return $text;
		}
		return substr( $text, 0, $max - 1 ) . '…';
	}

	/**
	 * Maps plan status text to a pill modifier class (secrets-free, display only).
	 *
	 * @param string $status Status label from plan definition.
	 * @return string CSS class suffix chain (includes base aio-bp-pill).
	 */
	private function status_pill_class( string $status ): string {
		$s = strtolower( str_replace( array( ' ', "\t" ), '_', $status ) );
		if ( str_contains( $s, 'pending' ) ) {
			return 'aio-bp-pill aio-bp-pill--pending';
		}
		if ( str_contains( $s, 'approv' ) ) {
			return 'aio-bp-pill aio-bp-pill--ok';
		}
		if ( str_contains( $s, 'reject' ) || str_contains( $s, 'fail' ) ) {
			return 'aio-bp-pill aio-bp-pill--bad';
		}
		if ( str_contains( $s, 'complet' ) || str_contains( $s, 'progress' ) ) {
			return 'aio-bp-pill aio-bp-pill--info';
		}
		return 'aio-bp-pill aio-bp-pill--neutral';
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
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Small admin list; fixed page size; OR on lineage meta only.
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
				'post_id'    => (int) $post->ID,
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
