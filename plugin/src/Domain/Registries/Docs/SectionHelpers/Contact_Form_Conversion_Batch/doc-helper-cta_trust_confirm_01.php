<?php
/**
 * Section helper documentation: cta_trust_confirm_01 (Trust confirm CTA). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_trust_confirm_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>CTA that confirms trust or reduces last-step friction (e.g. guarantee, reassurance). Use before or after a conversion step.</p><h3>User need</h3><p>Editors need a block that reinforces trust and one clear next action without overclaiming.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Trust or reassurance headline (e.g. "Ready to get started?", "We are here to help").</li><li><strong>Body</strong>: Optional; short reassurance or what to expect.</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: One clear action (contact, book, or next step).</li><li><strong>Trust line</strong>: Optional (e.g. guarantee, response time). Must be accurate; do not overclaim.</li></ul><h3>Conversion and friction</h3><p>Use to close a trust gap or confirm next step. Avoid vague reassurance; link to a real action. Do not duplicate the same reassurance elsewhere.</p><h3>Tone and mistakes to avoid</h3><p>Reassuring, specific. Avoid weak or generic "We care" without a clear action.</p><h3>SEO and accessibility</h3><p>Button label describes action; contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_trust_confirm_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
