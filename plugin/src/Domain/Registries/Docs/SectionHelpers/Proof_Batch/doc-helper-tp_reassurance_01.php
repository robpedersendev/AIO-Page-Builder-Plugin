<?php
/**
 * Section helper documentation: tp_reassurance_01 (Reassurance block). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-tp_reassurance_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Reassurance block with headline and repeatable short points. Use for trust or risk-reduction messaging on product, service, or checkout-support contexts.</p><h3>User need</h3><p>Editors need a simple block to list reassurance points (e.g. secure, guaranteed, support) in a scannable format.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Why choose us").</li><li><strong>Reassurance points (repeater)</strong>: <strong>Text</strong> (required)—one short point per row. Keep consistent in length and tone.</li></ul><h3>Credibility and proof quality</h3><p>State only points you can back up (e.g. real support channels, actual guarantees). Avoid vague or unverifiable reassurance.</p><h3>Accessibility</h3><p>Use a semantic list. Do not rely on icons or color alone; text must stand alone. Sufficient contrast.</p><h3>Mistakes to avoid</h3><p>Do not overpromise (e.g. "24/7 support" when not true). Do not duplicate the same points from guarantee or trust band sections without added value.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'tp_reassurance_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
