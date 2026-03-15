<?php
/**
 * Page template one-pager documentation: hub_locations_overview_01 (Locations hub overview). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_local_01' => 'Local hero. See doc-helper-hero_local_01.', 'mlp_location_info_01' => 'Location info. See doc-helper-mlp_location_info_01.', 'mlp_place_highlight_01' => 'Place highlight. See doc-helper-mlp_place_highlight_01.', 'cta_local_action_01' => 'Local action CTA. See doc-helper-cta_local_action_01. Drill-down.', 'fb_local_value_01' => 'Local value. See doc-helper-fb_local_value_01.', 'mlp_card_grid_01' => 'Card grid. See doc-helper-mlp_card_grid_01. Scannability.', 'tp_trust_band_01' => 'Trust band. See doc-helper-tp_trust_band_01.', 'cta_contact_01' => 'Contact CTA. See doc-helper-cta_contact_01.', 'lpu_contact_detail_01' => 'Contact detail. See doc-helper-lpu_contact_detail_01.', 'ptf_expectations_01' => 'Expectations. See doc-helper-ptf_expectations_01.', 'lpu_contact_panel_01' => 'Contact panel. See doc-helper-lpu_contact_panel_01.', 'cta_local_action_02' => 'Local action CTA. See doc-helper-cta_local_action_02.', 'mlp_listing_01' => 'Listing. See doc-helper-mlp_listing_01.', 'cta_directory_nav_01' => 'Directory nav CTA. See doc-helper-cta_directory_nav_01.',
);
$li = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Locations hub overview. Location info and place highlight; local CTA; local value and card grid; trust band and contact CTA; contact detail and expectations; contact panel and local CTA; listing; directory nav CTA.';
$flow    = 'Locations-overview; local and contact and directory CTAs. Semantic headings (spec §51.6).';
return array(
	'documentation_id'   => 'doc-onepager-hub_locations_overview_01',
	'documentation_type' => 'page_template_one_pager',
	'content_body'       => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>' . $flow . '</p><h3>CTA direction</h3><p>Hub aggregation: local action CTA → contact CTA → local action CTA → directory nav CTA. Emphasize local relevance, navigation, and scannability; drill-down to location or region pages.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper; hub meta; images where applicable. <strong>Hierarchy/navigation:</strong> Hub is parent to location detail pages; ensure nav reflects locations hierarchy. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; keep hub distinct from location detail. <strong>Accessibility:</strong> One H1; logical heading order; contrast and focus order for CTAs.</p>',
	'status'             => 'active',
	'source_reference'   => array( 'page_template_key' => 'hub_locations_overview_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'     => '1',
	'export_metadata'   => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
