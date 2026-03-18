<?php
/**
 * Section helper documentation: cta_quote_request_02 (Quote request CTA variant). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_quote_request_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Quote/estimate request CTA variant with optional secondary and trust line. Use when you want supporting copy and reassurance.</p><h3>User need</h3><p>Editors need a quote-request block with room for body and trust line.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Quote invite.</li><li><strong>Body</strong>: What happens next or scope (e.g. "We will contact you within 24 hours.").</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Quote request action.</li><li><strong>Trust line</strong>: Optional reassurance (e.g. "No obligation"). Do not overclaim.</li><li>Omit secondary, image when empty.</li></ul><h3>Conversion and friction</h3><p>Ensure the linked form or page matches the promise. One primary action; secondary only if distinct (e.g. "Call for quote").</p><h3>Tone and mistakes to avoid</h3><p>Clear, professional. Avoid vague "Get started" when the action is quote request.</p><h3>SEO and accessibility</h3><p>Button and link text descriptive.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_quote_request_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
