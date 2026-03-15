<?php
/**
 * Section helper documentation: ptf_onboarding_01 (Onboarding steps). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-ptf_onboarding_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Onboarding or setup steps with headline. Use for getting-started or activation flow on product or service pages.</p><h3>User need</h3><p>Editors need a block to walk users through first steps (e.g. sign up, activate, configure).</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "Get started".</li><li><strong>Steps (repeater)</strong>: <strong>Title</strong> (required), <strong>Description</strong> (optional). Order matches user journey.</li></ul><h3>Sequencing and clarity</h3><p>Order steps in the sequence users should follow. Keep titles short and actionable. One heading per section.</p><h3>Accessibility</h3><p>Use semantic list (ol/ul). One heading per section (spec §51.6). Do not rely on color alone. Optional numbers omit-safe.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'ptf_onboarding_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
