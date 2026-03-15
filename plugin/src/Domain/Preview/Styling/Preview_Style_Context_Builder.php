<?php
/**
 * Builds style context for admin preview/compare/detail so output matches frontend styling (Prompt 255).
 * Returns base stylesheet URL and inline CSS (global tokens, global component overrides, optional per-page).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Preview\Styling;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Styling\Global_Component_Override_Emitter;
use AIOPageBuilder\Domain\Styling\Global_Token_Variable_Emitter;
use AIOPageBuilder\Domain\Styling\Page_Style_Emitter;
use AIOPageBuilder\Infrastructure\Config\Plugin_Config;

/**
 * Builds preview-safe style context: base stylesheet URL and inline CSS for global and optional page-level styling.
 */
final class Preview_Style_Context_Builder {

	/** Relative path from plugin root to base CSS (matches Frontend_Style_Enqueue_Service). */
	private const BASE_CSS_REL = 'assets/css/aio-page-builder-base.css';

	/** @var Plugin_Config */
	private Plugin_Config $config;

	/** @var Global_Token_Variable_Emitter|null */
	private ?Global_Token_Variable_Emitter $token_emitter;

	/** @var Global_Component_Override_Emitter|null */
	private ?Global_Component_Override_Emitter $component_emitter;

	/** @var Page_Style_Emitter|null */
	private ?Page_Style_Emitter $page_emitter;

	public function __construct(
		Plugin_Config $config,
		?Global_Token_Variable_Emitter $token_emitter = null,
		?Global_Component_Override_Emitter $component_emitter = null,
		?Page_Style_Emitter $page_emitter = null
	) {
		$this->config           = $config;
		$this->token_emitter    = $token_emitter;
		$this->component_emitter = $component_emitter;
		$this->page_emitter     = $page_emitter;
	}

	/**
	 * Builds style context for a preview: base stylesheet URL and inline CSS (global + optional page-level).
	 *
	 * @param string $context_type 'section' or 'page'.
	 * @param string $entity_key   Section key or page template key; for page context with non-empty key, page-level CSS is included.
	 * @return array{base_stylesheet_url: string, inline_css: string}
	 */
	public function build_for_preview( string $context_type, string $entity_key ): array {
		$base_url = \rtrim( $this->config->plugin_url(), '/' ) . '/' . self::BASE_CSS_REL;
		$parts    = array();

		if ( $this->token_emitter !== null ) {
			$css = $this->token_emitter->emit_for_root();
			if ( $css !== '' ) {
				$parts[] = $css;
			}
		}
		if ( $this->component_emitter !== null ) {
			$css = $this->component_emitter->emit();
			if ( $css !== '' ) {
				$parts[] = $css;
			}
		}
		if ( $context_type === 'page' && $entity_key !== '' && $this->page_emitter !== null ) {
			$page_css = $this->page_emitter->emit_for_page( $entity_key );
			if ( $page_css !== '' ) {
				$parts[] = $page_css;
			}
		}

		return array(
			'base_stylesheet_url' => $base_url,
			'inline_css'          => \implode( "\n", $parts ),
		);
	}
}
