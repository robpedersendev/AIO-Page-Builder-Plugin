<?php
/**
 * Section helper documentation: gc_offer_compare_01 (Offer comparison block). Spec §15; documentation-object-schema.
 * Pricing/Offer batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-gc_offer_compare_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Offer comparison or tier block. For pricing or plan comparison. Use to support decision with clear, fair comparison.</p><h3>User need</h3><p>Editors need a block that explains comparison or tiers without biased framing.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Comparison headline (e.g. "Compare plans", "How options differ").</li><li><strong>Body</strong>: Comparison points or tier summary. No raw HTML. Use neutral wording; keep parallel structure.</li></ul><h3>Clarity and comparison quality</h3><p>State differences fairly; do not stack one option as "best" without justification. Avoid hidden conditions.</p><h3>GeneratePress / ACF</h3><p>Block structure; headline and body map to ACF.</p><h3>Tone and mistakes to avoid</h3><p>Neutral, factual. Avoid manipulative or vague comparison language.</p><h3>SEO and accessibility</h3><p>One heading; body as paragraphs; contrast and order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'gc_offer_compare_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
