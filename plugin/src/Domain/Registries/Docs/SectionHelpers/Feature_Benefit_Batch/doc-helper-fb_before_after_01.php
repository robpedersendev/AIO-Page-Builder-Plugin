<?php
/**
 * Section helper documentation: fb_before_after_01 (Before/after value framing). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-fb_before_after_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Before/after value framing with optional labels and repeatable points. Use for transformation or outcome messaging.</p><h3>User need</h3><p>Editors need to frame value as transformation without overclaiming or vague contrast.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional section headline.</li><li><strong>Before label</strong> / <strong>After label</strong>: Short column headers (e.g. "Before", "After"). Keep neutral and clear.</li><li><strong>Value points</strong> (repeater, required): Each row — <strong>Point text</strong>: one before/after pair or single outcome. Keep points parallel and truthful.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders in block structure; labels and repeater map to layout. Use GeneratePress for spacing.</p><h3>AIOSEO / FIFU</h3><p>Headline can support outcome/transformation intent.</p><h3>Tone and mistakes to avoid</h3><p>Use clear, honest tone. Avoid exaggerated before/after claims, vague "before" states, or manipulative framing.</p><h3>SEO and accessibility</h3><p>One section heading; ensure before/after structure is clear to screen readers (e.g. labelled regions or list semantics).</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'fb_before_after_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
