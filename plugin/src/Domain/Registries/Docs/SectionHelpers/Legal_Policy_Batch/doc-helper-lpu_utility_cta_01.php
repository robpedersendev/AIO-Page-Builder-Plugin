<?php
/**
 * Section helper documentation: lpu_utility_cta_01 (Utility CTA). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_utility_cta_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Utility CTA band (e.g. contact, support, back to top). Use for non-primary conversion actions on policy or utility pages. CTA classification rules apply. Omit button when label/link empty.</p><h3>User need</h3><p>Editors need a simple CTA block for utility actions (contact, help, back to top) without marketing styling.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (optional): e.g. "Need help?".</li><li><strong>Text</strong> (optional): Short supporting copy.</li><li><strong>Button label</strong> (optional): e.g. "Contact", "Back to top". Omit when empty.</li><li><strong>Button link</strong> (optional): URL and options. Omit when empty so button is not shown.</li></ul><h3>GeneratePress and accessibility</h3><p>Button label must be descriptive. Ensure contrast and focus order. This is utility-focused—clear over promotional.</p><h3>Practical notes</h3><p>Safe failure: do not render button when label or link is empty. Keeps legal/policy pages distinct from conversion-heavy marketing.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_utility_cta_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
