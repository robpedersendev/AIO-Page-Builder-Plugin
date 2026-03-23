<?php
/**
 * Page template one-pager documentation: nested_hub_services_specialized_01 (Service subcategory specialized offering). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$aio_pb_sections = array(
	'hero_cred_01'           => 'Credibility hero. See doc-helper-hero_cred_01.',
	'fb_service_offering_01' => 'Service offering. See doc-helper-fb_service_offering_01.',
	'tp_trust_band_01'       => 'Trust band. See doc-helper-tp_trust_band_01.',
	'cta_service_detail_02'  => 'Service detail CTA. See doc-helper-cta_service_detail_02.',
	'ptf_service_flow_01'    => 'Service flow. See doc-helper-ptf_service_flow_01.',
	'fb_why_choose_01'       => 'Why choose. See doc-helper-fb_why_choose_01.',
	'mlp_card_grid_01'       => 'Card grid. See doc-helper-mlp_card_grid_01.',
	'cta_consultation_01'    => 'Consultation CTA. See doc-helper-cta_consultation_01.',
	'tp_testimonial_02'      => 'Testimonial. See doc-helper-tp_testimonial_02.',
	'ptf_expectations_01'    => 'Expectations. See doc-helper-ptf_expectations_01.',
	'cta_booking_01'         => 'Booking CTA. See doc-helper-cta_booking_01.',
	'lpu_contact_panel_01'   => 'Contact panel. See doc-helper-lpu_contact_panel_01.',
	'mlp_listing_01'         => 'Listing. See doc-helper-mlp_listing_01.',
	'cta_contact_01'         => 'Contact CTA. See doc-helper-cta_contact_01.',
);
$aio_pb_li       = '';
foreach ( $aio_pb_sections as $aio_pb_k => $aio_pb_v ) {
	$aio_pb_li .= '<li><strong>' . $aio_pb_k . '</strong>: ' . $aio_pb_v . '</li>';
}
$aio_pb_purpose = 'Service subcategory specialized offering. Offering and trust; service CTA; flow and why choose; cards and consultation CTA; testimonial and expectations; booking CTA; contact panel; listing; contact CTA.';
$aio_pb_flow    = 'Specialized offering subcategory; drill-down to detail without becoming singular detail page. Specialized offering (e.g. Gel Manicures under Manicures); continued drill-down capacity.';
return array(
	'documentation_id'          => 'doc-onepager-nested_hub_services_specialized_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>' . $aio_pb_purpose . '</p><h3>Page flow</h3><p>' . $aio_pb_flow . '</p><h3>CTA direction</h3><p>Nested hub: service detail CTA → consultation CTA → booking CTA → contact CTA. Page narrows from parent hub to child detail; specialized offering subcategory.</p><h3>Section-by-section (ordered)</h3><ol>' . $aio_pb_li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper. <strong>Hierarchy:</strong> Nested hub under Services hub; parent to service detail pages. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs. <strong>Accessibility:</strong> One H1; logical heading order; contrast and focus order for CTAs.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'nested_hub_services_specialized_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
