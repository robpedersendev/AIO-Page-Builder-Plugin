<?php
/**
 * Section helper documentation: ptf_timeline_01 (Timeline). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-ptf_timeline_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Timeline with optional date/label per item. Use for milestones, history, or schedule (project, company, delivery).</p><h3>User need</h3><p>Editors need a block to show events or steps in time order with optional dates or phase labels.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "Timeline".</li><li><strong>Timeline items (repeater)</strong>: <strong>Date or label</strong> (optional)—e.g. "Phase 1", "Jan 2025"; <strong>Title</strong> (required); <strong>Description</strong> (optional). Order chronologically or by phase.</li></ul><h3>Sequencing and clarity</h3><p>Order items in time or logical sequence. Use one heading per section. Dates/labels must be omit-safe when empty.</p><h3>Accessibility</h3><p>Timeline items in semantic list (ol/ul). One heading per section (spec §51.6). Landmark and ARIA only where needed. Optional date/label omit-safe.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'ptf_timeline_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
