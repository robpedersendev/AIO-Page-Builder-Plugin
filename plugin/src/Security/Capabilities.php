<?php
/**
 * Capability registration.
 *
 * @package PrivatePluginBase
 */

declare(strict_types=1);

namespace PrivatePluginBase\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Manages plugin capabilities.
 */
final class Capabilities {

	/**
	 * Capability required to access plugin admin and manage settings.
	 *
	 * @var string
	 */
	public const MANAGE = 'manage_private_plugin_base';

	/**
	 * Adds the plugin capability to the administrator role.
	 *
	 * @return void
	 */
	public static function add_to_administrator(): void {
		$role = get_role( 'administrator' );
		if ( $role instanceof \WP_Role ) {
			$role->add_cap( self::MANAGE );
		}
	}

	/**
	 * Removes the plugin capability from all roles.
	 *
	 * @return void
	 */
	public static function remove_from_all(): void {
		$wp_roles = wp_roles();
		foreach ( array_keys( (array) $wp_roles->roles ) as $role_slug ) {
			$wp_roles->remove_cap( $role_slug, self::MANAGE );
		}
	}
}
