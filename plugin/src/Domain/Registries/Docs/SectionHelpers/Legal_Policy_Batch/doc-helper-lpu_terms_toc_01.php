<?php
/**
 * Section helper documentation: lpu_terms_toc_01 (Terms / policy TOC). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_terms_toc_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Table of contents for terms or policy page: heading and repeatable title/anchor_id. Use semantic list and skip links so users can jump to sections. Use with policy body sections.</p><h3>User need</h3><p>Editors need a block to list policy or terms sections with anchor links for long pages.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (optional): e.g. "Contents", "On this page".</li><li><strong>Table of contents items (repeater)</strong>: <strong>Title</strong> (required)—section title as shown; <strong>Anchor ID</strong> (optional)—ID that matches the target heading in policy body (e.g. "section-1"). Ensure IDs exist in page content.</li></ul><h3>GeneratePress and accessibility</h3><p>Render as a list of links. Use skip links or in-page anchors. Link text must describe target (section title). Ensure target headings use the same ID. Logical heading order on the page.</p><h3>Practical notes</h3><p>Safe failure: omit anchor_id when empty (link can still show; may not jump). Keep TOC in sync with actual section headings and IDs. This is structural—no legal advice in this block.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_terms_toc_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
