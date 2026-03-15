<?php
/**
 * Section helper documentation: fb_value_prop_01 (Value proposition block). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_value_prop_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Value proposition with headline, main statement, and optional supporting points. Use for core value messaging.</p><h3>User need</h3><p>Editors need one clear value statement plus supporting points without repeating hero or CTA copy.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional section headline.</li><li><strong>Value statement</strong> (required): One clear sentence or short paragraph stating the core value. Keep specific and benefit-led.</li><li><strong>Supporting points</strong> (repeater): Each row — <strong>Point text</strong>. Short bullets that reinforce the statement; do not repeat the statement.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders in block structure. Use GeneratePress for container and spacing.</p><h3>AIOSEO / FIFU</h3><p>Value statement can align with focus keyphrase. No image field.</p><h3>Tone and mistakes to avoid</h3><p>Use direct, confident tone. Avoid vague value statements ("We deliver excellence"), overclaiming, or long paragraphs that belong elsewhere.</p><h3>SEO and accessibility</h3><p>One primary heading; value statement as paragraph; supporting points as semantic list. Ensure contrast and hierarchy.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_value_prop_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
