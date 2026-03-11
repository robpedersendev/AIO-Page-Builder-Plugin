<?php
/**
 * Controlled compatibility layer for GenerateBlocks-compatible output (spec §7.2, §17.2, §54.2).
 * Adapts section render results to GB constructs when available; preserves native fallback.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\GenerateBlocks;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;

/**
 * Detects GenerateBlocks availability and maps eligible section results to GB block markup.
 * Does not replace native output; pipeline uses GB only when this layer is available and mapping applies.
 */
final class GenerateBlocks_Compatibility_Layer {

	/** @var callable(): bool */
	private $availability_check;

	/**
	 * Default availability check: true when generateblocks/container block is registered (WordPress context).
	 * In non-WP context (e.g. unit tests) returns false.
	 *
	 * @return callable(): bool
	 */
	public static function default_availability_check(): callable {
		return function (): bool {
			if ( ! class_exists( 'WP_Block_Type_Registry' ) ) {
				return false;
			}
			$registry = \WP_Block_Type_Registry::get_instance();
			return $registry->is_registered( GenerateBlocks_Mapping_Rules::BLOCK_CONTAINER );
		};
	}

	/**
	 * @param callable(): bool $availability_check Returns true when GenerateBlocks is available for use (e.g. plugin active, block registered).
	 */
	public function __construct( callable $availability_check ) {
		$this->availability_check = $availability_check;
	}

	/**
	 * Whether GenerateBlocks is available and may be used for mapping.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return (bool) ( $this->availability_check )();
	}

	/**
	 * Converts a section result to GenerateBlocks-compatible block markup when eligible.
	 * Preserves selector contract (wrapper class, id, data-*; inner class). Returns null when GB unavailable or section not eligible.
	 *
	 * @param Section_Render_Result $section Section render result.
	 * @return string|null Block markup (save-ready) or null to use native fallback.
	 */
	public function section_to_gb_markup( Section_Render_Result $section ): ?string {
		if ( ! $this->is_available() ) {
			return null;
		}
		$payload = $section->to_array();
		if ( ! GenerateBlocks_Mapping_Rules::is_eligible_for_gb( $payload ) ) {
			return null;
		}
		return $this->build_section_gb_markup( $section );
	}

	/**
	 * Builds GB Container + inner content (Headline blocks). Contract classes, id, data-* preserved.
	 *
	 * @param Section_Render_Result $section
	 * @return string
	 */
	private function build_section_gb_markup( Section_Render_Result $section ): string {
		$wrapper_attrs = $section->get_wrapper_attrs();
		$selector_map  = $section->get_selector_map();
		$inner_class   = $selector_map['inner_class'] ?? '';
		$classes       = isset( $wrapper_attrs['class'] ) && is_array( $wrapper_attrs['class'] ) ? $wrapper_attrs['class'] : array();
		$id            = isset( $wrapper_attrs['id'] ) && is_string( $wrapper_attrs['id'] ) ? $wrapper_attrs['id'] : '';
		$data_attrs    = isset( $wrapper_attrs['data_attributes'] ) && is_array( $wrapper_attrs['data_attributes'] ) ? $wrapper_attrs['data_attributes'] : array();

		$class_attr = implode( ' ', array_map( 'esc_attr', $classes ) );
		$attrs      = array( 'className' => $class_attr );
		if ( $id !== '' ) {
			$attrs['anchor'] = $id;
		}
		$attrs_json = wp_json_encode( $attrs );

		$data_attr_string = '';
		foreach ( $data_attrs as $name => $value ) {
			if ( is_string( $name ) && is_string( $value ) && $name !== '' ) {
				$data_attr_string .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
			}
		}

		$inner_open  = '<div class="' . esc_attr( $inner_class ) . '"' . $data_attr_string . '>';
		$inner_close = '</div>';
		$content     = $this->field_values_to_gb_inner_blocks( $section->get_field_values() );

		$inner_html = $inner_open . "\n" . $content . "\n" . $inner_close;

		return "<!-- wp:generateblocks/container " . $attrs_json . " -->\n"
			. $inner_html . "\n"
		. "<!-- /wp:generateblocks/container -->";
	}

	/**
	 * Maps field values to GB Headline block markup (h2 for headline/title, p for rest). Escapes content.
	 *
	 * @param array<string, mixed> $field_values
	 * @return string
	 */
	private function field_values_to_gb_inner_blocks( array $field_values ): string {
		$out = array();
		foreach ( $field_values as $key => $value ) {
			if ( is_array( $value ) ) {
				continue;
			}
			$text = is_string( $value ) ? trim( $value ) : (string) $value;
			if ( $text === '' ) {
				continue;
			}
			$escaped = esc_html( $text );
			$element = in_array( $key, GenerateBlocks_Mapping_Rules::HEADING_FIELD_KEYS, true )
				? GenerateBlocks_Mapping_Rules::HEADLINE_ELEMENT_HEADING
				: GenerateBlocks_Mapping_Rules::HEADLINE_ELEMENT_PARAGRAPH;
			$block_attrs = wp_json_encode( array( 'element' => $element ) );
			$out[] = "<!-- wp:generateblocks/headline " . $block_attrs . " -->" . $escaped . "<!-- /wp:generateblocks/headline -->";
		}
		return implode( "\n", $out );
	}
}
