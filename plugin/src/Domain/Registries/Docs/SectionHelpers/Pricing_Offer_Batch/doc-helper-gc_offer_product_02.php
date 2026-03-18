<?php
/**
 * Section helper documentation: gc_offer_product_02 (Offer product spec B). Spec §15; documentation-object-schema.
 * Pricing/Offer batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-gc_offer_product_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Second product-spec variant. Supports child_detail product pages.</p><h3>User need</h3><p>Editors need another product-offer block with same structure.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Product or offer headline. Specific.</li><li><strong>Body</strong>: Specs or key points. No raw HTML.</li></ul><h3>Clarity and offer framing</h3><p>One clear message; do not overclaim. Keep parallel with other product sections.</p><h3>GeneratePress / ACF</h3><p>Block structure; headline and body map to ACF.</p><h3>Tone and mistakes to avoid</h3><p>Clear, factual. Avoid vague or duplicate copy.</p><h3>SEO and accessibility</h3><p>One heading; body as paragraphs; contrast and order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'gc_offer_product_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
