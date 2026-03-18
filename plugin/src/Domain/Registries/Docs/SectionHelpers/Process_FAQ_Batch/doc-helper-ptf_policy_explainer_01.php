<?php
/**
 * Section helper documentation: ptf_policy_explainer_01 (Policy / legal explainer). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-ptf_policy_explainer_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Policy or legal explanation with headline, body, and optional steps. Use for policy summary or legal explainer on legal/compliance pages. Static content only; no legal advice in preview.</p><h3>User need</h3><p>Editors need a block to explain policy or process in plain language with optional step breakdown.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): e.g. "Policy summary".</li><li><strong>Body copy</strong> (required): Main explanation. Replace sample with your content. Not legal advice.</li><li><strong>Optional steps (repeater)</strong>: <strong>Title</strong> (required), <strong>Description</strong> (optional). Use when a step-by-step breakdown helps. Omit when empty.</li></ul><h3>Sequencing and clarity</h3><p>Body first; steps if needed for clarity. One heading; logical order. Keep tone neutral and accurate.</p><h3>Accessibility</h3><p>Clear structure and contrast. Do not embed critical content in images. This is not legal advice—have content reviewed.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'ptf_policy_explainer_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
