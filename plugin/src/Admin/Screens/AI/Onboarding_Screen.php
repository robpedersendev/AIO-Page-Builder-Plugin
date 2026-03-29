<?php
/**
 * Onboarding admin screen: step shell, draft save/load, prefill, readiness (onboarding-state-machine.md, spec §23, §53.2).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Build_Plan_Bootstrap_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\BuildPlan\Generation\AI_Run_To_Build_Plan_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Statuses;
use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Crawl_Context_Phase;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Telemetry;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_User_Facing_Status;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Readiness;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Planning_Context_Guard;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Keys;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_UI_State_Builder;
use AIOPageBuilder\Domain\AI\UI\AI_Provider_Admin_Capability_Summary_Builder;
use AIOPageBuilder\Domain\AI\Onboarding\Planning_Request_Result;
use AIOPageBuilder\Domain\Profile\Template_Preference_Profile;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Profile_Settings_Screen;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Industry_Hub_Navigation_Advisor;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Renders onboarding flow: steps, draft persistence, prefill, blocked state. No provider API or AI submission.
 */
final class Onboarding_Screen {

	public const SLUG = 'aio-page-builder-onboarding';

	/** Query arg: AI run id after onboarding planning when redirecting to Industry hub (link back to AI Runs). */
	public const QUERY_ONBOARDING_INDUSTRY_RUN = 'aio_onboarding_run';

	/** Gated by plugin capability for onboarding (spec §44.3). */
	private const CAPABILITY = Capabilities::RUN_ONBOARDING;

	/** Nonce action and wp_nonce_field() name for onboarding forms. */
	public const NONCE_ACTION = 'aio_onboarding_save';

	/**
	 * HTML id of the wizard POST form. Most step inputs render outside this form (embedded AI/Crawler UIs must not be nested in a form)
	 * and associate via the HTML5 `form` attribute. The submission site-goal field uses a visible textarea plus a hidden field inside this form
	 * synced by script because `form=""` association is unreliable for multipart text across browsers.
	 */
	private const MAIN_FORM_ID = 'aio-onboarding-main';

	/** Landmark target for skip link (must differ from {@see self::MAIN_FORM_ID}). */
	private const STEP_CONTENT_REGION_ID = 'aio-onboarding-step-content';

	/** Visible site-goal textarea id (no `name`; synced to {@see self::SUBMISSION_GOAL_POST_FIELD_ID}). */
	private const SUBMISSION_GOAL_VISIBLE_FIELD_ID = 'aio_onboarding_goal_visible';

	/** Hidden textarea inside the main form carrying `aio_onboarding_goal_or_intent` for POST. */
	private const SUBMISSION_GOAL_POST_FIELD_ID = 'aio_onboarding_goal_post';

	/** `aria-describedby` target for the site goal help text on the submission step. */
	private const SUBMISSION_GOAL_HELP_ID = 'aio-onboarding-goal-help';

	/** `aria-describedby` target wrapping non-blocking submission warnings. */
	private const SUBMISSION_WARNINGS_GROUP_ID = 'aio-onboarding-submission-warnings';

	private Service_Container $container;

	public function __construct( Service_Container $container ) {
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
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access this page.', 'aio-page-builder' ) );
		}

		$state = $this->get_ui_state();
		$state = $this->enrich_state_with_build_plan_lineages( $state );
		$this->render_shell( $state, $embed_in_hub );
	}

	/**
	 * Processes POST and returns a redirect URL. Call from admin_init before any HTML output (see Plugin::maybe_handle_onboarding_post_redirect).
	 *
	 * @return string|null Redirect URL or null when not an onboarding POST or no redirect applies.
	 */
	public function get_post_redirect_url(): ?string {
		return $this->handle_post();
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
			$this->debug_onboarding_line( 'post rejected: invalid nonce' );
			return null;
		}
		$action = isset( $_POST['aio_onboarding_action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_onboarding_action'] ) ) : '';
		if ( $action === '' ) {
			$this->debug_onboarding_line( 'post rejected: missing aio_onboarding_action' );
			return null;
		}

		$draft_service   = $this->container->get( 'onboarding_draft_service' );
		$prefill_service = $this->container->get( 'onboarding_prefill_service' );
		$draft           = $draft_service->get_draft();
		$this->debug_onboarding_line(
			'post action=' . $action
			. ' draft_step=' . ( isset( $draft['current_step_key'] ) ? (string) $draft['current_step_key'] : '' )
			. ' post_field_keys=' . $this->summarize_wizard_post_field_keys()
		);

		if ( $action === 'goto_step' ) {
			$target = isset( $_POST['aio_onboarding_target_step'] ) ? \sanitize_key( \wp_unslash( (string) $_POST['aio_onboarding_target_step'] ) ) : '';
			if ( ! in_array( $target, Onboarding_Step_Keys::ordered(), true ) ) {
				return $this->hub_redirect_url( array() );
			}
			$t_idx    = Onboarding_Step_Keys::index_of( $target );
			$furthest = isset( $draft['furthest_step_index'] ) ? (int) $draft['furthest_step_index'] : 0;
			$cur_idx  = Onboarding_Step_Keys::index_of( $draft['current_step_key'] ?? '' );
			$furthest = max( $furthest, $cur_idx >= 0 ? $cur_idx : 0 );
			if ( $t_idx < 0 || $t_idx > $furthest ) {
				return $this->hub_redirect_url( array() );
			}
			$this->persist_all_wizard_fields_from_post( $draft );
			$draft['current_step_key']         = $target;
			$draft['step_statuses'][ $target ] = Onboarding_Statuses::STEP_IN_PROGRESS;
			$draft['overall_status']           = Onboarding_Statuses::IN_PROGRESS;
			$this->bump_furthest_step_index( $draft );
			$draft_service->save_draft( $draft );
			$this->maybe_sync_build_plan_snapshot( $draft );
			return $this->hub_redirect_url( array() );
		}

		if ( $action === 'save_draft' ) {
			$this->persist_all_wizard_fields_from_post( $draft );
			$this->bump_furthest_step_index( $draft );
			if ( ( $draft['current_step_key'] ?? '' ) === Onboarding_Step_Keys::WELCOME ) {
				if ( ! $this->ensure_linked_shell_plan_for_draft( $draft, $draft_service ) ) {
					return $this->hub_redirect_url( array( 'onboarding_validation' => '1' ) );
				}
			} else {
				$this->maybe_sync_build_plan_snapshot( $draft );
			}
			$draft['overall_status'] = Onboarding_Statuses::DRAFT_SAVED;
			$draft_service->save_draft( $draft );
			$this->record_onboarding_telemetry( Onboarding_Telemetry::EVENT_DRAFT_SAVE, $draft );
			return $this->hub_redirect_url(
				array(
					'saved' => '1',
				)
			);
		}

		if ( $action === 'advance_step' ) {
			$this->persist_all_wizard_fields_from_post( $draft );
			if ( ( $draft['current_step_key'] ?? '' ) === Onboarding_Step_Keys::WELCOME ) {
				if ( ! $this->ensure_linked_shell_plan_for_draft( $draft, $draft_service ) ) {
					return $this->hub_redirect_url( array( 'onboarding_validation' => '1' ) );
				}
			}
			$this->bump_furthest_step_index( $draft );
			$prefill_fresh    = $this->container->get( 'onboarding_prefill_service' )->get_prefill_data( $draft );
			$profile_for_gate = isset( $prefill_fresh['profile'] ) && is_array( $prefill_fresh['profile'] ) ? $prefill_fresh['profile'] : array();
			$gate_errors      = Onboarding_Step_Readiness::get_step_validation_errors(
				$draft['current_step_key'],
				$profile_for_gate,
				$this->container->get( 'onboarding_prefill_service' )
			);
			if ( count( $gate_errors ) > 0 ) {
				$this->debug_onboarding_line(
					'advance blocked: validation failed for step=' . (string) ( $draft['current_step_key'] ?? '' )
					. ' error_count=' . (string) count( $gate_errors )
				);
				$this->record_onboarding_telemetry( Onboarding_Telemetry::EVENT_ADVANCE_BLOCKED, $draft );
				\set_transient( 'aio_onboarding_advance_validation_' . (string) \get_current_user_id(), $gate_errors, 120 );
				return $this->hub_redirect_url( array( 'onboarding_validation' => '1' ) );
			}
			$ordered = Onboarding_Step_Keys::ordered();
			$idx     = array_search( $draft['current_step_key'], $ordered, true );
			if ( $idx !== false && $idx < count( $ordered ) - 1 ) {
				$next = $ordered[ $idx + 1 ];
				if ( $next === Onboarding_Step_Keys::REVIEW && ! $prefill_service->is_provider_ready() ) {
					$this->debug_onboarding_line( 'advance blocked: provider not ready before review step' );
					$this->record_onboarding_telemetry( Onboarding_Telemetry::EVENT_ADVANCE_BLOCKED, $draft );
					\set_transient(
						'aio_onboarding_advance_validation_' . (string) \get_current_user_id(),
						array( __( 'Save an API key for at least one AI provider before Review.', 'aio-page-builder' ) ),
						120
					);
					return $this->hub_redirect_url( array( 'onboarding_validation' => '1' ) );
				}
				if ( $next === Onboarding_Step_Keys::SUBMISSION ) {
					$review_errs = Onboarding_Step_Readiness::get_review_blockers( $profile_for_gate, $prefill_service );
					if ( count( $review_errs ) > 0 ) {
						$this->debug_onboarding_line(
							'advance blocked: review gate before submission error_count=' . (string) count( $review_errs )
						);
						$this->record_onboarding_telemetry( Onboarding_Telemetry::EVENT_ADVANCE_BLOCKED, $draft );
						\set_transient( 'aio_onboarding_advance_validation_' . (string) \get_current_user_id(), $review_errs, 120 );
						return $this->hub_redirect_url( array( 'onboarding_validation' => '1' ) );
					}
				}
				$draft['step_statuses'][ $draft['current_step_key'] ] = Onboarding_Statuses::STEP_COMPLETED;
				$draft['current_step_key']                            = $next;
				$draft['step_statuses'][ $next ]                      = Onboarding_Statuses::STEP_IN_PROGRESS;
				$draft['overall_status']                              = Onboarding_Statuses::IN_PROGRESS;
				$this->bump_furthest_step_index( $draft );
				$draft_service->save_draft( $draft );
				$this->maybe_sync_build_plan_snapshot( $draft );
				$this->record_onboarding_telemetry( Onboarding_Telemetry::EVENT_STEP_ADVANCED, $draft );
				$this->debug_onboarding_line( 'advance ok: moved to step=' . $next );
			}
			return $this->hub_redirect_url( array() );
		}

		if ( $action === 'go_back' ) {
			$this->persist_all_wizard_fields_from_post( $draft );
			$this->bump_furthest_step_index( $draft );
			$ordered = Onboarding_Step_Keys::ordered();
			$idx     = array_search( $draft['current_step_key'], $ordered, true );
			if ( $idx !== false && $idx > 0 ) {
				$prev                            = $ordered[ $idx - 1 ];
				$draft['current_step_key']       = $prev;
				$draft['step_statuses'][ $prev ] = Onboarding_Statuses::STEP_IN_PROGRESS;
				$draft['overall_status']         = Onboarding_Statuses::IN_PROGRESS;
				$draft_service->save_draft( $draft );
				$this->maybe_sync_build_plan_snapshot( $draft );
			}
			return $this->hub_redirect_url( array() );
		}

		if ( $action === 'submit_planning_request' ) {
			$this->persist_all_wizard_fields_from_post( $draft );
			if ( ! $this->ensure_linked_shell_plan_for_draft( $draft, $draft_service ) ) {
				return $this->hub_redirect_url( array( 'onboarding_validation' => '1' ) );
			}
			if ( ! Capabilities::current_user_can_for_route( Capabilities::RUN_ONBOARDING )
				|| ! Capabilities::current_user_can_for_route( Capabilities::RUN_AI_PLANS ) ) {
				$this->debug_onboarding_line( 'submit_planning_request blocked: missing capability' );
				return $this->hub_redirect_url(
					array(
						'planning_result'  => 'blocked',
						'planning_message' => rawurlencode( __( 'You do not have permission to submit a planning request.', 'aio-page-builder' ) ),
					)
				);
			}
			if ( $this->container->has( 'onboarding_planning_request_orchestrator' ) ) {
				$this->record_onboarding_telemetry( Onboarding_Telemetry::EVENT_SUBMIT_ATTEMPTED, $draft );
				$orchestrator  = $this->container->get( 'onboarding_planning_request_orchestrator' );
				$result        = $orchestrator->submit();
				$arr           = $result->to_array();
				$transient_key = 'aio_onboarding_planning_result_' . \get_current_user_id();
				\set_transient( $transient_key, $arr, 120 );
				$this->debug_onboarding_line( 'submit_planning_request result_status=' . (string) ( $arr['status'] ?? '' ) );
				if ( $arr['status'] === Planning_Request_Result::STATUS_SUCCESS
					&& isset( $arr['run_id'] ) && is_string( $arr['run_id'] ) && $arr['run_id'] !== '' ) {
					$run_post_id    = isset( $arr['run_post_id'] ) ? (int) $arr['run_post_id'] : 0;
					$draft_after    = $draft_service->get_draft();
					$linked_bp_post = isset( $draft_after['linked_build_plan_post_id'] ) ? (int) $draft_after['linked_build_plan_post_id'] : 0;
					if ( $this->container->has( 'ai_run_to_build_plan_service' )
						&& Capabilities::current_user_can_for_route( Capabilities::VIEW_BUILD_PLANS ) ) {
						/** @var AI_Run_To_Build_Plan_Service $bp_svc */
						$bp_svc    = $this->container->get( 'ai_run_to_build_plan_service' );
						$bp_result = null;
						if ( $linked_bp_post > 0 ) {
							$bp_result = $bp_svc->create_from_completed_run( $arr['run_id'], $linked_bp_post );
						}
						if ( $bp_result === null || ! $bp_result->is_success() ) {
							$bp_result = $bp_svc->create_from_completed_run( $arr['run_id'] );
						}
						if ( $bp_result->is_success() ) {
							$plan_key                                 = (string) ( $bp_result->get_plan_id() ?? '' );
							$draft_fresh                              = $draft_service->get_draft();
							$draft_fresh['linked_build_plan_post_id'] = $bp_result->get_plan_post_id();
							$draft_fresh['linked_build_plan_key']     = $plan_key !== '' ? $plan_key : ( $draft_fresh['linked_build_plan_key'] ?? null );
							$draft_service->save_draft( $draft_fresh );
							if ( $run_post_id > 0 && $plan_key !== '' && $this->container->has( 'ai_run_service' ) ) {
								$run_svc = $this->container->get( 'ai_run_service' );
								if ( $run_svc instanceof AI_Run_Service ) {
									$run_row = $run_svc->get_run_by_post_id( $run_post_id );
									if ( $run_row !== null ) {
										$run_st = (string) ( $run_row['status'] ?? 'completed' );
										$run_svc->update_run(
											$run_post_id,
											$run_st,
											array( 'build_plan_ref' => $plan_key ),
											array()
										);
									}
								}
							}
							if ( $this->container->has( 'onboarding_build_plan_bootstrap_service' ) ) {
								$prefill  = $this->container->get( 'onboarding_prefill_service' )->get_prefill_data( $draft_fresh );
								$industry = $this->get_industry_profile_snapshot_for_plan();
								/** @var Onboarding_Build_Plan_Bootstrap_Service $bootstrap */
								$bootstrap = $this->container->get( 'onboarding_build_plan_bootstrap_service' );
								$bootstrap->sync_wizard_snapshot( $bp_result->get_plan_post_id(), $draft_fresh, $prefill, $industry );
							}
							if ( $plan_key !== '' ) {
								return Admin_Screen_Hub::tab_url(
									Build_Plans_Screen::SLUG,
									'build_plans',
									array( 'plan_id' => $plan_key )
								);
							}
						}
					}
					if ( Capabilities::current_user_can_for_route( Capabilities::ACCESS_INDUSTRY_WORKSPACE )
						&& $this->container->has( 'onboarding_industry_hub_navigation_advisor' )
						&& $run_post_id > 0 ) {
						/** @var Onboarding_Industry_Hub_Navigation_Advisor $nav_advisor */
						$nav_advisor    = $this->container->get( 'onboarding_industry_hub_navigation_advisor' );
						$suggested      = $nav_advisor->suggest_navigation( $run_post_id );
						$tab            = (string) ( $suggested['tab'] ?? 'profile' );
						$subtab         = isset( $suggested['subtab'] ) && is_string( $suggested['subtab'] ) ? $suggested['subtab'] : null;
						$extra_industry = array(
							self::QUERY_ONBOARDING_INDUSTRY_RUN => $arr['run_id'],
						);
						if ( ( $tab === 'reports' || $tab === 'comparisons' ) && $subtab !== null && $subtab !== '' ) {
							return Admin_Screen_Hub::subtab_url(
								Industry_Profile_Settings_Screen::SLUG,
								$tab,
								$subtab,
								$extra_industry
							);
						}
						return Admin_Screen_Hub::tab_url(
							Industry_Profile_Settings_Screen::SLUG,
							$tab,
							$extra_industry
						);
					}
					if ( Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) ) {
						return Admin_Screen_Hub::tab_url(
							AI_Runs_Screen::HUB_PAGE_SLUG,
							'ai_runs',
							array(
								'run_id' => $arr['run_id'],
								AI_Runs_Screen::QUERY_ONBOARDING_PLAN => AI_Runs_Screen::ONBOARDING_PLAN_SUCCESS_VALUE,
							)
						);
					}
				}
				return $this->hub_redirect_url(
					array(
						'planning_result' => $arr['status'],
						'run_id'          => $arr['run_id'] !== '' ? rawurlencode( $arr['run_id'] ) : '',
					)
				);
			}
			$this->debug_onboarding_line( 'post rejected: submit_planning_request but orchestrator not registered' );
			return null;
		}

		$this->debug_onboarding_line( 'post no_redirect: unhandled or noop action=' . $action );
		return null;
	}

	/**
	 * Builds a redirect URL back to the Onboarding hub tab (wizard), preserving aio_tab=onboarding.
	 *
	 * @param array<string, string> $extra Query args (values already sanitized for URL use where needed).
	 * @return string
	 */
	private function hub_redirect_url( array $extra = array() ): string {
		return Admin_Screen_Hub::tab_url( self::SLUG, 'onboarding', $extra );
	}

	/**
	 * Creates a shell Build Plan when possible and saves the draft; then syncs wizard fields into plan meta.
	 *
	 * @param array<string, mixed> $draft Draft (mutated).
	 */
	private function ensure_linked_shell_plan_for_draft( array &$draft, Onboarding_Draft_Service $draft_service ): bool {
		if ( ! $this->container->has( 'onboarding_build_plan_bootstrap_service' ) ) {
			return true;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::VIEW_BUILD_PLANS ) ) {
			return true;
		}
		/** @var Onboarding_Build_Plan_Bootstrap_Service $bootstrap */
		$bootstrap = $this->container->get( 'onboarding_build_plan_bootstrap_service' );
		$result    = $bootstrap->ensure_linked_shell_plan( $draft );
		if ( $result !== null && ! $result->is_success() ) {
			$draft_service->save_draft( $draft );
			\set_transient( 'aio_onboarding_advance_validation_' . (string) \get_current_user_id(), $result->get_errors(), 120 );
			return false;
		}
		$draft_service->save_draft( $draft );
		$this->maybe_sync_build_plan_snapshot( $draft );
		return true;
	}

	/**
	 * Adds build plan lineage options for the welcome step.
	 *
	 * @param array<string, mixed> $state UI state.
	 * @return array<string, mixed>
	 */
	private function enrich_state_with_build_plan_lineages( array $state ): array {
		$state['build_plan_lineages'] = array();
		if ( Capabilities::current_user_can_for_route( Capabilities::VIEW_BUILD_PLANS ) ) {
			$state['build_plan_lineages'] = $this->container->get( 'build_plan_lineage_service' )->list_lineages_for_onboarding_selector();
		}
		return $state;
	}

	/**
	 * Reads build plan lineage mode and fork fields from POST into the draft.
	 *
	 * @param array<string, mixed> $draft Draft (mutated).
	 */
	private function merge_build_plan_lineage_from_post_into_draft( array &$draft ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_post() before persist.
		if ( ! isset( $_POST['aio_onboarding_build_plan_mode'] ) ) {
			return;
		}
		$mode                             = \sanitize_key( \wp_unslash( (string) $_POST['aio_onboarding_build_plan_mode'] ) );
		$draft['build_plan_lineage_mode'] = $mode === 'fork' ? 'fork' : 'new';
		if ( isset( $_POST['aio_onboarding_fork_lineage_id'] ) ) {
			$draft['fork_lineage_id'] = \sanitize_text_field( \wp_unslash( (string) $_POST['aio_onboarding_fork_lineage_id'] ) );
		}
		if ( isset( $_POST['aio_onboarding_fork_version_purpose'] ) ) {
			$draft['fork_version_purpose'] = \sanitize_textarea_field( \wp_unslash( (string) $_POST['aio_onboarding_fork_version_purpose'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Updates the linked plan's onboarding snapshot when a plan post id is present.
	 *
	 * @param array<string, mixed> $draft Current draft.
	 */
	private function maybe_sync_build_plan_snapshot( array $draft ): void {
		if ( ! $this->container->has( 'onboarding_build_plan_bootstrap_service' ) ) {
			return;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::VIEW_BUILD_PLANS ) ) {
			return;
		}
		$post_id = isset( $draft['linked_build_plan_post_id'] ) ? (int) $draft['linked_build_plan_post_id'] : 0;
		if ( $post_id <= 0 ) {
			return;
		}
		$prefill  = $this->container->get( 'onboarding_prefill_service' )->get_prefill_data( $draft );
		$industry = $this->get_industry_profile_snapshot_for_plan();
		/** @var Onboarding_Build_Plan_Bootstrap_Service $bootstrap */
		$bootstrap = $this->container->get( 'onboarding_build_plan_bootstrap_service' );
		$bootstrap->sync_wizard_snapshot( $post_id, $draft, $prefill, $industry );
	}

	/**
	 * Returns the industry profile row for plan snapshots (no secrets).
	 *
	 * @return array<string, mixed>
	 */
	private function get_industry_profile_snapshot_for_plan(): array {
		if ( ! $this->container->has( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			return array();
		}
		$repo = $this->container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
		if ( ! $repo instanceof Industry_Profile_Repository ) {
			return array();
		}
		return $repo->get_profile();
	}

	/**
	 * Tracks the highest step index the user has opened so the stepper can allow jumping back to visited steps.
	 *
	 * @param array<string, mixed> $draft Draft (mutated).
	 */
	private function bump_furthest_step_index( array &$draft ): void {
		$idx = Onboarding_Step_Keys::index_of( $draft['current_step_key'] ?? '' );
		if ( $idx < 0 ) {
			return;
		}
		$prev                         = isset( $draft['furthest_step_index'] ) ? (int) $draft['furthest_step_index'] : 0;
		$draft['furthest_step_index'] = max( $prev, $idx );
	}

	/**
	 * Merges POSTed wizard fields into profile and industry stores for every navigation action so incomplete onboarding retains input.
	 *
	 * @param array<string, mixed> $draft Current draft (used to gate template preference writes).
	 * @return void
	 */
	private function persist_all_wizard_fields_from_post( array &$draft ): void {
		$this->merge_build_plan_lineage_from_post_into_draft( $draft );
		$this->merge_crawl_run_id_ref_from_post_into_draft( $draft );
		$this->persist_brand_profile_from_post( $draft );
		$this->persist_business_profile_from_post( $draft );
		$this->persist_template_preferences_from_post( $draft );
		$this->persist_industry_profile_from_post();
		$this->merge_goal_intent_from_post_into_draft( $draft );
	}

	/**
	 * Persists the crawl session pin from the review (or submission) step into the draft.
	 *
	 * @param array<string, mixed> $draft Draft (mutated).
	 */
	private function merge_crawl_run_id_ref_from_post_into_draft( array &$draft ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_post() before this runs.
		if ( ! isset( $_POST['aio_onboarding_crawl_run_id_ref'] ) ) {
			return;
		}
		$raw = \sanitize_text_field( \wp_unslash( (string) $_POST['aio_onboarding_crawl_run_id_ref'] ) );
		if ( $raw === '' || $raw === '__auto__' ) {
			$draft['crawl_run_id_ref'] = null;
			return;
		}
		$allowed = $this->list_allowed_crawl_run_ids_for_draft( $draft );
		if ( \in_array( $raw, $allowed, true ) ) {
			$draft['crawl_run_id_ref'] = $raw;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Crawl run ids that may be stored on the draft (snapshot list plus any existing pinned id so stale refs round-trip).
	 *
	 * @param array<string, mixed> $draft Current draft.
	 * @return list<string>
	 */
	private function list_allowed_crawl_run_ids_for_draft( array $draft ): array {
		$allowed = array();
		if ( $this->container->has( 'crawl_snapshot_service' ) ) {
			$snap = $this->container->get( 'crawl_snapshot_service' );
			if ( $snap instanceof Crawl_Snapshot_Service ) {
				foreach ( $snap->list_sessions( 50 ) as $session ) {
					if ( ! \is_array( $session ) ) {
						continue;
					}
					$rid = isset( $session['crawl_run_id'] ) && \is_string( $session['crawl_run_id'] ) ? $session['crawl_run_id'] : null;
					if ( $rid !== null && $rid !== '' ) {
						$allowed[] = $rid;
					}
				}
			}
		}
		$pinned = isset( $draft['crawl_run_id_ref'] ) && \is_string( $draft['crawl_run_id_ref'] ) ? \trim( $draft['crawl_run_id_ref'] ) : '';
		if ( $pinned !== '' && ! \in_array( $pinned, $allowed, true ) ) {
			$allowed[] = $pinned;
		}
		return $allowed;
	}

	/**
	 * Human-readable label for a crawl run id (timestamp when available).
	 */
	private function format_crawl_run_select_label( string $run_id ): string {
		if ( ! $this->container->has( 'crawl_snapshot_service' ) ) {
			return $run_id;
		}
		$snap = $this->container->get( 'crawl_snapshot_service' );
		if ( ! $snap instanceof Crawl_Snapshot_Service ) {
			return $run_id;
		}
		$sess = $snap->get_session( $run_id );
		if ( ! \is_array( $sess ) ) {
			return $run_id;
		}
		$ended   = isset( $sess['ended_at'] ) && \is_string( $sess['ended_at'] ) ? \trim( $sess['ended_at'] ) : '';
		$started = isset( $sess['started_at'] ) && \is_string( $sess['started_at'] ) ? \trim( $sess['started_at'] ) : '';
		$ts_raw  = $ended !== '' ? $ended : ( $started !== '' ? $started : '' );
		if ( $ts_raw === '' ) {
			return $run_id;
		}
		$ts = \strtotime( $ts_raw );
		if ( $ts === false ) {
			return $run_id . ' — ' . $ts_raw;
		}
		$when = \wp_date( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), $ts );
		return $run_id . ' — ' . $when;
	}

	/**
	 * Persists the site goal / intent string from the submission step into the onboarding draft.
	 *
	 * @param array<string, mixed> $draft Draft (mutated when POST field is present).
	 * @return void
	 */
	private function merge_goal_intent_from_post_into_draft( array &$draft ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_post() before persist.
		if ( ! isset( $_POST['aio_onboarding_goal_or_intent'] ) ) {
			return;
		}
		$draft['goal_or_intent_text'] = \sanitize_textarea_field( \wp_unslash( (string) $_POST['aio_onboarding_goal_or_intent'] ) );
	}

	/**
	 * Hidden field inside the main form so the site goal is POSTed with Save draft / Request plan (visible field is outside the form).
	 *
	 * @param array<string, mixed> $state UI state including `draft`.
	 * @return void
	 */
	private function render_submission_goal_post_field( array $state ): void {
		$draft_for_goal = isset( $state['draft'] ) && is_array( $state['draft'] ) ? $state['draft'] : array();
		$goal_val       = isset( $draft_for_goal['goal_or_intent_text'] ) && is_string( $draft_for_goal['goal_or_intent_text'] ) ? $draft_for_goal['goal_or_intent_text'] : '';
		?>
		<textarea
			name="aio_onboarding_goal_or_intent"
			id="<?php echo \esc_attr( self::SUBMISSION_GOAL_POST_FIELD_ID ); ?>"
			class="screen-reader-text"
			tabindex="-1"
			aria-hidden="true"
			rows="1"
			cols="1"
			style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;"
		><?php echo \esc_textarea( $goal_val ); ?></textarea>
		<?php
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
		if ( $current !== Onboarding_Step_Keys::TEMPLATE_PREFERENCES || ! $this->container->has( 'profile_store' ) ) {
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
		$this->debug_onboarding_line( 'persist template_preference_profile saved' );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Persists industry profile and question-pack answers from POST when industry_profile_store and registry are available (industry-question-pack-contract).
	 *
	 * @return void
	 */
	private function persist_industry_profile_from_post(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_post() before this method is called.
		if ( ! $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE )
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
	 * Hidden inputs so Review / Submission actions re-post profile data (step panels are outside the action form).
	 *
	 * @param array<string, mixed> $state UI state with prefill.profile.
	 */
	private function render_wizard_hidden_profile_carryover_fields( array $state ): void {
		$prefill = $state['prefill'] ?? array();
		$profile = isset( $prefill['profile'] ) && is_array( $prefill['profile'] ) ? $prefill['profile'] : array();
		$biz     = isset( $profile[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $profile[ Profile_Schema::ROOT_BUSINESS ] )
			? $profile[ Profile_Schema::ROOT_BUSINESS ] : array();
		$brand   = isset( $profile[ Profile_Schema::ROOT_BRAND ] ) && is_array( $profile[ Profile_Schema::ROOT_BRAND ] )
			? $profile[ Profile_Schema::ROOT_BRAND ] : array();

		$biz_fields = array(
			'aio_bp_biz_name'            => 'business_name',
			'aio_bp_biz_type'            => 'business_type',
			'aio_bp_biz_contact_goals'   => 'preferred_contact_or_conversion_goals',
			'aio_bp_biz_value_prop'      => 'value_proposition_notes',
			'aio_bp_biz_differentiators' => 'major_differentiators',
			'aio_bp_biz_target_audience' => 'target_audience_summary',
			'aio_bp_biz_primary_offers'  => 'primary_offers_summary',
			'aio_bp_biz_priorities'      => 'strategic_priorities',
			'aio_bp_biz_compliance'      => 'compliance_or_legal_notes',
			'aio_bp_biz_geo_market'      => 'core_geographic_market',
			'aio_bp_biz_marketing_lang'  => 'existing_marketing_language',
			'aio_bp_biz_seasonality'     => 'seasonality',
			'aio_bp_biz_visual_ref'      => 'visual_inspiration_references',
			'aio_bp_biz_sales_process'   => 'internal_sales_process_notes',
		);
		foreach ( $biz_fields as $post_key => $field ) {
			$val = isset( $biz[ $field ] ) && is_string( $biz[ $field ] ) ? $biz[ $field ] : '';
			echo '<input type="hidden" name="' . \esc_attr( $post_key ) . '" value="' . \esc_attr( $val ) . '" />';
		}
		$site_url = isset( $biz['current_site_url'] ) && is_string( $biz['current_site_url'] ) ? $biz['current_site_url'] : '';
		echo '<input type="hidden" name="aio_bp_biz_url" value="' . \esc_attr( $site_url ) . '" />';

		$brand_text = array(
			'aio_bp_brand_positioning'      => 'brand_positioning_summary',
			'aio_bp_brand_voice'            => 'brand_voice_summary',
			'aio_bp_brand_cta_style'        => 'preferred_cta_style',
			'aio_bp_brand_additional_rules' => 'additional_brand_rules',
			'aio_bp_brand_content_restrict' => 'content_restrictions',
		);
		foreach ( $brand_text as $post_key => $field ) {
			$val = isset( $brand[ $field ] ) && is_string( $brand[ $field ] ) ? $brand[ $field ] : '';
			echo '<input type="hidden" name="' . \esc_attr( $post_key ) . '" value="' . \esc_attr( $val ) . '" />';
		}
		$voice_tone = isset( $brand[ Profile_Schema::BRAND_VOICE_TONE ] ) && is_array( $brand[ Profile_Schema::BRAND_VOICE_TONE ] )
			? $brand[ Profile_Schema::BRAND_VOICE_TONE ] : array();
		$formality  = isset( $voice_tone['formality_level'] ) && is_string( $voice_tone['formality_level'] ) ? $voice_tone['formality_level'] : '';
		$clarity    = isset( $voice_tone['clarity_vs_sophistication'] ) && is_string( $voice_tone['clarity_vs_sophistication'] ) ? $voice_tone['clarity_vs_sophistication'] : '';
		$emotional  = isset( $voice_tone['emotional_positioning'] ) && is_string( $voice_tone['emotional_positioning'] ) ? $voice_tone['emotional_positioning'] : '';
		echo '<input type="hidden" name="aio_bp_brand_formality" value="' . \esc_attr( $formality ) . '" />';
		echo '<input type="hidden" name="aio_bp_brand_clarity" value="' . \esc_attr( $clarity ) . '" />';
		echo '<input type="hidden" name="aio_bp_brand_emotional_pos" value="' . \esc_attr( $emotional ) . '" />';
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_ui_state(): array {
		if ( $this->container->has( 'onboarding_ui_state_builder' ) ) {
			try {
				$builder = $this->container->get( 'onboarding_ui_state_builder' );
				return $builder->build_for_screen();
			} catch ( \Throwable $e ) {
				Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_ONBOARDING_UI_STATE_ERROR, $e->getMessage() );
				return $this->minimal_state();
			}
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
				'key'         => $key,
				'label'       => $labels[ $key ] ?? $key,
				'status'      => $draft['step_statuses'][ $key ] ?? Onboarding_Statuses::STEP_NOT_STARTED,
				'is_current'  => $key === $draft['current_step_key'],
				'is_jumpable' => false,
			);
		}
		$ufs = Onboarding_User_Facing_Status::resolve( $draft, (string) ( $draft['current_step_key'] ?? Onboarding_Step_Keys::WELCOME ), false );
		$cc  = Onboarding_Crawl_Context_Phase::summarize( array( 'latest_crawl_run_id' => null ), null );
		return array(
			'current_step_key'    => $draft['current_step_key'],
			'steps'               => $steps,
			'overall_status'      => $draft['overall_status'],
			'is_blocked'          => false,
			'blockers'            => array(),
			'review_advisories'   => array(),
			'user_facing_status'  => $ufs,
			'prefill'             => array(
				'profile'                        => array(),
				'current_site_url'               => '',
				'crawl_run_ids'                  => array(),
				'latest_crawl_run_id'            => null,
				'latest_crawl_session_timestamp' => null,
				'provider_refs'                  => array(),
			),
			'draft'               => $draft,
			'nonce'               => \wp_create_nonce( self::NONCE_ACTION ),
			'nonce_action'        => self::NONCE_ACTION,
			'can_save_draft'      => true,
			'resume_message'      => '',
			'is_provider_ready'   => false,
			'submission_warnings' => array(),
			'crawl_context'       => $cc,
		);
	}

	/**
	 * @param array<string, mixed> $state Onboarding state (steps, blockers, nonce, etc.).
	 * @param bool                 $embed_in_hub When true, top-level wrap/h1 are omitted; hub-embed inner wrapper is used (hub provides outer chrome).
	 * @return void
	 */
	private function render_shell( array $state, bool $embed_in_hub = false ): void {
		$current_step_key = $state['current_step_key'] ?? Onboarding_Step_Keys::WELCOME;
		if ( ! in_array( $current_step_key, Onboarding_Step_Keys::ordered(), true ) ) {
			$current_step_key = Onboarding_Step_Keys::WELCOME;
		}
		$steps = $state['steps'] ?? array();
		if ( ! is_array( $steps ) || count( $steps ) === 0 ) {
			$steps = $this->minimal_state()['steps'];
		}
		$is_blocked              = ! empty( $state['is_blocked'] );
		$blockers                = $state['blockers'] ?? array();
		$resume_message          = $state['resume_message'] ?? '';
		$nonce                   = $state['nonce'] ?? '';
		$nonce_action            = $state['nonce_action'] ?? self::NONCE_ACTION;
		$saved                   = isset( $_GET['saved'] ) && $_GET['saved'] === '1';
		$advance_validation_msgs = array();
		if ( isset( $_GET['onboarding_validation'] ) && (string) $_GET['onboarding_validation'] === '1' ) {
			$vkey = 'aio_onboarding_advance_validation_' . (string) \get_current_user_id();
			$vraw = \get_transient( $vkey );
			if ( is_array( $vraw ) ) {
				foreach ( $vraw as $m ) {
					if ( is_string( $m ) && $m !== '' ) {
						$advance_validation_msgs[] = $m;
					}
				}
			}
			\delete_transient( $vkey );
		}
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
		<style>
			.aio-onboarding .aio-onboarding-skip-link.screen-reader-text:focus {
				background: #f0f0f1;
				border: 1px solid #4f94d4;
				border-radius: 3px;
				box-shadow: 0 0 2px 2px rgba(79, 148, 212, 0.4);
				clip: auto !important;
				clip-path: none;
				color: #1d2327;
				display: block;
				font-size: 14px;
				font-weight: 600;
				height: auto;
				left: 8px;
				line-height: 1.5;
				padding: 12px 16px;
				text-decoration: none;
				top: 8px;
				width: auto;
				z-index: 100000;
			}
			.aio-onboarding .aio-onboarding-step-link:focus-visible {
				outline: 2px solid #2271b1;
				outline-offset: 2px;
				border-radius: 2px;
			}
		</style>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-onboarding aio-onboarding--stripe" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<a href="#<?php echo \esc_attr( self::STEP_CONTENT_REGION_ID ); ?>" class="screen-reader-text aio-onboarding-skip-link"><?php \esc_html_e( 'Skip to step content', 'aio-page-builder' ); ?></a>
		<?php else : ?>
		<div class="aio-page-builder-screen aio-onboarding aio-onboarding--hub-embed aio-onboarding--stripe" role="region" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<a href="#<?php echo \esc_attr( self::STEP_CONTENT_REGION_ID ); ?>" class="screen-reader-text aio-onboarding-skip-link"><?php \esc_html_e( 'Skip to step content', 'aio-page-builder' ); ?></a>
		<?php endif; ?>

			<?php
			$ufs_banner = isset( $state['user_facing_status'] ) && is_array( $state['user_facing_status'] ) ? $state['user_facing_status'] : null;
			if ( $ufs_banner !== null && isset( $ufs_banner['label'], $ufs_banner['hint'] ) && is_string( $ufs_banner['label'] ) && is_string( $ufs_banner['hint'] ) ) :
				?>
			<div class="notice aio-onboarding-ufs-banner" role="status" aria-live="polite" style="margin:1em 0;">
				<p><strong><?php echo \esc_html( $ufs_banner['label'] ); ?></strong> — <?php echo \esc_html( $ufs_banner['hint'] ); ?></p>
			</div>
			<?php endif; ?>

			<?php $this->render_ai_provider_capability_summary_if_available(); ?>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible" role="status">
					<p><?php \esc_html_e( 'Draft saved. You can return later to continue.', 'aio-page-builder' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( count( $advance_validation_msgs ) > 0 ) : ?>
				<div class="notice notice-warning" role="alert">
					<p><strong><?php \esc_html_e( 'Complete this step before continuing:', 'aio-page-builder' ); ?></strong></p>
					<ul>
						<?php foreach ( $advance_validation_msgs as $msg ) : ?>
							<li><?php echo \esc_html( $msg ); ?></li>
						<?php endforeach; ?>
					</ul>
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
					<p><strong><?php \esc_html_e( 'Required before planning:', 'aio-page-builder' ); ?></strong></p>
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
						<li class="aio-onboarding-step aio-step-<?php echo \esc_attr( $step['status'] ); ?> <?php echo ! empty( $step['is_current'] ) ? ' aio-step-current' : ''; ?><?php echo ! empty( $step['is_jumpable'] ) ? ' aio-onboarding-step--interactive' : ''; ?>">
							<?php if ( ! empty( $step['is_jumpable'] ) ) : ?>
								<form method="post" action="" class="aio-onboarding-step-jump">
									<?php \wp_nonce_field( $nonce_action, self::NONCE_ACTION ); ?>
									<input type="hidden" name="aio_onboarding_action" value="goto_step" />
									<input type="hidden" name="aio_onboarding_target_step" value="<?php echo \esc_attr( $step['key'] ); ?>" />
									<button type="submit" class="aio-onboarding-step-link">
										<span class="aio-step-label"><?php echo \esc_html( $step['label'] ); ?></span>
									</button>
								</form>
							<?php else : ?>
								<span class="aio-step-label"><?php echo \esc_html( $step['label'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>

			<section id="<?php echo \esc_attr( self::STEP_CONTENT_REGION_ID ); ?>" class="aio-onboarding-content" aria-labelledby="aio-onboarding-step-heading" tabindex="-1">
				<h2 id="aio-onboarding-step-heading" class="screen-reader-text"><?php \esc_html_e( 'Current step', 'aio-page-builder' ); ?></h2>
				<?php $this->render_step_content( $current_step_key, $state ); ?>
			</section>

			<form method="post" action="" class="aio-onboarding-actions" id="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>">
				<?php \wp_nonce_field( $nonce_action, self::NONCE_ACTION ); ?>
				<?php
				if ( $current_step_key === Onboarding_Step_Keys::REVIEW
					|| $current_step_key === Onboarding_Step_Keys::SUBMISSION ) {
					$this->render_wizard_hidden_profile_carryover_fields( $state );
				}
				if ( $current_step_key === Onboarding_Step_Keys::SUBMISSION ) {
					$this->render_submission_goal_post_field( $state );
				}
				?>
				<p class="submit">
					<?php if ( $current_step_key !== Onboarding_Step_Keys::WELCOME ) : ?>
						<button type="submit" name="aio_onboarding_action" value="go_back" class="button"><?php \esc_html_e( 'Back', 'aio-page-builder' ); ?></button>
					<?php endif; ?>
					<?php if ( $current_step_key !== Onboarding_Step_Keys::SUBMISSION ) : ?>
						<button type="submit" name="aio_onboarding_action" value="save_draft" class="button"><?php \esc_html_e( 'Save draft', 'aio-page-builder' ); ?></button>
						<?php if ( $current_step_key !== Onboarding_Step_Keys::REVIEW || empty( $state['is_blocked'] ) ) : ?>
							<button type="submit" name="aio_onboarding_action" value="advance_step" class="button button-primary">
								<?php
								if ( $current_step_key === Onboarding_Step_Keys::WELCOME ) {
									\esc_html_e( 'Get started', 'aio-page-builder' );
								} else {
									\esc_html_e( 'Next', 'aio-page-builder' );
								}
								?>
							</button>
						<?php endif; ?>
					<?php else : ?>
						<button type="submit" name="aio_onboarding_action" value="save_draft" class="button"><?php \esc_html_e( 'Save draft', 'aio-page-builder' ); ?></button>
						<?php if ( ! empty( $state['is_blocked'] ) ) : ?>
							<p class="aio-onboarding-ready"><?php \esc_html_e( 'Complete the required steps above before requesting a plan.', 'aio-page-builder' ); ?></p>
						<?php else : ?>
							<button type="submit" name="aio_onboarding_action" value="submit_planning_request" class="button button-primary" onclick="return window.confirm(<?php echo \wp_json_encode( __( 'Send a planning request to your AI provider using this profile and context? External API usage may apply charges according to your provider account.', 'aio-page-builder' ) ); ?>);"><?php \esc_html_e( 'Request AI plan', 'aio-page-builder' ); ?></button>
						<?php endif; ?>
					<?php endif; ?>
				</p>
				<?php if ( $current_step_key === Onboarding_Step_Keys::SUBMISSION ) : ?>
					<script>
					(function () {
						var v = document.getElementById( <?php echo \wp_json_encode( self::SUBMISSION_GOAL_VISIBLE_FIELD_ID ); ?> );
						var p = document.getElementById( <?php echo \wp_json_encode( self::SUBMISSION_GOAL_POST_FIELD_ID ); ?> );
						var f = document.getElementById( <?php echo \wp_json_encode( self::MAIN_FORM_ID ); ?> );
						if ( ! v || ! p || ! f ) { return; }
						function sync() { p.value = v.value; }
						v.addEventListener( 'input', sync );
						v.addEventListener( 'change', sync );
						f.addEventListener( 'submit', sync );
					})();
					</script>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders step content for the current onboarding step.
	 * Business profile and site-context steps display navigation guidance; template preferences,
	 * provider setup, review, and submission steps render their respective controls.
	 *
	 * @param string               $current_step_key Current step identifier.
	 * @param array<string, mixed> $state            Onboarding state (prefill, provider refs, etc.).
	 * @return void
	 */
	private function render_step_content( string $current_step_key, array $state ): void {
		$labels            = Onboarding_UI_State_Builder::step_labels();
		$label             = $labels[ $current_step_key ] ?? $current_step_key;
		$prefill           = $state['prefill'] ?? array();
		$provider_refs     = $prefill['provider_refs'] ?? array();
		$is_provider_ready = ! empty( $state['is_provider_ready'] );
		?>
		<div class="aio-onboarding-step-panel" data-step="<?php echo \esc_attr( $current_step_key ); ?>">
			<h3><?php echo \esc_html( $label ); ?></h3>
			<?php if ( $current_step_key === Onboarding_Step_Keys::WELCOME ) : ?>
				<?php $this->render_onboarding_welcome_step( $state ); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::PROVIDER_SETUP ) : ?>
				<p><?php \esc_html_e( 'Configure at least one AI provider (API key) to use AI planning. Credentials are stored securely and never shown in full after save.', 'aio-page-builder' ); ?></p>
				<p class="description"><?php \esc_html_e( 'You can finish other profile steps first. Provider setup becomes required when you reach Review and Submission.', 'aio-page-builder' ); ?></p>
				<p><?php \esc_html_e( 'Current readiness:', 'aio-page-builder' ); ?> <strong><?php echo $is_provider_ready ? \esc_html__( 'At least one provider is marked configured.', 'aio-page-builder' ) : \esc_html__( 'No provider configured yet.', 'aio-page-builder' ); ?></strong></p>
				<details class="aio-onboarding-api-key-guide-disclosure"<?php echo $is_provider_ready ? '' : ' open'; ?>>
					<summary class="aio-onboarding-embed-summary" style="cursor:pointer;padding:0.35rem 0;">
						<strong><?php \esc_html_e( 'How to get an API key', 'aio-page-builder' ); ?></strong>
						<span class="description"> — <?php \esc_html_e( 'Expand for step-by-step setup and provider links.', 'aio-page-builder' ); ?></span>
					</summary>
					<div class="aio-onboarding-api-key-guide" role="region" aria-labelledby="aio-api-key-guide-heading" style="margin-top:0.65rem;">
						<h4 id="aio-api-key-guide-heading" class="screen-reader-text"><?php \esc_html_e( 'How to get an API key', 'aio-page-builder' ); ?></h4>
						<ol class="aio-onboarding-numbered-steps">
							<li><?php \esc_html_e( 'Open the AI area of this plugin and go to the Providers tab (same controls are embedded below).', 'aio-page-builder' ); ?></li>
							<li><?php \esc_html_e( 'Choose a provider (for example OpenAI). In another browser tab, sign in to that provider’s developer console and create a new secret API key with permission to use chat/completions.', 'aio-page-builder' ); ?></li>
							<li><?php \esc_html_e( 'Copy the key, return here, paste it into the provider’s API key field, and save. Use “Test connection” if the screen offers it.', 'aio-page-builder' ); ?></li>
							<li><?php \esc_html_e( 'Never share keys in support tickets or screenshots; rotate a key if it may have leaked.', 'aio-page-builder' ); ?></li>
						</ol>
						<p class="description">
							<?php
							$openai_keys = 'https://platform.openai.com/api-keys';
							?>
							<a href="<?php echo \esc_url( $openai_keys ); ?>" target="_blank" rel="noopener noreferrer"><?php \esc_html_e( 'OpenAI API keys (opens in a new tab)', 'aio-page-builder' ); ?></a>
						</p>
					</div>
				</details>
				<?php $this->render_embedded_ai_providers_setup( $state ); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::SUBMISSION ) : ?>
				<?php
				$submission_warnings = isset( $state['submission_warnings'] ) && is_array( $state['submission_warnings'] ) ? $state['submission_warnings'] : array();
				$goal_desc_ids       = array( self::SUBMISSION_GOAL_HELP_ID );
				$warn_count          = 0;
				foreach ( $submission_warnings as $w ) {
					if ( is_array( $w ) && isset( $w['message'] ) && (string) $w['message'] !== '' ) {
						++$warn_count;
					}
				}
				if ( $warn_count > 0 ) {
					$goal_desc_ids[] = self::SUBMISSION_WARNINGS_GROUP_ID;
				}
				$goal_aria_desc = implode( ' ', $goal_desc_ids );
				?>
				<?php if ( ! $is_provider_ready ) : ?>
					<div class="notice notice-warning inline" role="status">
						<p><?php \esc_html_e( 'Configure an AI provider before you can request a plan.', 'aio-page-builder' ); ?></p>
					</div>
					<?php $this->render_embedded_ai_providers_setup( $state ); ?>
				<?php endif; ?>
				<p><?php \esc_html_e( 'Request an AI-generated plan from your profile and context. When the run completes, your onboarding Build Plan is filled with the AI output and opened for review.', 'aio-page-builder' ); ?></p>
				<p class="description"><strong><?php \esc_html_e( 'API usage', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( Build_Plan_Schema::DEFAULT_ONBOARDING_AI_COST_USD_NOTE ); ?></p>
				<?php
				$prefill_sub  = $state['prefill'] ?? array();
				$crawl_id_sub = isset( $prefill_sub['latest_crawl_run_id'] ) && is_string( $prefill_sub['latest_crawl_run_id'] ) ? trim( $prefill_sub['latest_crawl_run_id'] ) : '';
				$last_run_id  = $state['last_planning_run_id'] ?? null;
				?>
				<div class="aio-onboarding-preflight" role="region" aria-labelledby="aio-preflight-heading" style="margin:1em 0;padding:0.75rem 1rem;border:1px solid #c3c4c7;border-radius:6px;background:#fcfcfc;">
					<p id="aio-preflight-heading" style="margin:0 0 0.5rem;"><strong><?php \esc_html_e( 'Planning preflight', 'aio-page-builder' ); ?></strong></p>
					<ul style="margin:0;padding-left:1.2em;">
						<li><?php echo $is_provider_ready ? \esc_html__( 'AI provider: ready.', 'aio-page-builder' ) : \esc_html__( 'AI provider: not ready — configure keys before submitting.', 'aio-page-builder' ); ?></li>
						<li><?php echo $crawl_id_sub !== '' ? \esc_html__( 'Crawl context: latest session will be included when available.', 'aio-page-builder' ) : \esc_html__( 'Crawl context: none linked (optional).', 'aio-page-builder' ); ?></li>
						<li><?php echo ( $last_run_id !== null && is_string( $last_run_id ) && $last_run_id !== '' ) ? \esc_html__( 'Run context: you have a prior planning run on file; this submit starts a new provider request.', 'aio-page-builder' ) : \esc_html__( 'Run context: new planning request.', 'aio-page-builder' ); ?></li>
						<?php if ( $warn_count > 0 ) : ?>
						<li><?php \esc_html_e( 'Notes: review the warnings below before you submit.', 'aio-page-builder' ); ?></li>
						<?php endif; ?>
					</ul>
				</div>
				<?php
				$draft_for_goal = isset( $state['draft'] ) && is_array( $state['draft'] ) ? $state['draft'] : array();
				$goal_val       = isset( $draft_for_goal['goal_or_intent_text'] ) && is_string( $draft_for_goal['goal_or_intent_text'] ) ? $draft_for_goal['goal_or_intent_text'] : '';
				$min_goal       = Onboarding_Planning_Context_Guard::MIN_GOAL_LENGTH;
				?>
				<p>
					<label for="<?php echo \esc_attr( self::SUBMISSION_GOAL_VISIBLE_FIELD_ID ); ?>"><strong><?php \esc_html_e( 'Site goal and scope', 'aio-page-builder' ); ?></strong></label>
				</p>
				<p class="description" id="<?php echo \esc_attr( self::SUBMISSION_GOAL_HELP_ID ); ?>">
					<?php
					echo \esc_html(
						sprintf(
							/* translators: %d: minimum character count for the site goal field */
							__( 'Describe the overhaul or new site in detail (at least %d characters). Include audiences, offers, and any must-have pages so the planner can propose a full sitemap and template reuse.', 'aio-page-builder' ),
							$min_goal
						)
					);
					?>
				</p>
				<p>
					<textarea
						id="<?php echo \esc_attr( self::SUBMISSION_GOAL_VISIBLE_FIELD_ID ); ?>"
						class="large-text"
						rows="8"
						aria-describedby="<?php echo \esc_attr( $goal_aria_desc ); ?>"
					><?php echo \esc_textarea( $goal_val ); ?></textarea>
				</p>
				<?php if ( $warn_count > 0 ) : ?>
				<div id="<?php echo \esc_attr( self::SUBMISSION_WARNINGS_GROUP_ID ); ?>" role="group" aria-label="<?php \esc_attr_e( 'Submission notices', 'aio-page-builder' ); ?>">
					<?php
					foreach ( $submission_warnings as $warning ) :
						if ( ! is_array( $warning ) || ! isset( $warning['message'] ) || (string) $warning['message'] === '' ) {
							continue;
						}
						?>
				<div class="notice notice-warning" role="status"><p><?php echo \esc_html( (string) $warning['message'] ); ?></p></div>
						<?php
					endforeach;
					?>
				</div>
				<?php endif; ?>
				<?php
				$last_run_id      = $state['last_planning_run_id'] ?? null;
				$last_run_post_id = $state['last_planning_run_post_id'] ?? null;
				if ( $last_run_id !== null && $last_run_post_id !== null && (int) $last_run_post_id > 0 ) :
					$run_url = Admin_Screen_Hub::tab_url(
						AI_Runs_Screen::HUB_PAGE_SLUG,
						'ai_runs',
						array( 'run_id' => (string) $last_run_id )
					);
					?>
					<p><?php \esc_html_e( 'Last run:', 'aio-page-builder' ); ?> <a href="<?php echo \esc_url( $run_url ); ?>"><?php echo \esc_html( $last_run_id ); ?></a></p>
				<?php endif; ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::BUSINESS_PROFILE ) : ?>
				<?php $this->render_business_profile_step( $state ); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::BRAND_PROFILE ) : ?>
				<?php $this->render_brand_profile_step( $state ); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::AUDIENCE_OFFERS ) : ?>
				<?php $this->render_audience_offers_step( $state ); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::GEOGRAPHY_COMPETITORS ) : ?>
				<?php $this->render_geography_competitors_step( $state ); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::ASSET_INTAKE ) : ?>
				<?php $this->render_asset_intake_step( $state ); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::EXISTING_SITE ) : ?>
				<?php $this->render_existing_site_step( $state ); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::CRAWL_PREFERENCES ) : ?>
				<?php $this->render_crawl_preferences_step( $state ); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::TEMPLATE_PREFERENCES ) : ?>
				<?php $this->render_template_preferences_step( $state ); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::REVIEW ) : ?>
				<?php $this->render_review_step( $state ); ?>
			<?php else : ?>
				<p><?php \esc_html_e( 'This step could not be loaded. Use Next or Save draft after refreshing; if the problem persists, clear the onboarding draft from settings or contact support.', 'aio-page-builder' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders the welcome / overview step: README-style orientation, prerequisites, and step map.
	 *
	 * @param array<string, mixed> $state UI state including draft and build_plan_lineages.
	 * @return void
	 */
	private function render_onboarding_welcome_step( array $state ): void {
		$labels   = Onboarding_UI_State_Builder::step_labels();
		$ordered  = Onboarding_Step_Keys::ordered();
		$draft    = isset( $state['draft'] ) && is_array( $state['draft'] ) ? $state['draft'] : array();
		$lineages = isset( $state['build_plan_lineages'] ) && is_array( $state['build_plan_lineages'] ) ? $state['build_plan_lineages'] : array();
		$mode     = isset( $draft['build_plan_lineage_mode'] ) && $draft['build_plan_lineage_mode'] === 'fork' ? 'fork' : 'new';
		$fork_lid = isset( $draft['fork_lineage_id'] ) && is_string( $draft['fork_lineage_id'] ) ? $draft['fork_lineage_id'] : '';
		$fork_p   = isset( $draft['fork_version_purpose'] ) && is_string( $draft['fork_version_purpose'] ) ? $draft['fork_version_purpose'] : '';
		$form_id  = self::MAIN_FORM_ID;
		?>
		<div class="aio-onboarding-welcome">
			<div class="aio-onboarding-welcome-hero">
				<p class="aio-onboarding-welcome-kicker"><?php \esc_html_e( 'Orientation', 'aio-page-builder' ); ?></p>
				<p class="aio-onboarding-welcome-lead">
					<?php \esc_html_e( 'This wizard builds a structured profile of your business, brand, and site. When you click Get started, a Build Plan is created for this run and kept updated with your answers until you request an AI plan—then you are taken to that plan.', 'aio-page-builder' ); ?>
				</p>
			</div>

			<section class="aio-onboarding-welcome-card aio-onboarding-build-plan-choice" aria-labelledby="aio-welcome-bp-heading">
				<h4 id="aio-welcome-bp-heading" class="aio-onboarding-welcome-card-title"><?php \esc_html_e( 'Build Plan for this run', 'aio-page-builder' ); ?></h4>
				<p class="description">
					<?php echo \esc_html( Build_Plan_Schema::DEFAULT_ONBOARDING_AI_COST_USD_NOTE ); ?>
					<?php \esc_html_e( ' Each new version you add should include a short purpose so your team knows why it exists.', 'aio-page-builder' ); ?>
				</p>
				<fieldset class="aio-onboarding-fieldset">
					<legend class="screen-reader-text"><?php \esc_html_e( 'New plan or continue existing', 'aio-page-builder' ); ?></legend>
					<p>
						<label>
							<input type="radio" name="aio_onboarding_build_plan_mode" value="new" form="<?php echo \esc_attr( $form_id ); ?>" <?php \checked( $mode, 'new' ); ?> />
							<?php \esc_html_e( 'Start a new Build Plan lineage (version 1.0)', 'aio-page-builder' ); ?>
						</label>
					</p>
					<p>
						<label>
							<input type="radio" name="aio_onboarding_build_plan_mode" value="fork" form="<?php echo \esc_attr( $form_id ); ?>" <?php \checked( $mode, 'fork' ); ?> <?php echo count( $lineages ) === 0 ? 'disabled' : ''; ?> />
							<?php \esc_html_e( 'Continue an existing plan — create the next version (e.g. 2.0)', 'aio-page-builder' ); ?>
						</label>
					</p>
					<?php if ( count( $lineages ) === 0 ) : ?>
						<p class="description"><?php \esc_html_e( 'No prior onboarded plan lineages found yet. Complete onboarding once to fork later.', 'aio-page-builder' ); ?></p>
					<?php else : ?>
						<p>
							<label for="aio_onboarding_fork_lineage_id"><strong><?php \esc_html_e( 'Existing plan', 'aio-page-builder' ); ?></strong></label>
							<select name="aio_onboarding_fork_lineage_id" id="aio_onboarding_fork_lineage_id" class="widefat" form="<?php echo \esc_attr( $form_id ); ?>">
								<option value=""><?php \esc_html_e( 'Select…', 'aio-page-builder' ); ?></option>
								<?php foreach ( $lineages as $row ) : ?>
									<?php
									if ( ! is_array( $row ) ) {
										continue;
									}
									$lid = isset( $row['lineage_id'] ) ? (string) $row['lineage_id'] : '';
									$ttl = isset( $row['display_title'] ) ? (string) $row['display_title'] : $lid;
									$cnt = isset( $row['version_count'] ) ? (int) $row['version_count'] : 0;
									?>
									<option value="<?php echo \esc_attr( $lid ); ?>" <?php \selected( $fork_lid, $lid ); ?>>
										<?php echo \esc_html( $ttl . ' (' . sprintf( /* translators: %d: version count */ \_n( '%d version', '%d versions', $cnt, 'aio-page-builder' ), $cnt ) . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</p>
						<p>
							<label for="aio_onboarding_fork_version_purpose"><strong><?php \esc_html_e( 'Purpose of this new version', 'aio-page-builder' ); ?></strong></label>
							<span class="description"><?php \esc_html_e( 'Required when continuing a plan. Example: “Rebuild services hierarchy after rebrand.”', 'aio-page-builder' ); ?></span>
						</p>
						<p>
							<textarea class="large-text" name="aio_onboarding_fork_version_purpose" id="aio_onboarding_fork_version_purpose" rows="3" form="<?php echo \esc_attr( $form_id ); ?>"><?php echo \esc_textarea( $fork_p ); ?></textarea>
						</p>
					<?php endif; ?>
				</fieldset>
			</section>

			<div class="aio-onboarding-welcome-columns">
				<section class="aio-onboarding-welcome-card" aria-labelledby="aio-welcome-why-heading">
					<h4 id="aio-welcome-why-heading" class="aio-onboarding-welcome-card-title"><?php \esc_html_e( 'Why this exists', 'aio-page-builder' ); ?></h4>
					<p class="aio-onboarding-welcome-card-text">
						<?php \esc_html_e( 'AI outputs are only as good as the context you supply. We separate onboarding into short steps so required fields are explicit, optional context is optional, and nothing important is skipped by accident.', 'aio-page-builder' ); ?>
					</p>
				</section>
				<section class="aio-onboarding-welcome-card" aria-labelledby="aio-welcome-need-heading">
					<h4 id="aio-welcome-need-heading" class="aio-onboarding-welcome-card-title"><?php \esc_html_e( 'Before you start', 'aio-page-builder' ); ?></h4>
					<ul class="aio-onboarding-welcome-list">
						<li><?php \esc_html_e( 'Permission to save an AI provider API key (or a teammate who can).', 'aio-page-builder' ); ?></li>
						<li><?php \esc_html_e( 'Rough answers for business name, type, audience, offers, geography, and brand voice—estimates are fine.', 'aio-page-builder' ); ?></li>
						<li><?php \esc_html_e( 'Optional: your live site URL and crawl access if you want crawl-backed context.', 'aio-page-builder' ); ?></li>
						<li><?php \esc_html_e( 'About 15–30 minutes if you fill everything in one pass; longer if you pause and return.', 'aio-page-builder' ); ?></li>
					</ul>
				</section>
			</div>

			<section class="aio-onboarding-welcome-flow" aria-labelledby="aio-welcome-flow-heading">
				<h4 id="aio-welcome-flow-heading" class="aio-onboarding-welcome-section-title"><?php \esc_html_e( 'What happens when you click Next', 'aio-page-builder' ); ?></h4>
				<ol class="aio-onboarding-welcome-flow-steps">
					<li><?php \esc_html_e( 'Profile steps capture business, brand, audience, geography, assets, and your existing site story.', 'aio-page-builder' ); ?></li>
					<li><?php \esc_html_e( 'Crawl preferences and embedded crawler tools (when available) help align automated discovery with how you work.', 'aio-page-builder' ); ?></li>
					<li><?php \esc_html_e( 'AI Provider setup stores keys in segregated storage—never echoed in full after save.', 'aio-page-builder' ); ?></li>
					<li><?php \esc_html_e( 'Template preferences add advisory signals for page and section style.', 'aio-page-builder' ); ?></li>
					<li><?php \esc_html_e( 'Review checks required data; Submission runs the AI planner and opens your linked Build Plan when the run completes.', 'aio-page-builder' ); ?></li>
				</ol>
			</section>

			<section class="aio-onboarding-welcome-map" aria-labelledby="aio-welcome-map-heading">
				<h4 id="aio-welcome-map-heading" class="aio-onboarding-welcome-section-title"><?php \esc_html_e( 'The twelve steps at a glance', 'aio-page-builder' ); ?></h4>
				<p class="aio-onboarding-welcome-map-intro"><?php \esc_html_e( 'Completed steps show green when data validates; amber means you visited but something is still missing. Use the stepper to jump back to any step you have already opened.', 'aio-page-builder' ); ?></p>
				<ul class="aio-onboarding-welcome-step-grid" role="list">
					<?php
					$n = 0;
					foreach ( $ordered as $key ) {
						++$n;
						$step_label = isset( $labels[ $key ] ) ? (string) $labels[ $key ] : $key;
						?>
					<li class="aio-onboarding-welcome-step-tile" role="listitem">
						<span class="aio-onboarding-welcome-step-num" aria-hidden="true"><?php echo \esc_html( (string) $n ); ?></span>
						<span class="aio-onboarding-welcome-step-name"><?php echo \esc_html( $step_label ); ?></span>
					</li>
						<?php
					}
					?>
				</ul>
			</section>

			<div class="aio-onboarding-welcome-footnotes">
				<p><strong><?php \esc_html_e( 'Save draft', 'aio-page-builder' ); ?></strong> — <?php \esc_html_e( 'persists wizard position and merges any fields on the current screen into your stored profile (where applicable).', 'aio-page-builder' ); ?></p>
				<p><strong><?php \esc_html_e( 'Next', 'aio-page-builder' ); ?></strong> — <?php \esc_html_e( 'validates the current step’s required fields before advancing; you will see errors inline if something is missing.', 'aio-page-builder' ); ?></p>
				<p class="aio-onboarding-welcome-privacy"><?php \esc_html_e( 'Secrets stay server-side; onboarding never prints full API keys. Operational reporting, if enabled, is disclosed in plugin documentation and settings.', 'aio-page-builder' ); ?></p>
			</div>
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
		$has_extra_signals  = $proof_style !== '' || $content_density !== '' || $animation !== '' || $cta_intensity !== '' || $reduced_motion;
		?>
		<p><?php \esc_html_e( 'These preferences help guide template and page-style recommendations. They are advisory only and do not override structural or CTA rules.', 'aio-page-builder' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aio_template_preference_page_emphasis"><?php \esc_html_e( 'Page emphasis', 'aio-page-builder' ); ?></label></th>
				<td>
					<select name="aio_template_preference_page_emphasis" id="aio_template_preference_page_emphasis" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" aria-describedby="aio-page-emphasis-desc">
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
					<select name="aio_template_preference_conversion_posture" id="aio_template_preference_conversion_posture" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>">
						<option value="" <?php selected( $conversion_posture, '' ); ?>><?php \esc_html_e( 'Not specified', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CONVERSION_POSTURE_SOFT ); ?>" <?php selected( $conversion_posture, Template_Preference_Profile::CONVERSION_POSTURE_SOFT ); ?>><?php \esc_html_e( 'Soft', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CONVERSION_POSTURE_MODERATE ); ?>" <?php selected( $conversion_posture, Template_Preference_Profile::CONVERSION_POSTURE_MODERATE ); ?>><?php \esc_html_e( 'Moderate', 'aio-page-builder' ); ?></option>
						<option value="<?php echo \esc_attr( Template_Preference_Profile::CONVERSION_POSTURE_STRONG ); ?>" <?php selected( $conversion_posture, Template_Preference_Profile::CONVERSION_POSTURE_STRONG ); ?>><?php \esc_html_e( 'Strong', 'aio-page-builder' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<details class="aio-onboarding-embed-disclosure"<?php echo $has_extra_signals ? ' open' : ''; ?>>
			<summary class="aio-onboarding-embed-summary" style="cursor:pointer;padding:0.5rem 0;">
				<strong><?php \esc_html_e( 'Additional template signals', 'aio-page-builder' ); ?></strong>
				<span class="description"> — <?php \esc_html_e( 'Proof, density, motion, CTA intensity, and reduced motion.', 'aio-page-builder' ); ?></span>
			</summary>
			<div class="aio-onboarding-embed-details-inner" style="margin-top:0.75rem;">
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aio_template_preference_proof_style"><?php \esc_html_e( 'Proof style', 'aio-page-builder' ); ?></label></th>
				<td>
					<select name="aio_template_preference_proof_style" id="aio_template_preference_proof_style" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>">
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
					<select name="aio_template_preference_content_density" id="aio_template_preference_content_density" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>">
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
					<select name="aio_template_preference_animation" id="aio_template_preference_animation" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>">
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
					<select name="aio_template_preference_cta_intensity" id="aio_template_preference_cta_intensity" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>">
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
						<input type="checkbox" name="aio_template_preference_reduced_motion" id="aio_template_preference_reduced_motion" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" value="1" <?php checked( $reduced_motion ); ?> />
						<?php \esc_html_e( 'Prefer reduced motion in templates', 'aio-page-builder' ); ?>
					</label>
				</td>
			</tr>
		</table>
			</div>
		</details>
		<?php
	}
	/**
	 * Persists brand profile fields from POST. Step-agnostic: only writes keys present in POST.
	 * Nonce is verified in handle_post() before this method is called.
	 *
	 * @param array<string, mixed> $draft Current draft (unused; kept for signature consistency).
	 * @return void
	 */
	private function persist_brand_profile_from_post( array $draft ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_post() before this method is called.
		if ( ! $this->container->has( 'profile_store' ) ) {
			$this->debug_onboarding_line( 'persist brand_profile skipped: profile_store unavailable' );
			return;
		}
		$partial     = array();
		$text_fields = array(
			'aio_bp_brand_positioning'      => 'brand_positioning_summary',
			'aio_bp_brand_voice'            => 'brand_voice_summary',
			'aio_bp_brand_cta_style'        => 'preferred_cta_style',
			'aio_bp_brand_additional_rules' => 'additional_brand_rules',
			'aio_bp_brand_content_restrict' => 'content_restrictions',
		);
		foreach ( $text_fields as $post_key => $field ) {
			if ( isset( $_POST[ $post_key ] ) && is_string( $_POST[ $post_key ] ) ) {
				$partial[ $field ] = \sanitize_textarea_field( \wp_unslash( $_POST[ $post_key ] ) );
			}
		}
		$voice_partial = array();
		if ( isset( $_POST['aio_bp_brand_formality'] ) && is_string( $_POST['aio_bp_brand_formality'] ) ) {
			$voice_partial['formality_level'] = \sanitize_text_field( \wp_unslash( $_POST['aio_bp_brand_formality'] ) );
		}
		if ( isset( $_POST['aio_bp_brand_clarity'] ) && is_string( $_POST['aio_bp_brand_clarity'] ) ) {
			$voice_partial['clarity_vs_sophistication'] = \sanitize_text_field( \wp_unslash( $_POST['aio_bp_brand_clarity'] ) );
		}
		if ( isset( $_POST['aio_bp_brand_emotional_pos'] ) && is_string( $_POST['aio_bp_brand_emotional_pos'] ) ) {
			$voice_partial['emotional_positioning'] = \sanitize_textarea_field( \wp_unslash( $_POST['aio_bp_brand_emotional_pos'] ) );
		}
		if ( ! empty( $voice_partial ) ) {
			$partial[ Profile_Schema::BRAND_VOICE_TONE ] = $voice_partial;
		}
		if ( ! empty( $partial ) ) {
			$profile_store = $this->container->get( 'profile_store' );
			if ( $profile_store instanceof \AIOPageBuilder\Domain\Storage\Profile\Profile_Store ) {
				$profile_store->merge_brand_profile( $partial );
				$this->debug_onboarding_line( 'persist brand_profile merged field_count=' . (string) count( $partial ) );
			} else {
				$this->debug_onboarding_line( 'persist brand_profile failed: profile_store not Profile_Store instance' );
			}
		} elseif ( ( $draft['current_step_key'] ?? '' ) === Onboarding_Step_Keys::BRAND_PROFILE ) {
			$this->debug_onboarding_line(
				'persist brand_profile: no aio_bp_* POST fields merged on brand_profile step (inputs may be outside the POST form)'
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Persists business profile fields from POST. Step-agnostic: only writes keys present in POST.
	 * Nonce is verified in handle_post() before this method is called.
	 *
	 * @param array<string, mixed> $draft Current draft (unused; kept for signature consistency).
	 * @return void
	 */
	private function persist_business_profile_from_post( array $draft ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_post() before this method is called.
		if ( ! $this->container->has( 'profile_store' ) ) {
			$this->debug_onboarding_line( 'persist business_profile skipped: profile_store unavailable' );
			return;
		}
		$partial     = array();
		$text_fields = array(
			'aio_bp_biz_name'            => 'business_name',
			'aio_bp_biz_type'            => 'business_type',
			'aio_bp_biz_contact_goals'   => 'preferred_contact_or_conversion_goals',
			'aio_bp_biz_value_prop'      => 'value_proposition_notes',
			'aio_bp_biz_differentiators' => 'major_differentiators',
			'aio_bp_biz_target_audience' => 'target_audience_summary',
			'aio_bp_biz_primary_offers'  => 'primary_offers_summary',
			'aio_bp_biz_priorities'      => 'strategic_priorities',
			'aio_bp_biz_compliance'      => 'compliance_or_legal_notes',
			'aio_bp_biz_geo_market'      => 'core_geographic_market',
			'aio_bp_biz_marketing_lang'  => 'existing_marketing_language',
			'aio_bp_biz_seasonality'     => 'seasonality',
			'aio_bp_biz_visual_ref'      => 'visual_inspiration_references',
			'aio_bp_biz_sales_process'   => 'internal_sales_process_notes',
		);
		foreach ( $text_fields as $post_key => $field ) {
			if ( isset( $_POST[ $post_key ] ) && is_string( $_POST[ $post_key ] ) ) {
				$partial[ $field ] = \sanitize_textarea_field( \wp_unslash( $_POST[ $post_key ] ) );
			}
		}
		if ( isset( $_POST['aio_bp_biz_url'] ) && is_string( $_POST['aio_bp_biz_url'] ) ) {
			$partial['current_site_url'] = \esc_url_raw( \wp_unslash( $_POST['aio_bp_biz_url'] ) );
		}
		if ( ! empty( $partial ) ) {
			$profile_store = $this->container->get( 'profile_store' );
			if ( $profile_store instanceof \AIOPageBuilder\Domain\Storage\Profile\Profile_Store ) {
				$profile_store->merge_business_profile( $partial );
				$this->debug_onboarding_line( 'persist business_profile merged field_count=' . (string) count( $partial ) );
			} else {
				$this->debug_onboarding_line( 'persist business_profile failed: profile_store not Profile_Store instance' );
			}
		} elseif ( ( $draft['current_step_key'] ?? '' ) === Onboarding_Step_Keys::BUSINESS_PROFILE ) {
			$this->debug_onboarding_line(
				'persist business_profile: no aio_bp_* POST fields merged on business_profile step (inputs may be outside the POST form)'
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Renders business profile form fields; prefilled from stored business profile.
	 *
	 * @param array<string, mixed> $state Onboarding UI state.
	 * @return void
	 */
	private function render_business_profile_step( array $state ): void {
		$prefill = $state['prefill'] ?? array();
		$profile = $prefill['profile'] ?? array();
		$biz     = isset( $profile[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $profile[ Profile_Schema::ROOT_BUSINESS ] )
			? $profile[ Profile_Schema::ROOT_BUSINESS ] : array();
		$name    = isset( $biz['business_name'] ) ? (string) $biz['business_name'] : '';
		$type    = isset( $biz['business_type'] ) ? (string) $biz['business_type'] : '';
		$goals   = isset( $biz['preferred_contact_or_conversion_goals'] ) ? (string) $biz['preferred_contact_or_conversion_goals'] : '';
		$value   = isset( $biz['value_proposition_notes'] ) ? (string) $biz['value_proposition_notes'] : '';
		$diff    = isset( $biz['major_differentiators'] ) ? (string) $biz['major_differentiators'] : '';
		?>
		<p><?php \esc_html_e( 'Provide core business information. This data is used as context for AI page planning.', 'aio-page-builder' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aio_bp_biz_name"><?php \esc_html_e( 'Business name', 'aio-page-builder' ); ?></label></th>
				<td><input type="text" name="aio_bp_biz_name" id="aio_bp_biz_name" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="regular-text" value="<?php echo \esc_attr( $name ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_biz_type"><?php \esc_html_e( 'Business type', 'aio-page-builder' ); ?></label></th>
				<td>
					<input type="text" name="aio_bp_biz_type" id="aio_bp_biz_type" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="regular-text" value="<?php echo \esc_attr( $type ); ?>" />
					<p class="description"><?php \esc_html_e( 'e.g. local service, e-commerce, professional services, SaaS', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_biz_contact_goals"><?php \esc_html_e( 'Contact / conversion goals', 'aio-page-builder' ); ?></label></th>
				<td>
					<textarea name="aio_bp_biz_contact_goals" id="aio_bp_biz_contact_goals" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $goals ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Primary actions you want visitors to take (e.g. book a call, request a quote, buy online).', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_biz_value_prop"><?php \esc_html_e( 'Value proposition', 'aio-page-builder' ); ?></label></th>
				<td><textarea name="aio_bp_biz_value_prop" id="aio_bp_biz_value_prop" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $value ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_biz_differentiators"><?php \esc_html_e( 'Main differentiators', 'aio-page-builder' ); ?></label></th>
				<td><textarea name="aio_bp_biz_differentiators" id="aio_bp_biz_differentiators" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $diff ); ?></textarea></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Renders brand profile form fields; prefilled from stored brand profile.
	 *
	 * @param array<string, mixed> $state Onboarding UI state.
	 * @return void
	 */
	private function render_brand_profile_step( array $state ): void {
		$prefill      = $state['prefill'] ?? array();
		$profile      = $prefill['profile'] ?? array();
		$brand        = isset( $profile[ Profile_Schema::ROOT_BRAND ] ) && is_array( $profile[ Profile_Schema::ROOT_BRAND ] )
			? $profile[ Profile_Schema::ROOT_BRAND ] : array();
		$positioning  = isset( $brand['brand_positioning_summary'] ) ? (string) $brand['brand_positioning_summary'] : '';
		$voice_sum    = isset( $brand['brand_voice_summary'] ) ? (string) $brand['brand_voice_summary'] : '';
		$cta_style    = isset( $brand['preferred_cta_style'] ) ? (string) $brand['preferred_cta_style'] : '';
		$extra_rules  = isset( $brand['additional_brand_rules'] ) ? (string) $brand['additional_brand_rules'] : '';
		$restrictions = isset( $brand['content_restrictions'] ) ? (string) $brand['content_restrictions'] : '';
		$voice_tone   = isset( $brand[ Profile_Schema::BRAND_VOICE_TONE ] ) && is_array( $brand[ Profile_Schema::BRAND_VOICE_TONE ] )
			? $brand[ Profile_Schema::BRAND_VOICE_TONE ] : array();
		$formality    = isset( $voice_tone['formality_level'] ) ? (string) $voice_tone['formality_level'] : '';
		$clarity      = isset( $voice_tone['clarity_vs_sophistication'] ) ? (string) $voice_tone['clarity_vs_sophistication'] : '';
		$emotional    = isset( $voice_tone['emotional_positioning'] ) ? (string) $voice_tone['emotional_positioning'] : '';
		?>
		<p><?php \esc_html_e( 'Describe your brand voice, positioning, and content preferences. These guide AI-generated copy style.', 'aio-page-builder' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aio_bp_brand_positioning"><?php \esc_html_e( 'Brand positioning summary', 'aio-page-builder' ); ?></label></th>
				<td><textarea name="aio_bp_brand_positioning" id="aio_bp_brand_positioning" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $positioning ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_brand_voice"><?php \esc_html_e( 'Brand voice summary', 'aio-page-builder' ); ?></label></th>
				<td><textarea name="aio_bp_brand_voice" id="aio_bp_brand_voice" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $voice_sum ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_brand_formality"><?php \esc_html_e( 'Formality level', 'aio-page-builder' ); ?></label></th>
				<td>
					<select name="aio_bp_brand_formality" id="aio_bp_brand_formality" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>">
						<option value="" <?php \selected( $formality, '' ); ?>><?php \esc_html_e( 'Not specified', 'aio-page-builder' ); ?></option>
						<?php foreach ( Profile_Schema::FORMALITY_LEVELS as $level ) : ?>
							<option value="<?php echo \esc_attr( $level ); ?>" <?php \selected( $formality, $level ); ?>><?php echo \esc_html( ucfirst( str_replace( '_', ' ', $level ) ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_brand_clarity"><?php \esc_html_e( 'Clarity vs sophistication', 'aio-page-builder' ); ?></label></th>
				<td>
					<select name="aio_bp_brand_clarity" id="aio_bp_brand_clarity" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>">
						<option value="" <?php \selected( $clarity, '' ); ?>><?php \esc_html_e( 'Not specified', 'aio-page-builder' ); ?></option>
						<?php foreach ( Profile_Schema::CLARITY_VS_SOPHISTICATION as $opt ) : ?>
							<option value="<?php echo \esc_attr( $opt ); ?>" <?php \selected( $clarity, $opt ); ?>><?php echo \esc_html( ucfirst( str_replace( '_', ' ', $opt ) ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_brand_emotional_pos"><?php \esc_html_e( 'Emotional positioning', 'aio-page-builder' ); ?></label></th>
				<td>
					<input type="text" name="aio_bp_brand_emotional_pos" id="aio_bp_brand_emotional_pos" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="regular-text" value="<?php echo \esc_attr( $emotional ); ?>" />
					<p class="description"><?php \esc_html_e( 'e.g. trustworthy, empowering, friendly, authoritative', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_brand_cta_style"><?php \esc_html_e( 'Preferred CTA style', 'aio-page-builder' ); ?></label></th>
				<td><input type="text" name="aio_bp_brand_cta_style" id="aio_bp_brand_cta_style" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="regular-text" value="<?php echo \esc_attr( $cta_style ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_brand_additional_rules"><?php \esc_html_e( 'Additional brand rules', 'aio-page-builder' ); ?></label></th>
				<td><textarea name="aio_bp_brand_additional_rules" id="aio_bp_brand_additional_rules" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $extra_rules ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_brand_content_restrict"><?php \esc_html_e( 'Content restrictions', 'aio-page-builder' ); ?></label></th>
				<td>
					<textarea name="aio_bp_brand_content_restrict" id="aio_bp_brand_content_restrict" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="2"><?php echo \esc_textarea( $restrictions ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Topics, language, or claims to avoid in AI-generated content.', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Renders audience and offers form fields; prefilled from stored business profile.
	 *
	 * @param array<string, mixed> $state Onboarding UI state.
	 * @return void
	 */
	private function render_audience_offers_step( array $state ): void {
		$prefill    = $state['prefill'] ?? array();
		$profile    = $prefill['profile'] ?? array();
		$biz        = isset( $profile[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $profile[ Profile_Schema::ROOT_BUSINESS ] )
			? $profile[ Profile_Schema::ROOT_BUSINESS ] : array();
		$audience   = isset( $biz['target_audience_summary'] ) ? (string) $biz['target_audience_summary'] : '';
		$offers     = isset( $biz['primary_offers_summary'] ) ? (string) $biz['primary_offers_summary'] : '';
		$priorities = isset( $biz['strategic_priorities'] ) ? (string) $biz['strategic_priorities'] : '';
		$compliance = isset( $biz['compliance_or_legal_notes'] ) ? (string) $biz['compliance_or_legal_notes'] : '';
		?>
		<p><?php \esc_html_e( 'Describe your target audience and primary offers. This context shapes page structure and copy priorities.', 'aio-page-builder' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aio_bp_biz_target_audience"><?php \esc_html_e( 'Target audience summary', 'aio-page-builder' ); ?></label></th>
				<td><textarea name="aio_bp_biz_target_audience" id="aio_bp_biz_target_audience" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $audience ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_biz_primary_offers"><?php \esc_html_e( 'Primary offers summary', 'aio-page-builder' ); ?></label></th>
				<td>
					<textarea name="aio_bp_biz_primary_offers" id="aio_bp_biz_primary_offers" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $offers ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Main products or services offered.', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_biz_priorities"><?php \esc_html_e( 'Strategic priorities', 'aio-page-builder' ); ?></label></th>
				<td><textarea name="aio_bp_biz_priorities" id="aio_bp_biz_priorities" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $priorities ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_biz_compliance"><?php \esc_html_e( 'Compliance / legal notes', 'aio-page-builder' ); ?></label></th>
				<td>
					<textarea name="aio_bp_biz_compliance" id="aio_bp_biz_compliance" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="2"><?php echo \esc_textarea( $compliance ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Regulatory requirements or disclaimers that affect content.', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Renders geography and competitive context form fields; prefilled from stored business profile.
	 *
	 * @param array<string, mixed> $state Onboarding UI state.
	 * @return void
	 */
	private function render_geography_competitors_step( array $state ): void {
		$prefill     = $state['prefill'] ?? array();
		$profile     = $prefill['profile'] ?? array();
		$biz         = isset( $profile[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $profile[ Profile_Schema::ROOT_BUSINESS ] )
			? $profile[ Profile_Schema::ROOT_BUSINESS ] : array();
		$geo         = isset( $biz['core_geographic_market'] ) ? (string) $biz['core_geographic_market'] : '';
		$mktg_lang   = isset( $biz['existing_marketing_language'] ) ? (string) $biz['existing_marketing_language'] : '';
		$seasonality = isset( $biz['seasonality'] ) ? (string) $biz['seasonality'] : '';
		?>
		<p><?php \esc_html_e( 'Describe your primary market geography and competitive context. This helps AI planning prioritise relevant content angles.', 'aio-page-builder' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aio_bp_biz_geo_market"><?php \esc_html_e( 'Core geographic market', 'aio-page-builder' ); ?></label></th>
				<td>
					<input type="text" name="aio_bp_biz_geo_market" id="aio_bp_biz_geo_market" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="regular-text" value="<?php echo \esc_attr( $geo ); ?>" />
					<p class="description"><?php \esc_html_e( 'e.g. UK, Greater London, North America, global', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_biz_marketing_lang"><?php \esc_html_e( 'Existing marketing language', 'aio-page-builder' ); ?></label></th>
				<td>
					<textarea name="aio_bp_biz_marketing_lang" id="aio_bp_biz_marketing_lang" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $mktg_lang ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Key phrases, taglines, or copy already used in your marketing.', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aio_bp_biz_seasonality"><?php \esc_html_e( 'Seasonality notes', 'aio-page-builder' ); ?></label></th>
				<td>
					<textarea name="aio_bp_biz_seasonality" id="aio_bp_biz_seasonality" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="2"><?php echo \esc_textarea( $seasonality ); ?></textarea>
					<p class="description"><?php \esc_html_e( 'Seasonal demand patterns that affect content or offer emphasis.', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Renders asset intake form fields; prefilled from stored business profile.
	 *
	 * @param array<string, mixed> $state Onboarding UI state.
	 * @return void
	 */
	private function render_asset_intake_step( array $state ): void {
		$prefill    = $state['prefill'] ?? array();
		$profile    = $prefill['profile'] ?? array();
		$biz        = isset( $profile[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $profile[ Profile_Schema::ROOT_BUSINESS ] )
			? $profile[ Profile_Schema::ROOT_BUSINESS ] : array();
		$visual_ref = isset( $biz['visual_inspiration_references'] ) ? (string) $biz['visual_inspiration_references'] : '';
		$sales_proc = isset( $biz['internal_sales_process_notes'] ) ? (string) $biz['internal_sales_process_notes'] : '';
		$has_assets = trim( $visual_ref ) !== '' || trim( $sales_proc ) !== '';
		?>
		<p><?php \esc_html_e( 'Optional context for templates and copy. Expand the section below to add references and notes.', 'aio-page-builder' ); ?></p>
		<details class="aio-onboarding-embed-disclosure"<?php echo $has_assets ? ' open' : ''; ?>>
			<summary class="aio-onboarding-embed-summary" style="cursor:pointer;padding:0.5rem 0;">
				<strong><?php \esc_html_e( 'Visual references & sales notes', 'aio-page-builder' ); ?></strong>
				<span class="description"> — <?php \esc_html_e( 'Optional fields for design and funnel context.', 'aio-page-builder' ); ?></span>
			</summary>
			<div class="aio-onboarding-embed-details-inner" style="margin-top:0.75rem;">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="aio_bp_biz_visual_ref"><?php \esc_html_e( 'Visual inspiration references', 'aio-page-builder' ); ?></label></th>
						<td>
							<textarea name="aio_bp_biz_visual_ref" id="aio_bp_biz_visual_ref" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $visual_ref ); ?></textarea>
							<p class="description"><?php \esc_html_e( 'URLs or notes describing visual style, competitor sites, or design references.', 'aio-page-builder' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aio_bp_biz_sales_process"><?php \esc_html_e( 'Sales process notes', 'aio-page-builder' ); ?></label></th>
						<td>
							<textarea name="aio_bp_biz_sales_process" id="aio_bp_biz_sales_process" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="large-text" rows="3"><?php echo \esc_textarea( $sales_proc ); ?></textarea>
							<p class="description"><?php \esc_html_e( 'Notes about how prospects engage and convert, to help sequence page content.', 'aio-page-builder' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</details>
		<?php
	}

	/**
	 * Renders existing site context step; prefilled from stored business profile.
	 *
	 * @param array<string, mixed> $state Onboarding UI state.
	 * @return void
	 */
	private function render_existing_site_step( array $state ): void {
		$prefill     = $state['prefill'] ?? array();
		$stored_url  = $prefill['current_site_url'] ?? '';
		$profile     = $prefill['profile'] ?? array();
		$biz         = isset( $profile[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $profile[ Profile_Schema::ROOT_BUSINESS ] )
			? $profile[ Profile_Schema::ROOT_BUSINESS ] : array();
		$site_url    = isset( $biz['current_site_url'] ) && $biz['current_site_url'] !== ''
			? (string) $biz['current_site_url'] : ( is_string( $stored_url ) ? $stored_url : '' );
		$crawler_url = \add_query_arg( array( 'page' => Crawler_Sessions_Screen::SLUG ), \admin_url( 'admin.php' ) );
		?>
		<p><?php \esc_html_e( 'If you have an existing site, enter its URL here. This is used as context for AI planning and enables crawl-based content analysis.', 'aio-page-builder' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aio_bp_biz_url"><?php \esc_html_e( 'Current site URL', 'aio-page-builder' ); ?></label></th>
				<td>
					<input type="url" name="aio_bp_biz_url" id="aio_bp_biz_url" form="<?php echo \esc_attr( self::MAIN_FORM_ID ); ?>" class="regular-text" value="<?php echo \esc_attr( $site_url ); ?>" placeholder="https://" />
					<p class="description"><?php \esc_html_e( 'Leave blank if no existing site.', 'aio-page-builder' ); ?></p>
				</td>
			</tr>
		</table>
		<p>
			<?php
			printf(
				/* translators: %s: URL to Crawler Sessions screen */
				\esc_html__( 'To run a deep content analysis of your existing site, use the Crawler below or the full %s screen.', 'aio-page-builder' ),
				'<a href="' . \esc_url( $crawler_url ) . '">' . \esc_html__( 'Crawler Sessions', 'aio-page-builder' ) . '</a>'
			);
			?>
		</p>
		<?php $this->render_embedded_crawler_sessions( $state ); ?>
		<?php
	}

	/**
	 * Renders crawl preferences step; shows latest crawl context and links to Crawler Sessions screen.
	 *
	 * @param array<string, mixed> $state Onboarding UI state.
	 * @return void
	 */
	private function render_crawl_preferences_step( array $state ): void {
		$prefill       = $state['prefill'] ?? array();
		$crawl_run_ids = $prefill['crawl_run_ids'] ?? array();
		$crawler_url   = \add_query_arg( array( 'page' => Crawler_Sessions_Screen::SLUG ), \admin_url( 'admin.php' ) );
		$cc            = isset( $state['crawl_context'] ) && is_array( $state['crawl_context'] ) ? $state['crawl_context'] : null;
		?>
		<p><?php \esc_html_e( 'Crawl your existing site to give the AI planner richer context about your current content structure and gaps.', 'aio-page-builder' ); ?></p>
		<?php if ( $cc !== null && isset( $cc['headline'], $cc['detail'], $cc['next_step'] ) ) : ?>
			<?php
			$notice          = $cc['phase'] === Onboarding_Crawl_Context_Phase::PHASE_FAILED ? 'notice-error' : ( $cc['phase'] === Onboarding_Crawl_Context_Phase::PHASE_STALE || $cc['phase'] === Onboarding_Crawl_Context_Phase::PHASE_PARTIAL ? 'notice-warning' : 'notice-info' );
			$crawl_phase     = isset( $cc['phase'] ) ? (string) $cc['phase'] : '';
			$crawl_phase_lbl = isset( $cc['phase_label'] ) ? (string) $cc['phase_label'] : '';
			$crawl_aria      = (string) $cc['headline'] . ( $crawl_phase_lbl !== '' ? ' — ' . $crawl_phase_lbl : '' );
			?>
			<div class="notice <?php echo \esc_attr( $notice ); ?> inline" role="status" style="margin:1em 0;" data-aio-crawl-phase="<?php echo \esc_attr( $crawl_phase ); ?>" aria-label="<?php echo \esc_attr( $crawl_aria ); ?>">
				<p><strong><?php echo \esc_html( (string) $cc['headline'] ); ?></strong></p>
				<p><?php echo \esc_html( (string) $cc['detail'] ); ?></p>
				<p class="description"><?php echo \esc_html( (string) $cc['next_step'] ); ?></p>
			</div>
		<?php endif; ?>
		<?php if ( is_array( $crawl_run_ids ) && count( $crawl_run_ids ) > 1 ) : ?>
			<p class="description"><?php echo \esc_html( sprintf( /* translators: %d: number of stored crawl runs */ __( '%d crawl runs stored.', 'aio-page-builder' ), count( $crawl_run_ids ) ) ); ?></p>
		<?php endif; ?>
		<?php $this->render_embedded_crawler_sessions( $state ); ?>
		<p>
			<a href="<?php echo \esc_url( $crawler_url ); ?>" class="button button-secondary"><?php \esc_html_e( 'Open Crawler Sessions in full view', 'aio-page-builder' ); ?></a>
		</p>
		<p class="description"><?php \esc_html_e( 'After starting a crawl, you can continue the wizard; refresh this step later to see updated runs.', 'aio-page-builder' ); ?></p>
		<?php
	}

	/**
	 * Renders review step: profile summary and provider readiness.
	 *
	 * @param array<string, mixed> $state Onboarding UI state.
	 * @return void
	 */
	private function render_review_step( array $state ): void {
		$prefill           = $state['prefill'] ?? array();
		$profile           = $prefill['profile'] ?? array();
		$biz               = isset( $profile[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $profile[ Profile_Schema::ROOT_BUSINESS ] )
			? $profile[ Profile_Schema::ROOT_BUSINESS ] : array();
		$brand             = isset( $profile[ Profile_Schema::ROOT_BRAND ] ) && is_array( $profile[ Profile_Schema::ROOT_BRAND ] )
			? $profile[ Profile_Schema::ROOT_BRAND ] : array();
		$tpl               = isset( $profile[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] ) && is_array( $profile[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] )
			? $profile[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] : array();
		$provider_refs     = $prefill['provider_refs'] ?? array();
		$is_provider_ready = ! empty( $state['is_provider_ready'] );
		$biz_name          = isset( $biz['business_name'] ) && $biz['business_name'] !== '' ? (string) $biz['business_name'] : '';
		$biz_type          = isset( $biz['business_type'] ) && $biz['business_type'] !== '' ? (string) $biz['business_type'] : '';
		$audience          = isset( $biz['target_audience_summary'] ) && $biz['target_audience_summary'] !== '' ? (string) $biz['target_audience_summary'] : '';
		$offers            = isset( $biz['primary_offers_summary'] ) && $biz['primary_offers_summary'] !== '' ? (string) $biz['primary_offers_summary'] : '';
		$geo               = isset( $biz['core_geographic_market'] ) && $biz['core_geographic_market'] !== '' ? (string) $biz['core_geographic_market'] : '';
		$positioning       = isset( $brand['brand_positioning_summary'] ) && $brand['brand_positioning_summary'] !== '' ? (string) $brand['brand_positioning_summary'] : '';
		$voice             = isset( $brand['brand_voice_summary'] ) && $brand['brand_voice_summary'] !== '' ? (string) $brand['brand_voice_summary'] : '';
		$site_url          = isset( $prefill['current_site_url'] ) && is_string( $prefill['current_site_url'] ) ? trim( $prefill['current_site_url'] ) : '';
		$crawl_run_ids     = isset( $prefill['crawl_run_ids'] ) && is_array( $prefill['crawl_run_ids'] ) ? $prefill['crawl_run_ids'] : array();
		$draft_review      = isset( $state['draft'] ) && is_array( $state['draft'] ) ? $state['draft'] : array();
		$pinned_crawl      = isset( $draft_review['crawl_run_id_ref'] ) && is_string( $draft_review['crawl_run_id_ref'] ) ? trim( $draft_review['crawl_run_id_ref'] ) : '';
		$crawl_id          = isset( $prefill['latest_crawl_run_id'] ) && is_string( $prefill['latest_crawl_run_id'] ) ? trim( $prefill['latest_crawl_run_id'] ) : '';
		$crawl_ts          = isset( $prefill['latest_crawl_session_timestamp'] ) && is_string( $prefill['latest_crawl_session_timestamp'] ) ? trim( $prefill['latest_crawl_session_timestamp'] ) : '';
		$advisories        = isset( $state['review_advisories'] ) && is_array( $state['review_advisories'] ) ? $state['review_advisories'] : array();
		?>
		<p><?php \esc_html_e( 'This screen summarizes what planning will use. Required gaps are listed in the notice above; suggestions below are optional quality hints.', 'aio-page-builder' ); ?></p>
		<?php
		if ( count( $advisories ) > 0 ) :
			?>
			<div class="notice notice-info inline" role="region" aria-labelledby="aio-review-advisory-heading">
				<p id="aio-review-advisory-heading"><strong><?php \esc_html_e( 'Suggestions to strengthen the plan', 'aio-page-builder' ); ?></strong></p>
				<ul style="margin:0 0 0 1.2em;">
					<?php foreach ( $advisories as $a ) : ?>
						<?php if ( is_string( $a ) && $a !== '' ) : ?>
							<li><?php echo \esc_html( $a ); ?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<h4><?php \esc_html_e( 'Business & brand summary', 'aio-page-builder' ); ?></h4>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php \esc_html_e( 'Business name', 'aio-page-builder' ); ?></th>
				<td><?php echo $biz_name !== '' ? \esc_html( $biz_name ) : '<em>' . \esc_html__( 'Not set', 'aio-page-builder' ) . '</em>'; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php \esc_html_e( 'Business type', 'aio-page-builder' ); ?></th>
				<td><?php echo $biz_type !== '' ? \esc_html( $biz_type ) : '<em>' . \esc_html__( 'Not set', 'aio-page-builder' ) . '</em>'; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php \esc_html_e( 'Target audience', 'aio-page-builder' ); ?></th>
				<td><?php echo $audience !== '' ? \esc_html( $audience ) : '<em>' . \esc_html__( 'Not set', 'aio-page-builder' ) . '</em>'; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php \esc_html_e( 'Primary offers', 'aio-page-builder' ); ?></th>
				<td><?php echo $offers !== '' ? \esc_html( $offers ) : '<em>' . \esc_html__( 'Not set', 'aio-page-builder' ) . '</em>'; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php \esc_html_e( 'Geography', 'aio-page-builder' ); ?></th>
				<td><?php echo $geo !== '' ? \esc_html( $geo ) : '<em>' . \esc_html__( 'Not set', 'aio-page-builder' ) . '</em>'; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php \esc_html_e( 'Brand positioning', 'aio-page-builder' ); ?></th>
				<td><?php echo $positioning !== '' ? \esc_html( $positioning ) : '<em>' . \esc_html__( 'Not set', 'aio-page-builder' ) . '</em>'; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php \esc_html_e( 'Brand voice', 'aio-page-builder' ); ?></th>
				<td><?php echo $voice !== '' ? \esc_html( $voice ) : '<em>' . \esc_html__( 'Not set', 'aio-page-builder' ) . '</em>'; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php \esc_html_e( 'Current site URL', 'aio-page-builder' ); ?></th>
				<td><?php echo $site_url !== '' ? \esc_html( $site_url ) : '<em>' . \esc_html__( 'Not set', 'aio-page-builder' ) . '</em>'; ?></td>
			</tr>
		</table>
		<h4><?php \esc_html_e( 'Template preferences', 'aio-page-builder' ); ?></h4>
		<?php if ( count( $tpl ) > 0 ) : ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $tpl as $k => $v ) : ?>
					<?php
					if ( ! is_string( $k ) || $k === '' ) {
						continue;
					}
					?>
					<tr>
						<th scope="row"><?php echo \esc_html( str_replace( '_', ' ', $k ) ); ?></th>
						<td><?php echo \is_bool( $v ) ? ( $v ? \esc_html__( 'Yes', 'aio-page-builder' ) : \esc_html__( 'No', 'aio-page-builder' ) ) : \esc_html( (string) $v ); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php else : ?>
			<p class="description"><?php \esc_html_e( 'No template preference signals saved yet (optional).', 'aio-page-builder' ); ?></p>
		<?php endif; ?>
		<h4><?php \esc_html_e( 'Crawl context', 'aio-page-builder' ); ?></h4>
		<?php if ( $crawl_id !== '' ) : ?>
			<p><?php \esc_html_e( 'Latest crawl session:', 'aio-page-builder' ); ?> <code><?php echo \esc_html( $crawl_id ); ?></code>
			<?php if ( $crawl_ts !== '' ) : ?>
				<?php echo ' — '; ?>
				<?php
				$ts = \strtotime( $crawl_ts );
				echo \esc_html( $ts !== false ? \wp_date( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), $ts ) : $crawl_ts );
				?>
			<?php endif; ?>
			</p>
		<?php else : ?>
			<p class="description"><?php \esc_html_e( 'No crawl session linked yet. Crawl is optional; add one if you want the planner to read your public site.', 'aio-page-builder' ); ?></p>
		<?php endif; ?>
		<h4><?php \esc_html_e( 'AI provider readiness', 'aio-page-builder' ); ?></h4>
		<?php if ( $is_provider_ready ) : ?>
			<p class="aio-onboarding-ready"><?php \esc_html_e( 'At least one AI provider is configured and ready.', 'aio-page-builder' ); ?></p>
		<?php else : ?>
			<p class="aio-onboarding-not-ready"><?php \esc_html_e( 'No AI provider is configured yet. Add credentials in the embedded area below (same as AI → Providers), then save a draft or refresh.', 'aio-page-builder' ); ?></p>
					<?php $this->render_embedded_ai_providers_setup( $state ); ?>
		<?php endif; ?>
		<?php if ( count( $provider_refs ) > 0 ) : ?>
			<ul aria-label="<?php \esc_attr_e( 'Provider status', 'aio-page-builder' ); ?>">
				<?php foreach ( $provider_refs as $ref ) : ?>
					<?php
					if ( ! is_array( $ref ) ) {
						continue;
					}
					$pid = isset( $ref['provider_id'] ) ? (string) $ref['provider_id'] : '';
					$st  = isset( $ref['credential_state'] ) ? (string) $ref['credential_state'] : 'absent';
					?>
					<li><?php echo \esc_html( $pid . ': ' . Onboarding_Step_Readiness::describe_provider_credential_state( $st ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php
	}

	/**
	 * Embeds the AI Providers admin UI (credential forms POST to the AI hub; no nesting inside the wizard form).
	 *
	 * @return void
	 */
	private function render_embedded_ai_providers_setup( array $state ): void {
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_AI_PROVIDERS ) ) {
			$this->render_ai_providers_external_link_only();
			return;
		}
		$ready      = ! empty( $state['is_provider_ready'] );
		$summary_ln = $ready
			? __( 'AI provider: configured', 'aio-page-builder' )
			: __( 'AI provider: not configured yet', 'aio-page-builder' );
		?>
		<?php
		// * opening is a static boolean attribute fragment; class string is literal.
		printf(
			'<details class="aio-onboarding-embed-disclosure"%s>',
			! $ready ? ' open' : ''
		);
		?>
			<summary class="aio-onboarding-embed-summary" style="cursor:pointer;padding:0.5rem 0;">
				<strong><?php echo \esc_html( $summary_ln ); ?></strong>
				<span class="description"> — <?php \esc_html_e( 'Expand for credentials, model defaults, connection tests, and spend caps.', 'aio-page-builder' ); ?></span>
			</summary>
			<div class="aio-onboarding-embed-details-inner" style="margin-top:0.75rem;">
				<p class="description"><?php \esc_html_e( 'Why this matters: planning calls your provider’s API; usage may incur cost per their pricing.', 'aio-page-builder' ); ?></p>
				<p class="description"><?php \esc_html_e( 'What’s next: save a key, run “Test connection”, then continue the wizard. Submits here reload the admin.', 'aio-page-builder' ); ?></p>
				<div class="aio-onboarding-embed aio-onboarding-embed--ai-providers" role="region" aria-labelledby="aio-onboarding-embed-ai-providers-heading">
					<h4 id="aio-onboarding-embed-ai-providers-heading" class="aio-onboarding-embed-title"><?php \esc_html_e( 'Provider credentials & connection tests', 'aio-page-builder' ); ?></h4>
					<p class="description"><?php \esc_html_e( 'These controls match the AI Providers screen (AI → Providers tab).', 'aio-page-builder' ); ?></p>
					<?php
					$providers_screen = new AI_Providers_Screen( $this->container );
					$providers_screen->render( true );
					?>
				</div>
			</div>
		</details>
		<?php
	}

	/**
	 * Link to AI Providers when the user cannot manage keys in-place.
	 *
	 * @return void
	 */
	private function render_ai_providers_external_link_only(): void {
		$url = Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'providers' );
		?>
		<p class="aio-onboarding-external-link">
			<a href="<?php echo \esc_url( $url ); ?>" class="button button-primary"><?php \esc_html_e( 'Open AI Providers', 'aio-page-builder' ); ?></a>
		</p>
		<p class="description"><?php \esc_html_e( 'An administrator must configure API keys, or switch to an account with permission to manage AI providers.', 'aio-page-builder' ); ?></p>
		<?php
	}

	/**
	 * Embeds Crawler Sessions list/start form (POST targets crawler screen).
	 *
	 * @return void
	 */
	private function render_embedded_crawler_sessions( array $state ): void {
		if ( ! Capabilities::current_user_can_for_route( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS ) ) {
			$this->render_crawler_external_link_only();
			return;
		}
		$cc              = isset( $state['crawl_context'] ) && is_array( $state['crawl_context'] ) ? $state['crawl_context'] : null;
		$phase           = is_array( $cc ) && isset( $cc['phase'] ) ? (string) $cc['phase'] : Onboarding_Crawl_Context_Phase::PHASE_UNKNOWN;
		$open_auto       = in_array(
			$phase,
			array(
				Onboarding_Crawl_Context_Phase::PHASE_NONE,
				Onboarding_Crawl_Context_Phase::PHASE_FAILED,
				Onboarding_Crawl_Context_Phase::PHASE_RUNNING,
				Onboarding_Crawl_Context_Phase::PHASE_UNKNOWN,
			),
			true
		);
		$cc_label        = is_array( $cc ) && isset( $cc['phase_label'] ) ? (string) $cc['phase_label'] : '';
		$crawler_summary = __( 'Crawler tools', 'aio-page-builder' );
		if ( $cc_label !== '' ) {
			$crawler_summary .= ' — ' . $cc_label;
		}
		?>
		<?php
		printf(
			'<details class="aio-onboarding-embed-disclosure"%s data-aio-crawl-phase="%s" aria-label="%s">',
			$open_auto ? ' open' : '',
			\esc_attr( $phase ),
			\esc_attr( $crawler_summary )
		);
		?>
			<summary class="aio-onboarding-embed-summary" style="cursor:pointer;padding:0.5rem 0;">
				<strong><?php \esc_html_e( 'Crawler tools', 'aio-page-builder' ); ?></strong>
				<?php if ( $cc_label !== '' ) : ?>
					<span class="description"> (<?php echo \esc_html( $cc_label ); ?>)</span>
				<?php endif; ?>
				<span class="description"> — <?php \esc_html_e( 'Expand for start, retry, and session list (same as Crawler Sessions).', 'aio-page-builder' ); ?></span>
			</summary>
			<div class="aio-onboarding-embed-details-inner" style="margin-top:0.75rem;">
				<?php if ( is_array( $cc ) && isset( $cc['detail'], $cc['next_step'] ) ) : ?>
					<p class="description"><?php echo \esc_html( (string) $cc['detail'] ); ?></p>
					<p class="description"><?php echo \esc_html( (string) $cc['next_step'] ); ?></p>
				<?php endif; ?>
				<div class="aio-onboarding-embed aio-onboarding-embed--crawler" role="region" aria-labelledby="aio-onboarding-embed-crawler-heading">
					<h4 id="aio-onboarding-embed-crawler-heading" class="aio-onboarding-embed-title"><?php \esc_html_e( 'Start or review crawls', 'aio-page-builder' ); ?></h4>
					<p class="description"><?php \esc_html_e( 'Starting or retrying a crawl may redirect; open Onboarding again to continue the wizard.', 'aio-page-builder' ); ?></p>
					<?php
					$crawler = new Crawler_Sessions_Screen( $this->container );
					$crawler->render( true );
					?>
				</div>
			</div>
		</details>
		<?php
	}

	/**
	 * Link to crawler when the user lacks diagnostics/crawler capability.
	 *
	 * @return void
	 */
	private function render_crawler_external_link_only(): void {
		$url = \add_query_arg( array( 'page' => Crawler_Sessions_Screen::SLUG ), \admin_url( 'admin.php' ) );
		?>
		<p class="aio-onboarding-external-link">
			<a href="<?php echo \esc_url( $url ); ?>" class="button"><?php \esc_html_e( 'Open Crawler Sessions', 'aio-page-builder' ); ?></a>
		</p>
		<p class="description"><?php \esc_html_e( 'You may need an account with crawler access to run site analysis.', 'aio-page-builder' ); ?></p>
		<?php
	}

	/**
	 * Coarse onboarding analytics (no PII). Skips when service not registered.
	 *
	 * @param array<string, mixed> $draft Draft for current step key.
	 */
	private function record_onboarding_telemetry( string $event_id, array $draft ): void {
		if ( ! $this->container->has( 'onboarding_telemetry' ) ) {
			return;
		}
		$step = isset( $draft['current_step_key'] ) && is_string( $draft['current_step_key'] ) ? $draft['current_step_key'] : '';
		/** @var Onboarding_Telemetry $tel */
		$tel = $this->container->get( 'onboarding_telemetry' );
		$tel->record( $event_id, $step );
	}

	/**
	 * Writes one debug line for onboarding POST/persist tracing. Requires WP_DEBUG and WP_DEBUG_LOG (see Named_Debug_Log).
	 * Does not log field values.
	 *
	 * @param string $message Short diagnostic (no PII).
	 * @return void
	 */
	private function debug_onboarding_line( string $message ): void {
		Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_ONBOARDING_TRACE, $message );
	}

	/**
	 * Comma-separated POST field names for wizard-controlled inputs (nonce verified by caller).
	 *
	 * @return string
	 */
	private function summarize_wizard_post_field_keys(): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Only used from handle_post() after nonce verification.
		$keys = array();
		foreach ( array_keys( $_POST ) as $k ) {
			if ( ! is_string( $k ) || $k === '' ) {
				continue;
			}
			if ( preg_match( '/^(aio_bp_|aio_template_preference_|aio_primary_industry|aio_industry_|aio_secondary_industry|aio_industry_qp_)/', $k ) ) {
				$keys[] = $k;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		sort( $keys );
		return $keys === array() ? '(none)' : implode( ',', $keys );
	}

	/**
	 * Read-only provider capability summary (no secrets).
	 *
	 * @return void
	 */
	private function render_ai_provider_capability_summary_if_available(): void {
		if ( ! $this->container->has( 'ai_provider_capability_summary_builder' ) ) {
			return;
		}
		$builder = $this->container->get( 'ai_provider_capability_summary_builder' );
		if ( ! $builder instanceof AI_Provider_Admin_Capability_Summary_Builder ) {
			return;
		}
		$rows = $builder->build_rows();
		if ( $rows === array() ) {
			return;
		}
		?>
		<div class="aio-onboarding-provider-cap-summary" style="margin:1em 0;padding:8px 12px;border:1px solid #c3c4c7;background:#fcfcfc;">
			<h2 class="hndle"><?php \esc_html_e( 'AI provider capabilities (summary)', 'aio-page-builder' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Operational metadata only. API keys stay in the secure store and are never listed here.', 'aio-page-builder' ); ?></p>
			<table class="widefat striped" style="max-width:720px;">
				<thead>
					<tr>
						<th scope="col"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Credential', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Structured output', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Attachments', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Models', 'aio-page-builder' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><code><?php echo \esc_html( (string) ( $r['provider_id'] ?? '' ) ); ?></code></td>
						<td><?php echo ! empty( $r['credential_configured'] ) ? \esc_html__( 'Stored', 'aio-page-builder' ) : \esc_html__( 'Not stored', 'aio-page-builder' ); ?></td>
						<td><?php echo ! empty( $r['structured_output_supported'] ) ? \esc_html__( 'Yes', 'aio-page-builder' ) : \esc_html__( 'No', 'aio-page-builder' ); ?></td>
						<td><?php echo ! empty( $r['file_attachment_supported'] ) ? \esc_html__( 'Yes', 'aio-page-builder' ) : \esc_html__( 'No', 'aio-page-builder' ); ?></td>
						<td><?php echo \esc_html( (string) (int) ( $r['models_count'] ?? 0 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
