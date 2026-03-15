<?php
/**
 * Section helper documentation: gc_offer_10 (Offer block 10). Spec §15; documentation-object-schema.
 * Pricing/Offer batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-gc_offer_10',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Generic offer block 10. Use in sequence for multi-offer or pricing pages.</p><h3>User need</h3><p>Editors need another offer block with same structure.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Offer headline. Specific.</li><li><strong>Body</strong>: Supporting copy. No raw HTML.</li></ul><h3>Clarity and offer framing</h3><p>One clear message; keep parallel with other offer sections.</p><h3>GeneratePress / ACF</h3><p>Block structure; headline and body map to ACF.</p><h3>Tone and mistakes to avoid</h3><p>Clear, honest. Avoid vague or duplicate copy.</p><h3>SEO and accessibility</h3><p>One heading; body as paragraphs; contrast and order.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'gc_offer_10' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
