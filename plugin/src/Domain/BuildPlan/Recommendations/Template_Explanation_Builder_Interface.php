<?php
/**
 * Interface for template explanation payload used by Build Plan new-page recommendation (Prompt 192).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Recommendations;

defined( 'ABSPATH' ) || exit;

/**
 * Builds template explanation (name, family, CTA, deprecation) for a template key and optional payload.
 */
interface Template_Explanation_Builder_Interface {

	/**
	 * Builds explanation array for the given template key.
	 *
	 * @param string               $template_key Page template internal_key.
	 * @param array<string, mixed> $item_payload Optional item payload (purpose, page_type, etc.).
	 * @return array<string, mixed> template_key, name, template_category_class, template_family, cta_direction_summary, section_count, deprecation_status, replacement_keys, explanation_lines, etc.
	 */
	public function build_explanation( string $template_key, array $item_payload = array() ): array;
}
