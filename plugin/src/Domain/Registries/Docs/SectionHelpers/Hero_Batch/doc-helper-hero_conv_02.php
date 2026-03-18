<?php
/**
 * Section helper documentation: hero_conv_02 (Hero conversion secondary). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-hero_conv_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Dual-CTA hero for service or hub pages where you want one primary action and one clear secondary (e.g. Get started + Contact us).</p><h3>User need</h3><p>Editors need to present two distinct choices without diluting the main offer.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Main value or welcome; keep concise.</li><li><strong>Subheadline</strong>: Brief support; can hint at both paths.</li><li><strong>Eyebrow</strong>: Optional (e.g. "Your choice").</li><li><strong>Primary CTA</strong>: Main conversion action (e.g. Get started).</li><li><strong>Secondary link</strong>: One alternative (e.g. Contact us). Use only when both actions are meaningful; avoid filler.</li></ul><h3>GeneratePress / ACF</h3><p>Map both CTA link fields in ACF. Use GeneratePress for layout and spacing of the two buttons.</p><h3>AIOSEO / FIFU</h3><p>Align headline with page focus when this is the opener. No image field; use hero_media_01 for visual hero.</p><h3>Tone and mistakes to avoid</h3><p>Clear, choice-oriented tone. Avoid two similar CTAs (e.g. "Learn more" and "Read more") or burying the primary action.</p><h3>SEO and accessibility</h3><p>One primary heading; both links must have descriptive visible text and contrast. Preserve logical focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'hero_conv_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
