<?php
/**
 * Section helper documentation: tp_credential_02 (Credential strip). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-tp_credential_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Single-line strip of credential labels. Use for compact trust signals (badges, certifications in one row) in footer, header band, or dense layouts.</p><h3>User need</h3><p>Editors need a minimal block for many short credential labels without descriptions.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Trusted by").</li><li><strong>Credential items (repeater)</strong>: <strong>Label</strong> (required)—short text per credential (e.g. "ISO 9001", "BBB Accredited").</li></ul><h3>Credibility and proof quality</h3><p>Use only real, current credentials. Keep labels consistent in tone and length.</p><h3>Accessibility</h3><p>Present as a semantic list. Ensure sufficient contrast and avoid color-only differentiation.</p><h3>Mistakes to avoid</h3><p>Do not pack too many items; keep the strip readable. Do not use marketing claims as credential labels.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'tp_credential_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
