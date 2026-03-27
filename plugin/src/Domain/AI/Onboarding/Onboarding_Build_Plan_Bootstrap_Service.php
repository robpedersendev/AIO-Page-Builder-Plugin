<?php
/**
 * Links onboarding wizard state to a Build Plan post: shell creation and wizard snapshot sync.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Generator;
use AIOPageBuilder\Domain\BuildPlan\Generation\Plan_Generation_Result;
use AIOPageBuilder\Domain\BuildPlan\Lineage\Build_Plan_Lineage_Service;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Creates a placeholder Build Plan when the user starts onboarding and merges wizard snapshots into plan meta.
 */
final class Onboarding_Build_Plan_Bootstrap_Service {

	private Build_Plan_Generator $plan_generator;

	private Build_Plan_Repository $plan_repository;

	/** @var Build_Plan_Lineage_Service */
	private $lineage_service;

	public function __construct(
		Build_Plan_Generator $plan_generator,
		Build_Plan_Repository $plan_repository,
		Build_Plan_Lineage_Service $lineage_service
	) {
		$this->plan_generator   = $plan_generator;
		$this->plan_repository  = $plan_repository;
		$this->lineage_service  = $lineage_service;
	}

	/**
	 * Ensures the draft references a Build Plan CPT: creates a shell plan when missing or stale.
	 *
	 * @param array<string, mixed> $draft Draft (mutated with linked_build_plan_post_id and linked_build_plan_key on success).
	 * @return Plan_Generation_Result|null Null when shell creation was skipped (invalid state); result on attempt.
	 */
	public function ensure_linked_shell_plan( array &$draft ): ?Plan_Generation_Result {
		$existing_id = isset( $draft['linked_build_plan_post_id'] ) ? (int) $draft['linked_build_plan_post_id'] : 0;
		if ( $existing_id > 0 && $this->is_valid_build_plan_post( $existing_id ) ) {
			return null;
		}
		$draft['linked_build_plan_post_id'] = null;
		$draft['linked_build_plan_key']     = null;

		$shell       = Onboarding_Shell_Normalized_Output::minimal_array();
		$cost_note   = Build_Plan_Schema::DEFAULT_ONBOARDING_AI_COST_USD_NOTE;
		$fork_mode   = isset( $draft['build_plan_lineage_mode'] ) && $draft['build_plan_lineage_mode'] === 'fork';
		$base_ctx    = array(
			'onboarding_shell'           => true,
			'estimated_ai_cost_usd_note' => $cost_note,
		);
		if ( $fork_mode ) {
			$lid     = isset( $draft['fork_lineage_id'] ) ? trim( (string) $draft['fork_lineage_id'] ) : '';
			$purpose = isset( $draft['fork_version_purpose'] ) ? trim( (string) $draft['fork_version_purpose'] ) : '';
			if ( $lid === '' || $purpose === '' ) {
				return Plan_Generation_Result::failure(
					array( \__( 'Choose an existing plan lineage and describe what this new version is for.', 'aio-page-builder' ) )
				);
			}
			$next  = $this->lineage_service->get_next_version_seq( $lid );
			$label = (string) $next . '.0';
			$ctx   = array_merge(
				$base_ctx,
				array(
					'plan_lineage_id'             => $lid,
					'plan_version_seq'            => $next,
					'plan_version_label'          => $label,
					'version_purpose_description' => $purpose,
				)
			);
		} else {
			$lid = function_exists( 'wp_generate_uuid4' ) ? (string) \wp_generate_uuid4() : uniqid( 'aio-lineage-', true );
			$ctx = array_merge(
				$base_ctx,
				array(
					'plan_lineage_id'             => $lid,
					'plan_version_seq'            => 1,
					'plan_version_label'          => '1.0',
					'version_purpose_description' => \__( 'Initial site build plan from onboarding.', 'aio-page-builder' ),
				)
			);
		}

		$result = $this->plan_generator->generate(
			$shell,
			'onboarding:pending',
			'onboarding:pending:normalized_output',
			$ctx
		);
		if ( ! $result->is_success() ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::ADMIN_ONBOARDING_TRACE,
				'onboarding_shell_plan_failed errors=' . \wp_json_encode( $result->get_errors() )
			);
			return $result;
		}
		$draft['linked_build_plan_post_id'] = $result->get_plan_post_id();
		$draft['linked_build_plan_key']     = $result->get_plan_id();
		return $result;
	}

	/**
	 * Merges a secret-free wizard snapshot onto the plan definition for audit and continuity.
	 *
	 * @param int                  $post_id          Plan post ID.
	 * @param array<string, mixed> $draft            Onboarding draft.
	 * @param array<string, mixed> $prefill          Prefill payload from {@see Onboarding_Prefill_Service::get_prefill_data}.
	 * @param array<string, mixed> $industry_profile Industry profile row (optional); no secrets.
	 */
	public function sync_wizard_snapshot( int $post_id, array $draft, array $prefill, array $industry_profile = array() ): void {
		if ( $post_id <= 0 ) {
			return;
		}
		$definition = $this->plan_repository->get_plan_definition( $post_id );
		if ( $definition === array() ) {
			return;
		}
		$definition[ Build_Plan_Schema::KEY_ONBOARDING_WIZARD_SNAPSHOT ] = $this->build_snapshot_payload( $draft, $prefill, $industry_profile );
		$this->plan_repository->save_plan_definition( $post_id, $definition );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function build_snapshot_payload( array $draft, array $prefill, array $industry_profile ): array {
		$profile = isset( $prefill['profile'] ) && is_array( $prefill['profile'] ) ? $prefill['profile'] : array();
		$biz     = isset( $profile[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $profile[ Profile_Schema::ROOT_BUSINESS ] )
			? $profile[ Profile_Schema::ROOT_BUSINESS ] : array();
		$brand   = isset( $profile[ Profile_Schema::ROOT_BRAND ] ) && is_array( $profile[ Profile_Schema::ROOT_BRAND ] )
			? $profile[ Profile_Schema::ROOT_BRAND ] : array();
		$tpl     = isset( $profile[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] ) && is_array( $profile[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] )
			? $profile[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] : array();

		$step_statuses = isset( $draft['step_statuses'] ) && is_array( $draft['step_statuses'] ) ? $draft['step_statuses'] : array();

		return array(
			'captured_at_gmt'          => \gmdate( 'Y-m-d\TH:i:s\Z' ),
			'onboarding_draft'         => array(
				'version'                   => $draft['version'] ?? null,
				'overall_status'            => $draft['overall_status'] ?? null,
				'current_step_key'          => $draft['current_step_key'] ?? null,
				'furthest_step_index'       => $draft['furthest_step_index'] ?? null,
				'step_statuses'             => $step_statuses,
				'goal_or_intent_text'       => $draft['goal_or_intent_text'] ?? '',
				'profile_snapshot_ref'      => $draft['profile_snapshot_ref'] ?? null,
				'crawl_run_id_ref'          => $draft['crawl_run_id_ref'] ?? null,
				'provider_refs'             => $draft['provider_refs'] ?? array(),
				'last_planning_run_id'      => $draft['last_planning_run_id'] ?? null,
				'last_planning_run_post_id' => $draft['last_planning_run_post_id'] ?? null,
				'linked_build_plan_post_id'  => $draft['linked_build_plan_post_id'] ?? null,
				'linked_build_plan_key'      => $draft['linked_build_plan_key'] ?? null,
				'build_plan_lineage_mode'    => $draft['build_plan_lineage_mode'] ?? 'new',
				'fork_lineage_id'            => $draft['fork_lineage_id'] ?? '',
				'fork_version_purpose'       => $draft['fork_version_purpose'] ?? '',
			),
			'prefill_excerpt'          => array(
				'current_site_url'               => $prefill['current_site_url'] ?? '',
				'crawl_run_ids'                  => isset( $prefill['crawl_run_ids'] ) && is_array( $prefill['crawl_run_ids'] ) ? $prefill['crawl_run_ids'] : array(),
				'latest_crawl_run_id'            => $prefill['latest_crawl_run_id'] ?? null,
				'latest_crawl_session_timestamp' => $prefill['latest_crawl_session_timestamp'] ?? null,
			),
			'profile_excerpt'          => array(
				'business_profile'            => $biz,
				'brand_profile'               => $brand,
				'template_preference_profile' => $tpl,
			),
			'industry_profile_excerpt' => $industry_profile,
		);
	}

	private function is_valid_build_plan_post( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}
		$post = \get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}
		if ( $post->post_type !== Object_Type_Keys::BUILD_PLAN ) {
			return false;
		}
		$def = $this->plan_repository->get_plan_definition( $post_id );
		return $def !== array() && isset( $def[ Build_Plan_Schema::KEY_PLAN_ID ] );
	}
}
