<?php
/**
 * Section helper documentation: hero_edit_01 (Hero editorial / resource intro). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-hero_edit_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Editorial or resource page opener for articles, blog posts, or resource pages. Headline and subheadline with optional "Read more" or navigation CTA.</p><h3>User need</h3><p>Editors need a content-led hero that introduces the article or resource without overselling.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Article or resource title; clear and scannable.</li><li><strong>Subheadline</strong>: Brief introduction or summary.</li><li><strong>Eyebrow</strong>: Optional (e.g. "Resource").</li><li><strong>Primary CTA</strong>: Optional (e.g. "Read more", "Continue"); use when you want to scroll or link to content.</li></ul><h3>GeneratePress / ACF</h3><p>Map headline and subheadline; CTA optional. Keep subheadline concise.</p><h3>AIOSEO / FIFU</h3><p>Align headline with article focus and SEO. For featured image use hero_media_01 or theme/FIFU; this section has no image field.</p><h3>Tone and mistakes to avoid</h3><p>Editorial, informative tone. Avoid marketing hype in the intro.</p><h3>SEO and accessibility</h3><p>One primary heading; descriptive CTA if used. Ensure contrast and focus order.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'hero_edit_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
