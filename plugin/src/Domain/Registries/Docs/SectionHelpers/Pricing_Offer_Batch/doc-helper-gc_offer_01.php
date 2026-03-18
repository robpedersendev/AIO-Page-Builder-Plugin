<?php
/**
 * Section helper documentation: gc_offer_01 (Offer block 1). Spec §15; documentation-object-schema.
 * Pricing/Offer batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-gc_offer_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Generic offer block 1. Headline and body for offer or pricing context.</p><h3>User need</h3><p>Editors need a short block that states one offer point clearly.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Offer headline. Be specific.</li><li><strong>Body</strong>: Supporting copy. No raw HTML. Transparent; no hidden ambiguity.</li></ul><h3>Clarity and offer framing</h3><p>One clear message per section. Do not overclaim.</p><h3>GeneratePress / ACF</h3><p>Block structure; headline and body map to ACF.</p><h3>Tone and mistakes to avoid</h3><p>Clear, honest. Avoid vague or manipulative language.</p><h3>SEO and accessibility</h3><p>One heading; body as paragraphs; contrast and order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'gc_offer_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
