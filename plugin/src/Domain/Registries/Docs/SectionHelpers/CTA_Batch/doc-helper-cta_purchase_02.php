<?php
/**
 * Section helper documentation: cta_purchase_02 (Purchase CTA strong). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_purchase_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Strong purchase CTA with optional trust line. For product or checkout emphasis when you want higher visual weight and reassurance.</p><h3>User need</h3><p>Editors need a prominent purchase block (e.g. "Get it today") with trust copy (e.g. "Secure payment").</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Benefit or urgency (e.g. "Get it today").</li><li><strong>Body</strong>: (e.g. "Fast delivery. Secure checkout.").</li><li><strong>Primary button</strong>: (e.g. "Buy now").</li><li><strong>Trust line</strong>: (e.g. "Secure payment"). Omit if empty.</li></ul><h3>CTA-specific guidance</h3><p>Action language; state outcome or benefit. Trust line should be specific, not generic. Avoid weak offers.</p><h3>Tone and mistakes to avoid</h3><p>Confident, clear. Do not overclaim or repeat the same CTA from another section.</p><h3>SEO and accessibility</h3><p>Descriptive labels; contrast; focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_purchase_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
