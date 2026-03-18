<?php
/**
 * Page template one-pager documentation: pt_home_trust_01 (Home trust-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections     = array(
	'hero_cred_01'              => 'Credibility hero. See doc-helper-hero_cred_01. Trust-first opener.',
	'tp_trust_band_01'          => 'Trust band. See doc-helper-tp_trust_band_01. Reinforce credibility.',
	'fb_why_choose_01'          => 'Why choose. See doc-helper-fb_why_choose_01. Differentiators.',
	'cta_booking_01'            => 'Booking CTA. See doc-helper-cta_booking_01. First conversion; clear primary action.',
	'ptf_faq_01'                => 'FAQ. See doc-helper-ptf_faq_01. Reduce friction.',
	'mlp_card_grid_01'          => 'Card grid. See doc-helper-mlp_card_grid_01. Listings or cards.',
	'tp_testimonial_02'         => 'Testimonial. See doc-helper-tp_testimonial_02. Social proof.',
	'cta_inquiry_01'            => 'Inquiry CTA. See doc-helper-cta_inquiry_01. Second conversion point.',
	'lpu_support_escalation_01' => 'Support escalation. See doc-helper-lpu_support_escalation_01. Support and next steps.',
	'fb_differentiator_01'      => 'Differentiator. See doc-helper-fb_differentiator_01. Stand-out points.',
	'ptf_expectations_01'       => 'Expectations. See doc-helper-ptf_expectations_01. Set scope.',
	'cta_support_02'            => 'Support CTA. See doc-helper-cta_support_02. Final CTA for help.',
);
$section_list = '';
foreach ( $sections as $key => $guidance ) {
	$section_list .= '<li><strong>' . $key . '</strong>: ' . $guidance . '</li>';
}

return array(
	'documentation_id'          => 'doc-onepager-pt_home_trust_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>Home page trust-led. Lead with credibility and trust band; why choose and booking CTA; FAQ and cards; testimonial and inquiry CTA; support and differentiator; expectations; close with support CTA.</p>'
		. '<h3>Page flow</h3><p>Trust-first opener; why choose and booking CTA; FAQ and cards reduce friction; testimonial and inquiry CTA; support and differentiator; expectations set scope; final CTA for help.</p>'
		. '<h3>CTA direction</h3><p>Primary path: hero → booking CTA → inquiry CTA → support CTA. One primary action per CTA; avoid duplicate or competing CTAs.</p>'
		. '<h3>Section-by-section (ordered)</h3><ol>' . $section_list . '</ol>'
		. '<h3>Page-wide notes</h3><p><strong>GeneratePress:</strong> Use for container and spacing; keep section order as defined. <strong>ACF:</strong> Map fields per section helper. <strong>AIOSEO:</strong> Set focus keyphrase and meta for home. <strong>FIFU:</strong> Use for hero and section images where applicable. <strong>Navigation/hierarchy:</strong> Home is top-level. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; do not repeat the same CTA copy; ensure last section is the final CTA. <strong>Accessibility:</strong> One H1 per page; logical heading order; sufficient contrast and focus order for all CTAs.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'pt_home_trust_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
