<?php
/**
 * Section helper documentation: mlp_product_cards_01 (Product / service cards). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-mlp_product_cards_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Product or service cards with name, description, optional image, price label, and link. Use for product grid or offering list. Omit image/price/link when empty.</p><h3>User need</h3><p>Editors need consistent product/offering cards; empty image, price, or link omitted.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Products").</li><li><strong>Products</strong> (repeater, required): Each card — <strong>Name</strong> (required); <strong>Description</strong>; <strong>Image</strong>: optional, ACF or FIFU with alt; <strong>Price or label</strong>: optional; <strong>Link</strong>: optional. Use image when it helps; provide alt. Omit price when not applicable (e.g. "Contact for price" as label is fine).</li></ul><h3>GeneratePress / ACF / FIFU</h3><p>Section renders product card grid; repeater maps to cards. Use GeneratePress for columns and spacing. Image/price/link optional; omit when empty.</p><h3>AIOSEO</h3><p>Product names and description support product signals. Link text descriptive. Price label can support schema when consistent.</p><h3>Consistency</h3><p>Keep card structure parallel; use same price format (e.g. with or without currency) across cards.</p><h3>SEO and accessibility</h3><p>One section heading; each card has clear structure. Images require alt when present; links need visible, descriptive text.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'mlp_product_cards_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
