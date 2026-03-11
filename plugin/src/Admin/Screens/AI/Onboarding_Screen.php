<?php
/**
 * Onboarding admin screen: step shell, draft save/load, prefill, readiness (onboarding-state-machine.md, spec §23, §53.2).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Prefill_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Statuses;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Keys;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_UI_State_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders onboarding flow: steps, draft persistence, prefill, blocked state. No provider API or AI submission.
 */
final class Onboarding_Screen {

	public const SLUG = 'aio-page-builder-onboarding';

	private const CAPABILITY = 'manage_options';

	private const NONCE_ACTION = 'aio_onboarding_save';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Onboarding & Profile', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return self::CAPABILITY;
	}

	/**
	 * Renders the screen. Handles POST for save_draft and step navigation; then builds state and outputs shell.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access this page.', 'aio-page-builder' ) );
		}

		$redirect = $this->handle_post();
		if ( $redirect !== null ) {
			\wp_safe_redirect( $redirect );
			exit;
		}

		$state = $this->get_ui_state();
		$this->render_shell( $state );
	}

	/**
	 * Handles POST actions (save_draft, advance_step, go_back). Returns redirect URL or null to continue render.
	 *
	 * @return string|null Redirect URL or null.
	 */
	private function handle_post(): ?string {
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || ! isset( $_POST[ self::NONCE_ACTION ] ) ) {
			return null;
		}
		if ( ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( (string) $_POST[ self::NONCE_ACTION ] ) ), self::NONCE_ACTION ) ) {
			return null;
		}
		$action = isset( $_POST['aio_onboarding_action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_onboarding_action'] ) ) : '';
		if ( $action === '' || $this->container === null ) {
			return null;
		}

		$draft_service = $this->container->get( 'onboarding_draft_service' );
		$prefill_service = $this->container->get( 'onboarding_prefill_service' );
		$draft = $draft_service->get_draft();

		if ( $action === 'save_draft' ) {
			$draft['overall_status'] = Onboarding_Statuses::DRAFT_SAVED;
			$draft_service->save_draft( $draft );
			$url = \add_query_arg( array( 'page' => self::SLUG, 'saved' => '1' ), \admin_url( 'admin.php' ) );
			return $url;
		}

		if ( $action === 'advance_step' ) {
			$ordered = Onboarding_Step_Keys::ordered();
			$idx = array_search( $draft['current_step_key'], $ordered, true );
			if ( $idx !== false && $idx < count( $ordered ) - 1 ) {
				$next = $ordered[ $idx + 1 ];
				$draft['step_statuses'][ $draft['current_step_key'] ] = Onboarding_Statuses::STEP_COMPLETED;
				$draft['current_step_key'] = $next;
				$draft['step_statuses'][ $next ] = Onboarding_Statuses::STEP_IN_PROGRESS;
				$draft['overall_status'] = Onboarding_Statuses::IN_PROGRESS;
				if ( $next === Onboarding_Step_Keys::REVIEW && ! $prefill_service->is_provider_ready() ) {
					$draft['overall_status'] = Onboarding_Statuses::BLOCKED;
				}
				$draft_service->save_draft( $draft );
			}
			$url = \add_query_arg( array( 'page' => self::SLUG ), \admin_url( 'admin.php' ) );
			return $url;
		}

		if ( $action === 'go_back' ) {
			$ordered = Onboarding_Step_Keys::ordered();
			$idx = array_search( $draft['current_step_key'], $ordered, true );
			if ( $idx !== false && $idx > 0 ) {
				$prev = $ordered[ $idx - 1 ];
				$draft['current_step_key'] = $prev;
				$draft['step_statuses'][ $prev ] = Onboarding_Statuses::STEP_IN_PROGRESS;
				$draft['overall_status'] = Onboarding_Statuses::IN_PROGRESS;
				$draft_service->save_draft( $draft );
			}
			$url = \add_query_arg( array( 'page' => self::SLUG ), \admin_url( 'admin.php' ) );
			return $url;
		}

		return null;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_ui_state(): array {
		if ( $this->container && $this->container->has( 'onboarding_ui_state_builder' ) ) {
			$builder = $this->container->get( 'onboarding_ui_state_builder' );
			return $builder->build_for_screen();
		}
		return $this->minimal_state();
	}

	/**
	 * Minimal UI state when container or builder is unavailable (e.g. tests). No prefill.
	 *
	 * @return array<string, mixed>
	 */
	private function minimal_state(): array {
		$draft_svc = new Onboarding_Draft_Service( new \AIOPageBuilder\Infrastructure\Settings\Settings_Service() );
		$draft = $draft_svc->default_draft();
		$labels = Onboarding_UI_State_Builder::step_labels();
		$steps = array();
		foreach ( Onboarding_Step_Keys::ordered() as $key ) {
			$steps[] = array(
				'key'        => $key,
				'label'      => $labels[ $key ] ?? $key,
				'status'     => $draft['step_statuses'][ $key ] ?? Onboarding_Statuses::STEP_NOT_STARTED,
				'is_current' => $key === $draft['current_step_key'],
			);
		}
		return array(
			'current_step_key'  => $draft['current_step_key'],
			'steps'             => $steps,
			'overall_status'    => $draft['overall_status'],
			'is_blocked'        => false,
			'blockers'          => array(),
			'prefill'           => array( 'profile' => array(), 'current_site_url' => '', 'crawl_run_ids' => array(), 'latest_crawl_run_id' => null, 'provider_refs' => array() ),
			'draft'             => $draft,
			'nonce'             => \wp_create_nonce( self::NONCE_ACTION ),
			'nonce_action'      => self::NONCE_ACTION,
			'can_save_draft'    => true,
			'resume_message'    => '',
			'is_provider_ready' => false,
		);
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_shell( array $state ): void {
		$current_step_key = $state['current_step_key'] ?? Onboarding_Step_Keys::WELCOME;
		$steps = $state['steps'] ?? array();
		$is_blocked = ! empty( $state['is_blocked'] );
		$blockers = $state['blockers'] ?? array();
		$resume_message = $state['resume_message'] ?? '';
		$nonce = $state['nonce'] ?? '';
		$nonce_action = $state['nonce_action'] ?? self::NONCE_ACTION;
		$saved = isset( $_GET['saved'] ) && $_GET['saved'] === '1';
		?>
		<div class="wrap aio-page-builder-screen aio-onboarding" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible" role="status">
					<p><?php \esc_html_e( 'Draft saved. You can return later to continue.', 'aio-page-builder' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $resume_message !== '' ) : ?>
				<div class="notice notice-info" role="status">
					<p><?php echo \esc_html( $resume_message ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $is_blocked && count( $blockers ) > 0 ) : ?>
				<div class="notice notice-warning" role="alert" aria-live="polite">
					<p><strong><?php \esc_html_e( 'Cannot proceed until:', 'aio-page-builder' ); ?></strong></p>
					<ul>
						<?php foreach ( $blockers as $blocker ) : ?>
							<li><?php echo \esc_html( $blocker ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<nav class="aio-onboarding-stepper" aria-label="<?php \esc_attr_e( 'Onboarding steps', 'aio-page-builder' ); ?>">
				<ol class="aio-onboarding-steps">
					<?php foreach ( $steps as $step ) : ?>
						<li class="aio-onboarding-step aio-step-<?php echo \esc_attr( $step['status'] ); ?> <?php echo ! empty( $step['is_current'] ) ? ' aio-step-current' : ''; ?>">
							<span class="aio-step-label"><?php echo \esc_html( $step['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>

			<section class="aio-onboarding-content" aria-labelledby="aio-onboarding-step-heading">
				<h2 id="aio-onboarding-step-heading" class="screen-reader-text"><?php \esc_html_e( 'Current step', 'aio-page-builder' ); ?></h2>
				<?php $this->render_step_placeholder( $current_step_key, $state ); ?>
			</section>

			<form method="post" action="" class="aio-onboarding-actions">
				<?php \wp_nonce_field( $nonce_action, self::NONCE_ACTION ); ?>
				<p class="submit">
					<?php if ( $current_step_key !== Onboarding_Step_Keys::WELCOME ) : ?>
						<button type="submit" name="aio_onboarding_action" value="go_back" class="button"><?php \esc_html_e( 'Back', 'aio-page-builder' ); ?></button>
					<?php endif; ?>
					<?php if ( $current_step_key !== Onboarding_Step_Keys::SUBMISSION ) : ?>
						<button type="submit" name="aio_onboarding_action" value="save_draft" class="button"><?php \esc_html_e( 'Save draft', 'aio-page-builder' ); ?></button>
						<?php if ( $current_step_key !== Onboarding_Step_Keys::REVIEW || empty( $state['is_blocked'] ) ) : ?>
							<button type="submit" name="aio_onboarding_action" value="advance_step" class="button button-primary"><?php \esc_html_e( 'Next', 'aio-page-builder' ); ?></button>
						<?php endif; ?>
					<?php else : ?>
						<p class="aio-onboarding-ready"><?php \esc_html_e( 'Ready for AI planning. Submission will be available in a future update.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders placeholder content for the current step. Full step forms are out of scope for this prompt.
	 *
	 * @param string $current_step_key
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_step_placeholder( string $current_step_key, array $state ): void {
		$labels = Onboarding_UI_State_Builder::step_labels();
		$label = $labels[ $current_step_key ] ?? $current_step_key;
		$prefill = $state['prefill'] ?? array();
		$provider_refs = $prefill['provider_refs'] ?? array();
		$is_provider_ready = ! empty( $state['is_provider_ready'] );
		?>
		<div class="aio-onboarding-step-panel" data-step="<?php echo \esc_attr( $current_step_key ); ?>">
			<h3><?php echo \esc_html( $label ); ?></h3>
			<?php if ( $current_step_key === Onboarding_Step_Keys::WELCOME ) : ?>
				<p><?php \esc_html_e( 'Welcome to AIO Page Builder. This flow collects your business and brand context, existing site information, and AI provider setup so you can request an AI-generated plan. You can save your progress and return later.', 'aio-page-builder' ); ?></p>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::PROVIDER_SETUP ) : ?>
				<p><?php \esc_html_e( 'Configure at least one AI provider (API key) to use AI planning. Credentials are stored securely and never shown here.', 'aio-page-builder' ); ?></p>
				<p><?php \esc_html_e( 'Provider setup UI will be added in a future update. Current readiness:', 'aio-page-builder' ); ?> <?php echo $is_provider_ready ? \esc_html__( 'At least one provider configured.', 'aio-page-builder' ) : \esc_html__( 'No provider configured.', 'aio-page-builder' ); ?></p>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::REVIEW ) : ?>
				<p><?php \esc_html_e( 'Review your inputs before proceeding. Profile and provider readiness are checked here.', 'aio-page-builder' ); ?></p>
				<?php if ( count( $provider_refs ) > 0 ) : ?>
					<ul aria-label="<?php \esc_attr_e( 'Provider status', 'aio-page-builder' ); ?>">
						<?php foreach ( $provider_refs as $ref ) : ?>
							<li><?php echo \esc_html( ( $ref['provider_id'] ?? '' ) . ': ' . ( $ref['credential_state'] ?? 'absent' ) ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			<?php else : ?>
				<p><?php echo \esc_html( sprintf( __( 'Step “%s” — form fields will be added in a future update. Use Next to advance or Save draft to leave.', 'aio-page-builder' ), $label ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
