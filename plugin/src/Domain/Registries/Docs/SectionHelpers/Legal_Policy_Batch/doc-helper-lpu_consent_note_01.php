<?php
/**
 * Section helper documentation: lpu_consent_note_01 (Consent note). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_consent_note_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Consent or acknowledgment note with optional heading and checkbox label. Use with form-support sections; ensure visible labels and required-field indication (spec §51.9).</p><h3>User need</h3><p>Editors need a block to state consent or acknowledgment text, often above or beside a form or checkbox.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (optional): e.g. "Consent".</li><li><strong>Body</strong> (required): Consent or acknowledgment text. Plain language; state what the user is agreeing to.</li><li><strong>Checkbox label</strong> (optional): If a checkbox is used with the form, this can supply the label. Must be visible and associated with the control (not placeholder-only).</li></ul><h3>GeneratePress and accessibility</h3><p>Form controls must have visible, programmatically associated labels. Required fields must be indicated. Do not use placeholder as sole label. Consent text must be readable before submission.</p><h3>Practical notes</h3><p>This is not legal advice. Consent wording should be reviewed by legal. Safe failure: omit checkbox_label when empty; ensure body is never used as sole labeling for a critical control.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_consent_note_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
