<?php
/**
 * Section helper documentation: mlp_place_highlight_01 (Place / location highlight). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-mlp_place_highlight_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Place or location highlights with name, optional address, description, and link. Use for locations or venues. Omit address/link when empty.</p><h3>User need</h3><p>Editors need consistent place entries; empty address/link are omitted.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Locations").</li><li><strong>Places</strong> (repeater, required): Each place — <strong>Name</strong> (required); <strong>Address line</strong>; <strong>Description</strong>; <strong>Link</strong>. Use address when relevant (e.g. physical venue); omit when not applicable. Link to map or detail page when useful.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to place blocks. Use GeneratePress for spacing.</p><h3>AIOSEO</h3><p>Place names and address support local/place signals. Link text descriptive.</p><h3>Consistency</h3><p>Keep place structure parallel (e.g. all with address or all without) where possible.</p><h3>SEO and accessibility</h3><p>One section heading; place names and address should be clearly associated. Links need visible text.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'mlp_place_highlight_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
