<?php
/**
 * Section helper documentation: ptf_timeline_compact_01 (Timeline compact). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-ptf_timeline_compact_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Compact timeline with label and short description per item. Use for dense timeline display (schedule, milestones) where space is limited.</p><h3>User need</h3><p>Editors need a block for many timeline points with minimal copy per item.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "Key dates".</li><li><strong>Timeline items (repeater)</strong>: <strong>Label</strong> (required)—e.g. "Phase 1", date; <strong>Description</strong> (optional)—short. Order chronologically or by phase.</li></ul><h3>Sequencing and clarity</h3><p>Keep order logical. Short labels and descriptions. One heading per section. Omit description when empty.</p><h3>Accessibility</h3><p>Semantic list; one heading per section. Optional description omit-safe. Do not rely on color alone.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'ptf_timeline_compact_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
