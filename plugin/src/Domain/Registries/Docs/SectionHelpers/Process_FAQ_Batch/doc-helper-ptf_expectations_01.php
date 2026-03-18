<?php
/**
 * Section helper documentation: ptf_expectations_01 (Treatment / service expectations). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-ptf_expectations_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Expectation-setting list for treatment or service. Use for what to expect, duration, or outcomes on service, treatment, or local pages.</p><h3>User need</h3><p>Editors need a block to set expectations (e.g. duration, what happens, results) in a clear list.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "What to expect".</li><li><strong>Expectations (repeater)</strong>: <strong>Title</strong> (required)—e.g. "Duration"; <strong>Description</strong> (optional)—short detail. Order for clarity.</li></ul><h3>Sequencing and clarity</h3><p>Order by importance or by user journey. Be specific (e.g. "30–60 minutes" not "short"). One heading per section.</p><h3>Accessibility</h3><p>Use semantic list. One heading per section. Do not rely on color alone. Omit-safe optional description.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'ptf_expectations_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
