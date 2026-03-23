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
			if ( \user_can( $user_id, Capabilities::MANAGE_SETTINGS ) ||
				\user_can( $user_id, Capabilities::MANAGE_REPORTING_AND_PRIVACY ) ||
				\user_can( $user_id, Capabilities::EXPORT_DATA ) ||
				\user_can( $user_id, Capabilities::IMPORT_DATA ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_IMPORT_EXPORT_TAB ) {
			if ( \user_can( $user_id, Capabilities::EXPORT_DATA ) || \user_can( $user_id, Capabilities::IMPORT_DATA ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_AI_WORKSPACE ) {
			if ( \user_can( $user_id, Capabilities::VIEW_AI_RUNS ) ||
				\user_can( $user_id, Capabilities::MANAGE_AI_PROVIDERS ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_ONBOARDING_WORKSPACE ) {
			if ( \user_can( $user_id, Capabilities::RUN_ONBOARDING ) ||
				\user_can( $user_id, Capabilities::MANAGE_SETTINGS ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_PLANS_WORKSPACE ) {
			if ( \user_can( $user_id, Capabilities::VIEW_BUILD_PLANS ) || \user_can( $user_id, Capabilities::VIEW_LOGS ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_TEMPLATE_LIBRARY ) {
			if ( \user_can( $user_id, Capabilities::MANAGE_PAGE_TEMPLATES ) ||
				\user_can( $user_id, Capabilities::MANAGE_SECTION_TEMPLATES ) ||
				\user_can( $user_id, Capabilities::MANAGE_COMPOSITIONS ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		if ( $cap === Capabilities::ACCESS_INDUSTRY_WORKSPACE ) {
			if ( \user_can( $user_id, Capabilities::MANAGE_SETTINGS ) ||
				\user_can( $user_id, Capabilities::IMPORT_DATA ) ||
				\user_can( $user_id, Capabilities::VIEW_LOGS ) ) {
				return array( 'read' );
			}
			return array( 'do_not_allow' );
		}
		return $caps;
	}
}
