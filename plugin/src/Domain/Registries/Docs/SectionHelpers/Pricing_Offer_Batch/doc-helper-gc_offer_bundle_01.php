<?php
/**
 * Section helper documentation: gc_offer_bundle_01 (Offer bundle summary). Spec §15; documentation-object-schema.
 * Pricing/Offer batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-gc_offer_bundle_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Bundle or package offer summary. Headline and list of included items. Use for bundle pricing or package clarity.</p><h3>User need</h3><p>Editors need a block that states what is in the bundle clearly without hidden ambiguity.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Bundle headline (e.g. "What is included", "Bundle contents").</li><li><strong>Body</strong>: List of included items or key points. No raw HTML. Be specific; avoid "everything you need".</li></ul><h3>Clarity and transparency</h3><p>List what is included; state exclusions if relevant. Do not hide what is not in the bundle.</p><h3>GeneratePress / ACF</h3><p>Block structure; headline and body map to ACF.</p><h3>Tone and mistakes to avoid</h3><p>Clear, transparent. Avoid vague "Complete package" without listing.</p><h3>SEO and accessibility</h3><p>One heading; body as paragraphs or list; contrast and order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'gc_offer_bundle_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
