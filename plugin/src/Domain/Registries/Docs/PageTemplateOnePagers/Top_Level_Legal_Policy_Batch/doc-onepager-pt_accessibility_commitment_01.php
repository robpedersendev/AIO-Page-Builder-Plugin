<?php
/**
 * Page template one-pager documentation: pt_accessibility_commitment_01 (Accessibility commitment). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_edu_01'               => 'Education hero. See doc-helper-hero_edu_01.',
	'lpu_accessibility_help_01' => 'Accessibility help. See doc-helper-lpu_accessibility_help_01.',
	'ptf_policy_explainer_01'   => 'Policy explainer. See doc-helper-ptf_policy_explainer_01.',
	'cta_support_01'            => 'Support CTA. See doc-helper-cta_support_01.',
	'lpu_trust_disclosure_01'   => 'Trust disclosure. See doc-helper-lpu_trust_disclosure_01.',
	'tp_reassurance_01'         => 'Reassurance. See doc-helper-tp_reassurance_01.',
	'lpu_contact_panel_01'      => 'Contact panel. See doc-helper-lpu_contact_panel_01.',
	'cta_contact_01'            => 'Contact CTA. See doc-helper-cta_contact_01.',
	'lpu_support_escalation_01' => 'Support escalation. See doc-helper-lpu_support_escalation_01.',
	'lpu_form_intro_01'         => 'Form intro. See doc-helper-lpu_form_intro_01.',
	'ptf_faq_01'                => 'FAQ. See doc-helper-ptf_faq_01.',
	'cta_policy_utility_01'     => 'Policy CTA. See doc-helper-cta_policy_utility_01.',
);
$li       = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Accessibility commitment page. Hero and accessibility help; policy explainer and support CTA; trust disclosure and reassurance; contact panel and contact CTA; support escalation and form intro; FAQ; policy CTA.';
return array(
	'documentation_id'          => 'doc-onepager-pt_accessibility_commitment_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>Commitment and help content; softer CTA direction for utility pages. Semantic headings and landmarks (spec §51.9).</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO:</strong> Container and spacing; map fields per section helper. <strong>Hierarchy/navigation:</strong> Accessibility top-level. <strong>Mistakes to avoid:</strong> No legal or advisory guarantees; do not stack adjacent CTAs. <strong>Accessibility:</strong> One H1; logical headings; landmarks; form/label accessibility where applicable.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'pt_accessibility_commitment_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
