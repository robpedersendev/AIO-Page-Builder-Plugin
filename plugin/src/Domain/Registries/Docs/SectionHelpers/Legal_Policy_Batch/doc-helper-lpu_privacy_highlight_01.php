<?php
/**
 * Section helper documentation: lpu_privacy_highlight_01 (Privacy highlight). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_privacy_highlight_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Short privacy highlight block for top-level or embedded use. Clearly replace sample text with your policy. Not a substitute for full privacy policy.</p><h3>User need</h3><p>Editors need a compact block to surface a brief privacy message (e.g. on a hub or beside a form) with link to full policy.</p><h3>Field-by-field guidance</h3><ul><li><strong>Title</strong> (required): e.g. "Your privacy".</li><li><strong>Text</strong> (required): Short summary of how you handle data and where to read more. Replace placeholder with your actual wording.</li></ul><h3>GeneratePress and accessibility</h3><p>Keep text concise. Ensure link to full policy is descriptive. Sufficient contrast. This is informational—clarity over conversion.</p><h3>Practical notes</h3><p>This is not legal advice. Have privacy wording reviewed. Safe failure: never publish with sample placeholder text. Align with full privacy policy.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_privacy_highlight_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
