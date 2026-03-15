<?php
/**
 * Section helper documentation: mlp_gallery_01 (Gallery). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-mlp_gallery_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Gallery with headline and repeatable image plus optional caption. Use for media groups. Omit caption when empty.</p><h3>User need</h3><p>Editors need a structured gallery with required image and optional caption per item; empty captions omitted.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional.</li><li><strong>Gallery items</strong> (repeater, required): Each item — <strong>Image</strong> (required): ACF image or FIFU; <strong>Caption</strong>: optional. Every image must have descriptive alt text for accessibility. Use caption for context when it adds value; omit when empty.</li></ul><h3>GeneratePress / ACF / FIFU</h3><p>Section renders gallery grid or strip; repeater maps to items. Use GeneratePress for columns and spacing. Image supports ACF or FIFU; alt required. Caption omitted when empty.</p><h3>AIOSEO</h3><p>Headline and image alt text support media/entity signals. Do not keyword-stuff alt.</p><h3>Consistency and mistakes</h3><p>Use consistent image aspect or size where possible. Avoid decorative images without meaningful alt; do not use caption for duplicate headline.</p><h3>SEO and accessibility</h3><p>One section heading; each image must have alt. Caption should add information, not repeat alt. Ensure sufficient contrast for any overlay text.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'mlp_gallery_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
