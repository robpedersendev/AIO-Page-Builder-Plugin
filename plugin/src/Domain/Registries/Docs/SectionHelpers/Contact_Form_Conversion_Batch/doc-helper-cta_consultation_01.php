<?php
/**
 * Section helper documentation: cta_consultation_01 (Consultation CTA subtle). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_consultation_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Subtle CTA inviting consultation or discovery call. Clear primary button; omit secondary and image when empty.</p><h3>User need</h3><p>Editors need a consultation conversion block that fits mid-page or after proof without heavy emphasis.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Consultation invite (e.g. "Book a consultation", "Schedule a discovery call").</li><li><strong>Body</strong>: Optional (e.g. "Discuss your needs with our team.").</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Action (e.g. "Schedule a call"). Link to booking or contact.</li><li><strong>Trust line</strong>: Optional (e.g. "Free 15-minute call"). Only if accurate.</li></ul><h3>Conversion and page-fit</h3><p>Use where consultation is the natural next step. Ensure the link leads to a real scheduling or contact flow. Avoid stacking multiple consultation CTAs.</p><h3>Tone and mistakes to avoid</h3><p>Inviting, clear. Do not overclaim "free" or "no obligation" if there are conditions.</p><h3>SEO and accessibility</h3><p>Button label describes action; contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_consultation_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
