<?php
/**
 * Section helper documentation: gc_offer_local_01 (Offer local / service). Spec §15; documentation-object-schema.
 * Pricing/Offer batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-gc_offer_local_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Local or service-area offer block. Headline and short description. Use for location-specific pricing or offer.</p><h3>User need</h3><p>Editors need a block that states local/service offer clearly without hidden ambiguity.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Local or service offer headline. Be specific.</li><li><strong>Body</strong>: Short description (area, scope, or key point). No raw HTML.</li></ul><h3>Clarity and offer framing</h3><p>State what the offer covers and for whom (e.g. area). Do not overclaim geographic or service scope.</p><h3>GeneratePress / ACF</h3><p>Block structure; headline and body map to ACF.</p><h3>Tone and mistakes to avoid</h3><p>Clear, accurate. Avoid vague "We serve your area" without clarity.</p><h3>SEO and accessibility</h3><p>One heading; body as paragraphs; contrast and order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'gc_offer_local_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
