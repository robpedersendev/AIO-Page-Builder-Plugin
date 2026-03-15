<?php
/**
 * Section helper documentation: ptf_steps_01 (Step list). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-ptf_steps_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Numbered or bullet step list with headline and repeatable title/description. Use for how-to or process explanation on service or product pages.</p><h3>User need</h3><p>Editors need a block to present a sequence of steps clearly and in order.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "How it works".</li><li><strong>Steps (repeater)</strong>: <strong>Title</strong> (required)—step name; <strong>Description</strong> (optional)—short explanation. Keep order logical.</li></ul><h3>Sequencing and clarity</h3><p>Order steps in the order the user should follow. Use clear, action-oriented titles. One primary heading (h2) per section; steps in ol/ul or role="list".</p><h3>GeneratePress and accessibility</h3><p>Use one heading per section (spec §51.6). Do not rely on color alone. Optional step numbers must be field-driven and omit-safe. Landmark and ARIA per §51.7.</p><h3>AIOSEO</h3><p>Process content supports how-to signals. Structure and clarity help both users and search.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'ptf_steps_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
