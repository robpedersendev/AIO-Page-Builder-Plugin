<?php
/**
 * Conditional front-end enqueue of plugin base stylesheet (Prompt 245).
 * Loads only when built pages or approved preview contexts require it.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Plugin_Config;

/**
 * Registers and conditionally enqueues aio-page-builder-base.css on the front end.
 * When an emitter is provided, appends global token CSS variables to the base stylesheet (Prompt 249).
 */
final class Frontend_Style_Enqueue_Service {

	/** Stylesheet handle. */
	public const HANDLE_BASE = 'aio-page-builder-base';

	/** Relative path from plugin root to base CSS file. */
	private const BASE_CSS_REL = 'assets/css/aio-page-builder-base.css';

	/** @var Plugin_Config */
	private Plugin_Config $config;

	/** @var Global_Token_Variable_Emitter|null */
	private ?Global_Token_Variable_Emitter $emitter;

	public function __construct( Plugin_Config $config, ?Global_Token_Variable_Emitter $emitter = null ) {
		$this->config  = $config;
		$this->emitter = $emitter;
	}

	/**
	 * Registers the base stylesheet. Call on wp_enqueue_scripts (or earlier).
	 *
	 * @return void
	 */
	public function register(): void {
		$url = $this->config->plugin_url() . self::BASE_CSS_REL;
		$ver = $this->config->plugin_version();
		\wp_register_style(
			self::HANDLE_BASE,
			$url,
			array(),
			$ver
		);
	}

	/**
	 * Enqueues the base stylesheet if not already enqueued. Call from wp_enqueue_scripts.
	 *
	 * @return void
	 */
	public function enqueue_when_needed(): void {
		if ( ! $this->should_load_base_styles() ) {
			return;
		}
		$this->register();
		\wp_enqueue_style( self::HANDLE_BASE );
		if ( $this->emitter !== null ) {
			$css = $this->emitter->emit_for_root();
			if ( $css !== '' ) {
				\wp_add_inline_style( self::HANDLE_BASE, $css );
			}
		}
	}

	/**
	 * Whether the base stylesheet should load on this request (built page or approved context).
	 *
	 * @return bool
	 */
	public function should_load_base_styles(): bool {
		if ( \is_admin() || ! function_exists( 'get_queried_object' ) ) {
			return false;
		}
		$obj = \get_queried_object();
		if ( $obj instanceof \WP_Post ) {
			$content = isset( $obj->post_content ) ? (string) $obj->post_content : '';
			if ( $content !== '' && ( str_contains( $content, 'aio-page' ) || str_contains( $content, 'aio-s-' ) ) ) {
				return true;
			}
		}
		return (bool) \apply_filters( 'aio_page_builder_should_enqueue_base_styles', false );
	}
}
