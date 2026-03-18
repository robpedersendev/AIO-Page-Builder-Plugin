<?php
/**
 * Section helper documentation: cta_contact_02 (Contact CTA strong). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_contact_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Strong contact CTA with optional secondary action and trust line. Use when contact is a primary conversion goal.</p><h3>User need</h3><p>Editors need a prominent contact block (e.g. "Contact now" + "View locations").</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Get in touch").</li><li><strong>Body</strong>: (e.g. "Call, email, or visit.").</li><li><strong>Primary button</strong>: (e.g. "Contact now").</li><li><strong>Secondary button</strong>: (e.g. "View locations"). Omit when empty.</li></ul><h3>CTA-specific guidance</h3><p>Primary and secondary must be distinct. Action language; avoid generic buttons.</p><h3>Tone and mistakes to avoid</h3><p>Clear, inviting. Do not use two similar CTAs.</p><h3>SEO and accessibility</h3><p>Descriptive labels; contrast; focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_contact_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
