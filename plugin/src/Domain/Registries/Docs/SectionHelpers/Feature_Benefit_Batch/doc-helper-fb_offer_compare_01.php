<?php
/**
 * Section helper documentation: fb_offer_compare_01 (Offer comparison). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_offer_compare_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Comparison of offers with name, features, and optional CTA per offer. Use for plan or option comparison.</p><h3>User need</h3><p>Editors need a fair, scannable comparison of options so visitors can choose without confusion.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Compare options"). State the comparison clearly.</li><li><strong>Offers</strong> (repeater, required): Each offer — <strong>Offer name</strong> (required): plan or option name; <strong>Features</strong>: one per line or short list; <strong>CTA link</strong>: optional link per offer. Keep feature lists parallel across offers; avoid biased wording.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to comparison columns/cards. Use GeneratePress for layout and spacing.</p><h3>AIOSEO / FIFU</h3><p>Headline can support comparison intent. No image field; CTAs should have descriptive link text for SEO and a11y.</p><h3>Tone and mistakes to avoid</h3><p>Use neutral, factual tone for comparison. Avoid stacking one option as "best" without justification, vague feature labels, or duplicate CTAs that compete.</p><h3>SEO and accessibility</h3><p>Use one section heading; comparison must be structured (e.g. headings per offer). CTA links need visible, descriptive text and sufficient contrast.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_offer_compare_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
