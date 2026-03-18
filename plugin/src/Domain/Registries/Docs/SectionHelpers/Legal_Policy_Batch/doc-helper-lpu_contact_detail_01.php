<?php
/**
 * Section helper documentation: lpu_contact_detail_01 (Structured contact detail). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_contact_detail_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Structured contact block: title and address/phone/email fields. Use for a single location or entity. Omit empty fields for clean output.</p><h3>User need</h3><p>Editors need a block for full address and contact details in a consistent structure.</p><h3>Field-by-field guidance</h3><ul><li><strong>Title</strong> (optional): e.g. "Head office", "Registered address".</li><li><strong>Address line 1, Address line 2, City, Region / state, Postal code, Country</strong>: Use as needed; leave empty to omit from output.</li><li><strong>Phone, Email</strong>: Optional; use tel: and mailto: when rendered as links.</li></ul><h3>GeneratePress and accessibility</h3><p>Address can use semantic markup (e.g. address element). Ensure logical order and sufficient contrast. Link phone and email with descriptive text.</p><h3>Practical notes</h3><p>Safe failure: template should omit any empty field so users do not see blank labels. Keep details current.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_contact_detail_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
