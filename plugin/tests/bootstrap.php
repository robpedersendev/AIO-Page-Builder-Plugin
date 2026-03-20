<?php
/**
 * PHPUnit bootstrap.
 *
 * @package PrivatePluginBase
 */

// Composer autoload.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define ABSPATH if not in WordPress context.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/wordpress/' );
}
if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}
if ( ! function_exists( 'acf_add_local_field_group' ) ) {
	function acf_add_local_field_group( $group ) {
		// No-op for unit tests so ACF registration tests can run.
	}
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
// WordPress helpers when not in WP context (e.g. unit tests for Constants, Settings_Service).
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $path ) {
		return rtrim( $path, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) {
		return isset( $GLOBALS['_aio_test_options'][ $key ] ) ? $GLOBALS['_aio_test_options'][ $key ] : $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $key, $value ) {
		$GLOBALS['_aio_test_options'][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $key ) {
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
		return true;
	}
}
if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	function wp_generate_uuid4() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff )
		);
	}
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		$url = trim( (string) $url );
		return ( $url !== '' && preg_match( '#^https?://#i', $url ) ) ? $url : '';
	}
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string ) {
		return preg_replace( '@<[^>]*?>@s', '', (string) $string );
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( (string) $str );
	}
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( (string) $str );
	}
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $str ) {
		return preg_replace( '/[^a-z0-9\-]/', '-', strtolower( (string) $str ) );
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain -- Test stub; params are variables by design.
		echo esc_attr( __( $text, $domain ) );
	}
}
if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain -- Test stub; params are variables by design.
		echo esc_html( __( $text, $domain ) );
	}
}
if ( ! function_exists( 'sanitize_html_class' ) ) {
	function sanitize_html_class( $class ) {
		return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $class ) );
	}
}
if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return is_string( $data ) ? strip_tags( $data ) : '';
	}
}
// * Stub for page template detail preview (do_blocks). Unit tests get block markup as-is.
if ( ! function_exists( 'do_blocks' ) ) {
	function do_blocks( $content ) {
		return is_string( $content ) ? $content : '';
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		$url = trim( (string) $url );
		return ( $url !== '' && preg_match( '#^https?://#i', $url ) ) ? $url : '';
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '', $scheme = 'admin' ) {
		return 'http://example.org/wp-admin/' . ltrim( (string) $path, '/' );
	}
}
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $key_or_array, $value = false, $url = false ) {
		if ( $url === false ) {
			$url = 'http://example.org/wp-admin/admin.php';
		}
		$url = (string) $url;
		if ( is_array( $key_or_array ) ) {
			$query  = array();
			$parsed = parse_url( $url );
			if ( ! empty( $parsed['query'] ) ) {
				parse_str( $parsed['query'], $query );
			}
			$query = array_merge( $query, $key_or_array );
			$base  = ( isset( $parsed['scheme'] ) ? $parsed['scheme'] . '://' : '' ) . ( $parsed['host'] ?? '' ) . ( $parsed['path'] ?? '' );
			return $base . '?' . http_build_query( $query );
		}
		$sep = strpos( $url, '?' ) !== false ? '&' : '?';
		return $url . $sep . rawurlencode( (string) $key_or_array ) . ( $value !== false ? '=' . rawurlencode( (string) $value ) : '' );
	}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		if ( ! isset( $GLOBALS['_aio_actions'] ) || ! is_array( $GLOBALS['_aio_actions'] ) ) {
			$GLOBALS['_aio_actions'] = array();
		}
		if ( ! isset( $GLOBALS['_aio_actions'][ $tag ] ) || ! is_array( $GLOBALS['_aio_actions'][ $tag ] ) ) {
			$GLOBALS['_aio_actions'][ $tag ] = array();
		}
		$GLOBALS['_aio_actions'][ $tag ][] = $callback;
	}
}
if ( ! function_exists( 'do_action' ) ) {
	function do_action( $tag, ...$args ) {
		$callbacks = $GLOBALS['_aio_actions'][ $tag ] ?? array();
		if ( ! is_array( $callbacks ) || $callbacks === array() ) {
			return;
		}
		foreach ( $callbacks as $cb ) {
			if ( is_callable( $cb ) ) {
				call_user_func_array( $cb, $args );
			}
		}
	}
}
// * Stubs for heartbeat scheduler tests (spec §46.4, §53.5). Track scheduled state in $GLOBALS['_aio_cron_scheduled'].
if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook, $args = array() ) {
		return ! empty( $GLOBALS['_aio_cron_scheduled'][ $hook ] );
	}
}
if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
		if ( ! isset( $GLOBALS['_aio_cron_scheduled'] ) ) {
			$GLOBALS['_aio_cron_scheduled'] = array();
		}
		$GLOBALS['_aio_cron_scheduled'][ $hook ] = true;
		return true;
	}
}
if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
	function wp_clear_scheduled_hook( $hook ) {
		if ( isset( $GLOBALS['_aio_cron_scheduled'][ $hook ] ) ) {
			unset( $GLOBALS['_aio_cron_scheduled'][ $hook ] );
		}
	}
}
if ( ! function_exists( 'register_post_type' ) ) {
	function register_post_type( $post_type, $args = array() ) {
		if ( ! isset( $GLOBALS['_aio_registered_post_types'] ) ) {
			$GLOBALS['_aio_registered_post_types'] = array();
		}
		$GLOBALS['_aio_registered_post_types'][ $post_type ] = $args;
		return $post_type;
	}
}
if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		return ( (int) $number === 1 ) ? $single : $plural;
	}
}
if ( ! function_exists( 'dbDelta' ) ) {
	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- Stub must match WordPress function name dbDelta.
	function dbDelta( $sql ) {
		// No-op for unit tests; real table creation runs in WP context.
	}
}
// * Stubs for repository unit tests (get_post, meta, WP_Query, post insert/update). Control via $GLOBALS['_aio_*'].
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
// * Stubs for privacy exporter/eraser tests (Tools → Export/Erase Personal Data). Control via $GLOBALS['_aio_get_user_by_return'].
if ( ! class_exists( 'WP_User' ) ) {
	class WP_User {
		public $ID;
		public $user_email;
		public function __construct( $id = 0, $email = '' ) {
			$this->ID         = $id;
			$this->user_email = $email;
		}
	}
}
if ( ! function_exists( 'get_user_by' ) ) {
	function get_user_by( $field, $value ) {
		return isset( $GLOBALS['_aio_get_user_by_return'] ) ? $GLOBALS['_aio_get_user_by_return'] : null;
	}
}
if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $key = '', $single = false ) {
		$store = $GLOBALS['_aio_user_meta'] ?? array();
		$id    = (string) $user_id;
		if ( $key === '' ) {
			return isset( $store[ $id ] ) ? $store[ $id ] : array();
		}
		$val = isset( $store[ $id ][ $key ] ) ? $store[ $id ][ $key ] : '';
		return $single ? $val : array( $val );
	}
}
if ( ! function_exists( 'update_user_meta' ) ) {
	function update_user_meta( $user_id, $meta_key, $meta_value ) {
		$id = (string) $user_id;
		if ( ! isset( $GLOBALS['_aio_user_meta'][ $id ] ) ) {
			$GLOBALS['_aio_user_meta'][ $id ] = array();
		}
		$GLOBALS['_aio_user_meta'][ $id ][ $meta_key ] = $meta_value;
		return true;
	}
}
if ( ! function_exists( 'delete_user_meta' ) ) {
	function delete_user_meta( $user_id, $meta_key ) {
		$id = (string) $user_id;
		if ( isset( $GLOBALS['_aio_user_meta'][ $id ][ $meta_key ] ) ) {
			unset( $GLOBALS['_aio_user_meta'][ $id ][ $meta_key ] );
		}
		return true;
	}
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		return isset( $GLOBALS['_aio_transients'][ $key ] ) ? $GLOBALS['_aio_transients'][ $key ] : false;
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $expiration = 0 ) {
		if ( ! isset( $GLOBALS['_aio_transients'] ) || ! is_array( $GLOBALS['_aio_transients'] ) ) {
			$GLOBALS['_aio_transients'] = array();
		}
		$GLOBALS['_aio_transients'][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = array() ) {
		$posts = isset( $GLOBALS['_aio_wp_query_posts'] ) ? $GLOBALS['_aio_wp_query_posts'] : array();
		$post_type = isset( $args['post_type'] ) ? $args['post_type'] : '';
		if ( $post_type !== '' ) {
			$by_type = array();
			foreach ( $posts as $post ) {
				$pt = is_object( $post ) ? ( $post->post_type ?? '' ) : ( $post['post_type'] ?? '' );
				if ( $pt === $post_type ) {
					$by_type[] = $post;
				}
			}
			$posts = $by_type;
		}
		if ( ! empty( $args['fields'] ) && $args['fields'] === 'ids' ) {
			$out = array();
			foreach ( $posts as $post ) {
				$out[] = is_object( $post ) ? ( $post->ID ?? 0 ) : ( $post['ID'] ?? 0 );
			}
			return $out;
		}
		return $posts;
	}
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) {
		if ( isset( $GLOBALS['_aio_transients'][ $key ] ) ) {
			unset( $GLOBALS['_aio_transients'][ $key ] );
		}
		return true;
	}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		// No-op for unit tests; privacy filters registered in WP context.
	}
}
if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public $ID;
		public $post_type;
		public $post_title;
		public $post_status;
		public $post_name;
		public function __construct( $props = array() ) {
			foreach ( $props as $k => $v ) {
				$this->$k = $v;
			}
		}
	}
}
if ( ! function_exists( 'get_post' ) ) {
	/**
	 * Per-ID post stub: checks _aio_get_post_by_id[$id] first, then _aio_get_post_return.
	 *
	 * @param int|null $id
	 * @param string   $output
	 * @param string   $filter
	 * @return \WP_Post|\stdClass|null
	 */
	function get_post( $id = null, $output = OBJECT, $filter = 'raw' ) {
		if ( $id !== null && isset( $GLOBALS['_aio_get_post_by_id'] ) && is_array( $GLOBALS['_aio_get_post_by_id'] ) ) {
			$key = (int) $id;
			if ( array_key_exists( $key, $GLOBALS['_aio_get_post_by_id'] ) ) {
				return $GLOBALS['_aio_get_post_by_id'][ $key ];
			}
		}
		return isset( $GLOBALS['_aio_get_post_return'] ) ? $GLOBALS['_aio_get_post_return'] : null;
	}
}
if ( ! function_exists( 'get_term' ) ) {
	function get_term( $term_id, $taxonomy = '' ) {
		return isset( $GLOBALS['_aio_get_term_return'] ) ? $GLOBALS['_aio_get_term_return'] : null;
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		return isset( $GLOBALS['_aio_current_user_can_return'] ) ? (bool) $GLOBALS['_aio_current_user_can_return'] : false;
	}
}
if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = false ) {
		$store = $GLOBALS['_aio_post_meta'] ?? array();
		$id    = (string) $post_id;
		if ( $key === '' ) {
			return isset( $store[ $id ] ) ? $store[ $id ] : array();
		}
		$val = isset( $store[ $id ][ $key ] ) ? $store[ $id ][ $key ] : '';
		return $single ? $val : array( $val );
	}
}
if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $meta_key, $meta_value ) {
		$id = (string) $post_id;
		if ( ! isset( $GLOBALS['_aio_post_meta'][ $id ] ) ) {
			$GLOBALS['_aio_post_meta'][ $id ] = array();
		}
		$GLOBALS['_aio_post_meta'][ $id ][ $meta_key ] = $meta_value;
		return true;
	}
}
if ( ! function_exists( 'wp_insert_post' ) ) {
	function wp_insert_post( $postarr, $wp_error = false ) {
		$id = isset( $GLOBALS['_aio_wp_insert_post_return'] ) ? $GLOBALS['_aio_wp_insert_post_return'] : 0;
		return $wp_error && $id === 0 ? new \WP_Error( 'insert_failed', 'Stub' ) : $id;
	}
}
if ( ! function_exists( 'wp_update_post' ) ) {
	/**
	 * _aio_wp_update_post_raw_return bypasses the WP_Error conversion and returns verbatim.
	 * Use it when you need to test the branch that handles an integer 0 even when $wp_error=true.
	 *
	 * @param array $postarr
	 * @param bool  $wp_error
	 * @return int|\WP_Error
	 */
	function wp_update_post( $postarr, $wp_error = false ) {
		if ( isset( $GLOBALS['_aio_wp_update_post_raw_return'] ) ) {
			return $GLOBALS['_aio_wp_update_post_raw_return'];
		}
		$id  = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		$ret = isset( $GLOBALS['_aio_wp_update_post_return'] ) ? $GLOBALS['_aio_wp_update_post_return'] : $id;
		return $wp_error && $ret === 0 ? new \WP_Error( 'update_failed', 'Stub' ) : $ret;
	}
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
		public function get_error_code() {
			return $this->code;
		}
		public function get_error_message() {
			return $this->message;
		}
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof \WP_Error;
	}
}
if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		/** @var array<string, mixed> */
		public $query;

		/** @var int Set in get_posts() for count_definitions and pagination (when no_found_rows is false). */
		public $found_posts = 0;

		public function __construct( $query = array() ) {
			$this->query = is_array( $query ) ? $query : array();
		}

		public function get_posts() {
			$posts      = isset( $GLOBALS['_aio_wp_query_posts'] ) ? $GLOBALS['_aio_wp_query_posts'] : array();
			$post_type  = $this->query['post_type'] ?? '';
			$meta_query = $this->query['meta_query'] ?? null;
			$offset     = (int) ( $this->query['offset'] ?? 0 );
			$per_page   = (int) ( $this->query['posts_per_page'] ?? 0 );

			if ( $post_type !== '' ) {
				$by_type = array();
				foreach ( $posts as $post ) {
					$pt = is_object( $post ) ? ( $post->post_type ?? '' ) : ( $post['post_type'] ?? '' );
					if ( $pt === $post_type ) {
						$by_type[] = $post;
					}
				}
				$posts = $by_type;
			}

			if ( ! is_array( $meta_query ) || empty( $meta_query ) ) {
				$this->found_posts = count( $posts );
				if ( $per_page > 0 ) {
					$posts = array_slice( $posts, $offset, $per_page );
				} elseif ( $offset > 0 ) {
					$posts = array_slice( $posts, $offset );
				}
				return $posts;
			}
			$filter_meta_key   = null;
			$filter_meta_value = null;
			foreach ( $meta_query as $mq ) {
				if ( ! is_array( $mq ) || ! array_key_exists( 'value', $mq ) ) {
					continue;
				}
				$k = $mq['key'] ?? '';
				if ( $k === '_aio_internal_key' || $k === '_aio_scope_type' || $k === '_aio_scope_id' || $k === '_aio_status' ) {
					$filter_meta_key   = $k;
					$filter_meta_value = $mq['value'];
					break;
				}
			}
			if ( $filter_meta_key === null ) {
				$this->found_posts = count( $posts );
				if ( $per_page > 0 ) {
					$posts = array_slice( $posts, $offset, $per_page );
				} elseif ( $offset > 0 ) {
					$posts = array_slice( $posts, $offset );
				}
				return $posts;
			}
			$filtered = array();
			$meta     = $GLOBALS['_aio_post_meta'] ?? array();
			foreach ( $posts as $post ) {
				$id  = is_object( $post ) ? $post->ID : ( $post['ID'] ?? 0 );
				$row = $meta[ (string) $id ] ?? array();
				$val = $row[ $filter_meta_key ] ?? '';
				if ( $val === '' && $filter_meta_key === '_aio_internal_key' ) {
					if ( ! empty( $row['_aio_section_definition'] ) ) {
						$dec = json_decode( $row['_aio_section_definition'], true );
						$val = isset( $dec['internal_key'] ) ? (string) $dec['internal_key'] : '';
					}
					if ( $val === '' && ! empty( $row['_aio_page_template_definition'] ) ) {
						$dec = json_decode( $row['_aio_page_template_definition'], true );
						$val = isset( $dec['internal_key'] ) ? (string) $dec['internal_key'] : '';
					}
					if ( $val === '' && ! empty( $row['_aio_composition_definition'] ) ) {
						$dec = json_decode( $row['_aio_composition_definition'], true );
						$val = isset( $dec['composition_id'] ) ? (string) $dec['composition_id'] : '';
					}
					if ( $val === '' && ! empty( $row['_aio_snapshot_definition'] ) ) {
						$dec = json_decode( $row['_aio_snapshot_definition'], true );
						$val = isset( $dec['snapshot_id'] ) ? (string) $dec['snapshot_id'] : '';
					}
				}
				if ( $val === '' && in_array( $filter_meta_key, array( '_aio_scope_type', '_aio_scope_id' ), true ) && ! empty( $row['_aio_snapshot_definition'] ) ) {
					$dec = json_decode( $row['_aio_snapshot_definition'], true );
					$val = $filter_meta_key === '_aio_scope_type' ? ( (string) ( $dec['scope_type'] ?? '' ) ) : ( (string) ( $dec['scope_id'] ?? '' ) );
				}
				if ( (string) $val === (string) $filter_meta_value ) {
					$filtered[] = $post;
				}
			}
			$this->found_posts = count( $filtered );
			if ( $per_page > 0 ) {
				$filtered = array_slice( $filtered, $offset, $per_page );
			} elseif ( $offset > 0 ) {
				$filtered = array_slice( $filtered, $offset );
			}
			return $filtered;
		}
	}
}
// * Stubs for Plugin_Path_Manager tests. Control via $GLOBALS['_aio_wp_upload_dir'].
if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir() {
		if ( isset( $GLOBALS['_aio_wp_upload_dir'] ) && is_array( $GLOBALS['_aio_wp_upload_dir'] ) ) {
			return array_merge(
				array(
					'basedir' => '',
					'baseurl' => '',
					'error'   => false,
				),
				$GLOBALS['_aio_wp_upload_dir']
			);
		}
		return array(
			'basedir' => rtrim( sys_get_temp_dir(), '/\\' ),
			'baseurl' => '',
			'error'   => false,
		);
	}
}
if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $target ) {
		$target = rtrim( $target, '/\\' );
		if ( is_dir( $target ) ) {
			return true;
		}
		return @mkdir( $target, 0755, true );
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, $depth );
	}
}
if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = '' ) {
		return 'test-nonce-' . $action;
	}
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true ) {
		echo '<input type="hidden" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $action ) ) . '" />';
	}
}
if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, $display = true ) {
		if ( (string) $selected === (string) $current ) {
			echo $display ? ' selected="selected"' : ' selected';
		}
	}
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type = 'mysql', $gmt = false ) {
		return $type === 'mysql' ? gmdate( 'Y-m-d H:i:s' ) : (string) time();
	}
}
if ( ! function_exists( 'wp_rand' ) ) {
	function wp_rand( $min = 0, $max = null ) {
		return $max === null ? mt_rand() : mt_rand( (int) $min, (int) $max );
	}
}
// * Stubs for HTML_Fetcher tests. Set $GLOBALS['_aio_wp_remote_get_return'] to callable( $url, $args ) => array|WP_Error.
if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		if ( isset( $GLOBALS['_aio_wp_remote_get_return'] ) && is_callable( $GLOBALS['_aio_wp_remote_get_return'] ) ) {
			return $GLOBALS['_aio_wp_remote_get_return']( $url, $args );
		}
		return new \WP_Error( 'no_mock', 'Set _aio_wp_remote_get_return' );
	}
}
if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		if ( is_array( $response ) ) {
			if ( isset( $response['response']['code'] ) ) {
				return (int) $response['response']['code'];
			}
			if ( isset( $response['code'] ) ) {
				return (int) $response['code'];
			}
		}
		return 0;
	}
}
if ( ! function_exists( 'wp_remote_retrieve_headers' ) ) {
	function wp_remote_retrieve_headers( $response ) {
		return is_array( $response ) && isset( $response['headers'] ) ? $response['headers'] : array();
	}
}
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return is_array( $response ) && array_key_exists( 'body', $response ) ? $response['body'] : '';
	}
}
if ( ! function_exists( 'wp_remote_retrieve_header' ) ) {
	function wp_remote_retrieve_header( $response, $header ) {
		$headers = is_array( $response ) && isset( $response['headers'] ) ? $response['headers'] : array();
		$header  = strtolower( $header );
		foreach ( $headers as $k => $v ) {
			if ( strtolower( (string) $k ) === $header ) {
				return is_array( $v ) ? ( $v[0] ?? '' ) : (string) $v;
			}
		}
		return '';
	}
}
if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = array() ) {
		return isset( $GLOBALS['__driver_cost_test_mock_response'] )
			? $GLOBALS['__driver_cost_test_mock_response']
			: array( 'code' => 200, 'body' => '', 'headers' => array() );
	}
}
if ( ! function_exists( 'wp_create_nav_menu' ) ) {
	function wp_create_nav_menu( $menu_name ) {
		if ( isset( $GLOBALS['_aio_wp_create_nav_menu_return'] ) ) {
			return $GLOBALS['_aio_wp_create_nav_menu_return'];
		}
		return isset( $GLOBALS['_aio_test_nav_menu_id'] ) ? (int) $GLOBALS['_aio_test_nav_menu_id'] : 1;
	}
}
if ( ! function_exists( 'get_registered_nav_menus' ) ) {
	function get_registered_nav_menus(): array {
		return isset( $GLOBALS['_aio_test_registered_nav_menus'] ) ? (array) $GLOBALS['_aio_test_registered_nav_menus'] : array();
	}
}
if ( ! function_exists( 'get_theme_mod' ) ) {
	function get_theme_mod( $name, $default = false ) {
		$mods = isset( $GLOBALS['_aio_test_theme_mods'] ) ? (array) $GLOBALS['_aio_test_theme_mods'] : array();
		return array_key_exists( $name, $mods ) ? $mods[ $name ] : $default;
	}
}
if ( ! function_exists( 'set_theme_mod' ) ) {
	function set_theme_mod( $name, $value ): void {
		if ( ! isset( $GLOBALS['_aio_test_theme_mods'] ) ) {
			$GLOBALS['_aio_test_theme_mods'] = array();
		}
		$GLOBALS['_aio_test_theme_mods'][ $name ] = $value;
	}
}
if ( ! function_exists( 'wp_update_nav_menu_item' ) ) {
	function wp_update_nav_menu_item( $menu_id, $menu_item_db_id, $args = array(), $fire_after_hooks = true ) {
		return isset( $GLOBALS['_aio_test_nav_menu_item_id'] ) ? (int) $GLOBALS['_aio_test_nav_menu_item_id'] : 1;
	}
}
if ( ! function_exists( 'wp_delete_nav_menu' ) ) {
	function wp_delete_nav_menu( $menu ) {
		return true;
	}
}
