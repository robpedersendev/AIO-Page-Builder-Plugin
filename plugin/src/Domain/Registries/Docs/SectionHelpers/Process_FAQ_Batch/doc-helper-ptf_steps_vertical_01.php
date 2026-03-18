<?php
/**
 * Section helper documentation: ptf_steps_vertical_01 (Vertical step flow). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-ptf_steps_vertical_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Vertical flow of steps with headline. Use for onboarding or sequential process where top-to-bottom order is preferred.</p><h3>User need</h3><p>Editors need a block for steps that stack vertically (e.g. onboarding, guide).</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "Steps".</li><li><strong>Steps (repeater)</strong>: <strong>Title</strong> (required), <strong>Description</strong> (optional). Order is top to bottom.</li></ul><h3>Sequencing and clarity</h3><p>Order steps in user flow. Clear titles and optional descriptions reduce confusion. One heading per section.</p><h3>Accessibility</h3><p>Use semantic list (ol/ul). Do not rely on color alone. Step numbers if shown must be omit-safe. Landmark/ARIA per §51.7.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'ptf_steps_vertical_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
