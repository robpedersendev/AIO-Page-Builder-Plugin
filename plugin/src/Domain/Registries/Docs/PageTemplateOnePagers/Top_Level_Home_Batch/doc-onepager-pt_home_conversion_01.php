<?php
/**
 * Page template one-pager documentation: pt_home_conversion_01 (Home conversion-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections     = array(
	'hero_conv_01'         => 'Conversion hero. See doc-helper-hero_conv_01. Lead with offer and primary CTA.',
	'tp_testimonial_01'    => 'Testimonial proof. See doc-helper-tp_testimonial_01. Build trust early.',
	'fb_value_prop_01'     => 'Value proposition. See doc-helper-fb_value_prop_01. One clear value message.',
	'cta_consultation_01'  => 'Consultation CTA. See doc-helper-cta_consultation_01. First conversion point; clear primary action.',
	'ptf_how_it_works_01'  => 'How it works. See doc-helper-ptf_how_it_works_01. Explain process simply.',
	'fb_benefit_band_01'   => 'Benefit band. See doc-helper-fb_benefit_band_01. Reinforce benefits.',
	'tp_client_logo_01'    => 'Client logos. See doc-helper-tp_client_logo_01. Social proof.',
	'cta_contact_01'       => 'Contact CTA. See doc-helper-cta_contact_01. Second conversion point; link to contact/form.',
	'lpu_contact_panel_01' => 'Contact panel. See doc-helper-lpu_contact_panel_01. Channels and next steps.',
	'fb_feature_grid_01'   => 'Feature grid. See doc-helper-fb_feature_grid_01. Feature highlights.',
	'ptf_steps_01'         => 'Steps. See doc-helper-ptf_steps_01. Clear steps.',
	'cta_trust_confirm_01' => 'Trust confirm CTA. See doc-helper-cta_trust_confirm_01. Final CTA; reassurance.',
);
$section_list = '';
foreach ( $sections as $key => $guidance ) {
	$section_list .= '<li><strong>' . $key . '</strong>: ' . $guidance . '</li>';
}

return array(
	'documentation_id'          => 'doc-onepager-pt_home_conversion_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>Home page conversion-led. Lead with hero and proof, then consultation CTA; mid-page benefits and logos; contact CTA and panel; features and steps; close with trust CTA.</p>'
		. '<h3>Page flow</h3><p>Opener establishes offer; proof and value prop build trust; first CTA captures interest; how-it-works and benefits explain; second CTA and contact panel support conversion; features and steps reinforce; final CTA confirms.</p>'
		. '<h3>CTA direction</h3><p>Primary conversion path: hero → consultation CTA → contact CTA → trust CTA. Keep one primary action per CTA block; avoid duplicate or competing CTAs.</p>'
		. '<h3>Section-by-section (ordered)</h3><ol>' . $section_list . '</ol>'
		. '<h3>Page-wide notes</h3><p><strong>GeneratePress:</strong> Use for container and spacing; keep section order as defined. <strong>ACF:</strong> Map fields per section helper. <strong>AIOSEO:</strong> Set focus keyphrase and meta for home. <strong>FIFU:</strong> Use for hero and section images where applicable. <strong>Navigation/hierarchy:</strong> Home is top-level; ensure nav reflects this. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; do not repeat the same CTA copy; ensure last section is the final CTA. <strong>Accessibility:</strong> One H1 per page; logical heading order; sufficient contrast and focus order for all CTAs.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'pt_home_conversion_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
