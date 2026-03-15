<?php
/**
 * Section helper documentation: ptf_service_flow_01 (Service flow). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-ptf_service_flow_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Service delivery or workflow steps. Use for service page or delivery explanation so users understand what happens and in what order.</p><h3>User need</h3><p>Editors need a block to describe the service flow (e.g. consultation, delivery, follow-up).</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (optional): e.g. "Our service flow".</li><li><strong>Steps (repeater)</strong>: <strong>Title</strong> (required), <strong>Description</strong> (optional). Order reflects actual delivery sequence.</li></ul><h3>Sequencing and clarity</h3><p>Match steps to real process. Clear titles reduce anxiety and set expectations. One heading per section.</p><h3>Accessibility</h3><p>Semantic list; one heading per section. Do not rely on color or layout alone for order. Omit-safe optional fields.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'ptf_service_flow_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
