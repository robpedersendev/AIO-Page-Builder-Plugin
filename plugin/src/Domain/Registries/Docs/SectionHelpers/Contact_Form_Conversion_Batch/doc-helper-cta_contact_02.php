<?php
/**
 * Section helper documentation: cta_contact_02 (Contact CTA strong). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-cta_contact_02',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Strong contact CTA with optional secondary action and trust line. Use when contact is a primary page goal.</p><h3>User need</h3><p>Editors need a high-emphasis contact block with room for reassurance and one secondary action.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Clear contact invite. Can be benefit-led (e.g. "Talk to an expert").</li><li><strong>Body</strong>: Supporting copy; use for response-time or what happens next. Keep short.</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Main contact action. Descriptive label.</li><li><strong>Secondary button</strong>: Only if you have a real second action (e.g. "Call instead"). Omit when empty.</li><li><strong>Image</strong>: Optional. Use only if it supports the message; omit when empty.</li><li><strong>Trust line</strong>: Optional reassurance (e.g. response time, "No obligation"). Do not overclaim.</li></ul><h3>CTA clarity and friction</h3><p>Primary action must be the main conversion path. Secondary should not compete; avoid too many choices.</p><h3>Tone and mistakes to avoid</h3><p>Confident, clear. Avoid vague reassurance or duplicate contact CTAs on the same page.</p><h3>SEO and accessibility</h3><p>Button labels describe actions; ensure contrast and focus order.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'cta_contact_02' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
