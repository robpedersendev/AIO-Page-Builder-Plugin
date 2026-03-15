<?php
/**
 * Section helper documentation: fb_differentiator_01 (Differentiator list). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_differentiator_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>List of differentiators with title and description. Use for "what sets us apart" on service or product pages.</p><h3>User need</h3><p>Editors need to articulate differentiators clearly without generic or overclaimed copy.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "What sets us apart"). Avoid cliché if possible.</li><li><strong>Differentiators</strong> (repeater, required): Each row — <strong>Title</strong> (required): short differentiator name; <strong>Description</strong>: brief explanation. Be specific and evidence-based; avoid vague "We care" style.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to list or grid. Use GeneratePress for spacing.</p><h3>AIOSEO / FIFU</h3><p>Headline can support differentiation intent. No image field here.</p><h3>Tone and mistakes to avoid</h3><p>Use confident, specific tone. Avoid generic differentiators, overclaiming, or repeating the same point across items.</p><h3>SEO and accessibility</h3><p>One section heading; semantic list for items. Ensure contrast and clear hierarchy.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_differentiator_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
