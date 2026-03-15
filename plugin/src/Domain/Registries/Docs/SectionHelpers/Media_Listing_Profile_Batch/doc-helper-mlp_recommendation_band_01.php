<?php
/**
 * Section helper documentation: mlp_recommendation_band_01 (Recommendation band). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-mlp_recommendation_band_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Band of recommendations with title, description, and optional link. Use for related or recommended content. Omit link when empty.</p><h3>User need</h3><p>Editors need to surface related or recommended items with consistent structure; empty link omitted.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Recommended").</li><li><strong>Recommendations</strong> (repeater, required): Each item — <strong>Title</strong> (required); <strong>Description</strong>; <strong>Link</strong>: optional. Use link when item has a destination; omit when not. Keep titles descriptive.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to band. Use GeneratePress for layout.</p><h3>AIOSEO</h3><p>Headline and titles support related-content signals. Link text should be descriptive.</p><h3>Consistency</h3><p>Keep recommendation items parallel in structure and length where possible.</p><h3>SEO and accessibility</h3><p>One section heading; list semantics. Links need visible, descriptive text when present.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'mlp_recommendation_band_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
