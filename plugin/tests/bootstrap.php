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
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( (string) $str );
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
		/** @var array<string, mixed> */
		public $query;

		public function __construct( $query = array() ) {
			$this->query = is_array( $query ) ? $query : array();
		}

		public function get_posts() {
			$posts      = isset( $GLOBALS['_aio_wp_query_posts'] ) ? $GLOBALS['_aio_wp_query_posts'] : array();
			$post_type  = $this->query['post_type'] ?? '';
			$meta_query = $this->query['meta_query'] ?? null;

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

			if ( ! is_array( $meta_query ) || empty( $posts ) ) {
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
					$dec  = json_decode( $row['_aio_snapshot_definition'], true );
					$val  = $filter_meta_key === '_aio_scope_type' ? ( (string) ( $dec['scope_type'] ?? '' ) ) : ( (string) ( $dec['scope_id'] ?? '' ) );
				}
				if ( (string) $val === (string) $filter_meta_value ) {
					$filtered[] = $post;
				}
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
				array( 'basedir' => '', 'baseurl' => '', 'error' => false ),
				$GLOBALS['_aio_wp_upload_dir']
			);
		}
		return array( 'basedir' => rtrim( sys_get_temp_dir(), '/\\' ), 'baseurl' => '', 'error' => false );
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
