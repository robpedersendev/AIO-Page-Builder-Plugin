<?php
/**
 * Section helper documentation: lpu_contact_panel_01 (Contact panel). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-lpu_contact_panel_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Contact panel with heading and repeatable channels (label, value, type). Semantic contact info; omit empty channels. Use on policy, support, or utility pages.</p><h3>User need</h3><p>Editors need a flexible block to list contact methods (email, phone, URL) in a structured way.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (optional): e.g. "Contact us".</li><li><strong>Contact channels (repeater)</strong>: <strong>Label</strong> (required)—e.g. "Email", "Support phone"; <strong>Value</strong> (required)—e.g. address, number, URL; <strong>Type</strong> (optional)—email, phone, url, other for semantics or linking. Omit rows when value is empty.</li></ul><h3>GeneratePress and accessibility</h3><p>Use semantic list or definition list. Email and phone can be linked (mailto:, tel:). Ensure links have descriptive text. Sufficient contrast.</p><h3>Practical notes</h3><p>Keep contact details current. Safe failure: do not render channel rows with empty value.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'lpu_contact_panel_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
