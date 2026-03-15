<?php
/**
 * Section helper documentation: mlp_directory_entry_01 (Directory entry card). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-mlp_directory_entry_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Single directory entry with title, description, optional meta, image, and link. Use for directory detail or card. Omit meta/image/link when empty.</p><h3>User need</h3><p>Editors need one entry card with consistent fields; empty meta/image/link omitted.</p><h3>Field-by-field guidance</h3><ul><li><strong>Title</strong> (required): Entry name or title.</li><li><strong>Description</strong>: Short summary. Keep concise for card.</li><li><strong>Meta (e.g. category or type)</strong>: Optional. Use for category, type, or tag. Omit when not needed.</li><li><strong>Image</strong>: Optional, ACF or FIFU. Provide alt. Omit when empty.</li><li><strong>Link</strong>: Optional. Omit when no destination.</li></ul><h3>GeneratePress / ACF / FIFU</h3><p>Section renders single entry card. Image supports ACF/FIFU; alt required when present. Empty meta/image/link not rendered.</p><h3>AIOSEO</h3><p>Title and description support directory/entity signals. Link text descriptive.</p><h3>Consistency</h3><p>Use same meta and image pattern across directory entries where possible.</p><h3>SEO and accessibility</h3><p>Entry should have clear heading (title). Image requires alt when present; omit when empty. Links need visible text.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'mlp_directory_entry_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
