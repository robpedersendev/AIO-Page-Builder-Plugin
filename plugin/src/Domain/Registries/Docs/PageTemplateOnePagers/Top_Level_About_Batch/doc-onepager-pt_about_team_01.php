<?php
/**
 * Page template one-pager documentation: pt_about_team_01 (About team-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_cred_01'        => 'Credibility hero. See doc-helper-hero_cred_01. Trust-first opener.',
	'mlp_team_grid_01'    => 'Team grid. See doc-helper-mlp_team_grid_01. Team/profile presentation.',
	'fb_why_choose_01'    => 'Why choose. See doc-helper-fb_why_choose_01. Differentiators.',
	'cta_contact_02'      => 'Contact CTA. See doc-helper-cta_contact_02. First conversion point.',
	'tp_testimonial_01'   => 'Testimonial. See doc-helper-tp_testimonial_01. Social proof.',
	'ptf_faq_01'          => 'FAQ. See doc-helper-ptf_faq_01. Reduce friction.',
	'tp_partner_01'      => 'Partners. See doc-helper-tp_partner_01. Trust band.',
	'cta_inquiry_02'     => 'Inquiry CTA. See doc-helper-cta_inquiry_02. Second conversion point.',
	'lpu_contact_panel_01' => 'Contact panel. See doc-helper-lpu_contact_panel_01. Channels and next steps.',
	'fb_differentiator_01' => 'Differentiator. See doc-helper-fb_differentiator_01. Stand-out points.',
	'ptf_how_it_works_01' => 'How it works. See doc-helper-ptf_how_it_works_01. Process.',
	'cta_support_01'     => 'Support CTA. See doc-helper-cta_support_01. Final CTA.',
);
$section_list = '';
foreach ( $sections as $key => $guidance ) {
	$section_list .= '<li><strong>' . $key . '</strong>: ' . $guidance . '</li>';
}

return array(
	'documentation_id'   => 'doc-onepager-pt_about_team_01',
	'documentation_type' => 'page_template_one_pager',
	'content_body'       => '<h3>Page purpose</h3><p>About page team-led. Hero and team grid; why choose and contact CTA; testimonial and FAQ; partners and inquiry CTA; contact panel and differentiator; how-it-works; support CTA.</p>'
		. '<h3>Page flow</h3><p>Team and credibility; why choose and contact CTA; social proof and FAQ; partners and inquiry CTA; contact and differentiator; how-it-works; final CTA.</p>'
		. '<h3>CTA direction</h3><p>Primary path: contact CTA → inquiry CTA → support CTA. One primary action per CTA block; team and proof sections build trust before each CTA.</p>'
		. '<h3>Section-by-section (ordered)</h3><ol>' . $section_list . '</ol>'
		. '<h3>Page-wide notes</h3><p><strong>GeneratePress:</strong> Use for container and spacing; keep section order as defined. <strong>ACF:</strong> Map fields per section helper. <strong>AIOSEO:</strong> Set focus keyphrase and meta for about/team. <strong>FIFU:</strong> Use for hero and team/section images. <strong>Navigation/hierarchy:</strong> About is top-level. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; do not repeat the same CTA copy; ensure last section is the final CTA. <strong>Accessibility:</strong> One H1 per page; logical heading order; sufficient contrast and focus order for all CTAs.</p>',
	'status'             => 'active',
	'source_reference'   => array( 'page_template_key' => 'pt_about_team_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'     => '1',
	'export_metadata'   => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
