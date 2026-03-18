<?php
/**
 * Section helper documentation: cta_trust_confirm_01 (Trust confirm CTA). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_trust_confirm_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>CTA that combines trust or proof messaging with one primary action. Use when you want to reassure before conversion (e.g. guarantee, secure checkout).</p><h3>User need</h3><p>Editors need a conversion block that includes a trust line to reduce friction.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Benefit or reassurance.</li><li><strong>Body</strong>: Optional support.</li><li><strong>Primary button</strong>: Clear action.</li><li><strong>Trust line</strong>: Specific reassurance (e.g. "Money-back guarantee"). Omit if empty.</li></ul><h3>CTA-specific guidance</h3><p>Trust line must be specific and credible. Button uses action language; avoid weak offers.</p><h3>Tone and mistakes to avoid</h3><p>Confident, reassuring. Do not overclaim or use generic trust copy.</p><h3>SEO and accessibility</h3><p>Descriptive labels; contrast; focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_trust_confirm_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
