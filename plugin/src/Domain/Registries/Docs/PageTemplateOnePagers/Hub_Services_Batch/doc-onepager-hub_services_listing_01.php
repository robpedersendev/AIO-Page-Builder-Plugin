<?php
/**
 * Page template one-pager documentation: hub_services_listing_01 (Services hub listing-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_dir_01' => 'Directory hero. See doc-helper-hero_dir_01.', 'mlp_card_grid_01' => 'Card grid. See doc-helper-mlp_card_grid_01. Listing prominence.', 'fb_service_offering_01' => 'Service offering. See doc-helper-fb_service_offering_01.', 'cta_service_detail_02' => 'Service detail CTA. See doc-helper-cta_service_detail_02. Drill-down.', 'mlp_listing_01' => 'Listing. See doc-helper-mlp_listing_01.', 'tp_testimonial_02' => 'Testimonial. See doc-helper-tp_testimonial_02.', 'ptf_how_it_works_01' => 'How it works. See doc-helper-ptf_how_it_works_01.', 'cta_directory_nav_01' => 'Directory nav CTA. See doc-helper-cta_directory_nav_01. Category browsing.', 'fb_benefit_band_01' => 'Benefit band. See doc-helper-fb_benefit_band_01.', 'tp_trust_band_01' => 'Trust band. See doc-helper-tp_trust_band_01.', 'ptf_steps_01' => 'Steps. See doc-helper-ptf_steps_01.', 'cta_consultation_02' => 'Consultation CTA. See doc-helper-cta_consultation_02.', 'lpu_contact_panel_01' => 'Contact panel. See doc-helper-lpu_contact_panel_01.', 'cta_booking_02' => 'Booking CTA. See doc-helper-cta_booking_02.',
);
$li = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Services hub with listing prominence. Card grid and offering; service CTA; listing and testimonial; how-it-works and directory nav CTA; benefits and trust; steps and consultation CTA; contact panel; booking CTA.';
$flow    = 'Listing and category navigation; drill-down via service and directory CTAs.';
return array(
	'documentation_id'   => 'doc-onepager-hub_services_listing_01',
	'documentation_type' => 'page_template_one_pager',
	'content_body'       => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>' . $flow . '</p><h3>CTA direction</h3><p>Hub aggregation: service detail CTA → directory nav CTA → consultation CTA → booking CTA. Emphasize scannability; cards and listing support drill-down and category browsing.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper; hub meta. <strong>Hierarchy/navigation:</strong> Hub is parent to service detail pages. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; keep hub distinct from detail. <strong>Accessibility:</strong> One H1; logical heading order; contrast and focus order for CTAs.</p>',
	'status'             => 'active',
	'source_reference'   => array( 'page_template_key' => 'hub_services_listing_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'     => '1',
	'export_metadata'   => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
