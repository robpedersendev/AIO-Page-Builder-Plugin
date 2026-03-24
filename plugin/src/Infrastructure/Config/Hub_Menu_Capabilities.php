<?php
/**
 * Maps virtual hub capabilities to primitive checks so merged admin menus preserve prior access patterns.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Registers map_meta_cap for virtual hub capabilities (OR semantics across merged screens).
 *
 * * Never call user_can() / map_meta_cap recursively from this filter — it can trigger WP 6.1+ notices
 *   for meta capabilities such as delete_post. Use get_userdata()->allcaps for primitive checks.
 */
final class Hub_Menu_Capabilities {

	/**
	 * Hooks map_meta_cap. Call from Plugin bootstrap.
	 *
	 * @return void
	 */
	public static function register(): void {
		\add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
	}

	/**
	 * Maps virtual hub capabilities to primitive capability checks.
	 *
	 * @param string[] $caps    Primitive caps.
	 * @param string   $cap     Requested capability.
	 * @param int      $user_id User ID.
	 * @param array    $args    Extra args.
	 * @return string[]
	 */
	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		unset( $args );
		if ( $cap === Capabilities::ACCESS_SETTINGS_HUB ) {
			if ( self::user_has_primitive_cap( $user_id, Capabilities::MANAGE_SETTINGS ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::MANAGE_REPORTING_AND_PRIVACY ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::EXPORT_DATA ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::IMPORT_DATA ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_IMPORT_EXPORT_TAB ) {
			if ( self::user_has_primitive_cap( $user_id, Capabilities::EXPORT_DATA ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::IMPORT_DATA ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_AI_WORKSPACE ) {
			if ( self::user_has_primitive_cap( $user_id, Capabilities::VIEW_AI_RUNS ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::MANAGE_AI_PROVIDERS ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_ONBOARDING_WORKSPACE ) {
			if ( self::user_has_primitive_cap( $user_id, Capabilities::RUN_ONBOARDING ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::MANAGE_SETTINGS ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_PLANS_WORKSPACE ) {
			if ( self::user_has_primitive_cap( $user_id, Capabilities::VIEW_BUILD_PLANS ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::VIEW_LOGS ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_TEMPLATE_LIBRARY ) {
			// * Site admins always get the shell; VIEW_LOGS opens it for operators without template MANAGE_* (tabs stay gated per tab cap).
			if ( self::user_is_elevated_site_admin( $user_id ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::VIEW_LOGS ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::MANAGE_PAGE_TEMPLATES ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::MANAGE_SECTION_TEMPLATES ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::MANAGE_COMPOSITIONS ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		// * Registry CPT caps: if role grants are missing (activation gap), site admins still manage the template library.
		if ( $cap === Capabilities::MANAGE_SECTION_TEMPLATES
			|| $cap === Capabilities::MANAGE_PAGE_TEMPLATES
			|| $cap === Capabilities::MANAGE_COMPOSITIONS ) {
			if ( self::user_is_elevated_site_admin( $user_id ) ) {
				return array( 'read' );
			}
		}
		if ( $cap === Capabilities::ACCESS_INDUSTRY_WORKSPACE ) {
			if ( self::user_has_primitive_cap( $user_id, Capabilities::MANAGE_SETTINGS ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::IMPORT_DATA ) ||
				self::user_has_primitive_cap( $user_id, Capabilities::VIEW_LOGS ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		return $caps;
	}

	/**
	 * Site admin (manage_options) or network super admin.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function user_is_elevated_site_admin( int $user_id ): bool {
		if ( self::user_has_primitive_cap( $user_id, 'manage_options' ) ) {
			return true;
		}
		if ( \function_exists( 'is_multisite' ) && \is_multisite() && \function_exists( 'is_super_admin' ) && \is_super_admin( $user_id ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Reads a primitive capability from the user object without calling user_can() (avoids map_meta_cap recursion).
	 *
	 * @param int    $user_id User ID.
	 * @param string $cap     Capability name.
	 * @return bool
	 */
	private static function user_has_primitive_cap( int $user_id, string $cap ): bool {
		if ( $cap === '' ) {
			return false;
		}
		$user = \get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return false;
		}
		return ! empty( $user->allcaps[ $cap ] );
	}
}
