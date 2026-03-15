<?php
/**
 * Section helper documentation: cta_booking_01 (Booking CTA minimalist). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-cta_booking_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Minimalist booking CTA: heading, optional body, and one primary button. Use when you want a single, clear booking action without extra copy or secondary CTA.</p><h3>User need</h3><p>Editors need a simple "Reserve your spot" or "Book now" block with minimal layout.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Direct (e.g. "Reserve your spot").</li><li><strong>Body</strong>: Optional; leave empty for minimal.</li><li><strong>Primary button label/link</strong>: One action (e.g. "Book now"). Must be descriptive.</li></ul><h3>GeneratePress / ACF</h3><p>Map heading and primary CTA only when going minimal.</p><h3>CTA-specific guidance</h3><p>Use one clear action verb. Avoid "Click here"; use "Book now", "See availability", or similar. No weak or repeated offers.</p><h3>Tone and mistakes to avoid</h3><p>Direct and concise. Do not add a second CTA or long body copy in this minimalist variant.</p><h3>SEO and accessibility</h3><p>Button label must describe the action. Contrast and focus order required.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'cta_booking_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
