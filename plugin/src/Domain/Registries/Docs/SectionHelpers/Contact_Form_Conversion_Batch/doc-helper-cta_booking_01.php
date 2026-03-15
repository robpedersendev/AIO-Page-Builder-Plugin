<?php
/**
 * Section helper documentation: cta_booking_01 (Booking CTA minimalist). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-cta_booking_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Minimalist booking CTA. Single primary action; clear label. Use for reserve/book conversion with minimal copy.</p><h3>User need</h3><p>Editors need a focused booking conversion block without distraction.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Short booking invite (e.g. "Reserve your spot", "Book your appointment").</li><li><strong>Body</strong>: Optional. Omit or one short line.</li><li><strong>Primary button label</strong> (required): Action (e.g. "Book now", "See availability").</li><li><strong>Primary button link</strong>: Target booking page, calendar, or form.</li></ul><h3>Conversion and page-fit</h3><p>Use where booking is the natural next step. Ensure the link goes to a working booking flow. Do not use for "contact" if the primary action is booking.</p><h3>Tone and mistakes to avoid</h3><p>Direct, action-led. Avoid "Learn more" when the action is book/reserve.</p><h3>SEO and accessibility</h3><p>Button label describes action; link target clear.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'cta_booking_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
