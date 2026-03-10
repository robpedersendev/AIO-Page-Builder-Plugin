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
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( (string) $str );
	}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		// No-op for unit tests; CPT registration runs in WP context.
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
if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $sql ) {
		// No-op for unit tests; real table creation runs in WP context.
	}
}
// * Stubs for repository unit tests (get_post, meta, WP_Query, post insert/update). Control via $GLOBALS['_aio_*'].
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
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
	function get_post( $id = null, $output = OBJECT, $filter = 'raw' ) {
		return isset( $GLOBALS['_aio_get_post_return'] ) ? $GLOBALS['_aio_get_post_return'] : null;
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
	function wp_update_post( $postarr, $wp_error = false ) {
		$id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
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
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof \WP_Error;
	}
}
if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public function __construct( $query = array() ) {
			// Stub: posts are provided by tests via $GLOBALS['_aio_wp_query_posts'].
		}
		public function get_posts() {
			return isset( $GLOBALS['_aio_wp_query_posts'] ) ? $GLOBALS['_aio_wp_query_posts'] : array();
		}
	}
}
