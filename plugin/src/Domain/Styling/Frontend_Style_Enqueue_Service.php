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
 * When emitters are provided, appends global token variables (Prompt 249) and global component overrides (Prompt 250).
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

	/** @var Global_Component_Override_Emitter|null */
	private ?Global_Component_Override_Emitter $component_override_emitter;

	/** @var Page_Style_Emitter|null Per-page style emission (Prompt 254). */
	private ?Page_Style_Emitter $page_style_emitter;

	/** @var Style_Cache_Service|null When set, stylesheet version uses cache version for busting (Prompt 256). */
	private ?Style_Cache_Service $style_cache;

	public function __construct(
		Plugin_Config $config,
		?Global_Token_Variable_Emitter $emitter = null,
		?Global_Component_Override_Emitter $component_override_emitter = null,
		?Page_Style_Emitter $page_style_emitter = null,
		?Style_Cache_Service $style_cache = null
	) {
		$this->config                     = $config;
		$this->emitter                    = $emitter;
		$this->component_override_emitter = $component_override_emitter;
		$this->page_style_emitter         = $page_style_emitter;
		$this->style_cache                = $style_cache;
	}

	/**
	 * Registers the base stylesheet. Call on wp_enqueue_scripts (or earlier).
	 *
	 * @return void
	 */
	public function register(): void {
		$url = $this->config->plugin_url() . self::BASE_CSS_REL;
		$ver = $this->style_cache !== null ? $this->style_cache->get_version() : $this->config->plugin_version();
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
		$inline_parts = array();
		if ( $this->emitter !== null ) {
			$css = $this->emitter->emit_for_root();
			if ( $css !== '' ) {
				$inline_parts[] = $css;
			}
		}
		if ( $this->component_override_emitter !== null ) {
			$css = $this->component_override_emitter->emit();
			if ( $css !== '' ) {
				$inline_parts[] = $css;
			}
		}
		if ( $this->page_style_emitter !== null ) {
			$post         = \get_queried_object();
			$template_key = (string) \apply_filters( 'aio_page_builder_current_template_key', '', $post );
			if ( $template_key !== '' ) {
				$page_css = $this->page_style_emitter->emit_for_page( $template_key );
				if ( $page_css !== '' ) {
					$inline_parts[] = $page_css;
				}
			}
		}
		if ( ! empty( $inline_parts ) ) {
			\wp_add_inline_style( self::HANDLE_BASE, implode( ' ', $inline_parts ) );
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
