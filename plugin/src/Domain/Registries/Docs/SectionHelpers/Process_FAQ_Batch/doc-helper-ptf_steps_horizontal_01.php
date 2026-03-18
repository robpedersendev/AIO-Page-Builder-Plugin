<?php
/**
 * Section helper documentation: ptf_steps_horizontal_01 (Horizontal step flow). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-ptf_steps_horizontal_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Horizontal flow of steps with headline. Use for process or workflow with left-to-right reading order (e.g. service flow, buying process).</p><h3>User need</h3><p>Editors need a block where steps read horizontally, suited to wide layouts.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "Our process".</li><li><strong>Steps (repeater)</strong>: <strong>Title</strong> (required), <strong>Description</strong> (optional). Order reflects sequence.</li></ul><h3>Sequencing and clarity</h3><p>Keep step order logical. Titles should be scannable. Ensure responsive behavior so steps remain readable on small screens.</p><h3>Accessibility</h3><p>Use one heading per section. Step list must be in semantic list (ol/ul or role="list"). Do not rely on color or layout alone for sequence. Optional numbers omit-safe.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'ptf_steps_horizontal_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
