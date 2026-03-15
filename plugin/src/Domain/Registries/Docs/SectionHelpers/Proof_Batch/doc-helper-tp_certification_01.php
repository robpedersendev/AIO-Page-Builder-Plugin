<?php
/**
 * Section helper documentation: tp_certification_01 (Certification list). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-tp_certification_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>List of certification names with optional URLs. Use for compliance or accreditation lists on legal/trust or about pages.</p><h3>User need</h3><p>Editors need a text-focused list of certifications, optionally linking to verification.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Certifications").</li><li><strong>Certifications (repeater)</strong>: <strong>Name</strong> (required)—certification name; <strong>Optional URL</strong>—link to official or verification page. Use descriptive link text when rendered.</li></ul><h3>Credibility and proof quality</h3><p>List only current, valid certifications. URLs should point to authoritative or verification sources where possible.</p><h3>Accessibility</h3><p>Use a semantic list. Links must have descriptive text; avoid "link" or raw URL as sole label.</p><h3>Mistakes to avoid</h3><p>Do not list certifications you do not hold. Do not use misleading verification URLs.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'tp_certification_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
