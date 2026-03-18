<?php
/**
 * Section helper documentation: mlp_listing_01 (Listing). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-mlp_listing_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>List of items with title, optional description, image, and link. Use for directory or content lists. Omit optional fields when empty.</p><h3>User need</h3><p>Editors need a flexible listing structure; empty image/link are omitted for clean output.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional.</li><li><strong>Items</strong> (repeater, required): Each item — <strong>Title</strong> (required); <strong>Description</strong>, <strong>Image</strong>, <strong>Link</strong>: optional. Keep titles unique and scannable; use image only when useful (with alt).</li></ul><h3>GeneratePress / ACF / FIFU</h3><p>Section uses block structure; repeater maps to list. Use GeneratePress for spacing. Image can be ACF or FIFU; omit when empty.</p><h3>AIOSEO</h3><p>Headline and item titles support listing/directory signals.</p><h3>Consistency</h3><p>Keep listing structure consistent (e.g. all with or all without description). Avoid random mix of empty/full image per item unless intentional.</p><h3>SEO and accessibility</h3><p>One section heading; semantic list. Images require alt; omit when empty. Links need descriptive text.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'mlp_listing_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
