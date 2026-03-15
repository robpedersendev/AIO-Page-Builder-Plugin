<?php
/**
 * Section helper documentation: ptf_faq_by_category_01 (FAQ by category). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-ptf_faq_by_category_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>FAQ grouped by category with repeatable question/answer per group. Use for multi-topic FAQ on directory or hub pages.</p><h3>User need</h3><p>Editors need a block to organize many FAQs into categories (e.g. General, Billing, Support).</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "FAQ".</li><li><strong>FAQ categories (repeater)</strong>: <strong>Category name</strong> (required)—e.g. "General"; <strong>Items (repeater)</strong>: <strong>Question</strong> (required), <strong>Answer</strong> (required). Order categories and items for clarity.</li></ul><h3>Sequencing and clarity</h3><p>Order categories by user need. Within each category, order questions logically. One main section heading; category names as subheadings (e.g. h3).</p><h3>Accessibility</h3><p>Use semantic structure (headings, list or dl per category). Do not rely on color alone. Full content available to assistive tech.</p><h3>AIOSEO</h3><p>Structured FAQ by category supports findability. Clear headings and Q&amp;A structure help.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'ptf_faq_by_category_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
