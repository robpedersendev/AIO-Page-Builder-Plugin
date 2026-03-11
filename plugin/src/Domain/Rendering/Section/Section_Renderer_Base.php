<?php
/**
 * Base section renderer: transforms validated section context into render-ready structure (spec §17, §12, css-selector-contract).
 * Produces Section_Render_Result only; no block serialization or page assembly.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Section;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Transforms Section_Render_Context into Section_Render_Result.
 * Applies CSS selector contract (docs/contracts/css-selector-contract.md) for wrapper and selector map.
 */
final class Section_Renderer_Base {

	/** Class prefix per css-selector-contract. */
	private const PREFIX = 'aio-';

	/** Section wrapper class pattern: aio-s-{section_key}. */
	private const WRAPPER_CLASS_PATTERN = 'aio-s-%s';

	/** Variant modifier pattern: aio-s-{section_key}--variant-{variant_key}. */
	private const VARIANT_MODIFIER_PATTERN = 'aio-s-%s--variant-%s';

	/** Inner wrapper class pattern: aio-s-{section_key}__inner. */
	private const INNER_CLASS_PATTERN = 'aio-s-%s__inner';

	/** Section ID pattern for anchor: aio-section-{section_key}-{position}. */
	private const SECTION_ID_PATTERN = 'aio-section-%s-%d';

	/**
	 * Renders context to a structured result. Context must be valid (use Section_Render_Context_Builder to build/validate).
	 *
	 * @param Section_Render_Context $context Valid render context.
	 * @return Section_Render_Result
	 */
	public function render( Section_Render_Context $context ): Section_Render_Result {
		$definition = $context->get_section_definition();
		$section_key = (string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$variants = (array) ( $definition[ Section_Schema::FIELD_VARIANTS ] ?? array() );
		$default_variant = (string) ( $definition[ Section_Schema::FIELD_DEFAULT_VARIANT ] ?? '' );
		$variant = $context->get_variant_override() ?? $default_variant;
		if ( $variant === '' || ! array_key_exists( $variant, $variants ) ) {
			$variant = $default_variant;
		}
		$position = $context->get_position();

		$wrapper_class = sprintf( self::WRAPPER_CLASS_PATTERN, $section_key );
		$variant_modifier = sprintf( self::VARIANT_MODIFIER_PATTERN, $section_key, $variant );
		$inner_class = sprintf( self::INNER_CLASS_PATTERN, $section_key );
		$section_id = sprintf( self::SECTION_ID_PATTERN, $section_key, $position );

		$classes = array( $wrapper_class, $variant_modifier );
		$variant_descriptor = $variants[ $variant ] ?? array();
		if ( is_array( $variant_descriptor ) && ! empty( $variant_descriptor['css_modifiers'] ) && is_array( $variant_descriptor['css_modifiers'] ) ) {
			foreach ( $variant_descriptor['css_modifiers'] as $mod ) {
				if ( is_string( $mod ) && $mod !== '' && strpos( $mod, self::PREFIX ) === 0 ) {
					$classes[] = $mod;
				}
			}
		}

		$wrapper_attrs = array(
			'class'           => array_values( array_unique( $classes ) ),
			'id'              => $section_id,
			'data_attributes' => array(
				'data-aio-section'  => $section_key,
				'data-aio-variant'  => $variant,
				'data-aio-position' => (string) $position,
			),
		);

		$selector_map = array(
			'wrapper_class'    => $wrapper_class,
			'inner_class'      => $inner_class,
			'element_classes'  => array( 'inner' => $inner_class ),
		);

		$structural_nodes = array(
			array( 'role' => 'wrapper', 'class' => $wrapper_class ),
			array( 'role' => 'inner', 'class' => $inner_class ),
		);

		$structural_hint = (string) ( $definition[ Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF ] ?? '' );
		$asset_hints = (array) ( $definition[ Section_Schema::FIELD_ASSET_DECLARATION ] ?? array() );
		$accessibility_notes = array();
		if ( ! empty( $definition['accessibility_warnings_or_enhancements'] ) && is_string( $definition['accessibility_warnings_or_enhancements'] ) ) {
			$accessibility_notes[] = \sanitize_text_field( $definition['accessibility_warnings_or_enhancements'] );
		}

		$structure = array(
			'wrapper_attrs'       => $wrapper_attrs,
			'selector_map'        => $selector_map,
			'structural_nodes'     => $structural_nodes,
			'structural_hint'     => $structural_hint,
			'asset_hints'         => $asset_hints,
			'accessibility_notes'  => $accessibility_notes,
		);

		return new Section_Render_Result(
			$section_key,
			$variant,
			$position,
			$context->get_field_values(),
			$structure,
			array()
		);
	}
}
