<?php
/**
 * Section helper documentation: cta_booking_02 (Booking CTA media-backed). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_booking_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Booking CTA with optional image for visual emphasis. Use when a supporting image (e.g. calendar, team, venue) strengthens the booking message.</p><h3>User need</h3><p>Editors need a booking block that can include an image alongside heading, body, and primary CTA.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Book your appointment").</li><li><strong>Body</strong>: (e.g. "Choose a time that works for you.").</li><li><strong>Primary button</strong>: (e.g. "See availability").</li><li><strong>Image</strong>: Optional; use relevant asset with alt text. Omit when not needed.</li></ul><h3>GeneratePress / ACF / FIFU</h3><p>Map all fields. Image field stores attachment; optimize size and alt text. FIFU can be used for external image if supported.</p><h3>CTA-specific guidance</h3><p>Action language on the button; avoid generic "Submit". Image should support the offer, not distract.</p><h3>Tone and mistakes to avoid</h3><p>Clear, inviting. Do not use a weak or off-topic image.</p><h3>SEO and accessibility</h3><p>Descriptive button and image alt text. Contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_booking_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
