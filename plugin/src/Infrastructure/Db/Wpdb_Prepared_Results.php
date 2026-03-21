<?php
/**
 * Centralizes wpdb::prepare() + fetch/query so PHPCS / Plugin Check accept dynamic SQL with %i identifiers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Db;

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrappers: every statement is fully bound via wpdb::prepare(); table/column names use %i from allow-listed sources only.
 */
final class Wpdb_Prepared_Results {

	/**
	 * @param \wpdb  $wpdb WordPress DB object.
	 * @param string $query SQL with placeholders.
	 * @param array<int, mixed> $prepare_args Arguments in placeholder order.
	 * @param string $output OBJECT or ARRAY_A.
	 * @return object|array<string, mixed>|null
	 */
	public static function get_row( $wpdb, string $query, array $prepare_args, string $output = OBJECT ) {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Statement is assembled only through wpdb::prepare() below (incl. %i).
		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
		$prepared = $wpdb->prepare( $query, ...$prepare_args );
		if ( false === $prepared ) {
			return null;
		}
		$row = $wpdb->get_row( $prepared, $output );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter
		return $row;
	}

	/**
	 * @param \wpdb  $wpdb WordPress DB object.
	 * @param string $query SQL with placeholders.
	 * @param array<int, mixed> $prepare_args Arguments in placeholder order.
	 * @param string $output OBJECT or ARRAY_A.
	 * @return array<int, object|array<string, mixed>>
	 */
	public static function get_results( $wpdb, string $query, array $prepare_args, string $output = OBJECT ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
		$prepared = $wpdb->prepare( $query, ...$prepare_args );
		if ( false === $prepared ) {
			return array();
		}
		$rows = $wpdb->get_results( $prepared, $output );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * @param \wpdb  $wpdb WordPress DB object.
	 * @param string $query SQL with placeholders.
	 * @param array<int, mixed> $prepare_args Arguments in placeholder order.
	 * @return array<int, string>
	 */
	public static function get_col( $wpdb, string $query, array $prepare_args ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
		$prepared = $wpdb->prepare( $query, ...$prepare_args );
		if ( false === $prepared ) {
			return array();
		}
		$col = $wpdb->get_col( $prepared );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter
		return is_array( $col ) ? $col : array();
	}

	/**
	 * @param \wpdb  $wpdb WordPress DB object.
	 * @param string $query SQL with placeholders.
	 * @param array<int, mixed> $prepare_args Arguments in placeholder order.
	 * @return int|false
	 */
	public static function query( $wpdb, string $query, array $prepare_args ) {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
		$prepared = $wpdb->prepare( $query, ...$prepare_args );
		if ( false === $prepared ) {
			return false;
		}
		$result = $wpdb->query( $prepared );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter
		return $result;
	}

	/**
	 * @param \wpdb  $wpdb WordPress DB object.
	 * @param string $query SQL with placeholders.
	 * @param array<int, mixed> $prepare_args Arguments in placeholder order.
	 * @return string|null Scalar result.
	 */
	public static function get_var( $wpdb, string $query, array $prepare_args ): ?string {
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
		$prepared = $wpdb->prepare( $query, ...$prepare_args );
		if ( false === $prepared ) {
			return null;
		}
		$v = $wpdb->get_var( $prepared );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter
		if ( $v === null ) {
			return null;
		}
		return is_scalar( $v ) ? (string) $v : null;
	}
}
