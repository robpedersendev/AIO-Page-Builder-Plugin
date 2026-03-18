<?php
/**
 * Section helper documentation: cta_inquiry_01 (Inquiry CTA minimalist). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_inquiry_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Minimalist inquiry CTA: one primary action to contact or request info. Use when you need a simple "Get in touch" block.</p><h3>User need</h3><p>Editors need a single, clear inquiry action without extra copy or secondary CTA.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Have questions?").</li><li><strong>Body</strong>: Optional; leave empty for minimal.</li><li><strong>Primary button</strong>: (e.g. "Get in touch"). Descriptive label required.</li></ul><h3>CTA-specific guidance</h3><p>Use action language; avoid "Click here". One clear offer (e.g. contact, request info).</p><h3>Tone and mistakes to avoid</h3><p>Direct, helpful. Do not add competing CTAs or vague labels.</p><h3>SEO and accessibility</h3><p>Button label describes action. Contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_inquiry_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
