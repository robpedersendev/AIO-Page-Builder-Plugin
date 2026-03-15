<?php
/**
 * Section helper documentation: tp_trust_band_01 (Trust band). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-tp_trust_band_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Trust strip with headline and short trust points. Use for compact trust signals (e.g. Secure, Verified, Guaranteed) in footer, checkout, or landing contexts.</p><h3>User need</h3><p>Editors need a minimal block for a row of short trust points without long copy.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Why trust us").</li><li><strong>Trust points (repeater)</strong>: <strong>Text</strong> (required)—one short phrase per point (e.g. "Secure checkout", "Money-back guarantee"). Keep consistent in length.</li></ul><h3>Credibility and proof quality</h3><p>Use only points you can deliver. Avoid vague or unverifiable claims. Align with guarantee and reassurance sections where they appear on the same page.</p><h3>Accessibility</h3><p>Use semantic list. Do not rely on icons or color alone; text must convey meaning. Sufficient contrast.</p><h3>Mistakes to avoid</h3><p>Do not overclaim (e.g. "Award-winning" without basis). Do not duplicate the same wording from other trust sections without purpose.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'tp_trust_band_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
