<?php
/**
 * Section helper documentation: cta_inquiry_02 (Inquiry CTA proof-backed). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_inquiry_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Inquiry CTA with optional trust/proof line (e.g. "We respond within 24 hours"). Proof-backed variant for reassurance.</p><h3>User need</h3><p>Editors need an inquiry block that can include a trust line to reduce friction.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Request more information").</li><li><strong>Body</strong>: (e.g. "We respond within 24 hours.").</li><li><strong>Primary button</strong>: (e.g. "Send inquiry").</li><li><strong>Trust line</strong>: (e.g. "We reply within 24 hours"). Omit if empty.</li></ul><h3>CTA-specific guidance</h3><p>Action-oriented button; trust line should be specific and credible. Avoid weak or generic offers.</p><h3>Tone and mistakes to avoid</h3><p>Helpful, reassuring. Do not overclaim response times.</p><h3>SEO and accessibility</h3><p>Descriptive labels; contrast; focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_inquiry_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
