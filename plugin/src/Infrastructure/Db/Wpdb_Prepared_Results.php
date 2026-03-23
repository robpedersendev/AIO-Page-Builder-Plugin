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
	 * @param \wpdb             $wpdb WordPress DB object.
	 * @param string            $query SQL with placeholders.
	 * @param array<int, mixed> $prepare_args Arguments in placeholder order.
	 * @param string            $output OBJECT or ARRAY_A.
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
	 * @param \wpdb             $wpdb WordPress DB object.
	 * @param string            $query SQL with placeholders.
	 * @param array<int, mixed> $prepare_args Arguments in placeholder order.
	 * @param string            $output OBJECT or ARRAY_A.
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
	 * @param \wpdb             $wpdb WordPress DB object.
	 * @param string            $query SQL with placeholders.
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
	 * @param \wpdb             $wpdb WordPress DB object.
	 * @param string            $query SQL with placeholders.
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
	 * @param \wpdb             $wpdb WordPress DB object.
	 * @param string            $query SQL with placeholders.
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

	/**
	 * Resolves a post ID by post type and two exact meta key/value pairs (AND). Uses object cache (short TTL).
	 *
	 * @param \wpdb  $wpdb WordPress DB object.
	 * @param string $post_type Post type slug.
	 * @param string $meta_key_1 First meta key.
	 * @param string $meta_value_1 First meta value (exact string).
	 * @param string $meta_key_2 Second meta key.
	 * @param string $meta_value_2 Second meta value (exact string).
	 */
	public static function find_post_id_by_post_type_two_meta(
		$wpdb,
		string $post_type,
		string $meta_key_1,
		string $meta_value_1,
		string $meta_key_2,
		string $meta_value_2
	): int {
		$cache_key = 'aio_pb_p2m_' . md5( $post_type . '|' . $meta_key_1 . '|' . $meta_value_1 . '|' . $meta_key_2 . '|' . $meta_value_2 );
		$group     = 'aio_pb_db_lookup';
		$cached    = \wp_cache_get( $cache_key, $group );
		if ( false !== $cached ) {
			return (int) $cached;
		}
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Assembled via wpdb::prepare() below.
		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
		$sql = 'SELECT p.ID FROM ' . $wpdb->posts . ' AS p '
			. 'INNER JOIN ' . $wpdb->postmeta . ' AS m1 ON p.ID = m1.post_id AND m1.meta_key = %s AND m1.meta_value = %s '
			. 'INNER JOIN ' . $wpdb->postmeta . ' AS m2 ON p.ID = m2.post_id AND m2.meta_key = %s AND m2.meta_value = %s '
			. "WHERE p.post_type = %s AND p.post_status NOT IN ('trash','auto-draft') LIMIT 1";
		$id  = (int) self::get_var(
			$wpdb,
			$sql,
			array( $meta_key_1, $meta_value_1, $meta_key_2, $meta_value_2, $post_type )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter
		\wp_cache_set( $cache_key, $id, $group, 60 );
		return $id;
	}

	/**
	 * Resolves a post ID by post type and one exact meta key/value. Uses object cache (short TTL).
	 *
	 * @param \wpdb  $wpdb WordPress DB object.
	 * @param string $post_type Post type slug.
	 * @param string $meta_key Meta key.
	 * @param string $meta_value Meta value (exact string).
	 */
	public static function find_post_id_by_post_type_meta_key_value(
		$wpdb,
		string $post_type,
		string $meta_key,
		string $meta_value
	): int {
		$cache_key = 'aio_pb_p1m_' . md5( $post_type . '|' . $meta_key . '|' . $meta_value );
		$group     = 'aio_pb_db_lookup';
		$cached    = \wp_cache_get( $cache_key, $group );
		if ( false !== $cached ) {
			return (int) $cached;
		}
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Assembled via wpdb::prepare() below.
		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
		$sql = 'SELECT p.ID FROM ' . $wpdb->posts . ' AS p '
			. 'INNER JOIN ' . $wpdb->postmeta . ' AS m ON p.ID = m.post_id AND m.meta_key = %s AND m.meta_value = %s '
			. "WHERE p.post_type = %s AND p.post_status NOT IN ('trash','auto-draft') ORDER BY p.ID ASC LIMIT 1";
		$id  = (int) self::get_var( $wpdb, $sql, array( $meta_key, $meta_value, $post_type ) );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter
		\wp_cache_set( $cache_key, $id, $group, 60 );
		return $id;
	}

	/**
	 * Lists post IDs by post type and one meta key/value (prepared SQL; avoids WP_Query meta_key sniff noise).
	 *
	 * @param \wpdb  $wpdb WordPress DB object.
	 * @param string $post_type Post type slug.
	 * @param string $meta_key Meta key.
	 * @param string $meta_value Meta value (exact string).
	 * @param int    $limit Max rows (clamped).
	 * @param int    $offset Offset (non-negative).
	 * @param string $orderby_key One of post_date, ID, post_modified.
	 * @param string $order ASC or DESC.
	 * @return array<int, int>
	 */
	public static function find_post_ids_by_post_type_meta_key_value(
		$wpdb,
		string $post_type,
		string $meta_key,
		string $meta_value,
		int $limit,
		int $offset,
		string $orderby_key = 'post_date',
		string $order = 'DESC'
	): array {
		$columns = array(
			'post_date'     => 'p.post_date',
			'ID'            => 'p.ID',
			'post_modified' => 'p.post_modified',
		);
		$ob_expr = $columns[ $orderby_key ] ?? 'p.post_date';
		$order   = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
		$limit   = max( 1, min( 500, $limit ) );
		$offset  = max( 0, $offset );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ORDER BY uses allow-listed column only.
		// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
		$sql  = 'SELECT p.ID FROM ' . $wpdb->posts . ' AS p '
			. 'INNER JOIN ' . $wpdb->postmeta . ' AS m ON p.ID = m.post_id AND m.meta_key = %s AND m.meta_value = %s '
			. "WHERE p.post_type = %s AND p.post_status NOT IN ('trash','auto-draft') "
			. 'ORDER BY ' . $ob_expr . ' ' . $order . ' LIMIT %d OFFSET %d';
		$rows = self::get_col( $wpdb, $sql, array( $meta_key, $meta_value, $post_type, $limit, $offset ) );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:enable PluginCheck.Security.DirectDB.UnescapedDBParameter
		return array_map( 'intval', $rows );
	}
}
