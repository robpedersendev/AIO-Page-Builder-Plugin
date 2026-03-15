<?php
/**
 * Section helper documentation: fb_resource_explainer_01 (Resource explainer). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_resource_explainer_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Explainer block with headline, body copy, and optional key points. Use for resource or informational pages.</p><h3>User need</h3><p>Editors need to explain a topic or resource clearly with one body and optional takeaways.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Clear explainer title. Align with page or AIOSEO focus if primary explainer.</li><li><strong>Body copy</strong> (required): Main explanation. Use short paragraphs; no raw HTML in ACF.</li><li><strong>Key points</strong> (repeater): Each row — <strong>Point text</strong>. Optional summary bullets; do not duplicate body.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure. Use GeneratePress for container width and spacing; body copy maps to main content area.</p><h3>AIOSEO / FIFU</h3><p>Headline and body support explainer/resource intent. Align headline with focus keyphrase where appropriate.</p><h3>Tone and mistakes to avoid</h3><p>Use clear, instructive tone. Avoid long unbroken body text, key points that repeat the headline verbatim, or thin content.</p><h3>SEO and accessibility</h3><p>One primary heading; body as paragraphs; key points as semantic list. Ensure contrast and logical reading order.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_resource_explainer_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
