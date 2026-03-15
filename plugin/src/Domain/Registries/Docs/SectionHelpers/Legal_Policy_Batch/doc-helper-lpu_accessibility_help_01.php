<?php
/**
 * Section helper documentation: lpu_accessibility_help_01 (Accessibility help section). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-lpu_accessibility_help_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Accessibility help or statement section with heading, body, and optional link. Supports top-level accessibility pages or embedded accessibility info.</p><h3>User need</h3><p>Editors need a block to explain accessibility commitment and how users can get help or request accommodations.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): e.g. "Accessibility", "Accessibility statement".</li><li><strong>Body</strong> (required): Explain your approach to accessibility, known limitations, and how to contact you for issues or requests. Plain language.</li><li><strong>Link</strong> (optional): To full accessibility page, contact form, or resource. Use descriptive text.</li></ul><h3>GeneratePress and accessibility</h3><p>This content is about accessibility—ensure it is itself accessible: clear structure, contrast, and logical order. Do not embed critical info in images only.</p><h3>Practical notes</h3><p>Content should reflect actual practices. Safe failure: omit link when empty. This is not legal advice; align with your compliance requirements.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'lpu_accessibility_help_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
