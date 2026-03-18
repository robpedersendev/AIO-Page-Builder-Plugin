<?php
/**
 * LEGACY — NOT LOADED BY ACTIVE PLUGIN.
 * Old REST namespace. Active plugin registers REST routes elsewhere (AIOPageBuilder).
 * Quarantined in plugin/legacy/; see legacy/README.md. No inactive route is available at runtime.
 *
 * REST API namespace controller.
 *
 * @package PrivatePluginBase
 */

declare(strict_types=1);

namespace PrivatePluginBase\Rest;

use PrivatePluginBase\Security\Capabilities;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the REST API namespace and routes.
 */
final class NamespaceController {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	public const API_NAMESPACE = 'private-plugin-base/v1';

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Registers REST API routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::API_NAMESPACE,
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_status' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Permission callback for REST routes.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error True if permitted, WP_Error otherwise.
	 */
	public static function check_permission( WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to perform this action.', 'private-plugin-base' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Returns plugin status (scaffold endpoint).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_status( WP_REST_Request $request ): WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return new WP_REST_Response(
			array(
				'active' => true,
			),
			200
		);
	}
}
