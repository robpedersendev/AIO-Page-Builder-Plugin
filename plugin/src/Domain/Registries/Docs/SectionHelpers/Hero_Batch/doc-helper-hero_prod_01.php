<?php
/**
 * Section helper documentation: hero_prod_01 (Hero product entry). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-hero_prod_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Product or offering detail page opener. Headline and subheadline with primary CTA (e.g. Learn more, Buy now) for product-focused landing.</p><h3>User need</h3><p>Editors need a product-led hero that highlights the offer and one clear next step.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Product name or key benefit (e.g. "Introducing our product").</li><li><strong>Subheadline</strong>: Key benefit or tagline.</li><li><strong>Eyebrow</strong>: Optional (e.g. "Product").</li><li><strong>Primary CTA</strong>: Product action (Learn more, Add to cart, Get offer).</li></ul><h3>GeneratePress / ACF</h3><p>Map all hero fields. Use block layout for product emphasis.</p><h3>AIOSEO / FIFU</h3><p>Align headline with product/keyword focus. For product image use hero_media_01 or hero_split_01; FIFU can drive featured image elsewhere.</p><h3>Tone and mistakes to avoid</h3><p>Direct, benefit-focused. Avoid vague "Learn more" when a specific action (e.g. "See pricing") is clearer.</p><h3>SEO and accessibility</h3><p>One primary heading; CTA with descriptive label and contrast.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'hero_prod_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
