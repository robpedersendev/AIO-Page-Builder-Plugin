<?php
/**
 * Section helper documentation: ptf_faq_accordion_01 (FAQ accordion). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-ptf_faq_accordion_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>FAQ with optional accordion pattern. Use for interactive FAQ on dense pages. Ensure accessible accordion (aria-expanded, keyboard) and static fallback so content is available without JS.</p><h3>User need</h3><p>Editors need a FAQ block that can collapse/expand to save space while remaining accessible.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "FAQ".</li><li><strong>FAQ items (repeater)</strong>: <strong>Question</strong> (required), <strong>Answer</strong> (required). Same as standard FAQ; presentation may be accordion.</li></ul><h3>Sequencing and clarity</h3><p>Order questions logically. One heading (h2) for the section. Content must be available in static fallback (e.g. all expanded or in list) so no-JS users get full FAQ.</p><h3>Accessibility (accordion)</h3><p>If accordion: expose expanded state (aria-expanded), ensure keyboard operability (focus, enter/space to toggle), and provide static fallback (spec §51.3, §51.7). FAQ items in list or dl. Do not rely on color alone.</p><h3>AIOSEO</h3><p>FAQ structure supports Q&amp;A signals. Ensure full content is in markup for crawlers.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'ptf_faq_accordion_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
