<?php
/**
 * Plugin options.
 *
 * @package PrivatePluginBase
 */

declare(strict_types=1);

namespace PrivatePluginBase;

defined( 'ABSPATH' ) || exit;

/**
 * Manages plugin options storage.
 */
final class Options {

	/**
	 * Option name for plugin meta (version, etc.).
	 *
	 * @var string
	 */
	public const META = 'private_plugin_base_meta';

	/**
	 * Stores the current plugin version in options.
	 *
	 * @return void
	 */
	public static function set_version(): void {
		$meta = get_option( self::META, array() );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}
		$meta['version'] = '0.1.0';
		update_option( self::META, $meta );
	}
}
