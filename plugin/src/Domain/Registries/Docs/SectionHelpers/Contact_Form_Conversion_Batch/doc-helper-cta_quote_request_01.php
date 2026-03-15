<?php
/**
 * Section helper documentation: cta_quote_request_01 (Quote request CTA). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-cta_quote_request_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>CTA for quote or estimate request. Use where the next step is "get a quote" or "request estimate".</p><h3>User need</h3><p>Editors need a conversion block that drives quote/estimate requests with clear expectations.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Quote invite (e.g. "Get a free quote", "Request an estimate").</li><li><strong>Body</strong>: Optional; use for scope or what to expect (e.g. "No obligation").</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Action (e.g. "Request quote"). Link to quote form or contact.</li><li>Omit secondary, image, trust line when empty.</li></ul><h3>Form support and friction</h3><p>If the CTA leads to a form, ensure the form is quote/estimate-focused and has clear required fields. Set expectation in body or trust line (e.g. "We respond within 24 hours").</p><h3>Tone and mistakes to avoid</h3><p>Clear, low-pressure. Avoid "Free quote" if there are conditions; avoid stacking multiple quote CTAs.</p><h3>SEO and accessibility</h3><p>Button label describes action; contrast and focus order.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'cta_quote_request_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
