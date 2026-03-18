<?php
/**
 * Section helper documentation: gc_offer_value_01 (Offer value proposition A). Spec §15; documentation-object-schema.
 * Pricing/Offer batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-gc_offer_value_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Value proposition block for offer or product. Headline and supporting copy. Use for pricing or offer pages to state value clearly.</p><h3>User need</h3><p>Editors need a short block that states offer value without hidden ambiguity.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Value or offer headline. Be specific; align with page or AIOSEO focus.</li><li><strong>Body</strong>: Supporting copy (what is included, key benefit). No raw HTML. Keep transparent; avoid vague "best value".</li></ul><h3>Clarity and offer framing</h3><p>State what the offer is and for whom. Do not overclaim; avoid hidden conditions. One clear message per section.</p><h3>GeneratePress / ACF</h3><p>Section uses block structure; headline and body map to ACF fields. Use GeneratePress for container and spacing.</p><h3>Tone and mistakes to avoid</h3><p>Clear, honest. Avoid weak claims or vague reassurance; avoid manipulative pricing language.</p><h3>SEO and accessibility</h3><p>One heading per section; body as paragraphs. Contrast and logical order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'gc_offer_value_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
