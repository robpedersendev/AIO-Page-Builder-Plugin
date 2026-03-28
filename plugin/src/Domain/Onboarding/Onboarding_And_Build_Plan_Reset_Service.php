<?php
/**
 * Removes all Build Plan CPT posts and clears onboarding wizard state options (profile, AI runs, templates unchanged).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Telemetry;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * State-changing service; callers must verify capability and nonce.
 */
final class Onboarding_And_Build_Plan_Reset_Service {

	private Onboarding_Draft_Service $draft_service;

	private Settings_Service $settings;

	public function __construct( Onboarding_Draft_Service $draft_service, Settings_Service $settings ) {
		$this->draft_service = $draft_service;
		$this->settings      = $settings;
	}

	/**
	 * Deletes every `aio_build_plan` post (force delete), resets onboarding draft to defaults, clears legacy PB onboarding options and aggregate telemetry.
	 * Deletes short-lived onboarding transients for the current user only.
	 *
	 * @return array{build_plans_deleted: int}
	 */
	public function reset(): array {
		$deleted = $this->delete_all_build_plan_posts();
		$this->draft_service->clear_draft();
		$legacy = new Onboarding_State_Service( $this->settings );
		$this->settings->set(
			Option_Names::ONBOARDING_TELEMETRY_AGGREGATE,
			array(
				'v'       => Onboarding_Telemetry::OPTION_SHAPE_VERSION,
				'c'       => array(),
				'by_step' => array(),
				'recent'  => array(),
			)
		);
		$this->settings->set( Option_Names::PB_ONBOARDING_STATE, $legacy->default_state() );
		\delete_option( Option_Names::PB_ONBOARDING_DRAFT );
		\delete_option( Option_Names::PB_ONBOARDING_LAST_SUBMITTED_AT );
		$uid = (int) \get_current_user_id();
		if ( $uid > 0 ) {
			\delete_transient( 'aio_onboarding_planning_result_' . (string) $uid );
			\delete_transient( 'aio_onboarding_advance_validation_' . (string) $uid );
		}
		Named_Debug_Log::event(
			Named_Debug_Log_Event::ADMIN_ONBOARDING_BUILD_PLAN_RESET,
			'plans_deleted=' . (string) $deleted
		);
		return array(
			'build_plans_deleted' => $deleted,
		);
	}

	/**
	 * @return int Number of posts successfully deleted.
	 */
	private function delete_all_build_plan_posts(): int {
		$count    = 0;
		$post_ids = $this->query_all_build_plan_post_ids();
		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			if ( $post_id <= 0 ) {
				continue;
			}
			$result = \wp_delete_post( $post_id, true );
			if ( $result !== false && $result !== null ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * @return list<int>
	 */
	private function query_all_build_plan_post_ids(): array {
		$q   = new \WP_Query(
			array(
				'post_type'              => Object_Type_Keys::BUILD_PLAN,
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		$raw = $q->get_posts();
		if ( ! is_array( $raw ) ) {
			return array();
		}
		return $this->extract_post_ids_from_query_posts( $raw );
	}

	/**
	 * @param array<int, mixed> $posts Raw {@see \WP_Query::get_posts()} values (WP_Post, array, or id in test stubs).
	 * @return list<int>
	 */
	private function extract_post_ids_from_query_posts( array $posts ): array {
		$ids = array();
		foreach ( $posts as $p ) {
			$post_id = 0;
			if ( $p instanceof \WP_Post ) {
				$post_id = (int) $p->ID;
			} elseif ( is_int( $p ) ) {
				$post_id = $p;
			} elseif ( is_string( $p ) && ctype_digit( $p ) ) {
				$post_id = (int) $p;
			} elseif ( is_array( $p ) ) {
				$post_id = (int) ( $p['ID'] ?? 0 );
			} elseif ( is_object( $p ) && property_exists( $p, 'ID' ) ) {
				$post_id = (int) $p->ID;
			}
			if ( $post_id > 0 ) {
				$ids[] = $post_id;
			}
		}
		return $ids;
	}
}
