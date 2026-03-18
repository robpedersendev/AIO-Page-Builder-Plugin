<?php
/**
 * Section helper documentation: lpu_support_escalation_01 (Support escalation band). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_support_escalation_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Support or escalation band with title, description, and optional link. Use when users need a next step (e.g. "Need more help? Contact support."). Omit link when empty.</p><h3>User need</h3><p>Editors need a block to direct users to further support or escalation.</p><h3>Field-by-field guidance</h3><ul><li><strong>Title</strong> (required): e.g. "Need more help?".</li><li><strong>Description</strong> (optional): Short explanation and next step.</li><li><strong>Link</strong> (optional): To support page, contact form, or escalation. Use descriptive link text.</li></ul><h3>GeneratePress and accessibility</h3><p>Use clear heading and sufficient contrast. Link text must describe destination. Ensure focus order and keyboard access.</p><h3>Practical notes</h3><p>Safe failure: do not render link when empty. Keep support contact current. This is informational—no legal guarantees in this block.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_support_escalation_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
