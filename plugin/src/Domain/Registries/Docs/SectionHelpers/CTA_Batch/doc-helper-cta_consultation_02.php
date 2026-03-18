<?php
/**
 * Section helper documentation: cta_consultation_02 (Consultation CTA strong). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_consultation_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Strong consultation CTA with emphasis. Supports primary and secondary actions and optional image and trust line. Use when you want higher visual weight.</p><h3>User need</h3><p>Editors need a prominent consultation block with two possible actions (e.g. Book now + Learn more).</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Compelling invite (e.g. "Ready to get started?").</li><li><strong>Body</strong>: Support (e.g. "Book your consultation today.").</li><li><strong>Primary button</strong>: Main action (e.g. "Book now").</li><li><strong>Secondary button</strong>: One alternative (e.g. "Learn more"); omit when empty.</li><li><strong>Trust line</strong>: Optional (e.g. "Trusted by 1,000+ clients").</li><li><strong>Image</strong>: Optional; use only if it adds value.</li></ul><h3>GeneratePress / ACF</h3><p>Map all CTA fields. Omit secondary or image when not used.</p><h3>CTA-specific guidance</h3><p>Primary and secondary must be distinct; avoid two similar labels. Use action language; state the benefit or outcome.</p><h3>Tone and mistakes to avoid</h3><p>Confident but not pushy. Do not use generic "Submit" or repeated CTAs from elsewhere on the page.</p><h3>SEO and accessibility</h3><p>Descriptive button labels; sufficient contrast; visible focus.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_consultation_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
