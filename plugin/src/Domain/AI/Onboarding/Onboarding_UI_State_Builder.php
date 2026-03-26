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
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Repository_Interface;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

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

	/** @var Profile_Snapshot_Repository_Interface|null */
	private ?Profile_Snapshot_Repository_Interface $profile_snapshot_repository;

	/** @var Settings_Service|null */
	private ?Settings_Service $settings_service;

	public function __construct(
		Onboarding_Draft_Service $draft_service,
		Onboarding_Prefill_Service $prefill_service,
		?Industry_Profile_Repository $industry_profile_repository = null,
		?Industry_Question_Pack_Registry $industry_question_pack_registry = null,
		?Profile_Snapshot_Repository_Interface $profile_snapshot_repository = null,
		?Settings_Service $settings_service = null
	) {
		$this->draft_service                   = $draft_service;
		$this->prefill_service                 = $prefill_service;
		$this->industry_profile_repository     = $industry_profile_repository;
		$this->industry_question_pack_registry = $industry_question_pack_registry;
		$this->profile_snapshot_repository     = $profile_snapshot_repository;
		$this->settings_service                = $settings_service;
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
			Onboarding_Step_Keys::TEMPLATE_PREFERENCES  => __( 'Page & template preferences', 'aio-page-builder' ),
			Onboarding_Step_Keys::REVIEW                => __( 'Review', 'aio-page-builder' ),
			Onboarding_Step_Keys::SUBMISSION            => __( 'Submission', 'aio-page-builder' ),
		);
	}

	/**
	 * Builds full UI state for the onboarding screen. Call when rendering the screen.
	 *
	 * @return array<string, mixed> Keys: current_step_key, steps, overall_status, is_blocked, blockers, prefill, draft, nonce, nonce_action, can_save_draft, resume_message, is_provider_ready.
	 */
	public function build_for_screen(): array {
		$draft            = $this->draft_service->get_draft();
		$prefill          = $this->prefill_service->get_prefill_data( $draft );
		$current_step_key = $draft['current_step_key'] ?? Onboarding_Step_Keys::WELCOME;
		$overall_status   = $draft['overall_status'] ?? Onboarding_Statuses::NOT_STARTED;

		// When resuming from draft_saved, treat as in_progress for UI.
		$effective_status = $overall_status === Onboarding_Statuses::DRAFT_SAVED ? Onboarding_Statuses::IN_PROGRESS : $overall_status;

		$labels   = self::step_labels();
		$profile  = $prefill['profile'] ?? array();
		$profile  = is_array( $profile ) ? $profile : array();
		$furthest = isset( $draft['furthest_step_index'] ) ? (int) $draft['furthest_step_index'] : 0;
		$cur_idx  = Onboarding_Step_Keys::index_of( $current_step_key );
		$furthest = max( $furthest, $cur_idx >= 0 ? $cur_idx : 0 );
		$steps    = array();
		foreach ( Onboarding_Step_Keys::ordered() as $key ) {
			$idx     = Onboarding_Step_Keys::index_of( $key );
			$steps[] = array(
				'key'         => $key,
				'label'       => $labels[ $key ] ?? $key,
				'status'      => Onboarding_Step_Readiness::display_status_for_step( $key, $current_step_key, $profile, $this->prefill_service, $furthest ),
				'is_current'  => $key === $current_step_key,
				'is_jumpable' => $key !== $current_step_key && $idx >= 0 && $idx <= $furthest,
			);
		}

		$is_provider_ready = $this->prefill_service->is_provider_ready();
		$is_at_review      = $current_step_key === Onboarding_Step_Keys::REVIEW;
		$at_submission     = $current_step_key === Onboarding_Step_Keys::SUBMISSION;
		$review_blockers   = Onboarding_Step_Readiness::get_review_blockers( $profile, $this->prefill_service );
		$is_blocked        = ( $is_at_review || $at_submission ) && count( $review_blockers ) > 0;
		$blockers          = ( $is_at_review || $at_submission ) ? $review_blockers : array();

		$resume_message = $overall_status === Onboarding_Statuses::DRAFT_SAVED
			? __( 'You have saved draft progress. You can continue below.', 'aio-page-builder' )
			: '';

		$submission_warnings = $this->build_submission_warnings( $draft, $prefill );

		$state = array(
			'current_step_key'          => $current_step_key,
			'steps'                     => $steps,
			'overall_status'            => $effective_status,
			'is_blocked'                => $is_blocked,
			'blockers'                  => $blockers,
			'prefill'                   => $prefill,
			'draft'                     => $draft,
			'nonce'                     => \wp_create_nonce( 'aio_onboarding_save' ),
			'nonce_action'              => 'aio_onboarding_save',
			'can_save_draft'            => true,
			'resume_message'            => $resume_message,
			'is_provider_ready'         => $is_provider_ready,
			'submission_warnings'       => $submission_warnings,
			'last_planning_run_id'      => $draft['last_planning_run_id'] ?? null,
			'last_planning_run_post_id' => $draft['last_planning_run_post_id'] ?? null,
		);

		$state = $this->append_industry_question_pack_state( $state );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::ONBOARDING_UI_STATE_BUILT,
			'step=' . (string) ( $state['current_step_key'] ?? '' ) . ' provider_ready=' . ( ! empty( $state['is_provider_ready'] ) ? '1' : '0' ) . ' blocked=' . ( ! empty( $state['is_blocked'] ) ? '1' : '0' )
		);
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
			$state['industry_question_pack']         = null;
			$state['industry_question_pack_answers'] = array();
			return $state;
		}
		$profile = $this->industry_profile_repository->get_profile();
		$primary = isset( $profile['primary_industry_key'] ) && is_string( $profile['primary_industry_key'] )
			? trim( $profile['primary_industry_key'] )
			: '';
		if ( $primary === '' ) {
			$state['industry_question_pack']         = null;
			$state['industry_question_pack_answers'] = array();
			return $state;
		}
		$pack = $this->industry_question_pack_registry->get( $primary );
		if ( $pack === null ) {
			$state['industry_question_pack']         = null;
			$state['industry_question_pack_answers'] = array();
			return $state;
		}
		$qp_answers                              = isset( $profile['question_pack_answers'] ) && is_array( $profile['question_pack_answers'] )
			? $profile['question_pack_answers']
			: array();
		$state['industry_question_pack']         = $pack;
		$state['industry_question_pack_answers'] = isset( $qp_answers[ $primary ] ) && is_array( $qp_answers[ $primary ] )
			? $qp_answers[ $primary ]
			: array();
		return $state;
	}

	/**
	 * Builds change-detection and stale-crawl warnings for submission step. Surface only; does not block.
	 *
	 * Profile-vs-run comparison uses persisted profile snapshots from merge events when the snapshot repository
	 * is available; otherwise that signal is skipped (no invented warning). Stale crawl uses the latest crawl
	 * session timestamp from prefill when present.
	 *
	 * @param array<string, mixed> $draft
	 * @param array<string, mixed> $prefill
	 * @return array<int, array{code: string, message: string}>
	 */
	private function build_submission_warnings( array $draft, array $prefill ): array {
		$warnings  = array();
		$ctx_block = Onboarding_Planning_Context_Guard::get_blocking_message( $draft, $prefill );
		if ( $ctx_block !== null ) {
			$warnings[] = array(
				'code'    => 'planning_context_incomplete',
				'message' => $ctx_block,
			);
		}
		$threshold_days = 30;
		$settings       = $this->settings_service;
		if ( $settings !== null ) {
			$main = $settings->get( Option_Names::MAIN_SETTINGS );
			$raw  = isset( $main['onboarding_stale_crawl_warning_days'] ) ? (int) $main['onboarding_stale_crawl_warning_days'] : 0;
			if ( $raw > 0 ) {
				$threshold_days = $raw;
			}
		}

		$last_run_post_id = isset( $draft['last_planning_run_post_id'] ) ? (int) $draft['last_planning_run_post_id'] : 0;
		if ( $this->profile_snapshot_repository !== null && $last_run_post_id > 0 ) {
			$run_post = \get_post( $last_run_post_id );
			if ( $run_post instanceof \WP_Post ) {
				$run_ts = false;
				$gm     = isset( $run_post->post_modified_gmt ) ? (string) $run_post->post_modified_gmt : '';
				if ( $gm !== '' ) {
					$run_ts = \strtotime( $gm );
				}
				if ( $run_ts === false && isset( $run_post->post_modified ) ) {
					$run_ts = \strtotime( (string) $run_post->post_modified );
				}
				if ( $run_ts !== false ) {
					$merge_ts = $this->latest_profile_merge_snapshot_timestamp();
					if ( $merge_ts !== null && $merge_ts > $run_ts ) {
						$warnings[] = array(
							'code'    => 'profile_updated_since_last_run',
							'message' => \__( 'Your business or brand profile was saved after the last successful planning run. Submit again if you want the latest profile reflected in planning.', 'aio-page-builder' ),
						);
					}
				}
			}
		}

		$crawl_ts_str = isset( $prefill['latest_crawl_session_timestamp'] ) && is_string( $prefill['latest_crawl_session_timestamp'] )
			? \trim( $prefill['latest_crawl_session_timestamp'] )
			: '';
		if ( $crawl_ts_str !== '' ) {
			$crawl_ts = \strtotime( $crawl_ts_str );
			if ( $crawl_ts !== false ) {
				$age_seconds = \time() - $crawl_ts;
				$threshold   = $threshold_days * 86400;
				if ( $age_seconds > $threshold ) {
					$warnings[] = array(
						'code'    => 'stale_crawl_context',
						/* translators: %d: maximum age in whole days before crawl is considered stale. */
						'message' => \sprintf( \__( 'The most recent crawl data is older than %d days. Consider running a new crawl if your site content has changed.', 'aio-page-builder' ), $threshold_days ),
					);
				}
			}
		}

		return $warnings;
	}

	/**
	 * Returns the newest Unix timestamp among brand/business merge snapshots, or null when none exist.
	 *
	 * @return int|null
	 */
	private function latest_profile_merge_snapshot_timestamp(): ?int {
		if ( $this->profile_snapshot_repository === null ) {
			return null;
		}
		$snapshots = $this->profile_snapshot_repository->get_all( 200 );
		$max       = null;
		foreach ( $snapshots as $snap ) {
			$src = isset( $snap->source ) ? (string) $snap->source : '';
			if ( ! \in_array( $src, array( 'brand_profile_merge', 'business_profile_merge' ), true ) ) {
				continue;
			}
			$created = isset( $snap->created_at ) ? (string) $snap->created_at : '';
			if ( $created === '' ) {
				continue;
			}
			$ts = \strtotime( $created );
			if ( $ts === false ) {
				continue;
			}
			if ( $max === null || $ts > $max ) {
				$max = $ts;
			}
		}
		return $max;
	}
}
