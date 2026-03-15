<?php
/**
 * Page template one-pager documentation: hub_services_conversion_01 (Services hub conversion-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_conv_01' => 'Conversion hero. See doc-helper-hero_conv_01.', 'fb_value_prop_01' => 'Value prop. See doc-helper-fb_value_prop_01.', 'ptf_service_flow_01' => 'Service flow. See doc-helper-ptf_service_flow_01.', 'cta_consultation_01' => 'Consultation CTA. See doc-helper-cta_consultation_01.', 'fb_benefit_band_01' => 'Benefit band. See doc-helper-fb_benefit_band_01.', 'tp_testimonial_01' => 'Testimonial. See doc-helper-tp_testimonial_01.', 'mlp_card_grid_01' => 'Card grid. See doc-helper-mlp_card_grid_01.', 'cta_service_detail_01' => 'Service detail CTA. See doc-helper-cta_service_detail_01. Drill-down.', 'ptf_expectations_01' => 'Expectations. See doc-helper-ptf_expectations_01.', 'tp_guarantee_01' => 'Guarantee. See doc-helper-tp_guarantee_01.', 'fb_differentiator_01' => 'Differentiator. See doc-helper-fb_differentiator_01.', 'cta_quote_request_01' => 'Quote request CTA. See doc-helper-cta_quote_request_01.', 'lpu_contact_panel_01' => 'Contact panel. See doc-helper-lpu_contact_panel_01.', 'cta_booking_02' => 'Booking CTA. See doc-helper-cta_booking_02.',
);
$li = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Services hub conversion-led. Value prop and flow; consultation CTA; benefits and testimonial; cards and service CTA; expectations and guarantee; differentiator and quote CTA; contact panel; booking CTA.';
$flow    = 'Conversion posture with four CTAs; drill-down and booking intent.';
return array(
	'documentation_id'   => 'doc-onepager-hub_services_conversion_01',
	'documentation_type' => 'page_template_one_pager',
	'content_body'       => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>' . $flow . '</p><h3>CTA direction</h3><p>Hub aggregation with higher CTA intensity: consultation CTA → service detail CTA → quote request CTA → booking CTA. Emphasize section interplay and category-to-detail conversion.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper; hub meta. <strong>Hierarchy/navigation:</strong> Hub is parent to service detail pages. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; keep hub distinct from detail. <strong>Accessibility:</strong> One H1; logical heading order; contrast and focus order for CTAs.</p>',
	'status'             => 'active',
	'source_reference'   => array( 'page_template_key' => 'hub_services_conversion_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'     => '1',
	'export_metadata'   => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
