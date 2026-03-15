<?php
/**
 * Section helper documentation: ptf_how_it_works_01 (How it works). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-ptf_how_it_works_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>How-it-works explainer with headline and steps. Use for product, service, or resource explanation so users understand the process at a glance.</p><h3>User need</h3><p>Editors need a block to explain "how it works" in a few clear steps.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "How it works".</li><li><strong>Steps (repeater)</strong>: <strong>Title</strong> (required), <strong>Description</strong> (optional). Order matches the actual process.</li></ul><h3>Sequencing and clarity</h3><p>Order steps in the order the user experiences them. Action-oriented titles. One heading per section. Supports how-to and process signals.</p><h3>Accessibility</h3><p>Use semantic list (ol/ul). One heading per section (spec §51.6). Do not rely on color alone. Omit-safe optional description.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'ptf_how_it_works_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
