<?php
/**
 * Page template one-pager documentation: child_detail_location_contact_01 (Location detail contact/visit). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_local_01'          => 'Local hero. See doc-helper-hero_local_01.',
	'mlp_location_info_01'   => 'Location info. See doc-helper-mlp_location_info_01.',
	'lpu_contact_detail_01'  => 'Contact detail. See doc-helper-lpu_contact_detail_01.',
	'cta_contact_01'         => 'Contact CTA. See doc-helper-cta_contact_01.',
	'fb_local_value_01'      => 'Local value. See doc-helper-fb_local_value_01.',
	'mlp_place_highlight_01' => 'Place highlight. See doc-helper-mlp_place_highlight_01.',
	'cta_local_action_01'    => 'Local action CTA. See doc-helper-cta_local_action_01.',
	'tp_trust_band_01'       => 'Trust band. See doc-helper-tp_trust_band_01.',
	'lpu_contact_panel_01'   => 'Contact panel. See doc-helper-lpu_contact_panel_01.',
	'cta_contact_02'         => 'Contact CTA. See doc-helper-cta_contact_02.',
	'ptf_expectations_01'    => 'Expectations. See doc-helper-ptf_expectations_01.',
	'mlp_card_grid_01'       => 'Card grid. See doc-helper-mlp_card_grid_01.',
	'cta_directory_nav_01'   => 'Directory nav CTA. See doc-helper-cta_directory_nav_01.',
	'tp_reassurance_01'      => 'Reassurance. See doc-helper-tp_reassurance_01.',
	'cta_booking_01'         => 'Booking CTA. See doc-helper-cta_booking_01.',
);
$li       = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Single location detail with contact/visit emphasis. Contact detail and panel early; multiple contact and local CTAs; booking last.';
$flow    = 'Contact and visit path prominent; directory and booking support. Contact, local action, contact, directory nav, booking; last CTA booking.';
return array(
	'documentation_id'          => 'doc-onepager-child_detail_location_contact_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>' . $flow . '</p><h3>CTA direction</h3><p>Detail page: contact CTA → local action CTA → contact CTA → directory nav CTA → booking CTA (last). Location specificity; contact detail and panel prominent; child of Locations hub.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper; location meta and imagery. <strong>Hierarchy:</strong> Child detail under Locations hub. <strong>Local guidance:</strong> Contact detail and panel support visit/contact; CTA placement supports conversion. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; ensure last section is booking CTA. <strong>Accessibility:</strong> One H1; logical heading order; contrast and focus order for CTAs.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'child_detail_location_contact_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
