<?php
/**
 * Section helper documentation: cta_trust_confirm_02 (Trust confirm CTA variant). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-cta_trust_confirm_02',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Trust-confirm CTA variant with optional secondary and trust line. Use when you want emphasis and optional second action.</p><h3>User need</h3><p>Editors need a trust-closing block with room for body, secondary, and trust line.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Reassurance or next-step headline.</li><li><strong>Body</strong>: Optional; what happens next or guarantee.</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Main action.</li><li><strong>Secondary button</strong>: Only if distinct (e.g. "Contact support"). Omit when empty.</li><li><strong>Trust line</strong>: Optional; must be accurate.</li></ul><h3>Conversion and friction</h3><p>Primary action must be the main conversion. Avoid stacking multiple trust-confirm CTAs with the same message.</p><h3>Tone and mistakes to avoid</h3><p>Clear, reassuring. Do not use vague or duplicate reassurance.</p><h3>SEO and accessibility</h3><p>Button labels describe actions.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'cta_trust_confirm_02' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
