<?php
/**
 * Page template one-pager documentation: child_detail_service_educational_01 (Service detail educational). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_cred_01' => 'Credibility hero. See doc-helper-hero_cred_01.', 'fb_service_offering_01' => 'Service offering. See doc-helper-fb_service_offering_01.', 'ptf_how_it_works_01' => 'How it works. See doc-helper-ptf_how_it_works_01.', 'cta_service_detail_02' => 'Service detail CTA. See doc-helper-cta_service_detail_02.', 'ptf_expectations_01' => 'Expectations. See doc-helper-ptf_expectations_01.', 'tp_testimonial_01' => 'Testimonial. See doc-helper-tp_testimonial_01.', 'fb_why_choose_01' => 'Why choose. See doc-helper-fb_why_choose_01.', 'ptf_service_flow_01' => 'Service flow. See doc-helper-ptf_service_flow_01.', 'cta_consultation_01' => 'Consultation CTA. See doc-helper-cta_consultation_01.', 'tp_trust_band_01' => 'Trust band. See doc-helper-tp_trust_band_01.', 'tp_guarantee_01' => 'Guarantee. See doc-helper-tp_guarantee_01.', 'cta_quote_request_01' => 'Quote request CTA. See doc-helper-cta_quote_request_01.', 'fb_benefit_band_01' => 'Benefit band. See doc-helper-fb_benefit_band_01.', 'cta_booking_01' => 'Booking CTA. See doc-helper-cta_booking_01.', 'lpu_contact_panel_01' => 'Contact panel. See doc-helper-lpu_contact_panel_01.', 'cta_contact_01' => 'Contact CTA. See doc-helper-cta_contact_01.',
);
$li = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Single service detail with educational depth. How-it-works and expectations before CTAs; trust and guarantee; quote, booking and contact CTA.';
$flow    = 'Informational depth first; conversion after education. Suited to considered purchases. Service detail, consultation, quote, booking, contact; last CTA contact.';
return array(
	'documentation_id'   => 'doc-onepager-child_detail_service_educational_01',
	'documentation_type' => 'page_template_one_pager',
	'content_body'       => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>' . $flow . '</p><h3>CTA direction</h3><p>Detail page: service detail CTA → consultation CTA → quote CTA → booking CTA → contact CTA (last). Detail-page expectations: specificity, proof depth, FAQs where relevant; child of hub/nested hub.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper. <strong>Hierarchy:</strong> Child detail under Services hub; single service entity. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; ensure last section is contact CTA. <strong>Accessibility:</strong> One H1; logical heading order; contrast and focus order for CTAs.</p>',
	'status'             => 'active',
	'source_reference'   => array( 'page_template_key' => 'child_detail_service_educational_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'     => '1',
	'export_metadata'   => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
