<?php
/**
 * Section helper documentation: hero_conv_01 (Hero conversion primary). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-hero_conv_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>This hero section is the first thing visitors see on landing and sign-up flows. Use it to state the main value proposition and drive one primary action.</p><h3>User need</h3><p>Editors need a clear, conversion-focused opener that works with GeneratePress containers, ACF fields, and a single strong CTA.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): One clear benefit or offer. Keep it short; use AIOSEO focus phrase here if this is the main page headline.</li><li><strong>Subheadline</strong>: Supporting line that reinforces the headline. No repetition.</li><li><strong>Eyebrow</strong>: Optional category or kicker (e.g. "Limited time"). Leave empty if not needed.</li><li><strong>Primary CTA</strong>: One main action (e.g. Sign up, Get started). Link and label required; use descriptive button text for accessibility and SEO.</li><li><strong>Secondary link</strong>: Omit unless you have a real second action; avoid generic "Learn more" if the primary is already clear.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders inside the template’s block structure. Map ACF fields to the hero block; use GeneratePress block settings for container width and spacing. No raw HTML in ACF text fields.</p><h3>AIOSEO / FIFU</h3><p>If this hero carries the main page title, align headline with AIOSEO title tag. For FIFU: this section has no image field; use hero_media_01 or hero_split_01 if you need a hero image.</p><h3>Tone and mistakes to avoid</h3><p>Use direct, action-oriented tone. Avoid vague headlines ("Welcome"), multiple CTAs that compete, or long paragraphs in the hero. Do not stuff keywords; one primary CTA only.</p><h3>SEO and accessibility</h3><p>Use one primary heading (h1 or h2 per page context). CTA link must have visible, descriptive text and sufficient contrast. Ensure focus order and screen-reader clarity.</p>',
	'status'                    => 'active',
	'source_reference'          => array(
		'section_template_key' => 'hero_conv_01',
	),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
