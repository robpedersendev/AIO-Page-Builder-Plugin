<?php
/**
 * Base section renderer: transforms validated section context into render-ready structure (spec §17, §12, css-selector-contract).
 * Produces Section_Render_Result only; no block serialization or page assembly.
 * When Smart_Omission_Service is provided, applies smart omission to field_values (smart-omission-rendering-contract).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Section;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Rendering\Animation\Animation_Tier_Resolver;
use AIOPageBuilder\Domain\Rendering\Omission\Smart_Omission_Service;

/**
 * Transforms Section_Render_Context into Section_Render_Result.
 * Applies CSS selector contract (docs/contracts/css-selector-contract.md) for wrapper and selector map.
 */
final class Section_Renderer_Base {

	/** Class prefix per css-selector-contract. */
	private const PREFIX = 'aio-';

	/** @var Smart_Omission_Service|null */
	private ?Smart_Omission_Service $omission_service;

	/** @var Animation_Tier_Resolver|null */
	private ?Animation_Tier_Resolver $animation_tier_resolver;

	public function __construct(
		?Smart_Omission_Service $omission_service = null,
		?Animation_Tier_Resolver $animation_tier_resolver = null
	) {
		$this->omission_service       = $omission_service;
		$this->animation_tier_resolver = $animation_tier_resolver;
	}

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
	 * Optional $options['reduced_motion'] (bool) and $options['page_template'] (array) for animation resolution.
	 *
	 * @param Section_Render_Context $context Valid render context.
	 * @param array<string, mixed>   $options Optional: reduced_motion (bool), page_template (array) for animation tier resolution.
	 * @return Section_Render_Result
	 */
	public function render( Section_Render_Context $context, array $options = array() ): Section_Render_Result {
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

		$data_attributes = array(
			'data-aio-section'  => $section_key,
			'data-aio-variant'  => $variant,
			'data-aio-position' => (string) $position,
		);

		$animation_resolution = null;
		if ( $this->animation_tier_resolver !== null ) {
			$reduced_motion = ! empty( $options['reduced_motion'] );
			$page_template  = isset( $options['page_template'] ) && is_array( $options['page_template'] ) ? $options['page_template'] : null;
			$animation_resolution = $this->animation_tier_resolver->resolve( $definition, $page_template, $reduced_motion );
			$data_attributes['data-aio-animation-tier'] = $animation_resolution['effective_tier'];
			$data_attributes['data-aio-reduced-motion'] = $animation_resolution['reduced_motion_applied'] ? '1' : '0';
		}

		$wrapper_attrs = array(
			'class'           => array_values( array_unique( $classes ) ),
			'id'              => $section_id,
			'data_attributes' => $data_attributes,
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
			'wrapper_attrs'        => $wrapper_attrs,
			'selector_map'         => $selector_map,
			'structural_nodes'     => $structural_nodes,
			'structural_hint'      => $structural_hint,
			'asset_hints'          => $asset_hints,
			'accessibility_notes'  => $accessibility_notes,
		);
		if ( $animation_resolution !== null ) {
			$structure['animation_resolution'] = $animation_resolution;
		}

		$field_values = $context->get_field_values();
		$omission_result = null;

		if ( $this->omission_service !== null ) {
			$blueprint_raw = $definition[ Section_Field_Blueprint_Service::EMBEDDED_BLUEPRINT_KEY ] ?? null;
			$eligibility = is_array( $blueprint_raw ) && ! empty( $blueprint_raw['fields'] )
				? $this->omission_service->eligibility_from_blueprint( $blueprint_raw )
				: array();
			$cta_classification = (string) ( $definition['cta_classification'] ?? '' );
			$primary_cta_key = $this->infer_primary_cta_key( $eligibility, $field_values );
			$context_omission = array(
				'section_key'       => $section_key,
				'position'          => $position,
				'is_cta_classified' => $cta_classification === 'primary_cta' || $cta_classification === 'contact_cta' || $cta_classification === 'cta',
				'supplies_h1'       => $position === 0,
				'primary_cta_key'   => $primary_cta_key,
			);
			$applied = $this->omission_service->apply( $field_values, $eligibility, $context_omission );
			$field_values   = $applied['field_values'];
			$omission_result = $applied['omission_result'];
		}

		return new Section_Render_Result(
			$section_key,
			$variant,
			$position,
			$field_values,
			$structure,
			array(),
			$omission_result
		);
	}

	/**
	 * Infers primary CTA field key from eligibility (first cta role) or common names.
	 *
	 * @param array<string, array{optional: bool, role: string}> $eligibility
	 * @param array<string, mixed>                               $field_values
	 * @return string
	 */
	private function infer_primary_cta_key( array $eligibility, array $field_values ): string {
		foreach ( $eligibility as $key => $el ) {
			if ( is_array( $el ) && ( $el['role'] ?? '' ) === 'cta' ) {
				return $key;
			}
		}
		if ( array_key_exists( 'primary_cta', $field_values ) ) {
			return 'primary_cta';
		}
		if ( array_key_exists( 'cta', $field_values ) ) {
			return 'cta';
		}
		return '';
	}
}
