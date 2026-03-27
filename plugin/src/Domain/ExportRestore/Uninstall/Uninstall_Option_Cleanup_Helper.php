<?php
/**
 * Deletes plugin-owned options during confirmed uninstall: declared keys plus prefix-scoped dynamic keys.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Uninstall;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Db\Wpdb_Prepared_Results;

/**
 * Single entry point for option-row deletion used by {@see Uninstall_Cleanup_Service}.
 * Does not remove posts, terms, or uploads. Network options: none are registered today ({@see Uninstall_Option_Registry}).
 */
final class Uninstall_Option_Cleanup_Helper {

	/**
	 * Deletes every option key returned by {@see Uninstall_Option_Registry::removable_declared_option_keys()}.
	 *
	 * @return int Count of successful {@see delete_option()} calls (false when absent is not counted).
	 */
	public static function delete_declared_options(): int {
		$removed = 0;
		foreach ( Uninstall_Option_Registry::removable_declared_option_keys() as $option_key ) {
			if ( \delete_option( $option_key ) ) {
				++$removed;
			}
		}
		return $removed;
	}

	/**
	 * Deletes options whose names start with one of {@see Uninstall_Option_Registry::removable_dynamic_option_prefixes()}.
	 *
	 * @param \wpdb $wpdb WordPress DB handle.
	 * @return int Number of option rows removed.
	 */
	public static function delete_options_matching_dynamic_prefixes( \wpdb $wpdb ): int {
		$table = $wpdb->options;
		$total = 0;
		foreach ( Uninstall_Option_Registry::removable_dynamic_option_prefixes() as $prefix ) {
			$prefix = (string) $prefix;
			if ( $prefix === '' ) {
				continue;
			}
			$like = $wpdb->esc_like( $prefix ) . '%';
			$ids  = Wpdb_Prepared_Results::get_col( $wpdb, 'SELECT option_id FROM %i WHERE option_name LIKE %s', array( $table, $like ) );
			foreach ( $ids as $id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Confirmed-uninstall option purge; caching not applicable.
				$wpdb->delete( $table, array( 'option_id' => $id ) );
				if ( $wpdb->last_error === '' ) {
					++$total;
				}
			}
		}
		return $total;
	}
}
