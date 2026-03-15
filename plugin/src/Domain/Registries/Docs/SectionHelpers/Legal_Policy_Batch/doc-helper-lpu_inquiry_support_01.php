<?php
/**
 * Section helper documentation: lpu_inquiry_support_01 (Inquiry support). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-lpu_inquiry_support_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Inquiry or support section with heading, intro, and form-embed slot. Form provider supplies actual form; ensure labels and required-field indication (spec §51.9).</p><h3>User need</h3><p>Editors need a block to introduce and embed a contact or inquiry form (shortcode or block identifier).</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (optional): e.g. "Send an inquiry".</li><li><strong>Intro</strong> (optional): Short text above the form. Explain what the form is for and that required fields must be completed.</li><li><strong>Form embed (shortcode or block identifier)</strong> (optional): Reference to the form. Actual form is provided by your form plugin; this section provides context.</li></ul><h3>GeneratePress and accessibility</h3><p>Form must have visible labels and required-field indication. Do not use placeholder as sole label. Intro supports context but is not a substitute for field labels.</p><h3>Practical notes</h3><p>Safe failure: omit form_embed_slot when empty. Ensure form provider outputs accessible markup. This is not legal advice; consent or data-use text belongs in consent/policy sections.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'lpu_inquiry_support_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
