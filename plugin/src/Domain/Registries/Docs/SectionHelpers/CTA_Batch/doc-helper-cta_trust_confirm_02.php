<?php
/**
 * Section helper documentation: cta_trust_confirm_02 (Trust confirm CTA variant). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_trust_confirm_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Trust-supported CTA variant with optional secondary and trust line. Use when you need stronger emphasis and two possible actions.</p><h3>User need</h3><p>Editors need a trust-backed CTA block with primary and optional secondary action.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Reassurance or benefit.</li><li><strong>Body</strong>: Support.</li><li><strong>Primary button</strong>: Main action.</li><li><strong>Secondary button</strong>: Omit when empty.</li><li><strong>Trust line</strong>: Specific; omit when empty.</li></ul><h3>CTA-specific guidance</h3><p>Primary and secondary distinct; action language. Trust line credible and specific.</p><h3>Tone and mistakes to avoid</h3><p>Confident; avoid generic or repeated CTAs.</p><h3>SEO and accessibility</h3><p>Descriptive labels; contrast; focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_trust_confirm_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
