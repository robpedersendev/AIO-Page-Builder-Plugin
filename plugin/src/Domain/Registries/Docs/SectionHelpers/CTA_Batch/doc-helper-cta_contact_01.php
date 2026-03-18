<?php
/**
 * Section helper documentation: cta_contact_01 (Contact CTA subtle). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_contact_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Subtle contact CTA. Clear primary button; omit secondary when empty. Use for "Contact us" blocks without heavy emphasis.</p><h3>User need</h3><p>Editors need a simple contact conversion block.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Contact us").</li><li><strong>Body</strong>: (e.g. "We are here to help.").</li><li><strong>Primary button</strong>: (e.g. "Contact"). Descriptive label required.</li></ul><h3>CTA-specific guidance</h3><p>Use clear contact language; avoid "Submit" or "Click here". One primary action.</p><h3>Tone and mistakes to avoid</h3><p>Friendly, direct. Do not repeat the same CTA from elsewhere.</p><h3>SEO and accessibility</h3><p>Button label describes action. Contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_contact_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
