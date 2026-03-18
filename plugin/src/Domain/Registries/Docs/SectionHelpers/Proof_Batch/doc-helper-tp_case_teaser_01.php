<?php
/**
 * Section helper documentation: tp_case_teaser_01 (Case study teaser). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-tp_case_teaser_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Case study teaser with headline, outcome summary, client name, and optional link to full case study. Use for proof on service or offering pages.</p><h3>User need</h3><p>Editors need a block to highlight one case outcome and drive to the full story.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Teaser title (e.g. outcome or client focus).</li><li><strong>Outcome summary</strong>: Short description of result. Be specific and factual.</li><li><strong>Client name</strong>: Name or anonymized reference (e.g. "Enterprise client") with permission.</li><li><strong>Link to full case study</strong>: URL and label. Use descriptive link text (e.g. "Read full case study").</li></ul><h3>Credibility and proof quality</h3><p>Use real outcomes with client permission. Specific metrics or results build more trust than generic success claims.</p><h3>AIOSEO and accessibility</h3><p>Link text must describe destination. Ensure contrast and focus order for the CTA.</p><h3>Mistakes to avoid</h3><p>Do not fabricate outcomes or use client names without consent. Do not use "Click here" as link text.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'tp_case_teaser_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
