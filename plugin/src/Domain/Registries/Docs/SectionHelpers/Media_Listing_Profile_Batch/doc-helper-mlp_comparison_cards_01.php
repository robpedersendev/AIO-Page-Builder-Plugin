<?php
/**
 * Section helper documentation: mlp_comparison_cards_01 (Comparison cards). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-mlp_comparison_cards_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Comparison cards with title, optional features list, and link. Use for plan or option comparison. Omit link when empty.</p><h3>User need</h3><p>Editors need card-based comparison with consistent structure; empty link omitted.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Compare").</li><li><strong>Cards</strong> (repeater, required): Each card — <strong>Title</strong> (required): option/plan name; <strong>Features</strong>: one per line or short list; <strong>Link</strong>: optional. Keep features parallel across cards; use neutral wording. Omit link when no destination.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders card grid; repeater maps to cards. Use GeneratePress for columns and spacing.</p><h3>AIOSEO</h3><p>Headline and card titles support comparison intent. Link text descriptive when used.</p><h3>Tone and consistency</h3><p>Use factual, neutral tone for comparison. Avoid biased labels or duplicate comparison content elsewhere.</p><h3>SEO and accessibility</h3><p>One section heading; each card should have clear structure. Links need visible, descriptive text.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'mlp_comparison_cards_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
