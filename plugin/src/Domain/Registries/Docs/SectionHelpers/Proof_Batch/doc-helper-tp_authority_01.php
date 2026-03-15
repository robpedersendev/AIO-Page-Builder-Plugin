<?php
/**
 * Section helper documentation: tp_authority_01 (Authority highlights). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-tp_authority_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Authority or expertise highlights: title and optional quote/fact per item. Use for thought leadership or authority proof on about or service pages.</p><h3>User need</h3><p>Editors need a block to present expertise or authority points with optional supporting quotes or facts.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Why we are trusted").</li><li><strong>Highlights (repeater)</strong>: <strong>Title</strong> (required)—e.g. expertise area or claim; <strong>Quote or fact</strong> (optional)—supporting quote or verifiable fact.</li></ul><h3>Credibility and proof quality</h3><p>Use verifiable facts and real quotes. Avoid vague or unsubstantiated authority claims. Specificity builds trust.</p><h3>Accessibility</h3><p>Use semantic list or grid. Associate quotes with sources where present. Ensure contrast.</p><h3>Mistakes to avoid</h3><p>Do not overclaim expertise. Do not use unattributed or fabricated quotes.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'tp_authority_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
