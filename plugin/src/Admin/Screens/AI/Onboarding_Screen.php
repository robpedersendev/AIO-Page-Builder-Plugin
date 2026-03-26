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
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Prefill_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Statuses;
use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Readiness;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Keys;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_UI_State_Builder;
use AIOPageBuilder\Domain\AI\Onboarding\Planning_Request_Result;
use AIOPageBuilder\Domain\Profile\Template_Preference_Profile;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Renders onboarding flow: steps, draft persistence, prefill, blocked state. No provider API or AI submission.
 */
final class Onboarding_Screen {

	public const SLUG = 'aio-page-builder-onboarding';

	/** Gated by plugin capability for onboarding (spec §44.3). */
	private const CAPABILITY = Capabilities::RUN_ONBOARDING;

	/** Nonce action and wp_nonce_field() name for onboarding forms. */
	public const NONCE_ACTION = 'aio_onboarding_save';

	/**
	 * HTML id of the wizard POST form. Step inputs render outside that form (embedded AI/Crawler UIs must not be nested in a form);
	 * they associate via the form attribute so Next/Save draft submits all fields.
	 */
	private const MAIN_FORM_ID = 'aio-onboarding-main';

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
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access this page.', 'aio-page-builder' ) );
		}

		$state = $this->get_ui_state();
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
		if ( $this->container === null ) {
			$this->debug_onboarding_line( 'post rejected: service container unavailable' );
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
			return $this->hub_redirect_url( array() );
		}

		if ( $action === 'save_draft' ) {
			$this->persist_all_wizard_fields_from_post( $draft );
			$this->bump_furthest_step_index( $draft );
			$draft['overall_status'] = Onboarding_Statuses::DRAFT_SAVED;
			$draft_service->save_draft( $draft );
			return $this->hub_redirect_url(
				array(
					'saved' => '1',
				)
			);
		}

		if ( $action === 'advance_step' ) {
			$this->persist_all_wizard_fields_from_post( $draft );
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
				\set_transient( 'aio_onboarding_advance_validation_' . (string) \get_current_user_id(), $gate_errors, 120 );
				return $this->hub_redirect_url( array( 'onboarding_validation' => '1' ) );
			}
			$ordered = Onboarding_Step_Keys::ordered();
			$idx     = array_search( $draft['current_step_key'], $ordered, true );
			if ( $idx !== false && $idx < count( $ordered ) - 1 ) {
				$next = $ordered[ $idx + 1 ];
				if ( $next === Onboarding_Step_Keys::REVIEW && ! $prefill_service->is_provider_ready() ) {
					$this->debug_onboarding_line( 'advance blocked: provider not ready before review step' );
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
			}
			return $this->hub_redirect_url( array() );
		}

		if ( $action === 'submit_planning_request' ) {
			$this->persist_all_wizard_fields_from_post( $draft );
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
				$orchestrator  = $this->container->get( 'onboarding_planning_request_orchestrator' );
				$result        = $orchestrator->submit();
				$arr           = $result->to_array();
				$transient_key = 'aio_onboarding_planning_result_' . \get_current_user_id();
				\set_transient( $transient_key, $arr, 120 );
				$this->debug_onboarding_line( 'submit_planning_request result_status=' . (string) ( $arr['status'] ?? '' ) );
				if ( $arr['status'] === Planning_Request_Result::STATUS_SUCCESS
					&& isset( $arr['run_id'] ) && is_string( $arr['run_id'] ) && $arr['run_id'] !== ''
					&& Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) ) {
					return Admin_Screen_Hub::tab_url(
						AI_Runs_Screen::HUB_PAGE_SLUG,
						'ai_runs',
						array(
							'run_id' => $arr['run_id'],
							AI_Runs_Screen::QUERY_ONBOARDING_PLAN => AI_Runs_Screen::ONBOARDING_PLAN_SUCCESS_VALUE,
						)
					);
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
	private function persist_all_wizard_fields_from_post( array $draft ): void {
		$this->persist_brand_profile_from_post( $draft );
		$this->persist_business_profile_from_post( $draft );
		$this->persist_template_preferences_from_post( $draft );
		$this->persist_industry_profile_from_post();
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
		if ( $this->container && $this->container->has( 'onboarding_ui_state_builder' ) ) {
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
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-onboarding aio-onboarding--stripe" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php else : ?>
		<div class="aio-page-builder-screen aio-onboarding aio-onboarding--hub-embed aio-onboarding--stripe" role="region" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
		<?php endif; ?>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible" role="status">
					<p><?php \esc_html_e( 'Draft saved. You can return later to continue.', 'aio-page-builder' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( count( $advance_validation_msgs ) > 0 ) : ?>
				<div class="notice notice-error" role="alert">
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

			<section class="aio-onboarding-content" aria-labelledby="aio-onboarding-step-heading">
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
				?>
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
				<?php $this->render_onboarding_welcome_step(); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::PROVIDER_SETUP ) : ?>
				<p><?php \esc_html_e( 'Configure at least one AI provider (API key) to use AI planning. Credentials are stored securely and never shown in full after save.', 'aio-page-builder' ); ?></p>
				<p><?php \esc_html_e( 'Current readiness:', 'aio-page-builder' ); ?> <strong><?php echo $is_provider_ready ? \esc_html__( 'At least one provider is marked configured.', 'aio-page-builder' ) : \esc_html__( 'No provider configured yet.', 'aio-page-builder' ); ?></strong></p>
				<?php $this->render_embedded_ai_providers_setup(); ?>
			<?php elseif ( $current_step_key === Onboarding_Step_Keys::SUBMISSION ) : ?>
				<?php if ( ! $is_provider_ready ) : ?>
					<div class="notice notice-warning inline" role="status">
						<p><?php \esc_html_e( 'Configure an AI provider before you can request a plan.', 'aio-page-builder' ); ?></p>
					</div>
					<?php $this->render_embedded_ai_providers_setup(); ?>
				<?php endif; ?>
				<p><?php \esc_html_e( 'Request an AI-generated plan from your profile and context. The plan will appear in AI Runs; you can then create a Build Plan from it.', 'aio-page-builder' ); ?></p>
				<?php
				$submission_warnings = isset( $state['submission_warnings'] ) && is_array( $state['submission_warnings'] ) ? $state['submission_warnings'] : array();
				foreach ( $submission_warnings as $warning ) :
					if ( ! is_array( $warning ) || ! isset( $warning['message'] ) || (string) $warning['message'] === '' ) {
						continue;
					}
					?>
				<div class="notice notice-warning" role="status"><p><?php echo \esc_html( (string) $warning['message'] ); ?></p></div>
					<?php
				endforeach;
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
	 * @return void
	 */
	private function render_onboarding_welcome_step(): void {
		$labels  = Onboarding_UI_State_Builder::step_labels();
		$ordered = Onboarding_Step_Keys::ordered();
		?>
		<div class="aio-onboarding-welcome">
			<div class="aio-onboarding-welcome-hero">
				<p class="aio-onboarding-welcome-kicker"><?php \esc_html_e( 'Orientation', 'aio-page-builder' ); ?></p>
				<p class="aio-onboarding-welcome-lead">
					<?php \esc_html_e( 'This wizard builds a structured profile of your business, brand, and site so AIO Page Builder can generate an AI planning run you can turn into a Build Plan and real pages.', 'aio-page-builder' ); ?>
				</p>
			</div>

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
					<li><?php \esc_html_e( 'Review checks required data; Submission requests the plan (AI Run), which you can promote into a Build Plan.', 'aio-page-builder' ); ?></li>
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
		if ( $this->container === null || ! $this->container->has( 'profile_store' ) ) {
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
		if ( $this->container === null || ! $this->container->has( 'profile_store' ) ) {
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
		?>
		<p><?php \esc_html_e( 'Provide references to visual assets and any notes about your sales or content process. These guide template and copy decisions.', 'aio-page-builder' ); ?></p>
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
		<?php $this->render_embedded_crawler_sessions(); ?>
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
		$latest_run_id = $prefill['latest_crawl_run_id'] ?? null;
		$crawl_run_ids = $prefill['crawl_run_ids'] ?? array();
		$crawler_url   = \add_query_arg( array( 'page' => Crawler_Sessions_Screen::SLUG ), \admin_url( 'admin.php' ) );
		?>
		<p><?php \esc_html_e( 'Crawl your existing site to give the AI planner richer context about your current content structure and gaps.', 'aio-page-builder' ); ?></p>
		<?php if ( $latest_run_id !== null && $latest_run_id !== '' ) : ?>
			<p>
				<?php
				echo \esc_html(
					sprintf(
						/* translators: %s: crawl run ID */
						__( 'Latest crawl run: %s', 'aio-page-builder' ),
						(string) $latest_run_id
					)
				);
				?>
			</p>
			<?php if ( is_array( $crawl_run_ids ) && count( $crawl_run_ids ) > 1 ) : ?>
				<p class="description"><?php echo \esc_html( sprintf( /* translators: %d: number of stored crawl runs */ __( '%d crawl runs available.', 'aio-page-builder' ), count( $crawl_run_ids ) ) ); ?></p>
			<?php endif; ?>
		<?php else : ?>
			<p><?php \esc_html_e( 'No crawl runs recorded yet.', 'aio-page-builder' ); ?></p>
		<?php endif; ?>
		<?php $this->render_embedded_crawler_sessions(); ?>
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
		$provider_refs     = $prefill['provider_refs'] ?? array();
		$is_provider_ready = ! empty( $state['is_provider_ready'] );
		$biz_name          = isset( $biz['business_name'] ) && $biz['business_name'] !== '' ? (string) $biz['business_name'] : '';
		$biz_type          = isset( $biz['business_type'] ) && $biz['business_type'] !== '' ? (string) $biz['business_type'] : '';
		$audience          = isset( $biz['target_audience_summary'] ) && $biz['target_audience_summary'] !== '' ? (string) $biz['target_audience_summary'] : '';
		$positioning       = isset( $brand['brand_positioning_summary'] ) && $brand['brand_positioning_summary'] !== '' ? (string) $brand['brand_positioning_summary'] : '';
		?>
		<p><?php \esc_html_e( 'Review your stored profile and provider readiness before requesting an AI plan.', 'aio-page-builder' ); ?></p>
		<h4><?php \esc_html_e( 'Profile summary', 'aio-page-builder' ); ?></h4>
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
				<th scope="row"><?php \esc_html_e( 'Brand positioning', 'aio-page-builder' ); ?></th>
				<td><?php echo $positioning !== '' ? \esc_html( $positioning ) : '<em>' . \esc_html__( 'Not set', 'aio-page-builder' ) . '</em>'; ?></td>
			</tr>
		</table>
		<h4><?php \esc_html_e( 'Provider readiness', 'aio-page-builder' ); ?></h4>
		<?php if ( $is_provider_ready ) : ?>
			<p class="aio-onboarding-ready"><?php \esc_html_e( 'At least one AI provider is configured and ready.', 'aio-page-builder' ); ?></p>
		<?php else : ?>
			<p class="aio-onboarding-not-ready"><?php \esc_html_e( 'No AI provider is configured. Add credentials below (same as AI → Providers), then use Save draft or refresh this step.', 'aio-page-builder' ); ?></p>
			<?php $this->render_embedded_ai_providers_setup(); ?>
		<?php endif; ?>
		<?php if ( count( $provider_refs ) > 0 ) : ?>
			<ul aria-label="<?php \esc_attr_e( 'Provider status', 'aio-page-builder' ); ?>">
				<?php foreach ( $provider_refs as $ref ) : ?>
					<li><?php echo \esc_html( ( $ref['provider_id'] ?? '' ) . ': ' . ( $ref['credential_state'] ?? 'absent' ) ); ?></li>
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
	private function render_embedded_ai_providers_setup(): void {
		if ( $this->container === null ) {
			$this->render_ai_providers_external_link_only();
			return;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_AI_PROVIDERS ) ) {
			$this->render_ai_providers_external_link_only();
			return;
		}
		?>
		<div class="aio-onboarding-embed aio-onboarding-embed--ai-providers" role="region" aria-labelledby="aio-onboarding-embed-ai-providers-heading">
			<h4 id="aio-onboarding-embed-ai-providers-heading" class="aio-onboarding-embed-title"><?php \esc_html_e( 'Provider credentials & connection tests', 'aio-page-builder' ); ?></h4>
			<p class="description"><?php \esc_html_e( 'These controls match the AI Providers screen (AI → Providers tab). Submitting updates or tests will reload the admin; return to Onboarding to continue.', 'aio-page-builder' ); ?></p>
			<?php
			$providers_screen = new AI_Providers_Screen( $this->container );
			$providers_screen->render( true );
			?>
		</div>
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
	private function render_embedded_crawler_sessions(): void {
		if ( $this->container === null ) {
			$this->render_crawler_external_link_only();
			return;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS ) ) {
			$this->render_crawler_external_link_only();
			return;
		}
		?>
		<div class="aio-onboarding-embed aio-onboarding-embed--crawler" role="region" aria-labelledby="aio-onboarding-embed-crawler-heading">
			<h4 id="aio-onboarding-embed-crawler-heading" class="aio-onboarding-embed-title"><?php \esc_html_e( 'Start or review crawls', 'aio-page-builder' ); ?></h4>
			<p class="description"><?php \esc_html_e( 'Same controls as Crawler Sessions. Starting or retrying a crawl redirects here; open Onboarding again to continue the wizard.', 'aio-page-builder' ); ?></p>
			<?php
			$crawler = new Crawler_Sessions_Screen( $this->container );
			$crawler->render( true );
			?>
		</div>
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
}
