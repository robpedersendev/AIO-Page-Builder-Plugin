<?php
/**
 * Handles admin_post create Build Plan from a completed AI run (normalized output artifact).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Actions;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Domain\BuildPlan\Generation\AI_Run_To_Build_Plan_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Admin-post handler: verify nonce and capability, generate plan from run, update run metadata, redirect.
 */
final class Create_Build_Plan_From_AI_Run_Action {

	public const NONCE_NAME   = 'aio_create_bp_from_run_nonce';
	public const NONCE_ACTION = 'aio_create_build_plan_from_ai_run';
	public const PARAM_RUN_ID = 'run_id';

	/** Optional: approved template-lab chat session id (must be approved + applied to canonical). */
	public const PARAM_TEMPLATE_LAB_CHAT_SESSION = 'template_lab_chat_session_id';

	/** Query arg on redirect: result code. */
	public const QUERY_RESULT = 'aio_bp_from_run';

	public const RESULT_CREATED                    = 'created';
	public const RESULT_UNAUTHORIZED               = 'unauthorized';
	public const RESULT_BAD_REQUEST                = 'bad_request';
	public const RESULT_GENERATION_FAILED          = 'generation_failed';
	public const RESULT_TEMPLATE_LAB_LINK_REJECTED = 'tl_link_rejected';

	/**
	 * Handles POST: verify nonce and capability, create plan from run, redirect to plan workspace or run detail.
	 *
	 * @param Service_Container|null $container Container.
	 * @return void
	 */
	public static function handle( ?Service_Container $container = null ): void {
		$run_id = '';
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashed and sanitized below.
		if ( isset( $_REQUEST[ self::PARAM_RUN_ID ] ) && is_string( $_REQUEST[ self::PARAM_RUN_ID ] ) ) {
			$run_id = trim( (string) \wp_unslash( $_REQUEST[ self::PARAM_RUN_ID ] ) );
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$run_id = $run_id !== '' ? \sanitize_text_field( $run_id ) : '';

		$redirect_run = self::run_detail_url( $run_id );

		if ( $container === null ) {
			self::redirect_to( $redirect_run, self::RESULT_BAD_REQUEST );
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::APPROVE_BUILD_PLANS ) ) {
			self::redirect_to( $redirect_run, self::RESULT_UNAUTHORIZED );
		}

		$nonce = isset( $_REQUEST[ self::NONCE_NAME ] ) && is_string( $_REQUEST[ self::NONCE_NAME ] )
			? \sanitize_text_field( \wp_unslash( $_REQUEST[ self::NONCE_NAME ] ) )
			: '';
		if ( $nonce === '' || ! \wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::redirect_to( $redirect_run, self::RESULT_BAD_REQUEST );
		}

		if ( $run_id === '' ) {
			self::redirect_to(
				Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_runs' ),
				self::RESULT_BAD_REQUEST
			);
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashed; sanitized below.
		$tl_chat = '';
		if ( isset( $_REQUEST[ self::PARAM_TEMPLATE_LAB_CHAT_SESSION ] ) && is_string( $_REQUEST[ self::PARAM_TEMPLATE_LAB_CHAT_SESSION ] ) ) {
			$tl_chat = trim( (string) \wp_unslash( $_REQUEST[ self::PARAM_TEMPLATE_LAB_CHAT_SESSION ] ) );
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$tl_chat = $tl_chat !== '' ? \sanitize_text_field( $tl_chat ) : '';

		if ( ! $container->has( 'ai_run_service' ) || ! $container->has( 'ai_run_to_build_plan_service' ) ) {
			self::redirect_to( $redirect_run, self::RESULT_BAD_REQUEST );
		}

		/** @var AI_Run_To_Build_Plan_Service $service */
		$service = $container->get( 'ai_run_to_build_plan_service' );
		if ( ! $service instanceof AI_Run_To_Build_Plan_Service ) {
			self::redirect_to( $redirect_run, self::RESULT_BAD_REQUEST );
		}

		$run_svc = $container->get( 'ai_run_service' );
		$run     = $run_svc->get_run_by_id( $run_id );
		if ( $run === null ) {
			self::redirect_to( $redirect_run, self::RESULT_BAD_REQUEST );
		}

		$actor = (int) \get_current_user_id();
		$opts  = array(
			'actor_user_id' => $actor,
		);
		if ( $tl_chat !== '' ) {
			$opts['template_lab_chat_session_id'] = $tl_chat;
		}

		$result = $service->create_from_completed_run( $run_id, null, $opts );
		if ( ! $result->is_success() || $result->get_plan_id() === null ) {
			$errs = $result->get_errors();
			if ( $tl_chat !== '' && isset( $errs[0] ) && strpos( (string) $errs[0], '[aio_tl_link]' ) === 0 ) {
				self::redirect_to( $redirect_run, self::RESULT_TEMPLATE_LAB_LINK_REJECTED );
			}
			self::redirect_to( $redirect_run, self::RESULT_GENERATION_FAILED );
		}

		$plan_id = $result->get_plan_id();
		$post_id = (int) ( $run['id'] ?? 0 );
		$status  = (string) ( $run['status'] ?? 'completed' );
		if ( $post_id <= 0 ) {
			self::redirect_to( $redirect_run, self::RESULT_BAD_REQUEST );
		}
		$run_svc->update_run( $post_id, $status, array( 'build_plan_ref' => $plan_id ), array() );

		$redirect_plan = Admin_Screen_Hub::tab_url(
			Build_Plans_Screen::SLUG,
			'build_plans',
			array(
				'plan_id'          => $plan_id,
				self::QUERY_RESULT => self::RESULT_CREATED,
			)
		);
		\wp_safe_redirect( $redirect_plan );
		exit;
	}

	/**
	 * @param string $run_id Run internal key or empty.
	 * @return string
	 */
	public static function run_detail_url( string $run_id ): string {
		if ( $run_id === '' ) {
			return Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_runs' );
		}
		return Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_runs', array( 'run_id' => $run_id ) );
	}

	/**
	 * @param string $url  Safe redirect URL.
	 * @param string $code Result code appended as query arg.
	 * @return void
	 */
	private static function redirect_to( string $url, string $code ): void {
		\wp_safe_redirect( \add_query_arg( self::QUERY_RESULT, $code, $url ) );
		exit;
	}
}
