<?php
/**
 * Builds UI state for the onboarding screen: steps, status, blockers, prefill (onboarding-state-machine.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;

/**
 * Assembles everything the onboarding screen needs to render: step list, current step, blocked state, prefill, nonce.
 * When industry profile and question pack registry are provided, adds industry_question_pack and industry_question_pack_answers to state.
 */
final class Onboarding_UI_State_Builder {

	/** @var Onboarding_Draft_Service */
	private Onboarding_Draft_Service $draft_service;

	/** @var Onboarding_Prefill_Service */
	private Onboarding_Prefill_Service $prefill_service;

	/** @var Industry_Profile_Repository|null */
	private ?Industry_Profile_Repository $industry_profile_repository;

	/** @var Industry_Question_Pack_Registry|null */
	private ?Industry_Question_Pack_Registry $industry_question_pack_registry;

	public function __construct(
		Onboarding_Draft_Service $draft_service,
		Onboarding_Prefill_Service $prefill_service,
		?Industry_Profile_Repository $industry_profile_repository = null,
		?Industry_Question_Pack_Registry $industry_question_pack_registry = null
	) {
		$this->draft_service                  = $draft_service;
		$this->prefill_service                = $prefill_service;
		$this->industry_profile_repository    = $industry_profile_repository;
		$this->industry_question_pack_registry = $industry_question_pack_registry;
	}

	/**
	 * Step key to human-readable label (for screen readers and display).
	 *
	 * @return array<string, string>
	 */
	public static function step_labels(): array {
		return array(
			Onboarding_Step_Keys::WELCOME               => __( 'Welcome', 'aio-page-builder' ),
			Onboarding_Step_Keys::BUSINESS_PROFILE      => __( 'Business Profile', 'aio-page-builder' ),
			Onboarding_Step_Keys::BRAND_PROFILE         => __( 'Brand Profile', 'aio-page-builder' ),
			Onboarding_Step_Keys::AUDIENCE_OFFERS       => __( 'Audience & Offers', 'aio-page-builder' ),
			Onboarding_Step_Keys::GEOGRAPHY_COMPETITORS => __( 'Geography & Competitors', 'aio-page-builder' ),
			Onboarding_Step_Keys::ASSET_INTAKE          => __( 'Asset Intake', 'aio-page-builder' ),
			Onboarding_Step_Keys::EXISTING_SITE         => __( 'Existing Site', 'aio-page-builder' ),
			Onboarding_Step_Keys::CRAWL_PREFERENCES     => __( 'Crawl Preferences', 'aio-page-builder' ),
			Onboarding_Step_Keys::PROVIDER_SETUP        => __( 'AI Provider Setup', 'aio-page-builder' ),
			Onboarding_Step_Keys::TEMPLATE_PREFERENCES   => __( 'Page & template preferences', 'aio-page-builder' ),
			Onboarding_Step_Keys::REVIEW                => __( 'Review', 'aio-page-builder' ),
			Onboarding_Step_Keys::SUBMISSION           => __( 'Submission', 'aio-page-builder' ),
		);
	}

	/**
	 * Builds full UI state for the onboarding screen. Call when rendering the screen.
	 *
	 * @return array<string, mixed> Keys: current_step_key, steps, overall_status, is_blocked, blockers, prefill, draft, nonce, nonce_action, can_save_draft, resume_message, is_provider_ready.
	 */
	public function build_for_screen(): array {
		$draft = $this->draft_service->get_draft();
		$prefill = $this->prefill_service->get_prefill_data( $draft );
		$current_step_key = $draft['current_step_key'] ?? Onboarding_Step_Keys::WELCOME;
		$overall_status = $draft['overall_status'] ?? Onboarding_Statuses::NOT_STARTED;

		// When resuming from draft_saved, treat as in_progress for UI.
		$effective_status = $overall_status === Onboarding_Statuses::DRAFT_SAVED ? Onboarding_Statuses::IN_PROGRESS : $overall_status;

		$step_statuses = $draft['step_statuses'] ?? array();
		$labels = self::step_labels();
		$steps = array();
		foreach ( Onboarding_Step_Keys::ordered() as $key ) {
			$steps[] = array(
				'key'        => $key,
				'label'      => $labels[ $key ] ?? $key,
				'status'     => $step_statuses[ $key ] ?? Onboarding_Statuses::STEP_NOT_STARTED,
				'is_current' => $key === $current_step_key,
			);
		}

		$is_provider_ready = $this->prefill_service->is_provider_ready();
		$is_at_review = $current_step_key === Onboarding_Step_Keys::REVIEW;
		$is_blocked = $is_at_review && ! $is_provider_ready;
		$blockers = array();
		if ( $is_at_review && ! $is_provider_ready ) {
			$blockers[] = __( 'Configure an AI provider to continue.', 'aio-page-builder' );
		}

		$resume_message = $overall_status === Onboarding_Statuses::DRAFT_SAVED
			? __( 'You have saved draft progress. You can continue below.', 'aio-page-builder' )
			: '';

		$submission_warnings = $this->build_submission_warnings( $draft, $prefill );

		$state = array(
			'current_step_key'     => $current_step_key,
			'steps'                => $steps,
			'overall_status'       => $effective_status,
			'is_blocked'           => $is_blocked,
			'blockers'             => $blockers,
			'prefill'              => $prefill,
			'draft'                => $draft,
			'nonce'                => \wp_create_nonce( 'aio_onboarding_save' ),
			'nonce_action'         => 'aio_onboarding_save',
			'can_save_draft'       => true,
			'resume_message'       => $resume_message,
			'is_provider_ready'    => $is_provider_ready,
			'submission_warnings'  => $submission_warnings,
			'last_planning_run_id' => $draft['last_planning_run_id'] ?? null,
			'last_planning_run_post_id' => $draft['last_planning_run_post_id'] ?? null,
		);

		$state = $this->append_industry_question_pack_state( $state );
		return $state;
	}

	/**
	 * Appends industry_question_pack and industry_question_pack_answers when industry profile and registry are available.
	 *
	 * @param array<string, mixed> $state Current UI state.
	 * @return array<string, mixed>
	 */
	private function append_industry_question_pack_state( array $state ): array {
		if ( $this->industry_profile_repository === null || $this->industry_question_pack_registry === null ) {
			$state['industry_question_pack']       = null;
			$state['industry_question_pack_answers'] = array();
			return $state;
		}
		$profile = $this->industry_profile_repository->get_profile();
		$primary = isset( $profile['primary_industry_key'] ) && is_string( $profile['primary_industry_key'] )
			? trim( $profile['primary_industry_key'] )
			: '';
		if ( $primary === '' ) {
			$state['industry_question_pack']       = null;
			$state['industry_question_pack_answers'] = array();
			return $state;
		}
		$pack = $this->industry_question_pack_registry->get( $primary );
		if ( $pack === null ) {
			$state['industry_question_pack']       = null;
			$state['industry_question_pack_answers'] = array();
			return $state;
		}
		$qp_answers = isset( $profile['question_pack_answers'] ) && is_array( $profile['question_pack_answers'] )
			? $profile['question_pack_answers']
			: array();
		$state['industry_question_pack'] = $pack;
		$state['industry_question_pack_answers'] = isset( $qp_answers[ $primary ] ) && is_array( $qp_answers[ $primary ] )
			? $qp_answers[ $primary ]
			: array();
		return $state;
	}

	/**
	 * Builds change-detection and stale-crawl warnings for submission step. Surface only; does not block.
	 *
	 * @param array<string, mixed> $draft
	 * @param array<string, mixed> $prefill
	 * @return list<array{category: string, message: string, severity?: string}>
	 */
	private function build_submission_warnings( array $draft, array $prefill ): array {
		$warnings = array();
		// * Placeholder for change-detection (e.g. profile updated since last crawl) and stale-crawl age.
		return $warnings;
	}
}
