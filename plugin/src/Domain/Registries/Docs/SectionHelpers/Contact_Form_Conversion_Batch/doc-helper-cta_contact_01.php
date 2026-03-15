<?php
/**
 * Section helper documentation: cta_contact_01 (Contact CTA subtle). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch: conversion-focused guidance.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-cta_contact_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Subtle contact CTA. Clear primary button; omit secondary when empty. Use for contact conversion blocks without heavy emphasis.</p><h3>User need</h3><p>Editors need a simple contact conversion block that fits page flow and reduces friction.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Short contact invite (e.g. "Get in touch"). Avoid generic "Contact us" if the page already states it.</li><li><strong>Body</strong>: Optional supporting line. Use for response-time or next-step clarity; omit if redundant.</li><li><strong>Primary button label</strong> (required): Action-oriented (e.g. "Contact us", "Send a message"). Not "Submit" or "Click here".</li><li><strong>Primary button link</strong>: Target contact page or form anchor. Required for conversion.</li><li><strong>Secondary button</strong> / <strong>Image</strong> / <strong>Trust line</strong>: Omit when empty. Use trust line for response-time or reassurance only.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders in block structure. Map ACF fields to the CTA block; use GeneratePress for container and spacing.</p><h3>CTA clarity and friction</h3><p>One primary action only. Ensure the button leads to a clear next step (contact page or form). Avoid stacking multiple contact CTAs on the same page without distinct roles.</p><h3>Tone and mistakes to avoid</h3><p>Friendly, direct. Do not repeat the same CTA copy elsewhere; avoid vague "We are here to help" without a clear action.</p><h3>SEO and accessibility</h3><p>Button label must describe the action. Sufficient contrast and focus order; link target must be clear.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'cta_contact_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
