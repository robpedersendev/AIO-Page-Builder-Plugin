<?php
/**
 * Section helper documentation: cta_product_detail_01 (Product detail CTA). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_product_detail_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>CTA that drives users to product detail (e.g. "View product", "See specs"). Use on catalog or hub pages that link to product pages.</p><h3>User need</h3><p>Editors need a block that converts to product detail view or purchase path.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Product or benefit.</li><li><strong>Body</strong>: Optional support.</li><li><strong>Primary button</strong>: (e.g. "View product", "See details"). Describes the destination.</li></ul><h3>CTA-specific guidance</h3><p>Action language; user should understand they are going to product detail. Avoid generic "Learn more".</p><h3>Tone and mistakes to avoid</h3><p>Clear, product-focused. Do not repeat purchase or quote CTAs.</p><h3>SEO and accessibility</h3><p>Button label describes destination. Contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_product_detail_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
