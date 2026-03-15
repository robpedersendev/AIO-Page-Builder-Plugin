<?php
/**
 * Section helper documentation: cta_consultation_02 (Consultation CTA strong). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-cta_consultation_02',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Strong consultation CTA with emphasis. Supports primary and secondary actions; omit when empty.</p><h3>User need</h3><p>Editors need a high-emphasis consultation block with optional secondary and trust line.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Consultation invite (e.g. "Ready to get started?").</li><li><strong>Body</strong>: Supporting copy (e.g. "Book your consultation today.").</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Main action (e.g. "Book now").</li><li><strong>Secondary button</strong>: Only if distinct (e.g. "Learn more"). Omit when empty.</li><li><strong>Trust line</strong>: Optional (e.g. "Trusted by 1,000+ clients"). Must be accurate.</li></ul><h3>Conversion and friction</h3><p>Primary action must be the main conversion. Secondary should not compete. Use where consultation is a key page goal.</p><h3>Tone and mistakes to avoid</h3><p>Confident, clear. Avoid vague trust lines or duplicate consultation CTAs.</p><h3>SEO and accessibility</h3><p>Button labels describe actions; contrast and focus order.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'cta_consultation_02' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
