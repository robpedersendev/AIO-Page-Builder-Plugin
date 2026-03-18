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
use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Keys;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_UI_State_Builder;
use AIOPageBuilder\Domain\AI\Onboarding\Planning_Request_Result;
use AIOPageBuilder\Domain\Profile\Template_Preference_Profile;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders onboarding flow: steps, draft persistence, prefill, blocked state. No provider API or AI submission.
 */
final class Onboarding_Screen {

	public const SLUG = 'aio-page-builder-onboarding';

	/** Gated by plugin capability for onboarding (spec §44.3). */
	private const CAPABILITY = Capabilities::RUN_ONBOARDING;

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
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' || ! isset( $_POST[ self::NONCE_ACTION ] ) ) {
			return null;
		}
		if ( ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( (string) $_POST[ self::NONCE_ACTION ] ) ), self::NONCE_ACTION ) ) {
			return null;
		}
		$action = isset( $_POST['aio_onboarding_action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_onboarding_action'] ) ) : '';
		if ( $action === '' || $this->container === null ) {
			return null;
		}

		$draft_service   = $this->container->get( 'onboarding_draft_service' );
		$prefill_service = $this->container->get( 'onboarding_prefill_service' );
		$draft           = $draft_service->get_draft();

		if ( $action === 'save_draft' ) {
			$this->persist_template_preferences_from_post( $draft );
			$this->persist_industry_profile_from_post();
			$draft['overall_status'] = Onboarding_Statuses::DRAFT_SAVED;
			$draft_service->save_draft( $draft );
			$url = \add_query_arg(
				array(
					'page'  => self::SLUG,
					'saved' => '1',
				),
				\admin_url( 'admin.php' )
			);
			return $url;
		}

		if ( $action === 'advance_step' ) {
			$this->persist_template_preferences_from_post( $draft );
			$this->persist_industry_profile_from_post();
			$ordered = Onboarding_Step_Keys::ordered();
			$idx     = array_search( $draft['current_step_key'], $ordered, true );
			if ( $idx !== false && $idx < count( $ordered ) - 1 ) {
				$next = $ordered[ $idx + 1 ];
				$draft['step_statuses'][ $draft['current_step_key'] ] = Onboarding_Statuses::STEP_COMPLETED;
				$draft['current_step_key']                            = $next;
				$draft['step_statuses'][ $next ]                      = Onboarding_Statuses::STEP_IN_PROGRESS;
				$draft['overall_status']                              = Onboarding_Statuses::IN_PROGRESS;
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
			$idx     = array_search( $draft['current_step_key'], $ordered, true );
			if ( $idx !== false && $idx > 0 ) {
				$prev                            = $ordered[ $idx - 1 ];
				$draft['current_step_key']       = $prev;
				$draft['step_statuses'][ $prev ] = Onboarding_Statuses::STEP_IN_PROGRESS;
				$draft['overall_status']         = Onboarding_Statuses::IN_PROGRESS;
				$draft_service->save_draft( $draft );
			}
			$url = \add_query_arg( array( 'page' => self::SLUG ), \admin_url( 'admin.php' ) );
			return $url;
		}

		if ( $action === 'submit_planning_request' ) {
			if ( ! \current_user_can( Capabilities::RUN_ONBOARDING ) || ! \current_user_can( Capabilities::RUN_AI_PLANS ) ) {
				$url = \add_query_arg(
					array(
						'page'             => self::SLUG,
						'planning_result'  => 'blocked',
						'planning_message' => rawurlencode( __( 'You do not have permission to submit a planning request.', 'aio-page-builder' ) ),
					),
					\admin_url( 'admin.php' )
				);
				return $url;
			}
			if ( $this->container->has( 'onboarding_planning_request_orchestrator' ) ) {
				$orchestrator  = $this->container->get( 'onboarding_planning_request_orchestrator' );
				$result        = $orchestrator->submit();
				$arr           = $result->to_array();
				$transient_key = 'aio_onboarding_planning_result_' . \get_current_user_id();
				\set_transient( $transient_key, $arr, 60 );
				$url = \add_query_arg(
					array(
						'page'            => self::SLUG,
						'planning_result' => $arr['status'],
						'run_id'          => $arr['run_id'] !== '' ? rawurlencode( $arr['run_id'] ) : '',
					),
					\admin_url( 'admin.php' )
				);
				return $url;
			}
		}

		return null;
	}

	/**
	 * Persists template preference profile from POST when current step is template_preferences. Capability already checked at render.
	 *
	 * @param array<string, mixed> $draft Current draft (for step key).
	 * @return void
	 */
	private function persist_template_preferences_from_post( array $draft ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_post() before this method is called.
		$current = $draft['current_step_key'] ?? '';
		if ( $current !== Onboarding_Step_Keys::TEMPLATE_PREFERENCES || $this->container === null || ! $this->container->has( 'profile_store' ) ) {
			return;
		}
		$raw           = array(
			'page_emphasis'             => isset( $_POST['aio_template_preference_page_emphasis'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_template_preference_page_emphasis'] ) ) : '',
			'conversion_posture'        => isset( $_POST['aio_template_preference_conversion_posture'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_template_preference_conversion_posture'] ) ) : '',
			'proof_style'               => isset( $_POST['aio_template_preference_proof_style'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_template_preference_proof_style'] ) ) : '',
			'content_density'           => isset( $_POST['aio_template_preference_content_density'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_template_preference_content_density'] ) ) : '',
			'animation_preference'      => isset( $_POST['aio_template_preference_animation'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_template_preference_animation'] ) ) : '',
			'cta_intensity_preference'  => isset( $_POST['aio_template_preference_cta_intensity'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_template_preference_cta_intensity'] ) ) : '',
			'reduced_motion_preference' => isset( $_POST['aio_template_preference_reduced_motion'] ) && (string) $_POST['aio_template_preference_reduced_motion'] === '1',
		);
		$profile_store = $this->container->get( 'profile_store' );
		$profile_store->set_template_preference_profile( $raw );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Persists industry profile and question-pack answers from POST when industry_profile_store and registry are available (industry-question-pack-contract).
	 *
	 * @return void
	 */
	private function persist_industry_profile_from_post(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_post() before this method is called.
		if ( $this->container === null
			|| ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE )
			|| ! $this->container->has( 'industry_question_pack_registry' ) ) {
			return;
		}
		$repo = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
		if ( ! $repo instanceof Industry_Profile_Repository ) {
			return;
		}
		$partial = array();
		if ( isset( $_POST['aio_primary_industry_key'] ) && is_string( $_POST['aio_primary_industry_key'] ) ) {
			$partial[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] = \sanitize_text_field( \wp_unslash( $_POST['aio_primary_industry_key'] ) );
		}
		if ( isset( $_POST['aio_industry_subtype'] ) && is_string( $_POST['aio_industry_subtype'] ) ) {
			$partial[ Industry_Profile_Schema::FIELD_SUBTYPE ] = \sanitize_text_field( \wp_unslash( $_POST['aio_industry_subtype'] ) );
		}
		if ( isset( $_POST['aio_industry_service_model'] ) && is_string( $_POST['aio_industry_service_model'] ) ) {
			$partial[ Industry_Profile_Schema::FIELD_SERVICE_MODEL ] = \sanitize_text_field( \wp_unslash( $_POST['aio_industry_service_model'] ) );
		}
		if ( isset( $_POST['aio_industry_geo_model'] ) && is_string( $_POST['aio_industry_geo_model'] ) ) {
			$partial[ Industry_Profile_Schema::FIELD_GEO_MODEL ] = \sanitize_text_field( \wp_unslash( $_POST['aio_industry_geo_model'] ) );
		}
		if ( isset( $_POST['aio_secondary_industry_keys'] ) && is_array( $_POST['aio_secondary_industry_keys'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array values sanitized in array_map below.
			$raw_secondary = \wp_unslash( $_POST['aio_secondary_industry_keys'] );
			$partial[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] = array_filter(
				array_map(
					function ( $v ) {
						return \is_string( $v ) ? \sanitize_text_field( $v ) : '';
					},
					$raw_secondary
				)
			);
		}
		if ( ! empty( $partial ) ) {
			$repo->merge_profile( $partial );
		}
		$primary = isset( $_POST['aio_primary_industry_key'] ) && is_string( $_POST['aio_primary_industry_key'] )
			? \trim( \sanitize_text_field( \wp_unslash( $_POST['aio_primary_industry_key'] ) ) )
			: '';
		if ( $primary === '' ) {
			$profile = $repo->get_profile();
			$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				? \trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				: '';
		}
		if ( $primary !== '' ) {
			$qp_registry = $this->container->get( 'industry_question_pack_registry' );
			if ( $qp_registry instanceof Industry_Question_Pack_Registry ) {
				$pack = $qp_registry->get( $primary );
				if ( $pack !== null && isset( $pack['fields'] ) && \is_array( $pack['fields'] ) ) {
					$by_field = array();
					foreach ( $pack['fields'] as $field_def ) {
						$field_key = isset( $field_def['key'] ) && \is_string( $field_def['key'] ) ? $field_def['key'] : '';
						if ( $field_key === '' ) {
							continue;
						}
						$post_key = 'aio_industry_qp_' . $field_key;
						if ( isset( $_POST[ $post_key ] ) ) {
							// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized on next line.
							$val                    = \wp_unslash( $_POST[ $post_key ] );
							$by_field[ $field_key ] = \is_scalar( $val ) ? ( \is_string( $val ) ? \sanitize_text_field( $val ) : $val ) : '';
						}
					}
					if ( ! empty( $by_field ) ) {
						$repo->merge_profile(
							array(
								Industry_Profile_Schema::FIELD_QUESTION_PACK_ANSWERS => array( $primary => $by_field ),
							)
						);
					}
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
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
		$draft     = $draft_svc->default_draft();
		$labels    = Onboarding_UI_State_Builder::step_labels();
		$steps     = array();
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
			'prefill'           => array(
				'profile'             => array(),
				'current_site_url'    => '',
				'crawl_run_ids'       => array(),
				'latest_crawl_run_id' => null,
				'provider_refs'       => array(),
			),
			'draft'             => $draft,
			'nonce'             => \wp_create_nonce( self::NONCE_ACTION ),
			'nonce_action'      => self::NONCE_ACTION,
			'can_save_draft'    => true,
			'resume_message'    => '',
			'is_provider_ready' => false,
		);
	}

	/**
	 * @param array<string, mixed> $state Onboarding state (steps, blockers, nonce, etc.).
	 * @return void
	 */
	private function render_shell( array $state ): void {
		$current_step_key        = $state['current_step_key'] ?? Onboarding_Step_Keys::WELCOME;
		$steps                   = $state['steps'] ?? array();
		$is_blocked              = ! empty( $state['is_blocked'] );
		$blockers                = $state['blockers'] ?? array();
		$resume_message          = $state['resume_message'] ?? '';
		$nonce                   = $state['nonce'] ?? '';
		$nonce_action            = $state['nonce_action'] ?? self::NONCE_ACTION;
		$saved                   = isset( $_GET['saved'] ) && $_GET['saved'] === '1';
		$planning_result_status  = isset( $_GET['planning_result'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['planning_result'] ) ) : '';
		$planning_result_message = '';
		if ( $planning_result_status !== '' ) {
			$transient_key = 'aio_onboarding_planning_result_' . \get_current_user_id();
			$stored        = \get_transient( $transient_key );
			if ( is_array( $stored ) && isset( $stored['user_message'] ) && is_string( $stored['user_message'] ) ) {
				$planning_result_message = $stored['user_message'];
				\delete_transient( $transient_key );
			} else {
				$planning_result_message = isset( $_GET['planning_message'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['planning_message'] ) ) : __( 'Planning request completed. Check the result below.', 'aio-page-builder' );
			}
		}
		?>
		<div class="wrap aio-page-builder-screen aio-onboarding" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible" role="status">
					<p><?php \esc_html_e( 'Draft saved. You can return later to continue.', 'aio-page-builder' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $planning_result_status !== '' && $planning_result_message !== '' ) : ?>
				<?php
				$notice_class = 'notice-info';
				if ( $planning_result_status === Planning_Request_Result::STATUS_SUCCESS ) {
					$notice_class = 'notice-success';
				} elseif ( in_array( $planning_result_status, array( Planning_Request_Result::STATUS_VALIDATION_FAILED, Planning_Request_Result::STATUS_PROVIDER_FAILED ), true ) ) {
					$notice_class = 'notice-warning';
				} elseif ( $planning_result_status === Planning_Request_Result::STATUS_BLOCKED ) {
					$notice_class = 'notice-warning';
				}
				?>
				<div class="notice <?php echo \esc_attr( $notice_class ); ?> is-dismissible" role="status">
					<p><?php echo \esc_html( $planning_result_message ); ?></p>
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
						<?php if ( ! empty( $state['is_blocked'] ) ) : ?>
							<p class="aio-onboarding-ready"><?php \esc_html_e( 'Complete the required steps above before requesting a plan.', 'aio-page-builder' ); ?></p>
						<?php else : ?>
							<button type="submit" name="aio_onboarding_action" value="submit_planning_request" class="button button-primary"><?php \esc_html_e( 'Request AI plan', 'aio-page-builder' ); ?></button>
						<?php endif; ?>
					<?php endif; ?>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders placeholder content for the current step. Full step forms are out of scope for this prompt.
	 *
	 * @param string               $current_step_key Current step identifier.
	 * @param array<string, mixed> $state            Onboarding state (prefill, provider refs, etc.).
	 * @return void
	 */
	private function render_step_placeholder( string $current_step_key, array $state ): void {
		$labels            = Onboarding_UI_State_Builder::step_labels();
		$label             = $labels[ $current_step_key ] ?? $current_step_key;
		$prefill           = $state['prefill'] ?? array();
		$provider_refs     = $prefill['provider_refs'] ?? array();
		$is_provider_ready = ! empty( $state['is_provider_ready'] );
		?>
		<div class="aio-onboarding-step-panel" data-step="<?php echo \esc_attr( $current_step_key ); ?>">
			<h3><?php echo \esc_html( $label ); ?></h3>
			<?php if ( $current_step_key === Onboarding_Step_Keys::WELCOME ) : ?>
				<p><?php \esc_html_e( 'Welcome to AIO Page Builder. This flow collects your business and brand context, existing site information, and AI provider setup so you can request an AI-generated plan. You can save your progress and return later.', 'aio-page-builder' ); ?></p>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::PROVIDER_SETUP ) : ?>
				<p><?php \esc_html_e( 'Configure at least one AI provider (API key) to use AI planning. Credentials are stored securely and never shown here.', 'aio-page-builder' ); ?></p>
				<p><?php \esc_html_e( 'Provider setup UI will be added in a future update. Current readiness:', 'aio-page-builder' ); ?> <?php echo $is_provider_ready ? \esc_html__( 'At least one provider configured.', 'aio-page-builder' ) : \esc_html__( 'No provider configured.', 'aio-page-builder' ); ?></p>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::SUBMISSION ) : ?>
				<p><?php \esc_html_e( 'Request an AI-generated plan from your profile and context. The plan will appear in AI Runs; you can then create a Build Plan from it.', 'aio-page-builder' ); ?></p>
				<?php
				$last_run_id      = $state['last_planning_run_id'] ?? null;
				$last_run_post_id = $state['last_planning_run_post_id'] ?? null;
				if ( $last_run_id !== null && $last_run_post_id !== null && (int) $last_run_post_id > 0 ) :
					$run_url = \add_query_arg(
						array(
							'page'   => 'aio-page-builder-ai-runs',
							'run_id' => $last_run_id,
						),
						\admin_url( 'admin.php' )
					);
					?>
					<p><?php \esc_html_e( 'Last run:', 'aio-page-builder' ); ?> <a href="<?php echo \esc_url( $run_url ); ?>"><?php echo \esc_html( $last_run_id ); ?></a></p>
				<?php endif; ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::TEMPLATE_PREFERENCES ) : ?>
				<?php $this->render_template_preferences_step( $state ); ?>
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

	/**
	 * Renders bounded template preference fields (Prompt 212). Advisory only; prefill from profile.
	 *
	 * @param array<string, mixed> $state UI state (prefill.profile.template_preference_profile).
	 * @return void
	 */
	private function render_template_preferences_step( array $state ): void {
		$prefill            = $state['prefill'] ?? array();
		$profile            = $prefill['profile'] ?? array();
		$prefs              = $profile[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] ?? array();
		$prefs              = is_array( $prefs ) ? $prefs : array();
		$page_emphasis      = isset( $prefs['page_emphasis'] ) ? (string) $prefs['page_emphasis'] : '';
		$conversion_posture = isset( $prefs['conversion_posture'] ) ? (string) $prefs['conversion_posture'] : '';
		$proof_style        = isset( $prefs['proof_style'] ) ? (string) $prefs['proof_style'] : '';
		$content_density    = isset( $prefs['content_density'] ) ? (string) $prefs['content_density'] : '';
		$animation          = isset( $prefs['animation_preference'] ) ? (string) $prefs['animation_preference'] : '';
		$cta_intensity      = isset( $prefs['cta_intensity_preference'] ) ? (string) $prefs['cta_intensity_preference'] : '';
		$reduced_motion     = ! empty( $prefs['reduced_motion_preference'] );
		?>
		<p><?php \esc_html_e( 'These preferences help guide template and page-style recommendations. They are advisory only and do not override structural or CTA rules.', 'aio-page-builder' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aio_template_preference_page_emphasis"><?php \esc_html_e( 'Page emphasis', 'aio-page-builder' ); ?></label></th>
				<td>
					<select name="aio_template_preference_page_emphasis" id="aio_template_preference_page_emphasis" aria-describedby="aio-page-emphasis-desc">
						<option value="" <?php selected( $page_emphasis, '' ); ?>><?php \esc_html_e( 'Not specified', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::PAGE_EMPHASIS_INFORMATIONAL ); ?>" <?php selected( $page_emphasis, Template_Preference_Profile::PAGE_EMPHASIS_INFORMATIONAL ); ?>><?php \esc_html_e( 'Informational', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::PAGE_EMPHASIS_CONVERSION ); ?>" <?php selected( $page_emphasis, Template_Preference_Profile::PAGE_EMPHASIS_CONVERSION ); ?>><?php \esc_html_e( 'Conversion', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::PAGE_EMPHASIS_BALANCED ); ?>" <?php selected( $page_emphasis, Template_Preference_Profile::PAGE_EMPHASIS_BALANCED ); ?>><?php \esc_html_e( 'Balanced', 'aio-page-builder' ); ?></option>
					</select>
					<p id="aio-page-emphasis-desc" class="description"><?php \esc_html_e( 'Preferred focus: information vs. conversion vs. balanced.', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_template_preference_conversion_posture"><?php \esc_html_e( 'Conversion posture', 'aio-page-builder' ); ?></label></th>
				<td>
					<select name="aio_template_preference_conversion_posture" id="aio_template_preference_conversion_posture">
						<option value="" <?php selected( $conversion_posture, '' ); ?>><?php \esc_html_e( 'Not specified', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CONVERSION_POSTURE_SOFT ); ?>" <?php selected( $conversion_posture, Template_Preference_Profile::CONVERSION_POSTURE_SOFT ); ?>><?php \esc_html_e( 'Soft', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CONVERSION_POSTURE_MODERATE ); ?>" <?php selected( $conversion_posture, Template_Preference_Profile::CONVERSION_POSTURE_MODERATE ); ?>><?php \esc_html_e( 'Moderate', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CONVERSION_POSTURE_STRONG ); ?>" <?php selected( $conversion_posture, Template_Preference_Profile::CONVERSION_POSTURE_STRONG ); ?>><?php \esc_html_e( 'Strong', 'aio-page-builder' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_template_preference_proof_style"><?php \esc_html_e( 'Proof style', 'aio-page-builder' ); ?></label></th>
				<td>
					<select name="aio_template_preference_proof_style" id="aio_template_preference_proof_style">
						<option value="" <?php selected( $proof_style, '' ); ?>><?php \esc_html_e( 'Not specified', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::PROOF_STYLE_SOCIAL ); ?>" <?php selected( $proof_style, Template_Preference_Profile::PROOF_STYLE_SOCIAL ); ?>><?php \esc_html_e( 'Social proof', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::PROOF_STYLE_CREDENTIALS ); ?>" <?php selected( $proof_style, Template_Preference_Profile::PROOF_STYLE_CREDENTIALS ); ?>><?php \esc_html_e( 'Credentials', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::PROOF_STYLE_TESTIMONIALS ); ?>" <?php selected( $proof_style, Template_Preference_Profile::PROOF_STYLE_TESTIMONIALS ); ?>><?php \esc_html_e( 'Testimonials', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::PROOF_STYLE_MINIMAL ); ?>" <?php selected( $proof_style, Template_Preference_Profile::PROOF_STYLE_MINIMAL ); ?>><?php \esc_html_e( 'Minimal', 'aio-page-builder' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_template_preference_content_density"><?php \esc_html_e( 'Content density', 'aio-page-builder' ); ?></label></th>
				<td>
					<select name="aio_template_preference_content_density" id="aio_template_preference_content_density">
						<option value="" <?php selected( $content_density, '' ); ?>><?php \esc_html_e( 'Not specified', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CONTENT_DENSITY_COMPACT ); ?>" <?php selected( $content_density, Template_Preference_Profile::CONTENT_DENSITY_COMPACT ); ?>><?php \esc_html_e( 'Compact', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CONTENT_DENSITY_MODERATE ); ?>" <?php selected( $content_density, Template_Preference_Profile::CONTENT_DENSITY_MODERATE ); ?>><?php \esc_html_e( 'Moderate', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CONTENT_DENSITY_SPACIOUS ); ?>" <?php selected( $content_density, Template_Preference_Profile::CONTENT_DENSITY_SPACIOUS ); ?>><?php \esc_html_e( 'Spacious', 'aio-page-builder' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_template_preference_animation"><?php \esc_html_e( 'Animation preference', 'aio-page-builder' ); ?></label></th>
				<td>
					<select name="aio_template_preference_animation" id="aio_template_preference_animation">
						<option value="" <?php selected( $animation, '' ); ?>><?php \esc_html_e( 'Not specified', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::ANIMATION_FULL ); ?>" <?php selected( $animation, Template_Preference_Profile::ANIMATION_FULL ); ?>><?php \esc_html_e( 'Full', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::ANIMATION_REDUCED ); ?>" <?php selected( $animation, Template_Preference_Profile::ANIMATION_REDUCED ); ?>><?php \esc_html_e( 'Reduced', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::ANIMATION_MINIMAL ); ?>" <?php selected( $animation, Template_Preference_Profile::ANIMATION_MINIMAL ); ?>><?php \esc_html_e( 'Minimal', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::ANIMATION_NONE ); ?>" <?php selected( $animation, Template_Preference_Profile::ANIMATION_NONE ); ?>><?php \esc_html_e( 'None', 'aio-page-builder' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_template_preference_cta_intensity"><?php \esc_html_e( 'CTA intensity (advisory)', 'aio-page-builder' ); ?></label></th>
				<td>
					<select name="aio_template_preference_cta_intensity" id="aio_template_preference_cta_intensity">
						<option value="" <?php selected( $cta_intensity, '' ); ?>><?php \esc_html_e( 'Not specified', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CTA_INTENSITY_LOW ); ?>" <?php selected( $cta_intensity, Template_Preference_Profile::CTA_INTENSITY_LOW ); ?>><?php \esc_html_e( 'Low', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CTA_INTENSITY_MEDIUM ); ?>" <?php selected( $cta_intensity, Template_Preference_Profile::CTA_INTENSITY_MEDIUM ); ?>><?php \esc_html_e( 'Medium', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CTA_INTENSITY_HIGH ); ?>" <?php selected( $cta_intensity, Template_Preference_Profile::CTA_INTENSITY_HIGH ); ?>><?php \esc_html_e( 'High', 'aio-page-builder' ); ?></option>
					</select>
					<p class="description"><?php \esc_html_e( 'Does not override CTA rules.', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php \esc_html_e( 'Reduced motion', 'aio-page-builder' ); ?></th>
				<td>
					<label for="aio_template_preference_reduced_motion">
						<input type="checkbox" name="aio_template_preference_reduced_motion" id="aio_template_preference_reduced_motion" value="1" <?php checked( $reduced_motion ); ?> />
						<?php \esc_html_e( 'Prefer reduced motion in templates', 'aio-page-builder' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}
}
