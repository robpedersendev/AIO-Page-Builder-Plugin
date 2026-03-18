<?php
/**
 * Page template one-pager documentation: pt_about_story_01 (About story-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections     = array(
	'hero_edit_01'            => 'Editorial hero. See doc-helper-hero_edit_01. Story opener.',
	'tp_quote_01'             => 'Quote. See doc-helper-tp_quote_01. Build credibility.',
	'fb_value_prop_01'        => 'Value proposition. See doc-helper-fb_value_prop_01. One clear value message.',
	'cta_contact_01'          => 'Contact CTA. See doc-helper-cta_contact_01. First conversion point.',
	'ptf_timeline_01'         => 'Timeline. See doc-helper-ptf_timeline_01. Narrative structure.',
	'tp_authority_01'         => 'Authority. See doc-helper-tp_authority_01. Trust-building.',
	'lpu_trust_disclosure_01' => 'Trust disclosure. See doc-helper-lpu_trust_disclosure_01. Transparency.',
	'cta_consultation_02'     => 'Consultation CTA. See doc-helper-cta_consultation_02. Second conversion point.',
	'fb_benefit_detail_01'    => 'Benefit detail. See doc-helper-fb_benefit_detail_01. Reinforce benefits.',
	'ptf_steps_01'            => 'Steps. See doc-helper-ptf_steps_01. Clear steps.',
	'cta_policy_utility_01'   => 'Policy/utility CTA. See doc-helper-cta_policy_utility_01. Final CTA.',
);
$section_list = '';
foreach ( $sections as $key => $guidance ) {
	$section_list .= '<li><strong>' . $key . '</strong>: ' . $guidance . '</li>';
}

return array(
	'documentation_id'          => 'doc-onepager-pt_about_story_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>About page story-led. Editorial hero and quote; value prop and contact CTA; timeline and authority; trust disclosure and consultation CTA; benefit detail and steps; utility CTA.</p>'
		. '<h3>Page flow</h3><p>Story opener; value and contact CTA; timeline and authority build credibility; disclosure and consultation CTA; benefits and steps; close with utility CTA.</p>'
		. '<h3>CTA direction</h3><p>Primary path: contact CTA → consultation CTA → policy/utility CTA. One primary action per CTA block; keep narrative and trust-building flow before each CTA.</p>'
		. '<h3>Section-by-section (ordered)</h3><ol>' . $section_list . '</ol>'
		. '<h3>Page-wide notes</h3><p><strong>GeneratePress:</strong> Use for container and spacing; keep section order as defined. <strong>ACF:</strong> Map fields per section helper. <strong>AIOSEO:</strong> Set focus keyphrase and meta for about/story. <strong>FIFU:</strong> Use for hero and section images. <strong>Navigation/hierarchy:</strong> About is top-level; ensure nav reflects this. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; do not repeat the same CTA copy; ensure narrative flow supports trust before CTAs. <strong>Accessibility:</strong> One H1 per page; logical heading order; sufficient contrast and focus order for all CTAs.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'pt_about_story_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
