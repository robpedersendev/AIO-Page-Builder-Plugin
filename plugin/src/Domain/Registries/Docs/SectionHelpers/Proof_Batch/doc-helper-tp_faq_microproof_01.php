<?php
/**
 * Section helper documentation: tp_faq_microproof_01 (FAQ microproof hybrid). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-tp_faq_microproof_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>FAQ items with optional proof stat per item. Use for trust-supporting FAQ that combines Q&amp;A with microproof (e.g. a stat next to an answer) on service or product pages.</p><h3>User need</h3><p>Editors need a FAQ block where each answer can optionally include a supporting statistic or proof point.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Common questions").</li><li><strong>FAQ items (repeater)</strong>: <strong>Question</strong> (required)—clear, user-focused; <strong>Answer</strong> (required)—concise, factual; <strong>Optional proof stat</strong>—short stat or proof (e.g. "99%") when it supports the answer. Omit when not needed.</li></ul><h3>Credibility and proof quality</h3><p>Answers and proof stats must be accurate. Use real numbers; avoid placeholder or synthetic stats in production.</p><h3>AIOSEO and accessibility</h3><p>FAQ structure supports Q&amp;A signals. Use one section heading; present items in a list or definition list. If accordion is used, expose expanded state and ensure keyboard access. Do not rely on color alone.</p><h3>Mistakes to avoid</h3><p>Do not add proof stats that are misleading or unverifiable. Do not duplicate the same stat across many items.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'tp_faq_microproof_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
