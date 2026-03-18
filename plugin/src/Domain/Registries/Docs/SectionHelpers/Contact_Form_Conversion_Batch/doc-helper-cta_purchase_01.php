<?php
/**
 * Section helper documentation: cta_purchase_01 (Purchase CTA subtle). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_purchase_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Subtle purchase or add-to-cart CTA. Clear primary action; omit secondary when empty.</p><h3>User need</h3><p>Editors need a purchase conversion block that drives one clear action.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Purchase invite (e.g. "Add to cart", "Get it now").</li><li><strong>Body</strong>: Optional (e.g. "Complete your order in a few steps.").</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Purchase action. Link to cart, checkout, or product.</li><li>Omit secondary, image, trust line when empty.</li></ul><h3>Conversion and page-fit</h3><p>Use where purchase is the natural next step. Ensure the link goes to a working purchase flow. Do not mix with contact/inquiry CTAs on the same section.</p><h3>Tone and mistakes to avoid</h3><p>Direct, action-led. Avoid "Submit" or "Click here"; use specific purchase language.</p><h3>SEO and accessibility</h3><p>Button label describes action; link target clear.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_purchase_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
