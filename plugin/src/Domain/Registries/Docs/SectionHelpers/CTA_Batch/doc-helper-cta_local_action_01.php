<?php
/**
 * Section helper documentation: cta_local_action_01 (Local action CTA). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-cta_local_action_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>CTA for local or place-based action (e.g. "Find a location", "Visit us", "Get directions"). Use on location or service-area pages.</p><h3>User need</h3><p>Editors need a conversion block that drives a local action.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Visit us", "Find a location").</li><li><strong>Body</strong>: Optional (e.g. address or area).</li><li><strong>Primary button</strong>: Local action (e.g. "Get directions", "View map"). Describes the action.</li></ul><h3>CTA-specific guidance</h3><p>Use clear local action language. Avoid generic "Learn more"; state the local outcome.</p><h3>Tone and mistakes to avoid</h3><p>Friendly, location-focused. Do not use the same CTA as a contact or booking block.</p><h3>SEO and accessibility</h3><p>Button label describes local action. Contrast and focus order.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'cta_local_action_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
