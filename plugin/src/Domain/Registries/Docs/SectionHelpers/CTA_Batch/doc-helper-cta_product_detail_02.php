<?php
/**
 * Section helper documentation: cta_product_detail_02 (Product detail CTA strong). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_product_detail_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Strong product detail CTA with optional secondary and trust line. Use when product detail conversion is a key goal.</p><h3>User need</h3><p>Editors need a prominent product detail block with supporting copy.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Product benefit or invite.</li><li><strong>Body</strong>: Support.</li><li><strong>Primary button</strong>: Product detail action.</li><li><strong>Secondary / Trust line</strong>: Omit when empty.</li></ul><h3>CTA-specific guidance</h3><p>Primary and secondary distinct; action language. Avoid weak or repeated offers.</p><h3>Tone and mistakes to avoid</h3><p>Clear, product-focused. Do not duplicate purchase or compare CTAs.</p><h3>SEO and accessibility</h3><p>Descriptive labels; contrast; focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_product_detail_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
