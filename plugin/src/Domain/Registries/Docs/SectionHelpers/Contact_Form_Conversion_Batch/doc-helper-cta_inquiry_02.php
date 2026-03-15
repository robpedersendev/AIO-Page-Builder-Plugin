<?php
/**
 * Section helper documentation: cta_inquiry_02 (Inquiry CTA standard). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-cta_inquiry_02',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Inquiry CTA with body and optional trust line. Use for request-info or lead-capture conversion with supporting copy.</p><h3>User need</h3><p>Editors need an inquiry block that sets expectation (e.g. response time) and one clear action.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Inquiry invite (e.g. "Request more information").</li><li><strong>Body</strong>: Use for response-time or what happens next (e.g. "We respond within 24 hours.").</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Main inquiry action.</li><li><strong>Trust line</strong>: Optional (e.g. "We reply within 24 hours"). Do not duplicate body.</li><li>Omit secondary, image when empty.</li></ul><h3>Form support copy</h3><p>Body and trust line reduce friction by setting expectations. Ensure the linked form or page matches the promise (e.g. response time).</p><h3>Tone and mistakes to avoid</h3><p>Clear, reassuring. Avoid overclaiming response time or stacking duplicate inquiry CTAs.</p><h3>SEO and accessibility</h3><p>Button and link text descriptive; contrast and focus order.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'cta_inquiry_02' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
