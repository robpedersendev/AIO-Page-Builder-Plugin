<?php
/**
 * Section helper documentation: tp_credential_01 (Credential grid). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-tp_credential_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Grid of credentials: title, description, optional icon. Use for certifications, accreditations, or capability proof.</p><h3>User need</h3><p>Editors need a block to present credentials in a structured, scannable format.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Our credentials").</li><li><strong>Credentials (repeater)</strong>: <strong>Title</strong> (required)—e.g. certification name; <strong>Description</strong> (optional)—short explanation; <strong>Icon reference</strong> (optional)—reference to icon if used.</li></ul><h3>Credibility and proof quality</h3><p>List only real, current credentials. Avoid vague or unverifiable claims. Link to verification pages where appropriate.</p><h3>AIOSEO and accessibility</h3><p>Use semantic list or grid. Do not rely on icons or color alone for meaning. Ensure contrast for text.</p><h3>Mistakes to avoid</h3><p>Do not list expired or irrelevant credentials. Do not overclaim or imply endorsements without permission.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'tp_credential_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
