<?php
/**
 * Section helper documentation: fb_directory_value_01 (Directory value). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_directory_value_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Value proposition for directory or listing context: headline, intro, repeatable value items. Use for directory entry or category intro.</p><h3>User need</h3><p>Editors need to explain what the directory or category offers in a consistent structure.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "What you get").</li><li><strong>Intro</strong>: Short paragraph introducing the directory or category value. Keep concise.</li><li><strong>Value items</strong> (repeater, required): Each row — <strong>Label</strong> (required); <strong>Description</strong>: optional. Keep items consistent with directory structure.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to list. Use GeneratePress for container and spacing.</p><h3>AIOSEO / FIFU</h3><p>Headline and intro can support directory/category intent. No image field.</p><h3>Tone and mistakes to avoid</h3><p>Use clear, directory-appropriate tone. Avoid generic intro copy, duplicate items, or value items that do not match the directory content.</p><h3>SEO and accessibility</h3><p>One section heading; intro as paragraph; value items as semantic list. Ensure contrast and hierarchy.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_directory_value_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
