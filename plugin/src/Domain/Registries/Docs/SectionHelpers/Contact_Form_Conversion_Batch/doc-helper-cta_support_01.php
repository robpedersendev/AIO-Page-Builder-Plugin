<?php
/**
 * Section helper documentation: cta_support_01 (Support CTA minimalist). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_support_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Minimalist support CTA. Single primary action to help or contact support.</p><h3>User need</h3><p>Editors need a minimal block that drives one support/help action.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Support invite (e.g. "Need help?", "Contact support").</li><li><strong>Body</strong>: Optional; omit or one short line.</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Action (e.g. "Get help", "Contact support"). Link to support page or form.</li></ul><h3>Conversion and page-fit</h3><p>Use where support is the natural next step (e.g. after FAQ, end of page). Ensure the link goes to a real support path. Do not use for main contact if the page has a dedicated contact CTA.</p><h3>Tone and mistakes to avoid</h3><p>Helpful, direct. Avoid "Submit" or vague "Click here".</p><h3>SEO and accessibility</h3><p>Button label describes action.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_support_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
