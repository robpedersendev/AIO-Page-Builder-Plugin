<?php
/**
 * Page template one-pager documentation: pt_terms_overview_01 (Terms overview). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_legal_01' => 'Legal hero. See doc-helper-hero_legal_01.', 'lpu_terms_toc_01' => 'Terms TOC. See doc-helper-lpu_terms_toc_01.', 'lpu_legal_summary_01' => 'Legal summary. See doc-helper-lpu_legal_summary_01.', 'cta_policy_utility_01' => 'Policy CTA. See doc-helper-cta_policy_utility_01.', 'lpu_policy_body_01' => 'Policy body. See doc-helper-lpu_policy_body_01.', 'ptf_policy_explainer_01' => 'Policy explainer. See doc-helper-ptf_policy_explainer_01.', 'lpu_disclosure_header_01' => 'Disclosure header. See doc-helper-lpu_disclosure_header_01.', 'cta_contact_01' => 'Contact CTA. See doc-helper-cta_contact_01.', 'tp_reassurance_01' => 'Reassurance. See doc-helper-tp_reassurance_01.', 'lpu_consent_note_01' => 'Consent note. See doc-helper-lpu_consent_note_01.', 'lpu_footer_legal_01' => 'Footer legal. See doc-helper-lpu_footer_legal_01.', 'cta_policy_utility_02' => 'Policy CTA. See doc-helper-cta_policy_utility_02.',
);
$li = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Terms page overview. Hero and terms TOC; legal summary and policy CTA; policy body and explainer; disclosure and contact CTA; reassurance and consent; footer legal; policy CTA.';
return array(
	'documentation_id'   => 'doc-onepager-pt_terms_overview_01',
	'documentation_type' => 'page_template_one_pager',
	'content_body'       => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>Policy semantics; softer CTA direction for utility pages.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO:</strong> Container and spacing; map fields per section helper. <strong>Hierarchy/navigation:</strong> Legal/terms top-level. <strong>Mistakes to avoid:</strong> No legal advice; do not stack adjacent CTAs. <strong>Accessibility:</strong> One H1; logical headings; landmarks and contrast.</p>',
	'status'             => 'active',
	'source_reference'   => array( 'page_template_key' => 'pt_terms_overview_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'     => '1',
	'export_metadata'   => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
