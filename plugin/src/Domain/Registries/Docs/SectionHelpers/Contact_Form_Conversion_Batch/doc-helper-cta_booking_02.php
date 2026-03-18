<?php
/**
 * Section helper documentation: cta_booking_02 (Booking CTA media-backed). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_booking_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Booking CTA with optional image. Media-backed variant for visual emphasis. Use when booking is a primary conversion and imagery supports the message.</p><h3>User need</h3><p>Editors need a booking block with room for supporting copy and optional image.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Booking invite (e.g. "Book your appointment").</li><li><strong>Body</strong>: Optional (e.g. "Choose a time that works for you.").</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Booking action (e.g. "See availability").</li><li><strong>Image</strong>: Optional. Use when it adds context (e.g. practitioner, venue); omit when empty. Provide alt text.</li><li>Omit secondary, trust line when empty.</li></ul><h3>Conversion and page-fit</h3><p>Link must go to a real booking flow. Avoid decorative image that does not support the offer.</p><h3>Tone and mistakes to avoid</h3><p>Clear, inviting. Do not overclaim availability or speed.</p><h3>SEO and accessibility</h3><p>Button and image alt descriptive; contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_booking_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
