<?php
/**
 * Section helper documentation: fb_benefit_detail_01 (Detailed benefit). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-fb_benefit_detail_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Detailed benefits with title, description, and optional image. Use for media-assisted benefit blocks.</p><h3>User need</h3><p>Editors need to present benefits with optional imagery without overloading a single section.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional section headline.</li><li><strong>Benefits</strong> (repeater, required): Each row — <strong>Title</strong> (required); <strong>Description</strong>: brief explanation; <strong>Optional image</strong>: ACF image or FIFU-compatible field. Use image only when it adds clarity; omit when empty. Provide meaningful alt text for accessibility.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to benefit blocks. Use GeneratePress for layout and spacing. Image fields support FIFU if configured.</p><h3>AIOSEO / FIFU</h3><p>If using FIFU for optional image, ensure alt text is set. Headline can support benefit intent.</p><h3>Tone and mistakes to avoid</h3><p>Use benefit-led, clear tone. Avoid decorative images without alt, vague benefit titles, or long paragraphs in description.</p><h3>SEO and accessibility</h3><p>One section heading; each benefit should have clear structure. Images must have descriptive alt; omit image node when empty for a11y.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'fb_benefit_detail_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
