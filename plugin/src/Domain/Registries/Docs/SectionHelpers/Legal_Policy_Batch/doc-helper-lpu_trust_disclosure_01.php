<?php
/**
 * Section helper documentation: lpu_trust_disclosure_01 (Trust disclosure band). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_trust_disclosure_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Trust or disclosure band with title and body. For embedded disclosure in hub or detail contexts (e.g. affiliate, compensation, data use).</p><h3>User need</h3><p>Editors need a compact block to place a single disclosure where contextually relevant.</p><h3>Field-by-field guidance</h3><ul><li><strong>Title</strong> (required): e.g. "Disclosure", "Important".</li><li><strong>Body</strong> (required): Disclosure text. Replace sample with your actual disclosure. Not legal advice.</li></ul><h3>GeneratePress and accessibility</h3><p>Keep text readable; use short sentences. Sufficient contrast. Do not hide in images. This is informational—clarity over styling.</p><h3>Practical notes</h3><p>Content should be reviewed by legal/compliance. Safe failure: ensure body is never empty for published disclosure. Do not use for full policy; use policy body or legal summary for that.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_trust_disclosure_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
