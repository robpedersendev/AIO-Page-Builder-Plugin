<?php
/**
 * Section helper documentation: cta_consultation_01 (Consultation CTA subtle). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_consultation_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Subtle CTA inviting consultation or a discovery call. One clear primary button; omit secondary and image when empty. Use on service or hub pages.</p><h3>User need</h3><p>Editors need a conversion block that drives consultation bookings without overwhelming the page.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Direct invite (e.g. "Book a consultation").</li><li><strong>Body</strong>: Short support (e.g. "Discuss your needs with our team.").</li><li><strong>Primary button label/link</strong>: Action-oriented (e.g. "Schedule a call"). Use descriptive text, not "Submit" or "Click here".</li><li><strong>Trust line</strong>: Optional reassurance (e.g. "Free 15-minute call"). Omit if empty.</li><li><strong>Secondary / Image</strong>: Leave empty for subtle variant.</li></ul><h3>GeneratePress / ACF</h3><p>Map heading, body, primary CTA, and trust line. Section renders in template block structure.</p><h3>AIOSEO / FIFU</h3><p>CTA copy supports intent; no image required for this variant.</p><h3>CTA-specific guidance</h3><p>Use clear, action language (e.g. "Schedule a call" not "Learn more"). Avoid weak or generic offers; state what the user gets. One primary action only in this subtle variant.</p><h3>Tone and mistakes to avoid</h3><p>Professional, inviting tone. Do not repeat the same CTA wording used elsewhere on the page.</p><h3>SEO and accessibility</h3><p>Button and link labels must be descriptive and visible. Ensure contrast and focus order; do not rely on color alone.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_consultation_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
