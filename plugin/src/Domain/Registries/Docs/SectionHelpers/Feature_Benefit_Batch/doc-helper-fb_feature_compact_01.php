<?php
/**
 * Section helper documentation: fb_feature_compact_01 (Compact feature list). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_feature_compact_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Compact list of short feature statements. Use for dense feature display or sidebar.</p><h3>User need</h3><p>Editors need a minimal, scannable feature list without long descriptions.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Features").</li><li><strong>Features</strong> (repeater, required): Each row — <strong>Feature text</strong> (required): one short phrase or sentence per item. Keep parallel and concise.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to compact list. Use GeneratePress for spacing; suitable for sidebar or narrow column.</p><h3>AIOSEO / FIFU</h3><p>Headline may support feature intent. No image field.</p><h3>Tone and mistakes to avoid</h3><p>Use direct, concise tone. Avoid long sentences in feature text, mixing features with benefits in the same list, or too many items that reduce scannability.</p><h3>SEO and accessibility</h3><p>One section heading; semantic list for features. Ensure contrast and list semantics.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_feature_compact_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
