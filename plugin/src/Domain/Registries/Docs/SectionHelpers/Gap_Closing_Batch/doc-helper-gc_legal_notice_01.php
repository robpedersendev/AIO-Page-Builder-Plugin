<?php
/**
 * Section helper documentation: gc_legal_notice_01 (Legal notice block). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-gc_legal_notice_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Legal notice or disclaimer. Headline and body.</p><h3>Objection / gap-closing role</h3><p>Close disclosure gaps with clear, accurate language. Avoid vague disclaimers or hidden conditions.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Section headline. Be specific; align with page or AIOSEO focus where relevant.</li><li><strong>Body</strong>: Supporting content. Use for details, steps, or copy. No raw HTML. Omit when not needed.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; headline and body map to ACF fields. Use GeneratePress for container and spacing.</p><h3>Tone and mistakes to avoid</h3><p>Use clear, honest tone. Avoid weak claims, vague reassurance, duplicated objections elsewhere on the page, or manipulative language.</p><h3>SEO and accessibility</h3><p>One heading per section; body as paragraphs. Ensure contrast and logical order. Structure supports AIOSEO when headline is descriptive.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'gc_legal_notice_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
