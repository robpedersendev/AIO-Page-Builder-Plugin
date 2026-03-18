<?php
/**
 * Section helper documentation: ptf_buying_process_01 (Buying process roadmap). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-ptf_buying_process_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Buying or decision process as step roadmap. Use for product or service purchase flow so users understand the path to purchase.</p><h3>User need</h3><p>Editors need a block to explain "how to get started" or the buying journey in steps.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "How to get started".</li><li><strong>Steps (repeater)</strong>: <strong>Title</strong> (required)—e.g. "Choose", "Order"; <strong>Description</strong> (optional)—short support. Order reflects the real flow.</li></ul><h3>Sequencing and clarity</h3><p>Match steps to actual process. Clear, action-oriented titles. One section heading; list in order.</p><h3>Accessibility</h3><p>Semantic list; one heading per section. Do not rely on color alone. Omit-safe optional elements.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'ptf_buying_process_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
