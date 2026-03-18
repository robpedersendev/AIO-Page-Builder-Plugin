<?php
/**
 * Section helper documentation: cta_quote_request_01 (Quote request CTA minimalist). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_quote_request_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Minimalist quote request CTA: one primary action. Use when you need a simple "Get a quote" block.</p><h3>User need</h3><p>Editors need a single, clear quote request action.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Get a quote").</li><li><strong>Body</strong>: Optional.</li><li><strong>Primary button</strong>: (e.g. "Request quote"). Descriptive label required.</li></ul><h3>CTA-specific guidance</h3><p>Action language; state what the user gets. Avoid "Submit" or vague labels.</p><h3>Tone and mistakes to avoid</h3><p>Direct, professional. One clear offer.</p><h3>SEO and accessibility</h3><p>Button label describes action. Contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_quote_request_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
