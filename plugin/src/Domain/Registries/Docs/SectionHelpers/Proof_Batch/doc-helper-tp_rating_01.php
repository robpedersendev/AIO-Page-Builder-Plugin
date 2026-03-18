<?php
/**
 * Section helper documentation: tp_rating_01 (Rating display). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-tp_rating_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Single rating value with optional label. Use for star rating or score display on product, service, or directory entries.</p><h3>User need</h3><p>Editors need a compact block to show one aggregate rating (e.g. 4.5 out of 5).</p><h3>Field-by-field guidance</h3><ul><li><strong>Rating value</strong>: Numeric (e.g. 4.5). Use scale consistent with your review source.</li><li><strong>Rating label</strong>: Optional text (e.g. "out of 5", "stars"). Clarifies scale for users and screen readers.</li></ul><h3>Credibility and proof quality</h3><p>Value must reflect real, current data. Do not inflate or fabricate ratings. If from a third party, consider noting the source.</p><h3>AIOSEO and accessibility</h3><p>Rating can support rich results when structured correctly. Expose rating and scale to screen readers (e.g. "4.5 out of 5"). Do not rely on visual stars or color alone.</p><h3>Mistakes to avoid</h3><p>Do not use fake or outdated ratings. Do not omit the scale when it is not obvious (e.g. "out of 5").</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'tp_rating_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
