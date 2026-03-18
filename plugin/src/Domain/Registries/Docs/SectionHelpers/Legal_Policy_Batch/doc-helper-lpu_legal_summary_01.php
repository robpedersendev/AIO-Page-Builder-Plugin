<?php
/**
 * Section helper documentation: lpu_legal_summary_01 (Legal summary block). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_legal_summary_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Short legal summary with title, summary text, and optional last-updated. Not a substitute for full policy; use to orient users before or alongside full text.</p><h3>User need</h3><p>Editors need a compact block for a brief summary (e.g. at top of policy page) with optional date.</p><h3>Field-by-field guidance</h3><ul><li><strong>Title</strong> (required): e.g. "Summary", "Overview".</li><li><strong>Summary</strong> (required): Short plain-language summary. State that full policy applies; do not replace full terms.</li><li><strong>Last updated</strong> (optional): Date or text (e.g. "January 2025"). Helps users know freshness.</li></ul><h3>GeneratePress and formatting</h3><p>Keep summary concise. Use short paragraphs. Clarity and readability over marketing tone.</p><h3>Accessibility</h3><p>Ensure contrast and logical order. Do not hide critical summary in images. This is not legal advice.</p><h3>Practical notes</h3><p>Summary must align with full policy. Have legal review. Omit last_updated when empty to avoid showing blank.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_legal_summary_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
