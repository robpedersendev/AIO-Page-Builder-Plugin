<?php
/**
 * Handles admin_post create Build Plan from starter bundle (Prompt 409).
 * Nonce and capability checks; converts bundle to draft plan; redirects to Build Plan review or Industry Profile with error.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Actions;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Profile_Settings_Screen;
use AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Admin-post handler for creating a draft Build Plan from a selected starter bundle.
 */
final class Create_Plan_From_Starter_Bundle_Action {

	public const NONCE_NAME   = 'aio_create_plan_from_bundle_nonce';
	public const NONCE_ACTION = 'aio_create_plan_from_bundle';
	public const PARAM_BUNDLE_KEY = 'bundle_key';

	/**
	 * Handles POST/GET: verify nonce and capability, convert bundle to draft, redirect to plan or profile with result.
	 *
	 * @param Service_Container|null $container Container to resolve Industry_Starter_Bundle_To_Build_Plan_Service.
	 * @return void
	 */
	public static function handle( ?Service_Container $container = null ): void {
		$redirect_profile = \admin_url( 'admin.php?page=' . Industry_Profile_Settings_Screen::SLUG );
		$redirect_plans   = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG );

		if ( $container === null ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_plan_result', 'error', $redirect_profile ) );
			exit;
		}
		if ( ! \current_user_can( Capabilities::APPROVE_BUILD_PLANS ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_plan_result', 'unauthorized', $redirect_profile ) );
			exit;
		}
		$nonce = isset( $_REQUEST[ self::NONCE_NAME ] ) && is_string( $_REQUEST[ self::NONCE_NAME ] )
			? \sanitize_text_field( \wp_unslash( $_REQUEST[ self::NONCE_NAME ] ) )
			: '';
		if ( $nonce === '' || ! \wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_plan_result', 'error', $redirect_profile ) );
			exit;
		}
		$bundle_key = isset( $_REQUEST[ self::PARAM_BUNDLE_KEY ] ) && is_string( $_REQUEST[ self::PARAM_BUNDLE_KEY ] )
			? \trim( \sanitize_key( \wp_unslash( $_REQUEST[ self::PARAM_BUNDLE_KEY ] ) ) )
			: '';
		if ( $bundle_key === '' ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_plan_result', 'error', $redirect_profile ) );
			exit;
		}

		if ( ! $container->has( 'industry_starter_bundle_to_build_plan_service' ) ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_plan_result', 'error', $redirect_profile ) );
			exit;
		}
		$service = $container->get( 'industry_starter_bundle_to_build_plan_service' );
		if ( ! $service instanceof Industry_Starter_Bundle_To_Build_Plan_Service ) {
			\wp_safe_redirect( \add_query_arg( 'aio_bundle_plan_result', 'error', $redirect_profile ) );
			exit;
		}

		$result = $service->convert_to_draft( $bundle_key, array() );
		if ( $result->is_success() && $result->get_plan_id() !== null ) {
			\wp_safe_redirect( \add_query_arg( array( 'plan_id' => $result->get_plan_id(), 'aio_bundle_plan_result' => 'created' ), $redirect_plans ) );
			exit;
		}
		\wp_safe_redirect( \add_query_arg( 'aio_bundle_plan_result', 'error', $redirect_profile ) );
		exit;
	}
}
