<?php
/**
 * Section helper documentation: mlp_media_band_01 (Media band). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-mlp_media_band_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Band of media items with optional image and caption. Use for image strips or media highlights. Omit image/caption when empty.</p><h3>User need</h3><p>Editors need a flexible media strip; empty image or caption are omitted so layout stays clean.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional.</li><li><strong>Media items</strong> (repeater, required): Each item — <strong>Image</strong>: optional, ACF or FIFU; <strong>Caption</strong>: optional. When image is used, provide descriptive alt. Omit image/caption when not used.</li></ul><h3>GeneratePress / ACF / FIFU</h3><p>Section renders media band; repeater maps to items. Use GeneratePress for layout. Image optional; alt required when present. Empty image/caption not rendered.</p><h3>AIOSEO</h3><p>Image alt supports media signals. Avoid keyword-stuffing alt.</p><h3>Consistency</h3><p>Use media band when you need optional images (e.g. some rows with, some without). For all-required images use gallery section instead.</p><h3>SEO and accessibility</h3><p>One section heading; images require alt when present; omit image node when empty. Caption should add context.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'mlp_media_band_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
