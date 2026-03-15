<?php
/**
 * Section helper documentation: hero_dir_01 (Hero directory entry). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-hero_dir_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Directory or listing page opener. Headline and brief intro with a browse/search cue and primary CTA (e.g. Browse).</p><h3>User need</h3><p>Editors need a clear entry point for directory or catalog pages.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Directory purpose (e.g. "Browse our directory").</li><li><strong>Subheadline</strong>: What visitors can find (e.g. "Find what you need").</li><li><strong>Eyebrow</strong>: Optional (e.g. "Directory").</li><li><strong>Primary CTA</strong>: Browse, Search, or similar action into the directory.</li></ul><h3>GeneratePress / ACF</h3><p>Map headline, subheadline, and CTA. Keep copy short so the directory content leads.</p><h3>AIOSEO / FIFU</h3><p>Align with directory/catalog intent. No hero image in this section.</p><h3>Tone and mistakes to avoid</h3><p>Clear, navigational tone. Avoid long paragraphs; one CTA into the directory.</p><h3>SEO and accessibility</h3><p>One primary heading; CTA must describe action (e.g. "Browse directory").</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'hero_dir_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
