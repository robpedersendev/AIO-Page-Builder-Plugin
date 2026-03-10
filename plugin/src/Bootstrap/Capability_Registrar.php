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
 * Administrator receives all; Editor receives limited subset; Author/Contributor/Subscriber receive none.
 */
final class Capability_Registrar {

	private const ROLE_ADMINISTRATOR = 'administrator';
	private const ROLE_EDITOR        = 'editor';

	/**
	 * Registers all plugin capabilities and assigns them to Administrator and Editor per spec.
	 * Idempotent; safe to call on every activation.
	 *
	 * @return void
	 */
	public static function register(): void {
		$admin = \get_role( self::ROLE_ADMINISTRATOR );
		if ( $admin instanceof \WP_Role ) {
			foreach ( Capabilities::getAll() as $cap ) {
				$admin->add_cap( $cap );
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
	 * Removes all plugin capabilities from every role. Call from uninstall only.
	 *
	 * @return void
	 */
	public static function remove_from_all_roles(): void {
		$roles = \wp_roles();
		if ( ! $roles instanceof \WP_Roles ) {
			return;
		}
		$caps = Capabilities::getAll();
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
