<?php
/**
 * Section helper documentation: tp_review_01 (Review summary). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-tp_review_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Ratings and review count summary with optional headline and short summary. Use for product or service review proof.</p><h3>User need</h3><p>Editors need a block to surface aggregate rating and review count in a scannable way.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Customer reviews").</li><li><strong>Rating</strong>: Numeric value (e.g. 4.5). Use scale consistent with your review source (e.g. out of 5).</li><li><strong>Review count</strong>: Text (e.g. "100+ reviews", "Based on 50 reviews"). Be accurate; do not inflate.</li><li><strong>Short summary</strong>: Optional one-line summary. Keep factual.</li></ul><h3>Credibility and proof quality</h3><p>Rating and count must reflect real data. Misleading or fabricated review stats harm trust and can have compliance implications.</p><h3>AIOSEO and accessibility</h3><p>Review signals support rich results when structured correctly. Expose rating and count to screen readers (e.g. "4.5 out of 5, based on 100 reviews"). Do not rely on stars or color alone.</p><h3>Mistakes to avoid</h3><p>Do not use fake or exaggerated counts. Do not mix different rating scales without clarifying.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'tp_review_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
