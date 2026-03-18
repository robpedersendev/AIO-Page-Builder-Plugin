<?php
/**
 * Section helper documentation: fb_product_spec_01 (Product spec / value hybrid). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-fb_product_spec_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Product specifications with optional value proposition copy. Use for product or offering detail pages.</p><h3>User need</h3><p>Editors need to combine factual specs with value messaging without burying key information.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Specifications").</li><li><strong>Specifications</strong> (repeater): Each row — <strong>Label</strong> and <strong>Value</strong> (both required). Keep labels consistent (e.g. "Weight", "Dimensions").</li><li><strong>Value proposition copy</strong>: Short paragraph linking specs to benefits. Do not duplicate the hero or CTA; keep focused.</li></ul><h3>GeneratePress / ACF</h3><p>Section renders in block structure; repeater maps to spec table or list. Use GeneratePress for spacing.</p><h3>AIOSEO / FIFU</h3><p>Specs and value copy can support product entity signals. Align with AIOSEO schema if product page.</p><h3>Tone and mistakes to avoid</h3><p>Use factual tone for specs, benefit-led for value copy. Avoid invented specs, vague value copy, or long paragraphs in value_copy.</p><h3>SEO and accessibility</h3><p>Use one section heading; spec rows should be in a table or definition list for semantics. Ensure labels are associated with values.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'fb_product_spec_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
