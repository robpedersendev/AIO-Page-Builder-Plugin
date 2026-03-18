<?php
/**
 * Section helper documentation: mlp_related_content_01 (Related content). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-mlp_related_content_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Related or recommended content with title, optional excerpt, and link. Use for related articles or content. Omit excerpt/link when empty.</p><h3>User need</h3><p>Editors need to surface related items with consistent fields; empty excerpt/link omitted.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Related").</li><li><strong>Items</strong> (repeater, required): Each item — <strong>Title</strong> (required); <strong>Excerpt</strong>; <strong>Link</strong>. Use excerpt when it adds context; omit when empty. Link to actual content; omit when not available.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to list or cards. Use GeneratePress for spacing.</p><h3>AIOSEO</h3><p>Headline and titles support related-content signals. Link text should be descriptive.</p><h3>Consistency</h3><p>Keep related items genuinely related to page context. Avoid duplicate or thin excerpts.</p><h3>SEO and accessibility</h3><p>One section heading; list semantics. Links need visible, descriptive text.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'mlp_related_content_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
