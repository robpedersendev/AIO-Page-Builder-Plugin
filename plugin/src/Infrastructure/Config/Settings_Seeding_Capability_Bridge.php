<?php
/**
 * Bridges aio_manage_settings to registry CPT write caps during Settings hub seed actions.
 * Core insert/update checks use CPT meta caps; handlers are nonce-gated and match the General & seeding tab.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Scoped user_has_cap augmentation for template library seed callbacks.
 */
final class Settings_Seeding_Capability_Bridge {

	/** @var array<int, array<string, true>> */
	private static array $grant_stack = array();

	/**
	 * Runs a callback while optionally granting missing registry caps to users who have MANAGE_SETTINGS.
	 *
	 * @param callable(): mixed $callback Seed work.
	 * @param string            ...$grant_caps Capabilities::* to grant when absent (e.g. MANAGE_SECTION_TEMPLATES).
	 * @return mixed
	 */
	public static function run( callable $callback, string ...$grant_caps ) {
		$set = array();
		foreach ( $grant_caps as $c ) {
			if ( $c !== '' ) {
				$set[ $c ] = true;
			}
		}
		self::$grant_stack[] = $set;
		if ( count( self::$grant_stack ) === 1 ) {
			\add_filter( 'user_has_cap', array( self::class, 'filter_user_caps' ), 999, 4 );
		}
		try {
			return $callback();
		} finally {
			array_pop( self::$grant_stack );
			if ( self::$grant_stack === array() ) {
				\remove_filter( 'user_has_cap', array( self::class, 'filter_user_caps' ), 999 );
			}
		}
	}

	/**
	 * @param array<string, bool> $allcaps All caps for the user.
	 * @param string[]            $caps    Primitive caps being checked / merged (unused).
	 * @param array<int, mixed>   $args    Extra arguments (unused).
	 * @param \WP_User            $user    User object (unused).
	 * @return array<string, bool>
	 */
	public static function filter_user_caps( array $allcaps, array $caps, array $args, $user ): array {
		unset( $caps, $args, $user );
		if ( empty( $allcaps[ Capabilities::MANAGE_SETTINGS ] ) ) {
			return $allcaps;
		}
		foreach ( self::$grant_stack as $set ) {
			foreach ( array_keys( $set ) as $cap ) {
				if ( $cap !== '' && empty( $allcaps[ $cap ] ) ) {
					$allcaps[ $cap ] = true;
				}
			}
		}
		return $allcaps;
	}
}
