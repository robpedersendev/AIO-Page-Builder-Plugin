<?php
/**
 * Seeds minimal main-query / global post context for theme compatibility in live preview.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Frontend\Theme_Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Applies a synthetic page-like context so themes expecting singular/page queries can run without fatals.
 */
final class Preview_Shell_Service {

	/** @var bool */
	private static $applied = false;

	/** @var \WP_Post|null */
	private static $prior_post = null;

	/** @var array<string, mixed> */
	private static $saved_query = array();

	/**
	 * @return bool False when the shell cannot be applied safely.
	 */
	public static function apply(): bool {
		if ( self::$applied ) {
			return true;
		}
		global $wp_query, $post;
		if ( ! isset( $wp_query ) || ! ( $wp_query instanceof \WP_Query ) ) {
			return false;
		}

		self::$prior_post = isset( $post ) && $post instanceof \WP_Post ? $post : null;

		self::$saved_query = array(
			'is_page'           => (bool) $wp_query->is_page,
			'is_singular'       => (bool) $wp_query->is_singular,
			'is_home'           => (bool) $wp_query->is_home,
			'is_404'            => (bool) $wp_query->is_404,
			'is_preview'        => (bool) $wp_query->is_preview,
			'queried_object'    => $wp_query->queried_object,
			'queried_object_id' => (int) $wp_query->queried_object_id,
			'post'              => $wp_query->post,
			'posts'             => $wp_query->posts,
			'post_count'        => (int) $wp_query->post_count,
			'current_post'      => (int) $wp_query->current_post,
		);

		$now     = \function_exists( 'current_time' ) ? \current_time( 'mysql' ) : gmdate( 'Y-m-d H:i:s' );
		$now_gmt = \function_exists( 'current_time' ) ? \current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s' );

		$synthetic = new \WP_Post(
			(object) array(
				'ID'                    => 0,
				'post_author'           => 0,
				'post_date'             => $now,
				'post_date_gmt'         => $now_gmt,
				'post_content'          => '',
				'post_title'            => 'AIO Template Live Preview',
				'post_excerpt'          => '',
				'post_status'           => 'publish',
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_password'         => '',
				'post_name'             => 'aio-template-live-preview',
				'to_ping'               => '',
				'pinged'                => '',
				'post_modified'         => $now,
				'post_modified_gmt'     => $now_gmt,
				'post_content_filtered' => '',
				'post_parent'           => 0,
				'guid'                  => '',
				'menu_order'            => 0,
				'post_type'             => 'page',
				'post_mime_type'        => '',
				'comment_count'         => 0,
				'filter'                => 'raw',
			)
		);

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Synthetic preview shell; restored in restore().
		$post            = $synthetic;
		$GLOBALS['post'] = $synthetic;
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		$wp_query->is_page           = true;
		$wp_query->is_singular       = true;
		$wp_query->is_home           = false;
		$wp_query->is_404            = false;
		$wp_query->is_preview        = false;
		$wp_query->queried_object    = $synthetic;
		$wp_query->queried_object_id = 0;
		$wp_query->post              = $synthetic;
		$wp_query->posts             = array( $synthetic );
		$wp_query->post_count        = 1;
		$wp_query->current_post      = -1;

		self::$applied = true;
		return true;
	}

	/**
	 * @return void
	 */
	public static function restore(): void {
		if ( ! self::$applied ) {
			return;
		}
		global $wp_query, $post;
		if ( isset( $wp_query ) && $wp_query instanceof \WP_Query && self::$saved_query !== array() ) {
			$s                           = self::$saved_query;
			$wp_query->is_page           = (bool) ( $s['is_page'] ?? false );
			$wp_query->is_singular       = (bool) ( $s['is_singular'] ?? false );
			$wp_query->is_home           = (bool) ( $s['is_home'] ?? false );
			$wp_query->is_404            = (bool) ( $s['is_404'] ?? false );
			$wp_query->is_preview        = (bool) ( $s['is_preview'] ?? false );
			$wp_query->queried_object    = $s['queried_object'] ?? null;
			$wp_query->queried_object_id = (int) ( $s['queried_object_id'] ?? 0 );
			$wp_query->post              = $s['post'] ?? null;
			$wp_query->posts             = \is_array( $s['posts'] ?? null ) ? $s['posts'] : array();
			$wp_query->post_count        = (int) ( $s['post_count'] ?? 0 );
			$wp_query->current_post      = (int) ( $s['current_post'] ?? 0 );
		}

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores prior global post from preview shell.
		$post = self::$prior_post;
		if ( self::$prior_post === null ) {
			unset( $GLOBALS['post'] );
		} else {
			$GLOBALS['post'] = self::$prior_post;
		}
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		self::$applied     = false;
		self::$prior_post  = null;
		self::$saved_query = array();
	}
}
