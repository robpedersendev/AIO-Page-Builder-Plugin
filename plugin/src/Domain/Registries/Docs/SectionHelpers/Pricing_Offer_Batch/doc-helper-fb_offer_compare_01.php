<?php
/**
 * Section helper documentation: fb_offer_compare_01 (Offer comparison). Spec §15; documentation-object-schema.
 * Pricing/Offer batch: clarity and comparison guidance.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_offer_compare_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Comparison of offers with name, features, and optional CTA per offer. Use for plan or option comparison. Best fit: pricing pages, plan comparison, tier pages.</p><h3>User need</h3><p>Editors need to present options clearly and fairly so visitors can compare without hidden ambiguity.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Compare options", "Choose your plan"). State the comparison clearly.</li><li><strong>Offers</strong> (repeater, required): Each offer — <strong>Offer name</strong> (required): plan or option name; <strong>Features</strong>: one per line or short list; <strong>CTA link</strong>: optional per offer. Keep feature lists parallel across offers; use neutral wording. Avoid biased labels or hidden conditions.</li></ul><h3>Clarity and transparency</h3><p>Use consistent structure (e.g. same feature categories in same order). State what is included; avoid "Contact for price" without clarity on what the quote covers. Do not hide material differences in small print.</p><h3>GeneratePress / ACF / AIOSEO</h3><p>Section uses block structure; repeater maps to comparison columns/cards. Headline can support pricing/plan intent for AIOSEO. No raw HTML in fields.</p><h3>Tone and mistakes to avoid</h3><p>Neutral, factual. Avoid stacking one option as "best" without justification; avoid vague feature labels or duplicate CTAs that compete.</p><h3>SEO and accessibility</h3><p>One section heading; comparison must be structured (e.g. headings per offer). CTA links need visible, descriptive text. Ensure contrast and scannability.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_offer_compare_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
