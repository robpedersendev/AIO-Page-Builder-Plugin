<?php
/**
 * Build Plan workspace (detail) screen: three-zone shell, context rail, stepper (spec §31, build-plan-admin-ia-contract.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\BuildPlan;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Stepper_Builder;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Detail_Panel_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Message_Component;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders Build Plan detail with three-zone layout. Consumes UI state from Build_Plan_UI_State_Builder.
 * Row/detail and step-specific tables are placeholders in this prompt.
 */
final class Build_Plan_Workspace_Screen {

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_capability(): string {
		return Capabilities::VIEW_BUILD_PLANS;
	}

	/**
	 * Renders workspace for the given plan_id. Exits with not-found message if plan missing.
	 *
	 * @param string $plan_id Plan ID from request.
	 * @return void
	 */
	public function render( string $plan_id ): void {
		$state = $this->get_state( $plan_id );
		if ( $state === null ) {
			$this->render_not_found( $plan_id );
			return;
		}
		$current_step_index = $this->get_active_step_index( $state );
		$this->render_shell( $state, $current_step_index );
	}

	private function get_state( string $plan_id ): ?array {
		if ( ! $this->container || ! $this->container->has( 'build_plan_ui_state_builder' ) ) {
			return null;
		}
		$builder = $this->container->get( 'build_plan_ui_state_builder' );
		return $builder->build( $plan_id );
	}

	private function get_active_step_index( array $state ): int {
		$steps = $state['stepper_steps'] ?? array();
		if ( empty( $steps ) ) {
			return 0;
		}
		$step_param = isset( $_GET['step'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['step'] ) ) : '';
		if ( $step_param === '' ) {
			return 0;
		}
		if ( is_numeric( $step_param ) ) {
			$idx = (int) $step_param;
			return $idx >= 0 && $idx < count( $steps ) ? $idx : 0;
		}
		foreach ( $steps as $i => $s ) {
			if ( ( $s['step_type'] ?? '' ) === $step_param ) {
				return $i;
			}
		}
		return 0;
	}

	private function render_not_found( string $plan_id ): void {
		$list_url = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG );
		?>
		<div class="wrap aio-page-builder-screen aio-build-plan-workspace">
			<h1><?php \esc_html_e( 'Build Plan', 'aio-page-builder' ); ?></h1>
			<p class="aio-admin-notice"><?php \esc_html_e( 'Plan not found.', 'aio-page-builder' ); ?></p>
			<p><a href="<?php echo \esc_url( $list_url ); ?>"><?php \esc_html_e( 'Back to Build Plans', 'aio-page-builder' ); ?></a></p>
		</div>
		<?php
	}

	private function render_shell( array $state, int $active_step_index ): void {
		$plan_id   = (string) ( $state['plan_id'] ?? '' );
		$rail      = $state['context_rail'] ?? array();
		$steps     = $state['stepper_steps'] ?? array();
		$definition = $state['plan_definition'] ?? array();
		$current_step = $steps[ $active_step_index ] ?? null;
		$base_url  = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) );
		$can_export = \current_user_can( Capabilities::EXPORT_DATA ) || \current_user_can( Capabilities::DOWNLOAD_ARTIFACTS );
		$can_view_artifacts = \current_user_can( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS );
		?>
		<div class="wrap aio-page-builder-screen aio-build-plan-workspace aio-build-plan-three-zone">
			<div class="aio-build-plan-context-rail">
				<?php $this->render_context_rail( $rail, $plan_id, $base_url, $can_export, $can_view_artifacts ); ?>
			</div>
			<div class="aio-build-plan-main">
				<div class="aio-build-plan-stepper">
					<?php $this->render_stepper( $steps, $active_step_index, $base_url ); ?>
				</div>
				<div class="aio-build-plan-workspace-content">
					<?php $this->render_step_workspace( $state, $current_step, $active_step_index, $definition, $base_url ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_context_rail( array $rail, string $plan_id, string $base_url, bool $can_export, bool $can_view_artifacts ): void {
		$list_url = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG );
		?>
		<div class="aio-context-rail-inner">
			<h2 class="aio-context-rail-title"><?php echo \esc_html( (string) ( $rail['plan_title'] ?? __( 'Build Plan', 'aio-page-builder' ) ) ); ?></h2>
			<dl class="aio-context-rail-meta">
				<dt><?php \esc_html_e( 'Plan ID', 'aio-page-builder' ); ?></dt>
				<dd><code><?php echo \esc_html( (string) ( $rail['plan_id'] ?? '' ) ); ?></code></dd>
				<dt><?php \esc_html_e( 'Source AI run', 'aio-page-builder' ); ?></dt>
				<dd><code><?php echo \esc_html( (string) ( $rail['ai_run_ref'] ?? '' ) ); ?></code></dd>
				<dt><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></dt>
				<dd><span class="aio-status-badge aio-status-<?php echo \esc_attr( \sanitize_html_class( (string) ( $rail['plan_status'] ?? '' ) ) ); ?>"><?php echo \esc_html( (string) ( $rail['plan_status'] ?? '' ) ); ?></span></dd>
				<dt><?php \esc_html_e( 'Site purpose', 'aio-page-builder' ); ?></dt>
				<dd><?php echo \esc_html( (string) ( $rail['site_purpose_summary'] ?? '' ) ); ?></dd>
				<dt><?php \esc_html_e( 'Site flow', 'aio-page-builder' ); ?></dt>
				<dd><?php echo \esc_html( (string) ( $rail['site_flow_summary'] ?? '' ) ); ?></dd>
			</dl>
			<?php
			$warnings = $rail['warnings_summary'] ?? array();
			if ( ! empty( $warnings ) ) :
				?>
				<div class="aio-context-rail-warnings">
					<h3><?php \esc_html_e( 'Warnings', 'aio-page-builder' ); ?></h3>
					<ul>
						<?php foreach ( $warnings as $w ) : ?>
							<li><?php echo \esc_html( is_array( $w ) ? (string) ( $w['message'] ?? \wp_json_encode( $w ) ) : (string) $w ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<div class="aio-context-rail-actions">
				<p><a href="<?php echo \esc_url( $list_url ); ?>" class="button"><?php \esc_html_e( 'Save and exit', 'aio-page-builder' ); ?></a></p>
				<?php if ( $can_export ) : ?>
					<p><span class="button button-secondary" aria-disabled="true"><?php \esc_html_e( 'Export plan', 'aio-page-builder' ); ?></span> <span class="description"><?php \esc_html_e( '(Coming soon)', 'aio-page-builder' ); ?></span></p>
				<?php endif; ?>
				<?php if ( $can_view_artifacts && (string) ( $rail['ai_run_ref'] ?? '' ) !== '' ) : ?>
					<p><a href="<?php echo \esc_url( \admin_url( 'admin.php?page=aio-page-builder-ai-runs&run_id=' . \rawurlencode( (string) $rail['ai_run_ref'] ) ) ); ?>" class="button button-secondary"><?php \esc_html_e( 'View source artifacts', 'aio-page-builder' ); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private function render_stepper( array $steps, int $active_index, string $base_url ): void {
		?>
		<nav class="aio-stepper-nav" aria-label="<?php \esc_attr_e( 'Plan steps', 'aio-page-builder' ); ?>">
			<ol class="aio-stepper-list">
				<?php foreach ( $steps as $idx => $step ) : ?>
					<?php
					$step_type   = (string) ( $step['step_type'] ?? '' );
					$title       = (string) ( $step['title'] ?? $step_type );
					$badge       = (string) ( $step['status_badge'] ?? '' );
					$unresolved  = (int) ( $step['unresolved_count'] ?? 0 );
					$is_blocked  = ! empty( $step['is_blocked'] );
					$is_active   = $idx === $active_index;
					$step_url    = $base_url . '&step=' . $idx;
					$can_go      = $idx <= $active_index || ! $is_blocked;
					?>
					<li class="aio-stepper-item <?php echo $is_active ? 'aio-stepper-item-active' : ''; ?> <?php echo $is_blocked ? 'aio-stepper-item-blocked' : ''; ?>">
						<?php if ( $can_go ) : ?>
							<a href="<?php echo \esc_url( $step_url ); ?>" class="aio-stepper-link">
								<span class="aio-stepper-number"><?php echo \esc_html( (string) ( $step['step_number'] ?? ( $idx + 1 ) ) ); ?></span>
								<span class="aio-stepper-title"><?php echo \esc_html( $title ); ?></span>
								<span class="aio-stepper-badge aio-badge-<?php echo \esc_attr( $badge ); ?>"><?php echo \esc_html( $badge ); ?></span>
								<?php if ( $unresolved > 0 ) : ?>
									<span class="aio-stepper-unresolved"><?php echo \esc_html( (string) $unresolved ); ?></span>
								<?php endif; ?>
							</a>
						<?php else : ?>
							<span class="aio-stepper-link aio-stepper-link-disabled">
								<span class="aio-stepper-number"><?php echo \esc_html( (string) ( $step['step_number'] ?? ( $idx + 1 ) ) ); ?></span>
								<span class="aio-stepper-title"><?php echo \esc_html( $title ); ?></span>
								<span class="aio-stepper-badge aio-badge-<?php echo \esc_attr( $badge ); ?>"><?php echo \esc_html( $badge ); ?></span>
								<?php if ( $unresolved > 0 ) : ?>
									<span class="aio-stepper-unresolved"><?php echo \esc_html( (string) $unresolved ); ?></span>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
	}

	private function render_step_workspace( array $state, ?array $current_step, int $active_step_index, array $definition, string $base_url ): void {
		if ( $current_step === null ) {
			echo '<p class="aio-empty-state">' . \esc_html__( 'No step selected.', 'aio-page-builder' ) . '</p>';
			return;
		}
		$step_type = (string) ( $current_step['step_type'] ?? '' );
		$is_blocked = ! empty( $current_step['is_blocked'] );
		$unresolved = (int) ( $current_step['unresolved_count'] ?? 0 );

		if ( $is_blocked ) {
			echo '<div class="aio-empty-state aio-empty-state-blocked"><p>' . \esc_html__( 'This step is blocked until earlier required actions are completed.', 'aio-page-builder' ) . '</p></div>';
			return;
		}

		switch ( $step_type ) {
			case Build_Plan_Schema::STEP_TYPE_OVERVIEW:
				$this->render_overview_shell( $definition );
				break;
			case Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW:
				$this->render_hierarchy_shell( $definition );
				break;
			case Build_Plan_Schema::STEP_TYPE_CONFIRMATION:
				$this->render_confirmation_shell( $definition );
				break;
			case Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES:
			case Build_Plan_Schema::STEP_TYPE_NEW_PAGES:
			case Build_Plan_Schema::STEP_TYPE_NAVIGATION:
			case Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS:
			case Build_Plan_Schema::STEP_TYPE_SEO:
				$this->render_actionable_step_workspace( $state, $current_step, $active_step_index, $definition );
				break;
			default:
				echo '<div class="aio-empty-state"><p>' . \esc_html__( 'No recommendations were generated for this step.', 'aio-page-builder' ) . '</p></div>';
		}
	}

	/**
	 * Renders table + detail + bulk bar for an actionable step using shared components.
	 *
	 * @param array<string, mixed> $state Full UI state (plan_id, plan_definition, etc.).
	 * @param array<string, mixed> $current_step Current stepper step data.
	 * @param int                  $active_step_index Step index.
	 * @param array<string, mixed> $definition Plan definition.
	 */
	private function render_actionable_step_workspace( array $state, array $current_step, int $active_step_index, array $definition ): void {
		$plan_id = (string) ( $state['plan_id'] ?? $definition[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' );
		$unresolved = (int) ( $current_step['unresolved_count'] ?? 0 );
		if ( $unresolved === 0 ) {
			echo '<div class="aio-empty-state"><p>' . \esc_html__( 'All recommendations in this step have already been resolved.', 'aio-page-builder' ) . '</p></div>';
			return;
		}
		if ( ! $this->container || ! $this->container->has( 'build_plan_ui_state_builder' ) ) {
			echo '<div class="aio-empty-state"><p>' . \esc_html__( 'Item list is not available.', 'aio-page-builder' ) . '</p></div>';
			return;
		}
		$detail_item_id = isset( $_GET['detail'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['detail'] ) ) : null;
		if ( $detail_item_id === '' ) {
			$detail_item_id = null;
		}
		$selected_ids = array();
		if ( ! empty( $_GET['selected'] ) && is_array( $_GET['selected'] ) ) {
			$selected_ids = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_GET['selected'] ) );
			$selected_ids = array_values( array_filter( $selected_ids ) );
		}
		$capabilities = array(
			'can_approve'         => \current_user_can( Capabilities::APPROVE_BUILD_PLANS ),
			'can_execute'         => \current_user_can( Capabilities::EXECUTE_BUILD_PLANS ),
			'can_view_artifacts'  => \current_user_can( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS ),
		);
		$builder = $this->container->get( 'build_plan_ui_state_builder' );
		$workspace = $builder->build_step_workspace( $plan_id, $active_step_index, $capabilities, $detail_item_id, $selected_ids );

		$step_messages = $workspace['step_messages'] ?? array();
		$list_payload  = array(
			Step_Item_List_Component::KEY_STEP_LIST_ROWS => $workspace['step_list_rows'] ?? array(),
			Step_Item_List_Component::KEY_COLUMN_ORDER   => $workspace['column_order'] ?? array(),
		);
		$bulk_payload  = array( Bulk_Action_Bar_Component::KEY_BULK_ACTION_STATES => $workspace['bulk_action_states'] ?? array() );
		$detail_payload = $workspace['detail_panel'] ?? array();

		$message_component = new Step_Message_Component();
		$bulk_component    = new Bulk_Action_Bar_Component();
		$list_component    = new Step_Item_List_Component();
		$detail_component  = new Detail_Panel_Component();
		?>
		<div class="aio-step-workspace-actionable">
			<?php $message_component->render_list( $step_messages ); ?>
			<?php $bulk_component->render( $bulk_payload ); ?>
			<div class="aio-step-workspace-list-detail">
				<div class="aio-step-workspace-list">
					<?php $list_component->render( $list_payload, $detail_item_id ); ?>
				</div>
				<div class="aio-step-workspace-detail">
					<?php $detail_component->render( $detail_payload ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_overview_shell( array $definition ): void {
		$summary  = (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_SUMMARY ] ?? '' );
		$steps    = $definition[ Build_Plan_Schema::KEY_STEPS ] ?? array();
		$overview = null;
		foreach ( is_array( $steps ) ? $steps : array() as $step ) {
			if ( is_array( $step ) && ( $step['step_type'] ?? '' ) === Build_Plan_Schema::STEP_TYPE_OVERVIEW ) {
				$items = $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ?? array();
				if ( ! empty( $items ) && is_array( $items[0] ?? null ) ) {
					$overview = $items[0];
					break;
				}
			}
		}
		$payload = is_array( $overview ) ? ( $overview['payload'] ?? array() ) : array();
		$planning_mode = (string) ( $payload['planning_mode'] ?? 'mixed' );
		$confidence    = (string) ( $payload['overall_confidence'] ?? 'medium' );
		?>
		<div class="aio-step-overview">
			<p class="aio-plan-summary"><?php echo \esc_html( $summary ?: __( 'No summary.', 'aio-page-builder' ) ); ?></p>
			<p><strong><?php \esc_html_e( 'Planning mode:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( $planning_mode ); ?> | <strong><?php \esc_html_e( 'Confidence:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( $confidence ); ?></p>
			<p class="aio-overview-actions"><a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ) ) . '&step=1' ) ); ?>" class="button button-primary"><?php \esc_html_e( 'Start review', 'aio-page-builder' ); ?></a></p>
		</div>
		<?php
	}

	private function render_hierarchy_shell( array $definition ): void {
		$site_flow = (string) ( $definition[ Build_Plan_Schema::KEY_SITE_FLOW_SUMMARY ] ?? '' );
		?>
		<div class="aio-step-hierarchy">
			<p><?php echo \esc_html( $site_flow ?: __( 'No hierarchy or flow summary for this plan.', 'aio-page-builder' ) ); ?></p>
		</div>
		<?php
	}

	private function render_confirmation_shell( array $definition ): void {
		$status = (string) ( $definition[ Build_Plan_Schema::KEY_STATUS ] ?? '' );
		$completed = $status === Build_Plan_Schema::STATUS_COMPLETED;
		if ( $completed ) {
			?>
			<div class="aio-step-confirmation aio-completion-state">
				<p class="aio-completion-banner"><?php \esc_html_e( 'Plan completed.', 'aio-page-builder' ); ?></p>
				<p><?php \esc_html_e( 'Counts and links to logs/export will be added in a later prompt.', 'aio-page-builder' ); ?></p>
			</div>
			<?php
		} else {
			?>
			<div class="aio-step-confirmation">
				<p><?php \esc_html_e( 'Review approved and denied items; confirm or start execution. (Placeholder — actions in a later prompt.)', 'aio-page-builder' ); ?></p>
			</div>
			<?php
		}
	}
}
