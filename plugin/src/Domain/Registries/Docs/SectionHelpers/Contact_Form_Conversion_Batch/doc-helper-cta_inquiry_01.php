<?php
/**
 * Section helper documentation: cta_inquiry_01 (Inquiry CTA minimalist). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-cta_inquiry_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Minimalist inquiry CTA. Single primary action to contact or request info. Use where space or focus is limited.</p><h3>User need</h3><p>Editors need a minimal block that drives one inquiry action without extra copy.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Short inquiry invite (e.g. "Request more information").</li><li><strong>Body</strong>: Optional. Omit or use one short line (e.g. response time).</li><li><strong>Primary button label</strong> (required): Action (e.g. "Send inquiry", "Request info"). Not "Submit".</li><li><strong>Primary button link</strong>: Target inquiry form or contact page.</li></ul><h3>Form support and friction</h3><p>If the button links to a form, ensure the form has clear labels and required-field indication. This section does not implement the form; it supports the conversion step. Avoid stacking multiple inquiry CTAs.</p><h3>Tone and mistakes to avoid</h3><p>Direct, low-friction. Avoid vague "Learn more" when the action is inquiry.</p><h3>SEO and accessibility</h3><p>Button label describes action; sufficient contrast.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'cta_inquiry_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
