<?php
/**
 * Handles admin_post save of industry Build Plan item override (Prompt 369).
 * Nonce and capability checks; sanitizes review note; persists via Industry_Build_Plan_Item_Override_Service; redirects back to plan.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Actions;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Build_Plan_Item_Override_Service;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Admin-post handler for saving a Build Plan item override (accept anyway / review note).
 */
final class Save_Industry_Build_Plan_Override_Action {

	public const NONCE_NAME   = 'aio_build_plan_override_nonce';
	public const NONCE_ACTION = 'aio_save_industry_build_plan_override';

	/**
	 * Handles POST: verify nonce and capability, save override, redirect back to plan workspace.
	 *
	 * @return void
	 */
	public static function handle(): void {
		$redirect = self::redirect_url();
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( (string) $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			\wp_safe_redirect( $redirect . ( strpos( $redirect, '?' ) !== false ? '&' : '?' ) . 'aio_plan_override=error' );
			exit;
		}
		if ( ! \current_user_can( Capabilities::APPROVE_BUILD_PLANS ) ) {
			\wp_safe_redirect( $redirect . ( strpos( $redirect, '?' ) !== false ? '&' : '?' ) . 'aio_plan_override=error' );
			exit;
		}
		$plan_id = isset( $_POST['plan_id'] ) && is_string( $_POST['plan_id'] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST['plan_id'] ) ) )
			: '';
		$item_id = isset( $_POST['item_id'] ) && is_string( $_POST['item_id'] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST['item_id'] ) ) )
			: '';
		$state = isset( $_POST['state'] ) && is_string( $_POST['state'] )
			? trim( \sanitize_key( \wp_unslash( $_POST['state'] ) ) )
			: Industry_Override_Schema::STATE_ACCEPTED;
		if ( $state !== Industry_Override_Schema::STATE_ACCEPTED && $state !== Industry_Override_Schema::STATE_REJECTED ) {
			$state = Industry_Override_Schema::STATE_ACCEPTED;
		}
		$reason = isset( $_POST['reason'] ) && is_string( $_POST['reason'] )
			? \sanitize_textarea_field( \wp_unslash( $_POST['reason'] ) )
			: '';
		$reason = Industry_Override_Schema::sanitize_reason( $reason );

		$service = new Industry_Build_Plan_Item_Override_Service();
		$ok = $service->record_override( $plan_id, $item_id, $state, $reason );
		\wp_safe_redirect( $redirect . ( strpos( $redirect, '?' ) !== false ? '&' : '?' ) . 'aio_plan_override=' . ( $ok ? 'saved' : 'error' ) );
		exit;
	}

	private static function redirect_url(): string {
		if ( isset( $_POST['_wp_http_referer'] ) && is_string( $_POST['_wp_http_referer'] ) ) {
			$ref = \esc_url_raw( \wp_unslash( $_POST['_wp_http_referer'] ) );
			if ( $ref !== '' ) {
				return $ref;
			}
		}
		$plan_id = isset( $_POST['plan_id'] ) && is_string( $_POST['plan_id'] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST['plan_id'] ) ) )
			: '';
		if ( $plan_id !== '' ) {
			return \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=2' );
		}
		return \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG );
	}
}
