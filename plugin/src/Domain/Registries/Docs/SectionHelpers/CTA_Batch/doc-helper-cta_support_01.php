<?php
/**
 * Section helper documentation: cta_support_01 (Support CTA). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-cta_support_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>CTA that drives users to support (e.g. "Get help", "Contact support", "View help center"). Use on product or service pages where support is a next step.</p><h3>User need</h3><p>Editors need a block that converts to support channel (help center, contact, FAQ).</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Need help?").</li><li><strong>Body</strong>: Optional (e.g. "We are here to help.").</li><li><strong>Primary button</strong>: (e.g. "Contact support", "View help"). Describes the action.</li></ul><h3>CTA-specific guidance</h3><p>Action language; user should understand they are going to support. Avoid generic "Learn more".</p><h3>Tone and mistakes to avoid</h3><p>Helpful, clear. Do not repeat contact or inquiry CTAs with the same intent.</p><h3>SEO and accessibility</h3><p>Button label describes action. Contrast and focus order.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'cta_support_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
