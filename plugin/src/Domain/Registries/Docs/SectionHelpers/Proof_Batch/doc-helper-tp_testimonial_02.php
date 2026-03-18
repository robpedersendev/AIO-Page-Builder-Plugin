<?php
/**
 * Section helper documentation: tp_testimonial_02 (Single quote / quoted proof). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-tp_testimonial_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Single pull quote with optional attribution. Use for one strong testimonial or authority quote on editorial or highlight blocks.</p><h3>User need</h3><p>Editors need a focused block for one impactful quote without a card grid.</p><h3>Field-by-field guidance</h3><ul><li><strong>Quote</strong> (required): The exact quote text. Keep it concise and impactful.</li><li><strong>Attribution</strong> (optional): e.g. "Name, Title" or "Name — Company". Always include for credibility.</li></ul><h3>Credibility and proof quality</h3><p>Use real quotes with clear attribution. Specificity and source authority strengthen trust.</p><h3>AIOSEO and accessibility</h3><p>Associate quote with source programmatically (e.g. cite). Ensure contrast; avoid long quotes as images of text.</p><h3>Mistakes to avoid</h3><p>Do not leave attribution empty when the quote is from a real person. Do not alter quotes in a way that changes meaning.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'tp_testimonial_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
