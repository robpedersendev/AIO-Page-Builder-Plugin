<?php
/**
 * Stable rules for mapping section render results to GenerateBlocks-compatible constructs (spec §7.2, §17.2, §54.2).
 * Defines allowed mappings and unsupported patterns; used by GenerateBlocks_Compatibility_Layer.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\GenerateBlocks;

defined( 'ABSPATH' ) || exit;

/**
 * Bounded mapping rules. Do not add one-off or undocumented mappings.
 *
 * Supported: section wrapper → GB Container (with contract classes/id); headline/title → GB Headline (h2);
 * other scalar text fields → GB Headline (element p) or core/paragraph. Unsupported: repeater/grid without
 * a defined mapping, custom block types not listed here, render-callback blocks.
 */
final class GenerateBlocks_Mapping_Rules {

	/** GenerateBlocks block namespace/name for container. */
	public const BLOCK_CONTAINER = 'generateblocks/container';

	/** GenerateBlocks block namespace/name for headline (H1–H6, p, span, etc.). */
	public const BLOCK_HEADLINE = 'generateblocks/headline';

	/** Field keys mapped to primary heading (h2). Must match pipeline heading keys for consistency. */
	public const HEADING_FIELD_KEYS = array( 'headline', 'title' );

	/** Default HTML element for GB Headline when used as heading. */
	public const HEADLINE_ELEMENT_HEADING = 'h2';

	/** Default HTML element for GB Headline when used as paragraph. */
	public const HEADLINE_ELEMENT_PARAGRAPH = 'p';

	/**
	 * Whether a section result is eligible for GenerateBlocks mapping.
	 * Eligible: has section_key, wrapper_attrs with class, and at least structural_nodes (wrapper + inner).
	 * Repeater/group field values (array) are not yet mapped; sections that rely on them are not eligible.
	 *
	 * @param array<string, mixed> $section_payload Section_Render_Result::to_array() shape.
	 * @return bool
	 */
	public static function is_eligible_for_gb( array $section_payload ): bool {
		$key = $section_payload['section_key'] ?? null;
		if ( ! is_string( $key ) || $key === '' ) {
			return false;
		}
		$wrapper = $section_payload['wrapper_attrs'] ?? array();
		if ( ! is_array( $wrapper ) || empty( $wrapper['class'] ) || ! is_array( $wrapper['class'] ) ) {
			return false;
		}
		$field_values = $section_payload['field_values'] ?? array();
		if ( ! is_array( $field_values ) ) {
			return false;
		}
		foreach ( $field_values as $value ) {
			if ( is_array( $value ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns keyed unsupported pattern descriptions for documentation and fallback messaging.
	 *
	 * @return array<string, string>
	 */
	public static function unsupported_patterns(): array {
		return array(
			'repeater_or_group_fields' => 'Section field values that are arrays (repeaters, groups) are not mapped to GenerateBlocks; use native block output.',
			'missing_wrapper_attrs'    => 'Section without valid wrapper_attrs (class list) is not mapped to GB Container.',
			'custom_block_types'       => 'Only generateblocks/container and generateblocks/headline are used; no other GB or third-party block types are emitted.',
			'render_callback_blocks'   => 'No blocks that rely on render callbacks for section content are used.',
		);
	}

	/**
	 * Allowed block names this layer may emit. Used for validation and documentation.
	 *
	 * @return array<int, string>
	 */
	public static function allowed_block_names(): array {
		return array(
			self::BLOCK_CONTAINER,
			self::BLOCK_HEADLINE,
		);
	}
}
