<?php
/**
 * Section helper documentation: cta_compare_next_01 (Compare next CTA). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_compare_next_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>CTA that drives users to compare options or move to the next comparison step. Use on product or comparison flows.</p><h3>User need</h3><p>Editors need a block that encourages "Compare" or "See next option" action.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Compare options").</li><li><strong>Body</strong>: Optional support.</li><li><strong>Primary button</strong>: (e.g. "Compare now", "See comparison"). Describes the next step.</li></ul><h3>CTA-specific guidance</h3><p>Action language; user should understand they are comparing or moving to next step. Avoid vague "Learn more".</p><h3>Tone and mistakes to avoid</h3><p>Clear, forward-moving. Do not repeat the same CTA from product detail sections.</p><h3>SEO and accessibility</h3><p>Button label describes action. Contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_compare_next_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
