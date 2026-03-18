<?php
/**
 * Page template one-pager documentation: hub_locations_regional_01 (Locations hub regional). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_local_01'          => 'Local hero. See doc-helper-hero_local_01.',
	'fb_local_value_01'      => 'Local value. See doc-helper-fb_local_value_01.',
	'mlp_location_info_01'   => 'Location info. See doc-helper-mlp_location_info_01.',
	'cta_local_action_01'    => 'Local action CTA. See doc-helper-cta_local_action_01.',
	'mlp_place_highlight_01' => 'Place highlight. See doc-helper-mlp_place_highlight_01.',
	'mlp_card_grid_01'       => 'Card grid. See doc-helper-mlp_card_grid_01.',
	'tp_reassurance_01'      => 'Reassurance. See doc-helper-tp_reassurance_01.',
	'cta_local_action_02'    => 'Local action CTA. See doc-helper-cta_local_action_02.',
	'lpu_contact_detail_01'  => 'Contact detail. See doc-helper-lpu_contact_detail_01.',
	'lpu_contact_panel_01'   => 'Contact panel. See doc-helper-lpu_contact_panel_01.',
	'ptf_expectations_01'    => 'Expectations. See doc-helper-ptf_expectations_01.',
	'cta_contact_02'         => 'Contact CTA. See doc-helper-cta_contact_02.',
	'mlp_listing_01'         => 'Listing. See doc-helper-mlp_listing_01.',
	'cta_directory_nav_01'   => 'Directory nav CTA. See doc-helper-cta_directory_nav_01.',
);
$li       = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Locations hub regional. Local value and location info; local CTA; place highlight and card grid; reassurance and local CTA; contact detail and panel; expectations and contact CTA; listing; directory nav CTA.';
$flow    = 'Regional emphasis; local and contact and directory CTAs for drill-down.';
return array(
	'documentation_id'          => 'doc-onepager-hub_locations_regional_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>' . $flow . '</p><h3>CTA direction</h3><p>Hub aggregation: local action CTA → local action CTA → contact CTA → directory nav CTA. Emphasize local navigation and regional drill-down; keep hub distinct from location detail pages.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO/FIFU:</strong> Container and spacing; map fields per section helper; hub meta. <strong>Hierarchy/navigation:</strong> Hub is parent to location/region pages. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs. <strong>Accessibility:</strong> One H1; logical heading order; contrast and focus order for CTAs.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'hub_locations_regional_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
