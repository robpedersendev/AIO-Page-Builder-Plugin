<?php
/**
 * Section helper documentation: lpu_contact_panel_01 (Contact panel). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_contact_panel_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Contact panel with heading and repeatable channels (label, value, type). Semantic contact info; omit empty channels. Use alongside contact CTAs or form sections.</p><h3>User need</h3><p>Editors need a structured way to show contact channels (email, phone, URL) that supports conversion and reduces friction.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Optional (e.g. "Contact us").</li><li><strong>Contact channels</strong> (repeater, required): Each row — <strong>Label</strong> (required), <strong>Value</strong> (required, e.g. email, phone), <strong>Type</strong> (optional: email, phone, url, other). Keep labels consistent; use type for semantics and display. Omit empty channels in output.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders in block structure; repeater maps to channel list. Use GeneratePress for spacing.</p><h3>Conversion and page-fit</h3><p>Use near contact CTAs or form so users have multiple ways to reach you. Ensure values are correct and clickable where appropriate (e.g. mailto:, tel:). Do not duplicate the same contact info in multiple sections without reason.</p><h3>Tone and mistakes to avoid</h3><p>Clear, accurate. Avoid placeholder or fake contact data; keep channel list current.</p><h3>SEO and accessibility</h3><p>Use semantic structure for channels; ensure links have descriptive text and contrast.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_contact_panel_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
