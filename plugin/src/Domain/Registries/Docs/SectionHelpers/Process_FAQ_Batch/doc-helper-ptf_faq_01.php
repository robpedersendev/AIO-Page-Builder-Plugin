<?php
/**
 * Section helper documentation: ptf_faq_01 (FAQ standard). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-ptf_faq_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Standard FAQ with headline and repeatable question/answer. Use for general or category FAQ on service or product pages.</p><h3>User need</h3><p>Editors need a block to list common questions and answers in a clear, scannable format.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "Frequently asked questions".</li><li><strong>FAQ items (repeater)</strong>: <strong>Question</strong> (required)—user-focused, concise; <strong>Answer</strong> (required)—direct, factual. Order by importance or topic.</li></ul><h3>Sequencing and clarity</h3><p>Order questions by user need or theme. One section heading (h2). Questions as headings or in list; answers clearly associated. Structured data may apply for FAQ.</p><h3>AIOSEO and accessibility</h3><p>FAQ content supports Q&amp;A signals. Use one heading per section (spec §51.6). FAQ items in list or definition list. If accordion is used elsewhere, ensure aria-expanded and keyboard; here standard list is fine. Do not rely on color alone (spec §51.3, §51.7).</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'ptf_faq_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
