<?php
/**
 * Section helper documentation: lpu_inquiry_support_01 (Inquiry support). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_inquiry_support_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Inquiry or support section with heading, intro, and form-embed slot. Form provider supplies actual form; ensure labels and required-field indication (spec §51.9).</p><h3>User need</h3><p>Editors need a section that introduces the inquiry form and holds the form shortcode or block reference.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Optional (e.g. "Send an inquiry").</li><li><strong>Intro</strong>: Short copy above the form (e.g. "Use the form below. All fields marked required must be completed."). Sets expectation and reduces friction.</li><li><strong>Form embed (shortcode or block identifier)</strong>: Reference to the form (shortcode, block id, or provider-specific ref). This section does not implement the form; it provides the slot. Ensure the form has clear labels and required-field indication.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders heading, intro, and form slot. Use GeneratePress for container and spacing. Form output is provided by the form provider.</p><h3>Form support copy and friction</h3><p>Intro should explain what to expect (e.g. response time, required fields). Do not promise what the form or process cannot deliver. Do not expose implementation details; keep content user-facing.</p><h3>Tone and mistakes to avoid</h3><p>Clear, helpful. Avoid vague "We will get back to you"; avoid hidden or unclear required fields in the form itself.</p><h3>SEO and accessibility</h3><p>Heading and intro support form context. Form must have accessible labels and required-field indication per spec §51.9.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_inquiry_support_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
