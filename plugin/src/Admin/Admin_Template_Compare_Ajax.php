<?php
/**
 * AJAX handler for template compare list mutations without full-page redirect.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Registers wp_ajax handler for add/remove compare items.
 */
final class Admin_Template_Compare_Ajax {

	/**
	 * Hooks wp_ajax for logged-in administrators.
	 *
	 * @return void
	 */
	public static function register(): void {
		\add_action( 'wp_ajax_aio_template_compare_mutate', array( self::class, 'handle_mutate' ) );
	}

	/**
	 * JSON response: keys, count, in_compare, add_url, remove_url for the requested template key.
	 *
	 * @return void
	 */
	public static function handle_mutate(): void {
		\check_ajax_referer( Template_Compare_Screen::NONCE_AJAX_ACTION, 'nonce' );
		if ( ! Capabilities::current_user_can_or_site_admin( Capabilities::ACCESS_TEMPLATE_LIBRARY ) ) {
			\wp_send_json_error(
				array( 'message' => \__( 'You do not have permission to update the compare list.', 'aio-page-builder' ) ),
				403
			);
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified via check_ajax_referer above.
		$op = isset( $_POST['compare_op'] ) ? \sanitize_key( (string) \wp_unslash( $_POST['compare_op'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$type_raw = isset( $_POST['template_type'] ) ? \sanitize_key( (string) \wp_unslash( $_POST['template_type'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$key = isset( $_POST['template_key'] ) ? \sanitize_key( (string) \wp_unslash( $_POST['template_key'] ) ) : '';
		if ( $op !== 'add' && $op !== 'remove' ) {
			\wp_send_json_error( array( 'message' => \__( 'Invalid operation.', 'aio-page-builder' ) ), 400 );
		}
		$type = $type_raw === 'page' ? 'page' : 'section';
		$res  = Template_Compare_Screen::mutate_compare_list( $type, $op, $key );
		if ( ! $res['success'] ) {
			\wp_send_json_error( array( 'message' => $res['message'] ), 400 );
		}
		$keys       = $res['keys'];
		$in_compare = \in_array( $key, $keys, true );
		\wp_send_json_success(
			array(
				'keys'          => $keys,
				'count'         => \count( $keys ),
				'in_compare'    => $in_compare,
				'add_url'       => Template_Compare_Screen::get_compare_add_url( $type, $key ),
				'remove_url'    => Template_Compare_Screen::get_compare_remove_url( $type, $key ),
				'template_key'  => $key,
				'template_type' => $type,
			)
		);
	}
}
