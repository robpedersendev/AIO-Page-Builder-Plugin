<?php
/**
 * admin-post: clears empty-definition repair backoff and re-runs rebuild from the linked AI run.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Actions;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Empty_Definition_Repair_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Support\Logging\Admin_Ux_Trace;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Nonce-backed rebuild for plans whose CPT exists but _aio_plan_definition is empty (see workspace notice).
 */
final class Repair_Empty_Build_Plan_Definition_Action {

	public const ACTION       = 'aio_repair_empty_build_plan_definition';
	public const NONCE_ACTION = 'aio_repair_empty_build_plan_definition';
	public const NONCE_NAME   = 'aio_repair_empty_bp_nonce';

	public const PARAM_PLAN_POST_ID = 'plan_post_id';

	public const QUERY_RESULT = 'aio_bp_repair_result';

	public const RESULT_OK           = 'ok';
	public const RESULT_FAIL         = 'fail';
	public const RESULT_BAD_REQUEST  = 'bad_request';
	public const RESULT_UNAUTHORIZED = 'unauthorized';

	/**
	 * @param Service_Container $container Bootstrap container.
	 */
	public static function handle( Service_Container $container ): void {
		$fallback = Admin_Screen_Hub::tab_url( Build_Plans_Screen::SLUG, 'build_plans' );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::ADMIN_REPAIR_EMPTY_BP_ENTER,
			'user_id=' . (string) (int) \get_current_user_id()
		);
		if ( ! Capabilities::current_user_can_for_route( Capabilities::APPROVE_BUILD_PLANS ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_REPAIR_EMPTY_BP_FAIL_UNAUTHORIZED, 'ok=0' );
			self::redirect_with_result( $fallback, self::RESULT_UNAUTHORIZED );
		}
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST[ self::NONCE_NAME ] ) ) : '';
		if ( $nonce === '' || ! \wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_REPAIR_EMPTY_BP_FAIL_NONCE, 'ok=0' );
			self::redirect_with_result( $fallback, self::RESULT_BAD_REQUEST );
		}
		$plan_post_id = isset( $_POST[ self::PARAM_PLAN_POST_ID ] ) ? (int) $_POST[ self::PARAM_PLAN_POST_ID ] : 0;
		if ( $plan_post_id <= 0 ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::ADMIN_REPAIR_EMPTY_BP_FAIL_BAD_POST_ID,
				'plan_post_id=' . (string) $plan_post_id
			);
			self::redirect_with_result( $fallback, self::RESULT_BAD_REQUEST );
		}
		if ( ! $container->has( 'build_plan_repository' ) || ! $container->has( 'build_plan_empty_definition_repair_service' ) ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::ADMIN_REPAIR_EMPTY_BP_FAIL_MISSING_SERVICES,
				'plan_post_id=' . (string) $plan_post_id . ' has_repo=' . ( $container->has( 'build_plan_repository' ) ? '1' : '0' ) . ' has_repair=' . ( $container->has( 'build_plan_empty_definition_repair_service' ) ? '1' : '0' )
			);
			self::redirect_with_result( $fallback, self::RESULT_FAIL );
		}
		/** @var Build_Plan_Repository $repo */
		$repo   = $container->get( 'build_plan_repository' );
		$record = $repo->get_by_id( $plan_post_id );
		if ( $record === null ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::ADMIN_REPAIR_EMPTY_BP_FAIL_PLAN_NOT_FOUND,
				'plan_post_id=' . (string) $plan_post_id
			);
			self::redirect_with_result( $fallback, self::RESULT_BAD_REQUEST );
		}
		$plan_key = trim( (string) ( $record['internal_key'] ?? '' ) );
		if ( $plan_key === '' ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::ADMIN_REPAIR_EMPTY_BP_FAIL_PLAN_KEY_EMPTY,
				'plan_post_id=' . (string) $plan_post_id
			);
			self::redirect_with_result( $fallback, self::RESULT_FAIL );
		}
		\delete_transient( 'aio_bp_empty_def_repair_skip_' . (string) $plan_post_id );
		Named_Debug_Log::event(
			Named_Debug_Log_Event::ADMIN_REPAIR_EMPTY_BP_REPAIR_INVOKED,
			'plan_post_id=' . (string) $plan_post_id . ' plan_key=' . $plan_key
		);
		Admin_Ux_Trace::admin_post_boundary(
			self::ACTION,
			'repair_invoke',
			array( 'expect' => 'definition_regenerated' ),
			array( 'plan_post_id' => (string) $plan_post_id ),
			array( 'hub:plans', 'tab:build_plans', 'section:empty_plan_repair', 'action:repair_empty_definition' )
		);

		/** @var Build_Plan_Empty_Definition_Repair_Service $repair */
		$repair = $container->get( 'build_plan_empty_definition_repair_service' );
		$ok     = $repair->repair_if_needed( $plan_post_id, $plan_key );

		Named_Debug_Log::event(
			Named_Debug_Log_Event::BUILD_PLAN_EMPTY_DEFINITION_REPAIR_MANUAL,
			'plan_post_id=' . (string) $plan_post_id . ' ok=' . ( $ok ? '1' : '0' )
		);

		$target = Admin_Screen_Hub::tab_url(
			Build_Plans_Screen::SLUG,
			'build_plans',
			array(
				'plan_id'          => $plan_key,
				'id'               => (string) $plan_post_id,
				self::QUERY_RESULT => $ok ? self::RESULT_OK : self::RESULT_FAIL,
			)
		);
		Named_Debug_Log::event(
			Named_Debug_Log_Event::ADMIN_REPAIR_EMPTY_BP_REDIRECT,
			'plan_post_id=' . (string) $plan_post_id . ' result=' . ( $ok ? self::RESULT_OK : self::RESULT_FAIL )
		);
		Admin_Ux_Trace::admin_post_boundary(
			self::ACTION,
			'redirect_workspace',
			array(),
			array(
				'redirect' => 'plans_workspace',
				'repair'   => $ok ? self::RESULT_OK : self::RESULT_FAIL,
			),
			array( 'hub:plans', 'tab:build_plans', 'action:repair_empty_definition' )
		);
		\wp_safe_redirect( $target );
		exit;
	}

	/**
	 * @param string $url    Admin URL.
	 * @param string $result Result code.
	 */
	private static function redirect_with_result( string $url, string $result ): void {
		\wp_safe_redirect( \add_query_arg( self::QUERY_RESULT, $result, $url ) );
		exit;
	}
}
