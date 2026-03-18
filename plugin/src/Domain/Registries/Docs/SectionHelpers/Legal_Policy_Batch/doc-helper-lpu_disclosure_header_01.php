<?php
/**
 * Section helper documentation: lpu_disclosure_header_01 (Disclosure header). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_disclosure_header_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Header band with title and short disclosure notice. For policy or consent context at the top of a page or block. Omit notice when empty.</p><h3>User need</h3><p>Editors need a clear way to surface an important disclosure before policy or consent content.</p><h3>Field-by-field guidance</h3><ul><li><strong>Title</strong> (required): Short disclosure title (e.g. "Important notice", "Disclosure").</li><li><strong>Notice</strong> (optional): Brief disclosure text. Replace placeholder with your actual policy language. Keep readable; use short sentences.</li></ul><h3>GeneratePress and formatting</h3><p>Use heading hierarchy (e.g. h2 for title). Ensure spacing and contrast for long-form readability. This is informational content—clarity over conversion styling.</p><h3>AIOSEO and accessibility</h3><p>Clear structure supports comprehension. Ensure sufficient contrast and logical heading order. Do not embed critical disclosure in images only.</p><h3>Practical notes</h3><p>This is not legal advice. Work with your legal or compliance team for actual disclosure wording. Safe failure: omit notice field when empty so the section does not show blank placeholder text.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_disclosure_header_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
