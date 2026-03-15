<?php
/**
 * Section helper documentation: fb_benefit_band_01 (Benefit band). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_benefit_band_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Band of benefit statements with optional headline. Use for value highlights on service or product pages.</p><h3>User need</h3><p>Editors need a compact way to list key benefits without long copy, suitable for bands or strips.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Why it matters"). Keep short and benefit-oriented.</li><li><strong>Benefits</strong> (repeater, required): Each row — <strong>Benefit text</strong> (required): one clear benefit per item. Keep each item one short sentence or phrase; avoid stacking multiple benefits in one row.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders in template block structure. Repeater maps to the band layout; use GeneratePress for container width and spacing.</p><h3>AIOSEO / FIFU</h3><p>Headline may support focus phrase. No image field in this section.</p><h3>Tone and mistakes to avoid</h3><p>Use direct, outcome-focused tone. Avoid vague benefits ("We are good"), repetition with the headline, or mixing benefits with features in the same list.</p><h3>SEO and accessibility</h3><p>One heading for section; list semantics for benefit items. Ensure contrast and scannability; avoid reliance on colour alone for meaning.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_benefit_band_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
