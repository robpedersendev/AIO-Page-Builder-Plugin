<?php
/**
 * Section helper documentation: tp_quote_01 (Pull quote). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-tp_quote_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Single pull quote with source. Use for editorial or authority highlight on articles or resource pages.</p><h3>User need</h3><p>Editors need a focused block for one quote with clear attribution.</p><h3>Field-by-field guidance</h3><ul><li><strong>Quote</strong> (required): Exact quote text. Keep concise and impactful.</li><li><strong>Source</strong> (optional): Attribution (e.g. name, publication, role). Always include for credibility.</li></ul><h3>Credibility and proof quality</h3><p>Use real quotes with accurate attribution. Do not alter wording in a way that changes meaning. Source adds authority.</p><h3>Accessibility</h3><p>Associate quote with source programmatically (e.g. cite element). Ensure contrast; avoid quote as image of text.</p><h3>Mistakes to avoid</h3><p>Do not leave source empty when the quote is from an identifiable person or entity. Do not misattribute quotes.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'tp_quote_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
