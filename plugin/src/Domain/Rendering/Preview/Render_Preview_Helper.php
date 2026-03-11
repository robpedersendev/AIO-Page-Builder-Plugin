<?php
/**
 * Non-destructive preview payload helpers for section/page/composition outputs (spec §17, §59.5).
 * Internal use only; not a public preview route or alternate builder path.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Preview;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;

/**
 * Builds preview_payload shapes for registry/admin use. Does not mutate content or create pages.
 *
 * Example render preview payload (page, from build_page_preview):
 * [
 *   'type' => 'page', 'source_type' => 'page_template', 'source_key' => 'tpl_landing',
 *   'section_count' => 2, 'section_previews' => [ ['section_key' => 'st01_hero', 'variant' => 'default', 'position' => 0], ... ],
 *   'block_content_preview' => '<!-- wp:html -->...', 'block_content_length' => 512,
 *   'survivability_notes' => [ 'durable_native_blocks', 'generateblocks_compatible' ], 'valid' => true,
 * ]
 */
final class Render_Preview_Helper {

	/** Max length of block_content in preview payload (truncation for display). */
	private const PREVIEW_CONTENT_MAX_LENGTH = 2000;

	/**
	 * Builds a section-level preview payload from a section render result.
	 *
	 * @param Section_Render_Result $section
	 * @return array<string, mixed> preview_payload shape: type=section, section_key, variant, position, wrapper_classes, field_keys, structural_hint.
	 */
	public function build_section_preview( Section_Render_Result $section ): array {
		$wrapper = $section->get_wrapper_attrs();
		$classes = isset( $wrapper['class'] ) && is_array( $wrapper['class'] ) ? $wrapper['class'] : array();
		return array(
			'type'             => 'section',
			'section_key'      => $section->get_section_key(),
			'variant'          => $section->get_variant(),
			'position'         => $section->get_position(),
			'wrapper_classes'  => $classes,
			'field_keys'       => array_keys( $section->get_field_values() ),
			'structural_hint'  => $section->get_structural_hint(),
			'valid'            => $section->is_valid(),
		);
	}

	/**
	 * Builds a page-level preview payload from a page assembly result (template or composition).
	 *
	 * @param Page_Block_Assembly_Result $assembly
	 * @param bool                       $include_content_preview Whether to include truncated block_content (default true).
	 * @return array<string, mixed> preview_payload shape: type=page, source_type, source_key, section_count, section_previews, block_content_preview (truncated), survivability_notes.
	 */
	public function build_page_preview( Page_Block_Assembly_Result $assembly, bool $include_content_preview = true ): array {
		$ordered = $assembly->get_ordered_sections();
		$section_previews = array();
		foreach ( $ordered as $s ) {
			$section_previews[] = array(
				'section_key' => $s['section_key'] ?? '',
				'variant'     => $s['variant'] ?? '',
				'position'    => $s['position'] ?? 0,
			);
		}
		$content = $assembly->get_block_content();
		$block_content_preview = '';
		if ( $include_content_preview ) {
			$block_content_preview = strlen( $content ) > self::PREVIEW_CONTENT_MAX_LENGTH
				? substr( $content, 0, self::PREVIEW_CONTENT_MAX_LENGTH ) . '...'
				: $content;
		}
		return array(
			'type'                  => 'page',
			'source_type'           => $assembly->get_source_type(),
			'source_key'            => $assembly->get_source_key(),
			'section_count'         => count( $ordered ),
			'section_previews'       => $section_previews,
			'block_content_preview'  => $block_content_preview,
			'block_content_length'   => strlen( $content ),
			'survivability_notes'    => $assembly->get_survivability_notes(),
			'valid'                  => $assembly->is_valid(),
		);
	}

	/**
	 * Builds a composition-backed preview payload (same shape as page; source_type=composition).
	 *
	 * @param Page_Block_Assembly_Result $assembly Assembly result with source_type=composition.
	 * @param bool                       $include_content_preview
	 * @return array<string, mixed> preview_payload
	 */
	public function build_composition_preview( Page_Block_Assembly_Result $assembly, bool $include_content_preview = true ): array {
		$payload = $this->build_page_preview( $assembly, $include_content_preview );
		$payload['type'] = 'composition';
		return $payload;
	}
}
