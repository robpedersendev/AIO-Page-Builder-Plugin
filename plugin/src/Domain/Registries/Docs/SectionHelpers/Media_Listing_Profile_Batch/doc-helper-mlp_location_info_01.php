<?php
/**
 * Section helper documentation: mlp_location_info_01 (Location info). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-mlp_location_info_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Location or map-like info block with address and optional hours/contact. Use for place or venue detail. Omit hours/contact when empty.</p><h3>User need</h3><p>Editors need a single location block with address and optional hours/contact; empty fields omitted.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Find us").</li><li><strong>Address</strong>: Full address or main address line. Use consistent format.</li><li><strong>Hours or availability</strong>: Optional. Omit when not applicable.</li><li><strong>Contact label or phone</strong>: Optional. Omit when not used.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders single info block. Use GeneratePress for container. Empty hours/contact not rendered.</p><h3>AIOSEO</h3><p>Address and location support local/place signals. Keep address format consistent for schema.</p><h3>Consistency</h3><p>Use same address format across the site. Do not duplicate contact info that appears in global footer/header without reason.</p><h3>SEO and accessibility</h3><p>One section heading; address and optional fields should be clearly labelled. Ensure contrast for text.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'mlp_location_info_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
