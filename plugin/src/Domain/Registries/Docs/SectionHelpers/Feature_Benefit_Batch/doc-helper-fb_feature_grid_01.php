<?php
/**
 * Section helper documentation: fb_feature_grid_01 (Feature grid). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_feature_grid_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Grid of features with title, description, and optional icon. Use for product or service capability lists.</p><h3>User need</h3><p>Editors need a clear way to present multiple features in a scannable grid with consistent structure.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional section headline (e.g. "What we offer"). Keep benefit-focused; align with AIOSEO focus phrase if this section carries primary keyword.</li><li><strong>Features</strong> (repeater, required): Each row — <strong>Title</strong> (required): short feature name; <strong>Description</strong>: brief explanation; <strong>Icon reference</strong>: optional icon id or class. Keep titles parallel in structure; avoid long paragraphs in description.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders inside the template block structure. Map ACF repeater to the grid; use GeneratePress block settings for column count and spacing. No raw HTML in text fields.</p><h3>AIOSEO / FIFU</h3><p>Headline can support focus keyphrase. This section has no image field; use benefit_detail or media sections for imagery.</p><h3>Tone and mistakes to avoid</h3><p>Use clear, benefit-led tone. Avoid vague titles ("Feature 1"), duplicate meaning across items, or mixing features with CTAs in the same list.</p><h3>SEO and accessibility</h3><p>Use one heading level for the section headline. Grid/list must be semantic (list or grid with proper roles). Ensure sufficient contrast and visible labels; icon_ref must be omit-safe.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_feature_grid_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
