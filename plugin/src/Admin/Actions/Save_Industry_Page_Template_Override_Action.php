<?php
/**
 * Handles admin_post save of industry page template override (Prompt 368).
 * Nonce and capability checks; sanitizes reason; persists via Industry_Page_Template_Override_Service; redirects back.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Actions;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\Templates\Page_Templates_Directory_Screen;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Page_Template_Override_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Admin-post handler for saving a page template override (use anyway / explicit accept).
 */
final class Save_Industry_Page_Template_Override_Action {

	public const NONCE_NAME   = 'aio_page_template_override_nonce';
	public const NONCE_ACTION = 'aio_save_industry_page_template_override';

	/**
	 * Handles POST: verify nonce and capability, save override, redirect.
	 *
	 * @return void
	 */
	public static function handle(): void {
		$redirect = self::redirect_url();
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( (string) $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			\wp_safe_redirect( $redirect . ( strpos( $redirect, '?' ) !== false ? '&' : '?' ) . 'aio_template_override=error' );
			exit;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			\wp_safe_redirect( $redirect . ( strpos( $redirect, '?' ) !== false ? '&' : '?' ) . 'aio_template_override=error' );
			exit;
		}
		$template_key = isset( $_POST['template_key'] ) && is_string( $_POST['template_key'] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST['template_key'] ) ) )
			: '';
		$state        = isset( $_POST['state'] ) && is_string( $_POST['state'] )
			? trim( \sanitize_key( \wp_unslash( $_POST['state'] ) ) )
			: Industry_Override_Schema::STATE_ACCEPTED;
		if ( $state !== Industry_Override_Schema::STATE_ACCEPTED && $state !== Industry_Override_Schema::STATE_REJECTED ) {
			$state = Industry_Override_Schema::STATE_ACCEPTED;
		}
		$reason = isset( $_POST['reason'] ) && is_string( $_POST['reason'] )
			? \sanitize_textarea_field( \wp_unslash( $_POST['reason'] ) )
			: '';
		$reason = Industry_Override_Schema::sanitize_reason( $reason );

		$service = new Industry_Page_Template_Override_Service();
		$ok      = $service->record_override( $template_key, $state, $reason );
		\wp_safe_redirect( $redirect . ( strpos( $redirect, '?' ) !== false ? '&' : '?' ) . 'aio_template_override=' . ( $ok ? 'saved' : 'error' ) );
		exit;
	}

	private static function redirect_url(): string {
		if ( isset( $_POST['_wp_http_referer'] ) && is_string( $_POST['_wp_http_referer'] ) ) {
			$ref = \esc_url_raw( \wp_unslash( $_POST['_wp_http_referer'] ) );
			if ( $ref !== '' ) {
				return $ref;
			}
		}
		return \admin_url( 'admin.php?page=' . Page_Templates_Directory_Screen::SLUG );
	}
}
