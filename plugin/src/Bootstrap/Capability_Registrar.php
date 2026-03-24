<?php
/**
 * Registers plugin custom capabilities and default role mappings at activation (spec §44.2, §44.3).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Registers capabilities with WordPress roles. Call from Lifecycle_Manager::register_capabilities.
 * Every role with manage_options receives the full plugin set (Administrator and custom admin roles).
 * Editor receives a limited subset; other roles receive none unless they have manage_options.
 */
final class Capability_Registrar {

	private const ROLE_EDITOR = 'editor';

	/**
	 * Registers all plugin capabilities and assigns them per spec.
	 * Idempotent; safe to call on every activation.
	 *
	 * @return void
	 */
	public static function register(): void {
		$wp_roles = \wp_roles();
		foreach ( $wp_roles->roles as $role_key => $_role_data ) {
			$role = \get_role( $role_key );
			if ( ! $role instanceof \WP_Role || ! $role->has_cap( 'manage_options' ) ) {
				continue;
			}
			foreach ( Capabilities::get_all() as $cap ) {
				$role->add_cap( $cap );
			}
		}

		$editor = \get_role( self::ROLE_EDITOR );
		if ( $editor instanceof \WP_Role ) {
			foreach ( Capabilities::get_editor_defaults() as $cap ) {
				$editor->add_cap( $cap );
			}
		}
	}

	/**
	 * Re-applies capability grants when any manage_options role is missing a plugin cap.
	 * Activation can stop before register_capabilities; uninstall/reinstall can leave gaps. Idempotent.
	 *
	 * @return void
	 */
	public static function maybe_repair_administrator_caps(): void {
		$wp_roles = \wp_roles();
		foreach ( $wp_roles->roles as $role_key => $_role_data ) {
			$role = \get_role( $role_key );
			if ( ! $role instanceof \WP_Role || ! $role->has_cap( 'manage_options' ) ) {
				continue;
			}
			foreach ( Capabilities::get_all() as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					self::register();
					$uid = (int) \get_current_user_id();
					if ( $uid > 0 && \function_exists( 'clean_user_cache' ) ) {
						\clean_user_cache( $uid );
					}
					return;
				}
			}
		}
	}

	/**
	 * Removes all plugin capabilities from every role. Call from uninstall only.
	 *
	 * @return void
	 */
	public static function remove_from_all_roles(): void {
		$roles = \wp_roles();
		$caps  = Capabilities::get_all();
		foreach ( $roles->roles as $role_key => $role_data ) {
			$role = \get_role( $role_key );
			if ( $role instanceof \WP_Role ) {
				foreach ( $caps as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}

	/**
	 * Returns whether a role has a given capability. For diagnostics only; does not check user.
	 *
	 * @param string $role_key Role key (e.g. administrator, editor).
	 * @param string $cap      Capability name.
	 * @return bool
	 */
	public static function role_has_cap( string $role_key, string $cap ): bool {
		$role = \get_role( $role_key );
		if ( ! $role instanceof \WP_Role ) {
			return false;
		}
		return $role->has_cap( $cap );
	}
}
