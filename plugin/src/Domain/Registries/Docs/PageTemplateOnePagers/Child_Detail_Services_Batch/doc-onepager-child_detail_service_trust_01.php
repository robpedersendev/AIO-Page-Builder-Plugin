<?php
/**
 * Page template one-pager documentation: child_detail_service_trust_01 (Service detail trust-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$aio_pb_sections = array(
	'hero_cred_01'           => 'Credibility hero. See doc-helper-hero_cred_01.',
	'fb_service_offering_01' => 'Service offering. See doc-helper-fb_service_offering_01.',
	'tp_trust_band_01'       => 'Trust band. See doc-helper-tp_trust_band_01.',
	'tp_guarantee_01'        => 'Guarantee. See doc-helper-tp_guarantee_01.',
	'cta_consultation_01'    => 'Consultation CTA. See doc-helper-cta_consultation_01.',
	'tp_testimonial_01'      => 'Testimonial. See doc-helper-tp_testimonial_01.',
	'fb_why_choose_01'       => 'Why choose. See doc-helper-fb_why_choose_01.',
	'ptf_how_it_works_01'    => 'How it works. See doc-helper-ptf_how_it_works_01.',
	'cta_booking_01'         => 'Booking CTA. See doc-helper-cta_booking_01.',
	'tp_reassurance_01'      => 'Reassurance. See doc-helper-tp_reassurance_01.',
	'ptf_expectations_01'    => 'Expectations. See doc-helper-ptf_expectations_01.',
	'cta_service_detail_01'  => 'Service detail CTA. See doc-helper-cta_service_detail_01.',
	'fb_benefit_band_01'     => 'Benefit band. See doc-helper-fb_benefit_band_01.',
	'cta_quote_request_01'   => 'Quote request CTA. See doc-helper-cta_quote_request_01.',
	'lpu_contact_panel_01'   => 'Contact panel. See doc-helper-lpu_contact_panel_01.',
	'cta_contact_01'         => 'Contact CTA. See doc-helper-cta_contact_01.',
);
$aio_pb_li       = '';
foreach ( $aio_pb_sections as $aio_pb_k => $aio_pb_v ) {
	$aio_pb_li .= '<li><strong>' . $aio_pb_k . '</strong>: ' . $aio_pb_v . '</li>';
}
$aio_pb_purpose = 'Single service detail with trust emphasis. Trust band, guarantee, reassurance before CTAs.';
$aio_pb_flow    = 'Trust-first; guarantee and reassurance support conversion. Consultation, booking, service detail, quote, contact; last CTA contact.';
return array(
	'documentation_id'          => 'doc-onepager-child_detail_service_trust_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>' . $aio_pb_purpose . '</p><h3>Page flow</h3><p>' . $aio_pb_flow . '</p><h3>CTA direction</h3><p>Detail page: consultation CTA → booking CTA → service detail CTA → quote CTA → contact CTA (last). Detail-page expectations: trust-led; guarantee and reassurance prominent; child of hub/nested hub.</p><h3>Section-by-section (ordered)</h3><ol>' . $aio_pb_li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper. <strong>Hierarchy:</strong> Child detail under Services hub; single service entity. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; ensure last section is contact CTA. <strong>Accessibility:</strong> One H1; logical heading order; contrast and focus order for CTAs.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'child_detail_service_trust_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
