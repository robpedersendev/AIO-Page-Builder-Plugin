<?php
/**
 * Section helper documentation: lpu_form_intro_01 (Form intro). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-lpu_form_intro_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Intro or helper text above a form. Accessible helper text (spec §51.9); do not use as sole labeling for form controls. Use with form-embed sections.</p><h3>User need</h3><p>Editors need a block to introduce a form (e.g. what it is for, that required fields are marked) without replacing field labels.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (optional): e.g. "Submit your request".</li><li><strong>Body</strong> (optional): Short intro (e.g. "Complete the form below. Required fields are marked."). Supports context only; each form field must have its own visible label.</li></ul><h3>GeneratePress and accessibility</h3><p>Form fields must have visible, programmatically associated labels. Required-field indication must be present. Placeholder must not be the only label. Intro is supplementary.</p><h3>Practical notes</h3><p>Safe failure: omit body when empty. Do not put critical instructions only in intro—repeat at field level where needed. This is not legal advice; consent text belongs in consent note or policy.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'lpu_form_intro_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
