<?php
/**
 * Section helper documentation: hero_cred_01 (Hero credibility-first). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-hero_cred_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Trust-led hero for about, credibility, or trust-heavy landing pages. Headline and subheadline focus on proof and credibility; no strong CTA required.</p><h3>User need</h3><p>Editors need an opener that establishes trust before asking for action.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Credibility statement (e.g. "Trusted by teams everywhere").</li><li><strong>Subheadline</strong>: Supporting proof or context.</li><li><strong>Eyebrow</strong>: Optional (e.g. "Why choose us").</li><li><strong>Primary CTA / Secondary link</strong>: Often left empty for credibility-first hero; add one CTA only if it fits the page goal.</li></ul><h3>GeneratePress / ACF</h3><p>ACF fields map to headline and subheadline; CTA fields optional. Use block layout for emphasis and readability.</p><h3>AIOSEO / FIFU</h3><p>Headline can support brand or trust keywords. No image in this section.</p><h3>Tone and mistakes to avoid</h3><p>Confident, evidence-based tone. Avoid overclaiming or stacking multiple CTAs when the goal is trust-first.</p><h3>SEO and accessibility</h3><p>One primary heading; sufficient contrast for text. If CTAs are present, use descriptive link text.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'hero_cred_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
