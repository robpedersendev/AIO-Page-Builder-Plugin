<?php
/**
 * Section helper documentation: hero_split_01 (Hero split layout). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-hero_split_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Split-layout hero: text on one side, image on the other. Use for balanced openers where both copy and visual matter. Variants: Default (text left, image right), Image left.</p><h3>User need</h3><p>Editors need a hero that balances headline/CTA with a supporting image in a split block.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Main message.</li><li><strong>Subheadline</strong>: Supporting copy on the text side.</li><li><strong>Eyebrow</strong>: Optional.</li><li><strong>Primary CTA</strong>: One clear action.</li><li><strong>Split image</strong>: Image for the other side; use relevant, high-quality asset with alt text.</li></ul><h3>GeneratePress / ACF</h3><p>Map all fields including split_image. Choose variant in section settings (Default or Image left). GeneratePress grid/container controls the split ratio.</p><h3>AIOSEO / FIFU</h3><p>Split image should have descriptive alt text. Align headline with page focus.</p><h3>Tone and mistakes to avoid</h3><p>Balanced tone; copy and image should support the same message. Avoid generic stock imagery that doesn’t match the headline.</p><h3>SEO and accessibility</h3><p>One primary heading; split image must have alt text. Ensure text contrast and focus order on both sides.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'hero_split_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
