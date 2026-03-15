<?php
/**
 * Section helper documentation: fb_why_choose_01 (Why choose us). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_why_choose_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Why choose us block with headline and repeatable reasons (title, description). Use for service or provider differentiation.</p><h3>User need</h3><p>Editors need to state reasons to choose without generic or duplicate proof content.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Why choose us"). Can be varied for context.</li><li><strong>Reasons</strong> (repeater, required): Each row — <strong>Title</strong> (required): short reason label; <strong>Description</strong>: brief explanation. Keep reasons distinct; support with evidence where possible.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to list or grid. Use GeneratePress for layout.</p><h3>AIOSEO / FIFU</h3><p>Headline may support differentiation or trust intent. No image field.</p><h3>Tone and mistakes to avoid</h3><p>Use confident, specific tone. Avoid vague reasons ("We are the best"), repetition with testimonials or proof sections, or too many items that dilute impact.</p><h3>SEO and accessibility</h3><p>One section heading; semantic list for reasons. Ensure contrast and clear hierarchy.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_why_choose_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
