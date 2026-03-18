<?php
/**
 * Page template one-pager documentation: hub_services_proof_01 (Services hub proof-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_cred_01'           => 'Credibility hero. See doc-helper-hero_cred_01.',
	'tp_trust_band_01'       => 'Trust band. See doc-helper-tp_trust_band_01.',
	'tp_testimonial_01'      => 'Testimonial. See doc-helper-tp_testimonial_01.',
	'cta_service_detail_01'  => 'Service detail CTA. See doc-helper-cta_service_detail_01. Drill-down.',
	'fb_service_offering_01' => 'Service offering. See doc-helper-fb_service_offering_01.',
	'tp_client_logo_01'      => 'Client logos. See doc-helper-tp_client_logo_01.',
	'ptf_service_flow_01'    => 'Service flow. See doc-helper-ptf_service_flow_01.',
	'cta_consultation_01'    => 'Consultation CTA. See doc-helper-cta_consultation_01.',
	'fb_why_choose_01'       => 'Why choose. See doc-helper-fb_why_choose_01.',
	'tp_case_teaser_01'      => 'Case teaser. See doc-helper-tp_case_teaser_01.',
	'mlp_card_grid_01'       => 'Card grid. See doc-helper-mlp_card_grid_01. Scannability.',
	'cta_booking_01'         => 'Booking CTA. See doc-helper-cta_booking_01.',
	'lpu_contact_panel_01'   => 'Contact panel. See doc-helper-lpu_contact_panel_01.',
	'cta_contact_01'         => 'Contact CTA. See doc-helper-cta_contact_01.',
);
$li       = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Services hub with strong proof. Trust and testimonial before first service CTA; offering and logos; flow and consultation CTA; why choose and case; cards and booking CTA; contact panel; contact CTA.';
$flow    = 'Category-wide value and proof; drill-down CTAs to service detail pages. Semantic headings per section.';
return array(
	'documentation_id'          => 'doc-onepager-hub_services_proof_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>' . $flow . '</p><h3>CTA direction</h3><p>Hub aggregation: service detail CTA → consultation CTA → booking CTA → contact CTA. Emphasize scannability and section interplay; drill-down to individual service pages.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper; hub meta; images where applicable. <strong>Hierarchy/navigation:</strong> Hub is parent to service detail pages; ensure nav reflects services hierarchy. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; keep hub distinct from detail pages. <strong>Accessibility:</strong> One H1; logical heading order; sufficient contrast and focus order for all CTAs.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'hub_services_proof_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
