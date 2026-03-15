<?php
/**
 * Page template one-pager documentation: pt_contact_directions_01 (Contact directions-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_local_01'          => 'Local hero. See doc-helper-hero_local_01. Location-focused opener.',
	'lpu_contact_detail_01'  => 'Contact detail. See doc-helper-lpu_contact_detail_01. Address, phone, email.',
	'mlp_location_info_01'   => 'Location info. See doc-helper-mlp_location_info_01. Map/directions context.',
	'cta_local_action_01'   => 'Local action CTA. See doc-helper-cta_local_action_01. First local conversion point.',
	'lpu_contact_panel_01'   => 'Contact panel. See doc-helper-lpu_contact_panel_01. Channels.',
	'tp_trust_band_01'      => 'Trust band. See doc-helper-tp_trust_band_01. Reassurance.',
	'lpu_accessibility_help_01' => 'Accessibility help. See doc-helper-lpu_accessibility_help_01. Access and contact options.',
	'cta_contact_01'        => 'Contact CTA. See doc-helper-cta_contact_01. Contact conversion point.',
	'fb_local_value_01'     => 'Local value. See doc-helper-fb_local_value_01. Local offer or value.',
	'ptf_expectations_01'   => 'Expectations. See doc-helper-ptf_expectations_01. Set scope.',
	'cta_local_action_02'  => 'Local action CTA. See doc-helper-cta_local_action_02. Final CTA.',
);
$section_list = '';
foreach ( $sections as $key => $guidance ) {
	$section_list .= '<li><strong>' . $key . '</strong>: ' . $guidance . '</li>';
}

return array(
	'documentation_id'   => 'doc-onepager-pt_contact_directions_01',
	'documentation_type' => 'page_template_one_pager',
	'content_body'       => '<h3>Page purpose</h3><p>Contact page directions-led. Local hero and contact/location info; local CTA; contact panel and trust band; accessibility and contact CTA; local value and expectations; local CTA.</p>'
		. '<h3>Page flow</h3><p>Location-focused opener; contact and location; local CTA; panel and trust; accessibility and contact CTA; value and expectations; final local CTA.</p>'
		. '<h3>CTA direction</h3><p>Primary path: local action CTA → contact CTA → local action CTA. One primary action per CTA block; present location and contact method clearly. Do not make implementation promises about directions or map providers.</p>'
		. '<h3>Section-by-section (ordered)</h3><ol>' . $section_list . '</ol>'
		. '<h3>Page-wide notes</h3><p><strong>GeneratePress:</strong> Use for container and spacing; keep section order as defined. <strong>ACF:</strong> Map fields per section helper. <strong>AIOSEO:</strong> Set focus keyphrase and meta for contact/directions. <strong>FIFU:</strong> Use for hero/section images where applicable. <strong>Navigation/hierarchy:</strong> Contact is top-level. <strong>Contact-method notes:</strong> Present address and location info clearly; accessibility help supports inclusive contact. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; do not repeat the same CTA copy; ensure last section is the final CTA. <strong>Accessibility:</strong> One H1 per page; logical heading order; sufficient contrast and focus order for all CTAs and links.</p>',
	'status'             => 'active',
	'source_reference'   => array( 'page_template_key' => 'pt_contact_directions_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'     => '1',
	'export_metadata'   => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
