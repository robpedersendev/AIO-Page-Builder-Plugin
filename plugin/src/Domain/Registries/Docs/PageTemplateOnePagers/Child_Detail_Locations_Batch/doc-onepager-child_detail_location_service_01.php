<?php
/**
 * Page template one-pager documentation: child_detail_location_service_01 (Location detail + service). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_local_01' => 'Local hero. See doc-helper-hero_local_01.', 'fb_local_value_01' => 'Local value. See doc-helper-fb_local_value_01.', 'fb_service_offering_01' => 'Service offering. See doc-helper-fb_service_offering_01.', 'cta_service_detail_01' => 'Service detail CTA. See doc-helper-cta_service_detail_01.', 'mlp_location_info_01' => 'Location info. See doc-helper-mlp_location_info_01.', 'tp_trust_band_01' => 'Trust band. See doc-helper-tp_trust_band_01.', 'cta_consultation_01' => 'Consultation CTA. See doc-helper-cta_consultation_01.', 'mlp_place_highlight_01' => 'Place highlight. See doc-helper-mlp_place_highlight_01.', 'ptf_expectations_01' => 'Expectations. See doc-helper-ptf_expectations_01.', 'cta_booking_01' => 'Booking CTA. See doc-helper-cta_booking_01.', 'lpu_contact_detail_01' => 'Contact detail. See doc-helper-lpu_contact_detail_01.', 'tp_reassurance_01' => 'Reassurance. See doc-helper-tp_reassurance_01.', 'cta_contact_01' => 'Contact CTA. See doc-helper-cta_contact_01.', 'lpu_contact_panel_01' => 'Contact panel. See doc-helper-lpu_contact_panel_01.', 'cta_local_action_01' => 'Local action CTA. See doc-helper-cta_local_action_01.',
);
$li = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Single location with service link (e.g. Salt Lake City service page). Service offering and service CTA; consultation, booking, contact, local CTAs.';
$flow    = 'Location + service combination; service and local conversion paths. Service detail, consultation, booking, contact, local action; last CTA local.';
return array(
	'documentation_id'   => 'doc-onepager-child_detail_location_service_01',
	'documentation_type' => 'page_template_one_pager',
	'content_body'       => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>' . $flow . '</p><h3>CTA direction</h3><p>Detail page: service detail CTA → consultation CTA → booking CTA → contact CTA → local action CTA (last). Location + service interplay; location specificity and service conversion; child of Locations hub.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper; location and service meta; imagery for place and service. <strong>Hierarchy:</strong> Child detail under Locations hub; location + service compatibility. <strong>Local guidance:</strong> Service offering and local value support location-specific service messaging; keep distinct from pure service-detail pages. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; ensure last section is local action CTA. <strong>Accessibility:</strong> One H1; logical heading order; contrast and focus order for CTAs.</p>',
	'status'             => 'active',
	'source_reference'   => array( 'page_template_key' => 'child_detail_location_service_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'     => '1',
	'export_metadata'   => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
