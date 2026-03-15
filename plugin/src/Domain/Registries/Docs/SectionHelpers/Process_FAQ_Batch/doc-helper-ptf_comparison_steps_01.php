<?php
/**
 * Section helper documentation: ptf_comparison_steps_01 (Comparison by step). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-ptf_comparison_steps_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Step-by-step comparison (e.g. Option A vs B per step). Use for plan or option comparison on pricing or product pages.</p><h3>User need</h3><p>Editors need a block to compare two (or more) options across the same criteria or steps.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "Compare".</li><li><strong>Comparison steps (repeater)</strong>: <strong>Title</strong> (required)—criterion or step name; <strong>Option A</strong> (optional)—first option text; <strong>Option B</strong> (optional)—second option text. Order for clarity.</li></ul><h3>Sequencing and clarity</h3><p>Use consistent criteria order. Keep option text short and comparable. One heading per section. Table or list layout should be clear.</p><h3>Accessibility</h3><p>Use semantic structure (e.g. table with scope, or list with clear labels). Do not rely on color or position alone to convey which is "better". One heading per section.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'ptf_comparison_steps_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
