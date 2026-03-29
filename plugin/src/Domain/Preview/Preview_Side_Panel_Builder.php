<?php
/**
 * Builds preview side-panel metadata payloads for section and page template detail screens (template-preview-and-dummy-data-contract §4).
 * Produces name, description, purpose/CTA, placement, variants, field blueprint ref, helper ref, animation tier, and related metadata.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Builds stable side-panel payloads from template/section definitions and optional preview context.
 * No rendering; metadata only. Safe for admin display.
 */
final class Preview_Side_Panel_Builder {

	/**
	 * Builds side-panel metadata for a section template detail (template-preview §4.2).
	 *
	 * @param array<string, mixed>           $section_definition Section definition (name, purpose_summary, section_purpose_family, cta_classification, placement_tendency, variants, helper_ref, field_blueprint_ref, etc.).
	 * @param Synthetic_Preview_Context|null $context Optional; when provided, adds reduced_motion and animation_tier to payload.
	 * @return array<string, mixed> name, description, purpose_family, cta_classification, placement_tendency, variants, field_blueprint_ref, helper_ref, preview_available, reduced_motion, animation_tier.
	 */
	public function build_for_section( array $section_definition, ?Synthetic_Preview_Context $context = null ): array {
		$name       = (string) ( $section_definition['name'] ?? $section_definition['internal_key'] ?? '' );
		$desc       = (string) ( $section_definition['purpose_summary'] ?? '' );
		$purpose    = (string) ( $section_definition['section_purpose_family'] ?? '' );
		$cta        = (string) ( $section_definition['cta_classification'] ?? '' );
		$placement  = (string) ( $section_definition['placement_tendency'] ?? '' );
		$variants   = $section_definition['variants'] ?? array();
		$helper_ref = (string) ( $section_definition['helper_ref'] ?? '' );
		$field_ref  = (string) ( $section_definition['field_blueprint_ref'] ?? '' );
		$preview    = ( ( $section_definition['preview_image_ref'] ?? $section_definition['preview_description'] ?? '' ) !== '' );

		$payload = array(
			'name'                => $name,
			'description'         => $desc,
			'purpose_family'      => $purpose,
			'cta_classification'  => $cta,
			'placement_tendency'  => $placement,
			'variants'            => \is_array( $variants ) ? $variants : array(),
			'field_blueprint_ref' => $field_ref,
			'helper_ref'          => $helper_ref,
			'preview_available'   => $preview,
		);

		if ( $context !== null ) {
			$payload['reduced_motion'] = $context->is_reduced_motion();
			$payload['animation_tier'] = $context->get_animation_tier();
		}

		return $payload;
	}

	/**
	 * Builds side-panel metadata for a page template detail (template-preview §4.1).
	 *
	 * @param array<string, mixed>           $page_definition Page template definition (name, purpose_summary, ordered_sections, template_category_class, template_family, one_pager, etc.).
	 * @param Synthetic_Preview_Context|null $context Optional; when provided, adds reduced_motion and animation_tier.
	 * @return array<string, mixed> name, description, used_sections, differentiation_notes, purpose_cta_direction, category, hierarchy_role, one_pager_link, composition_provenance, reduced_motion, animation_tier.
	 */
	public function build_for_page( array $page_definition, ?Synthetic_Preview_Context $context = null ): array {
		$name           = (string) ( $page_definition['name'] ?? $page_definition['internal_key'] ?? '' );
		$desc           = (string) ( $page_definition['purpose_summary'] ?? '' );
		$ordered        = $page_definition['ordered_sections'] ?? array();
		$category       = (string) ( $page_definition['template_category_class'] ?? '' );
		$family         = (string) ( $page_definition['template_family'] ?? '' );
		$hierarchy      = (string) ( $page_definition['hierarchy_role'] ?? '' );
		$one_pager      = $page_definition['one_pager'] ?? array();
		$one_pager_link = \is_array( $one_pager ) && isset( $one_pager['link'] ) ? (string) $one_pager['link'] : '';

		$used_sections = array();
		foreach ( \is_array( $ordered ) ? $ordered : array() as $item ) {
			$key = $item['section_key'] ?? $item['key'] ?? '';
			if ( $key !== '' ) {
				$used_sections[] = array(
					'section_key' => $key,
					'position'    => (int) ( $item['position'] ?? count( $used_sections ) ),
				);
			}
		}

		$payload = array(
			'name'                   => $name,
			'description'            => $desc,
			'used_sections'          => $used_sections,
			'differentiation_notes'  => '',
			'purpose_cta_direction'  => $family !== '' ? $family : $category,
			'category'               => $category,
			'hierarchy_role'         => $hierarchy,
			'one_pager_link'         => $one_pager_link,
			'composition_provenance' => '',
		);

		if ( $context !== null ) {
			$payload['reduced_motion'] = $context->is_reduced_motion();
			$payload['animation_tier'] = $context->get_animation_tier();
		}

		return $payload;
	}

	/**
	 * Merges synthetic preview context into a payload suitable for renderer input (section).
	 * Combines side-panel metadata with field_values for a single section.
	 *
	 * @param array<string, mixed>           $section_definition
	 * @param array<string, mixed>           $field_values     Synthetic field values from Synthetic_Preview_Data_Generator.
	 * @param Synthetic_Preview_Context|null $context
	 * @return array<string, mixed> section_key, variant, field_values, side_panel, options (reduced_motion, animation_tier).
	 */
	public function build_section_preview_payload( array $section_definition, array $field_values, ?Synthetic_Preview_Context $context = null ): array {
		$key     = (string) ( $section_definition['internal_key'] ?? '' );
		$variant = $context !== null ? $context->get_variant() : 'default';
		$side    = $this->build_for_section( $section_definition, $context );
		$options = array(
			'reduced_motion' => $context !== null && $context->is_reduced_motion(),
			'animation_tier' => $context !== null ? $context->get_animation_tier() : Synthetic_Preview_Context::ANIMATION_TIER_NONE,
		);
		return array(
			'section_key'  => $key,
			'variant'      => $variant,
			'field_values' => $field_values,
			'side_panel'   => $side,
			'options'      => $options,
		);
	}

	/**
	 * Merges synthetic preview context into a payload suitable for page preview (multiple sections).
	 *
	 * @param array<string, mixed>                                                                      $page_definition
	 * @param array<int, array{section_key: string, position: int, field_values: array<string, mixed>}> $section_field_values From Synthetic_Preview_Data_Generator::generate_for_page().
	 * @param Synthetic_Preview_Context|null                                                            $context
	 * @return array<string, mixed> template_key, section_field_values, side_panel, options.
	 */
	public function build_page_preview_payload( array $page_definition, array $section_field_values, ?Synthetic_Preview_Context $context = null ): array {
		$key     = (string) ( $page_definition['internal_key'] ?? '' );
		$side    = $this->build_for_page( $page_definition, $context );
		$options = array(
			'reduced_motion' => $context !== null && $context->is_reduced_motion(),
			'animation_tier' => $context !== null ? $context->get_animation_tier() : Synthetic_Preview_Context::ANIMATION_TIER_NONE,
		);
		return array(
			'template_key'         => $key,
			'section_field_values' => $section_field_values,
			'side_panel'           => $side,
			'options'              => $options,
		);
	}
}
