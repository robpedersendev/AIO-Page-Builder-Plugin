<?php
/**
 * Section helper documentation: hero_media_01 (Hero media-forward). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-hero_media_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Media-forward hero with headline, subheadline, and a prominent hero image slot. Use for visual landing pages or brand heroes where imagery leads.</p><h3>User need</h3><p>Editors need a hero that combines strong copy with a hero image (ACF image or FIFU-compatible).</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Main message; keep readable over the image (contrast).</li><li><strong>Subheadline</strong>: Supporting line.</li><li><strong>Eyebrow</strong>: Optional.</li><li><strong>Primary CTA</strong>: One main action.</li><li><strong>Hero image</strong>: Prominent image; use high-quality, relevant asset. Optimize for performance; alt text required for accessibility.</li></ul><h3>GeneratePress / ACF</h3><p>Map all fields including hero_image. ACF image field stores attachment ID; ensure image is sized appropriately for hero. GeneratePress can control overlay or layout.</p><h3>AIOSEO / FIFU</h3><p>If using FIFU for featured image, align with hero image or use hero for section-specific visual. Alt text supports SEO and a11y.</p><h3>Tone and mistakes to avoid</h3><p>Visual-first but copy must remain clear. Avoid low-contrast text over busy images; use overlay if needed.</p><h3>SEO and accessibility</h3><p>One primary heading; hero image must have descriptive alt text. CTA and text must meet contrast requirements.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'hero_media_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
