<?php
/**
 * Section helper documentation: mlp_detail_spec_01 (Detail spec block). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-mlp_detail_spec_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Specification table or list with label/value rows. Use for product or detail specs. Omit rows when empty.</p><h3>User need</h3><p>Editors need consistent spec display; empty rows are omitted.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Specifications").</li><li><strong>Spec rows</strong> (repeater, required): Each row — <strong>Label</strong> (required); <strong>Value</strong> (required). Keep labels consistent (e.g. "Material", "Dimensions"). Use same terminology as product/entity elsewhere.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders spec table or list; repeater maps to rows. Use GeneratePress for spacing. Empty rows not rendered.</p><h3>AIOSEO</h3><p>Spec labels and values support product/entity detail signals.</p><h3>Consistency</h3><p>Use parallel labels across pages (e.g. same "Weight" label format). Avoid mixing units or formats in same section.</p><h3>SEO and accessibility</h3><p>One section heading; use table or definition list semantics so label-value association is clear to screen readers.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'mlp_detail_spec_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
