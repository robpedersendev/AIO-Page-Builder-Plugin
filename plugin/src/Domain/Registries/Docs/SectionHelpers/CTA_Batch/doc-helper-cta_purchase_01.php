<?php
/**
 * Section helper documentation: cta_purchase_01 (Purchase CTA subtle). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_purchase_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Subtle purchase or add-to-cart CTA. One clear primary action; omit secondary when empty. Use on product or offering pages.</p><h3>User need</h3><p>Editors need a conversion block that drives purchase without overwhelming the layout.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Add to cart").</li><li><strong>Body</strong>: Optional (e.g. "Complete your order in a few steps.").</li><li><strong>Primary button</strong>: Action (e.g. "Add to cart", "Buy now"). Must be specific.</li></ul><h3>CTA-specific guidance</h3><p>Use clear purchase language; avoid "Learn more" when the action is purchase. One primary action; no repeated generic buttons.</p><h3>Tone and mistakes to avoid</h3><p>Direct, conversion-focused. Do not dilute with vague copy or multiple competing CTAs.</p><h3>SEO and accessibility</h3><p>Button label describes action. Contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_purchase_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
