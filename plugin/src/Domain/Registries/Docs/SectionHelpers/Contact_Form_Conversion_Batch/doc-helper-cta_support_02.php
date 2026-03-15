<?php
/**
 * Section helper documentation: cta_support_02 (Support CTA standard). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-cta_support_02',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Support CTA with body and optional trust line. Use for help/support conversion with supporting copy.</p><h3>User need</h3><p>Editors need a support block that sets expectation (e.g. response time) and one clear action.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Support invite.</li><li><strong>Body</strong>: Optional; what to expect (e.g. "We respond within 24 hours.").</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Support action.</li><li><strong>Trust line</strong>: Optional; must be accurate. Omit secondary, image when empty.</li></ul><h3>Conversion and friction</h3><p>Link must go to a real support path (form, page, or contact). Set expectation in body to reduce friction.</p><h3>Tone and mistakes to avoid</h3><p>Helpful, clear. Do not overclaim response time or availability.</p><h3>SEO and accessibility</h3><p>Button and link text descriptive.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'cta_support_02' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
