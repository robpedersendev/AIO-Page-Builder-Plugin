<?php
/**
 * Page template one-pager documentation: child_detail_service_proof_dense_01 (Service detail proof-dense). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_cred_01'           => 'Credibility hero. See doc-helper-hero_cred_01.',
	'fb_service_offering_01' => 'Service offering. See doc-helper-fb_service_offering_01.',
	'tp_trust_band_01'       => 'Trust band. See doc-helper-tp_trust_band_01.',
	'cta_consultation_01'    => 'Consultation CTA. See doc-helper-cta_consultation_01.',
	'tp_testimonial_01'      => 'Testimonial. See doc-helper-tp_testimonial_01.',
	'tp_guarantee_01'        => 'Guarantee. See doc-helper-tp_guarantee_01.',
	'tp_testimonial_02'      => 'Testimonial. See doc-helper-tp_testimonial_02.',
	'cta_booking_01'         => 'Booking CTA. See doc-helper-cta_booking_01.',
	'fb_why_choose_01'       => 'Why choose. See doc-helper-fb_why_choose_01.',
	'tp_client_logo_01'      => 'Client logos. See doc-helper-tp_client_logo_01.',
	'ptf_expectations_01'    => 'Expectations. See doc-helper-ptf_expectations_01.',
	'cta_quote_request_01'   => 'Quote request CTA. See doc-helper-cta_quote_request_01.',
	'ptf_how_it_works_01'    => 'How it works. See doc-helper-ptf_how_it_works_01.',
	'cta_service_detail_01'  => 'Service detail CTA. See doc-helper-cta_service_detail_01.',
	'lpu_contact_panel_01'   => 'Contact panel. See doc-helper-lpu_contact_panel_01.',
	'cta_contact_01'         => 'Contact CTA. See doc-helper-cta_contact_01.',
);
$li       = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Single service detail with proof density. Multiple trust and testimonial blocks; guarantee and logos; quote and contact CTA.';
$flow    = 'Proof-heavy; builds trust before each CTA. Suited to high-consideration services. Consultation, booking, quote, service detail, contact; last CTA contact.';
return array(
	'documentation_id'          => 'doc-onepager-child_detail_service_proof_dense_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>' . $flow . '</p><h3>CTA direction</h3><p>Detail page: consultation CTA → booking CTA → quote CTA → service detail CTA → contact CTA (last). Detail-page expectations: proof depth, specificity; child of hub/nested hub.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper. <strong>Hierarchy:</strong> Child detail under Services hub; single service entity. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; ensure last section is contact CTA. <strong>Accessibility:</strong> One H1; logical heading order; contrast and focus order for CTAs.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'child_detail_service_proof_dense_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
