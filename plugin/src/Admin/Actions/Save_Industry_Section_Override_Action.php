<?php
/**
 * Handles admin_post save of industry section override (Prompt 367).
 * Nonce and capability checks; sanitizes reason; persists via Industry_Section_Override_Service; redirects back.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Actions;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\AdminRouting\Template_Library_Hub_Urls;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Section_Override_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Admin-post handler for saving a section override (use anyway / explicit accept).
 */
final class Save_Industry_Section_Override_Action {

	public const NONCE_NAME   = 'aio_section_override_nonce';
	public const NONCE_ACTION = 'aio_save_industry_section_override';

	/**
	 * Handles POST: verify nonce and capability, save override, redirect.
	 *
	 * @return void
	 */
	public static function handle(): void {
		$redirect = self::redirect_url();
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ||
			! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( (string) $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			\wp_safe_redirect( $redirect . ( strpos( $redirect, '?' ) !== false ? '&' : '?' ) . 'aio_section_override=error' );
			exit;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
			\wp_safe_redirect( $redirect . ( strpos( $redirect, '?' ) !== false ? '&' : '?' ) . 'aio_section_override=error' );
			exit;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce and capability verified above.
		$section_key = isset( $_POST['section_key'] ) && is_string( $_POST['section_key'] )
			? trim( \sanitize_text_field( \wp_unslash( $_POST['section_key'] ) ) )
			: '';
		$state       = isset( $_POST['state'] ) && is_string( $_POST['state'] )
			? trim( \sanitize_key( \wp_unslash( $_POST['state'] ) ) )
			: Industry_Override_Schema::STATE_ACCEPTED;
		if ( $state !== Industry_Override_Schema::STATE_ACCEPTED && $state !== Industry_Override_Schema::STATE_REJECTED ) {
			$state = Industry_Override_Schema::STATE_ACCEPTED;
		}
		$reason = isset( $_POST['reason'] ) && is_string( $_POST['reason'] )
			? \sanitize_textarea_field( \wp_unslash( $_POST['reason'] ) )
			: '';
		$reason = Industry_Override_Schema::sanitize_reason( $reason );

		$service = new Industry_Section_Override_Service();
		$ok      = $service->record_override( $section_key, $state, $reason );
		\wp_safe_redirect( $redirect . ( strpos( $redirect, '?' ) !== false ? '&' : '?' ) . 'aio_section_override=' . ( $ok ? 'saved' : 'error' ) );
		exit;
	}

	private static function redirect_url(): string {
		if ( isset( $_POST['_wp_http_referer'] ) && is_string( $_POST['_wp_http_referer'] ) ) {
			$ref = \esc_url_raw( \wp_unslash( $_POST['_wp_http_referer'] ) );
			if ( $ref !== '' ) {
				return $ref;
			}
		}
		return Template_Library_Hub_Urls::tab_url( Template_Library_Hub_Urls::TAB_SECTION );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
