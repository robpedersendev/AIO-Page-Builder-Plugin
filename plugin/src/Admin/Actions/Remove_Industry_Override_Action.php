<?php
/**
 * Handles admin_post removal of a single industry override (Prompt 436).
 * Nonce and capability checks per scope; redirects back to override management screen.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Actions;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\Industry\Industry_Override_Management_Screen;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Build_Plan_Item_Override_Service;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Page_Template_Override_Service;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Section_Override_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Admin-post handler for removing one override (section, page template, or build plan item).
 */
final class Remove_Industry_Override_Action {

	public const NONCE_NAME   = 'aio_remove_industry_override_nonce';
	public const NONCE_ACTION  = 'aio_remove_industry_override';

	/**
	 * Handles POST: verify nonce and scope-specific capability, remove override, redirect.
	 *
	 * @return void
	 */
	public static function handle(): void {
		$redirect = \admin_url( 'admin.php?page=' . Industry_Override_Management_Screen::SLUG );
		$sep      = strpos( $redirect, '?' ) !== false ? '&' : '?';

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( (string) $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			\wp_safe_redirect( $redirect . $sep . 'aio_override_remove=error' );
			exit;
		}

		$target_type = isset( $_POST['target_type'] ) && is_string( $_POST['target_type'] )
			? trim( \sanitize_key( \wp_unslash( $_POST['target_type'] ) ) )
			: '';
		$target_key  = isset( $_POST['target_key'] ) && is_string( $_POST['target_key'] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST['target_key'] ) ) )
			: '';
		$plan_id     = isset( $_POST['plan_id'] ) && is_string( $_POST['plan_id'] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST['plan_id'] ) ) )
			: '';

		if ( $target_type === '' || $target_key === '' ) {
			\wp_safe_redirect( $redirect . $sep . 'aio_override_remove=error' );
			exit;
		}

		$ok = false;
		if ( $target_type === Industry_Override_Schema::TARGET_TYPE_SECTION ) {
			if ( ! \current_user_can( Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
				\wp_safe_redirect( $redirect . $sep . 'aio_override_remove=error' );
				exit;
			}
			$service = new Industry_Section_Override_Service();
			$ok      = $service->remove_override( $target_key );
		} elseif ( $target_type === Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE ) {
			if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
				\wp_safe_redirect( $redirect . $sep . 'aio_override_remove=error' );
				exit;
			}
			$service = new Industry_Page_Template_Override_Service();
			$ok      = $service->remove_override( $target_key );
		} elseif ( $target_type === Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM ) {
			if ( ! \current_user_can( Capabilities::APPROVE_BUILD_PLANS ) ) {
				\wp_safe_redirect( $redirect . $sep . 'aio_override_remove=error' );
				exit;
			}
			if ( $plan_id === '' ) {
				\wp_safe_redirect( $redirect . $sep . 'aio_override_remove=error' );
				exit;
			}
			$service = new Industry_Build_Plan_Item_Override_Service();
			$ok      = $service->remove_override( $plan_id, $target_key );
		} else {
			\wp_safe_redirect( $redirect . $sep . 'aio_override_remove=error' );
			exit;
		}

		\wp_safe_redirect( $redirect . $sep . 'aio_override_remove=' . ( $ok ? 'removed' : 'error' ) );
		exit;
	}
}
