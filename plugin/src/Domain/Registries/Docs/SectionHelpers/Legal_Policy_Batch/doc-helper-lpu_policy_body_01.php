<?php
/**
 * Section helper documentation: lpu_policy_body_01 (Policy body). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_policy_body_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Policy or terms body with optional heading and WYSIWYG content. For privacy, terms, or accessibility policy pages. Long-form informational content.</p><h3>User need</h3><p>Editors need a block to place full policy or terms text with proper structure and formatting.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (optional): Section heading (e.g. "Privacy policy", "Section 1").</li><li><strong>Body</strong> (required): WYSIWYG content. Use headings, lists, and paragraphs for structure. Replace sample text with your actual policy content.</li></ul><h3>GeneratePress and formatting</h3><p>Use consistent heading levels (h2, h3) and spacing for scannability. Long-form policy benefits from clear paragraph breaks and optional table of contents (e.g. lpu_terms_toc_01). Avoid dense walls of text.</p><h3>AIOSEO and accessibility</h3><p>Structure supports readability and assistive tech. Ensure heading hierarchy is logical. Sufficient contrast and line length. This is not legal advice—content accuracy is your responsibility.</p><h3>Practical notes</h3><p>Do not use placeholder or sample policy text in production. Have content reviewed by legal or compliance. Safe failure: ensure body is never empty for published policy pages.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_policy_body_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
