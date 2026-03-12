<?php
/**
 * Converts ordered section render results into durable native block content (spec §7.5, §17.5, §18, rendering-contract §6.2, §6.3).
 * Produces save-ready block markup only; does not create or save pages.
 * When a GenerateBlocks compatibility layer is provided and available, eligible sections may be emitted as GB blocks.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Blocks;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Rendering\GenerateBlocks\GenerateBlocks_Compatibility_Layer;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;

/**
 * Assembles ordered Section_Render_Result instances into a single block content string.
 * Uses core/html for section wrappers and semantic HTML for field content (durable, no render callbacks).
 * Optional GenerateBlocks_Compatibility_Layer: when set and available, eligible sections use GB container/headline.
 */
final class Native_Block_Assembly_Pipeline {

	/** Field keys treated as primary heading (h2). */
	private const HEADING_KEYS = array( 'headline', 'title' );

	/** @var GenerateBlocks_Compatibility_Layer|null */
	private $gb_layer;

	/** @var Form_Provider_Registry|null */
	private $form_provider_registry;

	/**
	 * @param GenerateBlocks_Compatibility_Layer|null $gb_layer Optional; when set and available, eligible sections emit GenerateBlocks-compatible markup.
	 * @param Form_Provider_Registry|null            $form_provider_registry Optional; when set, sections with form_provider + form_id emit form shortcode (form-provider-integration-contract.md).
	 */
	public function __construct( ?GenerateBlocks_Compatibility_Layer $gb_layer = null, ?Form_Provider_Registry $form_provider_registry = null ) {
		$this->gb_layer               = $gb_layer;
		$this->form_provider_registry = $form_provider_registry;
	}

	/**
	 * Assembles ordered section results into page-level block content.
	 *
	 * @param string                    $source_type   One of Page_Block_Assembly_Result::SOURCE_TYPE_*.
	 * @param string                    $source_key    Template internal_key or composition_id.
	 * @param array<Section_Render_Result|array> $ordered_section_results Ordered section render results (object or to_array() shape).
	 * @return Page_Block_Assembly_Result
	 */
	public function assemble( string $source_type, string $source_key, array $ordered_section_results ): Page_Block_Assembly_Result {
		$section_payloads = array();
		$blocks           = array();
		$errors           = array();
		$used_gb          = false;

		foreach ( $ordered_section_results as $item ) {
			$result = $this->normalize_section_result( $item );
			if ( $result === null ) {
				$errors[] = 'Assembly skipped invalid section item (missing section_key or invalid structure).';
				continue;
			}
			if ( ! $result->is_valid() ) {
				$errors[] = sprintf( 'Section %s at position %d has errors: %s', $result->get_section_key(), $result->get_position(), implode( '; ', $result->get_errors() ) );
			}
			$section_payloads[] = $result->to_array();

			$block_markup = null;
			if ( $this->gb_layer !== null ) {
				$block_markup = $this->gb_layer->section_to_gb_markup( $result );
				if ( $block_markup !== null ) {
					$used_gb = true;
				}
			}
			if ( $block_markup === null ) {
				$block_markup = $this->section_to_block_markup( $result );
			}
			$blocks[] = $block_markup;
		}

		$block_content = implode( "\n\n", $blocks );
		$notes         = array( 'durable_native_blocks' );
		if ( $used_gb ) {
			$notes[] = 'generateblocks_compatible';
		}
		if ( ! empty( $errors ) ) {
			$notes[] = 'partial_or_warning';
		}

		return new Page_Block_Assembly_Result(
			$source_type,
			$source_key,
			$section_payloads,
			$block_content,
			array(), // No render callbacks for section content.
			$notes,
			$errors
		);
	}

	/**
	 * @param Section_Render_Result|array $item
	 * @return Section_Render_Result|null
	 */
	private function normalize_section_result( $item ): ?Section_Render_Result {
		if ( $item instanceof Section_Render_Result ) {
			return $item;
		}
		if ( ! is_array( $item ) ) {
			return null;
		}
		$key = $item['section_key'] ?? null;
		if ( ! is_string( $key ) || $key === '' ) {
			return null;
		}
		$variant   = (string) ( $item['variant'] ?? '' );
		$position  = isset( $item['position'] ) ? (int) $item['position'] : 0;
		$fields    = isset( $item['field_values'] ) && is_array( $item['field_values'] ) ? $item['field_values'] : array();
		$wrapper   = $item['wrapper_attrs'] ?? array();
		$selector  = $item['selector_map'] ?? array();
		$nodes     = $item['structural_nodes'] ?? array();
		$hint      = (string) ( $item['structural_hint'] ?? '' );
		$assets    = isset( $item['asset_hints'] ) && is_array( $item['asset_hints'] ) ? $item['asset_hints'] : array();
		$a11y      = isset( $item['accessibility_notes'] ) && is_array( $item['accessibility_notes'] ) ? $item['accessibility_notes'] : array();
		$errs      = isset( $item['errors'] ) && is_array( $item['errors'] ) ? $item['errors'] : array();
		$structure = array(
			'wrapper_attrs'       => is_array( $wrapper ) ? $wrapper : array( 'class' => array(), 'id' => '', 'data_attributes' => array() ),
			'selector_map'        => is_array( $selector ) ? $selector : array( 'wrapper_class' => '', 'inner_class' => '', 'element_classes' => array() ),
			'structural_nodes'    => is_array( $nodes ) ? $nodes : array(),
			'structural_hint'     => $hint,
			'asset_hints'         => $assets,
			'accessibility_notes'  => $a11y,
		);
		return new Section_Render_Result( $key, $variant, $position, $fields, $structure, $errs );
	}

	private function section_to_block_markup( Section_Render_Result $section ): string {
		$wrapper_attrs = $section->get_wrapper_attrs();
		$selector_map  = $section->get_selector_map();
		$inner_class   = $selector_map['inner_class'] ?? '';
		$classes       = isset( $wrapper_attrs['class'] ) && is_array( $wrapper_attrs['class'] ) ? $wrapper_attrs['class'] : array();
		$id            = isset( $wrapper_attrs['id'] ) && is_string( $wrapper_attrs['id'] ) ? $wrapper_attrs['id'] : '';
		$data_attrs    = isset( $wrapper_attrs['data_attributes'] ) && is_array( $wrapper_attrs['data_attributes'] ) ? $wrapper_attrs['data_attributes'] : array();

		$class_attr = implode( ' ', array_map( 'esc_attr', $classes ) );
		$open       = '<div';
		if ( $class_attr !== '' ) {
			$open .= ' class="' . $class_attr . '"';
		}
		if ( $id !== '' ) {
			$open .= ' id="' . esc_attr( $id ) . '"';
		}
		foreach ( $data_attrs as $name => $value ) {
			if ( is_string( $name ) && is_string( $value ) && $name !== '' ) {
				$open .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
			}
		}
		$open .= '>';

		$inner_open = $inner_class !== '' ? '<div class="' . esc_attr( $inner_class ) . '">' : '';
		$inner_close = $inner_class !== '' ? '</div>' : '';

		$content = $this->field_values_to_inner_html( $section->get_field_values(), $section->get_section_key() );

		$inner = $inner_open . $content . $inner_close;
		$html  = $open . $inner . '</div>';

		return "<!-- wp:html -->\n" . $html . "\n<!-- /wp:html -->";
	}

	/**
	 * Maps field values to semantic HTML (h2 for headline/title, p for rest). Escapes output.
	 * When form_provider_registry is set and field_values contains form_provider + form_id, emits form shortcode (form-provider-integration-contract.md).
	 *
	 * @param array<string, mixed> $field_values
	 * @param string               $section_key  Section internal_key (for form section detection; unused when no registry).
	 * @return string
	 */
	private function field_values_to_inner_html( array $field_values, string $section_key = '' ): string {
		$out = array();

		$form_shortcode = null;
		if ( $this->form_provider_registry !== null ) {
			$provider = isset( $field_values[ Form_Provider_Registry::FIELD_FORM_PROVIDER ] )
				? trim( (string) $field_values[ Form_Provider_Registry::FIELD_FORM_PROVIDER ] )
				: '';
			$form_id = isset( $field_values[ Form_Provider_Registry::FIELD_FORM_ID ] )
				? trim( (string) $field_values[ Form_Provider_Registry::FIELD_FORM_ID ] )
				: '';
			if ( $provider !== '' && $form_id !== '' ) {
				$form_shortcode = $this->form_provider_registry->build_shortcode( $provider, $form_id );
			}
		}

		$skip_keys = array(
			Form_Provider_Registry::FIELD_FORM_PROVIDER,
			Form_Provider_Registry::FIELD_FORM_ID,
		);

		foreach ( $field_values as $key => $value ) {
			if ( in_array( $key, $skip_keys, true ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				continue;
			}
			$text = is_string( $value ) ? trim( $value ) : (string) $value;
			if ( $text === '' ) {
				continue;
			}
			$escaped = esc_html( $text );
			if ( in_array( $key, self::HEADING_KEYS, true ) ) {
				$out[] = '<h2>' . $escaped . '</h2>';
			} else {
				$out[] = '<p>' . $escaped . '</p>';
			}
		}

		if ( $form_shortcode !== null && $form_shortcode !== '' ) {
			$out[] = $form_shortcode;
		}

		return implode( "\n", $out );
	}
}
