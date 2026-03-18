<?php
/**
 * Section helper documentation: cta_local_action_02 (Local action CTA strong). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_local_action_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Strong local action CTA with optional secondary and trust line. Use when local action is a primary conversion goal.</p><h3>User need</h3><p>Editors need a prominent local action block (e.g. "Get directions" + "Book a visit").</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Local value or invite.</li><li><strong>Body</strong>: Support.</li><li><strong>Primary button</strong>: Main local action.</li><li><strong>Secondary / Trust line</strong>: Omit when empty.</li></ul><h3>CTA-specific guidance</h3><p>Primary and secondary distinct; action language. Avoid vague or repeated CTAs.</p><h3>Tone and mistakes to avoid</h3><p>Clear, local-focused. Do not duplicate contact or booking CTAs.</p><h3>SEO and accessibility</h3><p>Descriptive labels; contrast; focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_local_action_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
