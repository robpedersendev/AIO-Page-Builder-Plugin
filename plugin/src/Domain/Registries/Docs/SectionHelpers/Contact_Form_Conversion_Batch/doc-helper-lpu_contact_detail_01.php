<?php
/**
 * Section helper documentation: lpu_contact_detail_01 (Structured contact detail). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_contact_detail_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Structured contact block: title and address/phone/email fields. Omit empty fields for clean output. Use for physical location or single contact point.</p><h3>User need</h3><p>Editors need a single structured block for address and contact details that supports conversion and directions.</p><h3>Field-by-field guidance</h3><ul><li><strong>Title</strong>: Optional (e.g. "Head office", "Main office").</li><li><strong>Address line 1</strong>, <strong>Line 2</strong>, <strong>City</strong>, <strong>Region</strong>, <strong>Postcode</strong>, <strong>Country</strong>: Use for physical address. Omit when empty.</li><li><strong>Phone</strong>, <strong>Email</strong>: Contact details. Omit when empty. Use consistent format.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders structured block; empty fields are omitted. Use GeneratePress for container.</p><h3>Conversion and page-fit</h3><p>Use near contact CTA or form. Ensure address is correct for maps/directions. Phone and email should be clickable (tel:, mailto:) where appropriate.</p><h3>Tone and mistakes to avoid</h3><p>Accurate, consistent. Do not use placeholder address or fake data.</p><h3>SEO and accessibility</h3><p>Structured data supports local/contact signals. Ensure links have descriptive text.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_contact_detail_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
