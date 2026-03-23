<?php
/**
 * Page template one-pager documentation: pt_utility_legal_01 (Utility legal links). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$aio_pb_sections = array(
	'hero_compact_01'           => 'Compact hero. See doc-helper-hero_compact_01.',
	'lpu_terms_toc_01'          => 'Terms TOC. See doc-helper-lpu_terms_toc_01.',
	'lpu_privacy_highlight_01'  => 'Privacy highlight. See doc-helper-lpu_privacy_highlight_01.',
	'cta_policy_utility_01'     => 'Policy CTA. See doc-helper-cta_policy_utility_01.',
	'lpu_legal_summary_01'      => 'Legal summary. See doc-helper-lpu_legal_summary_01.',
	'lpu_contact_panel_01'      => 'Contact panel. See doc-helper-lpu_contact_panel_01.',
	'ptf_faq_01'                => 'FAQ. See doc-helper-ptf_faq_01.',
	'cta_support_01'            => 'Support CTA. See doc-helper-cta_support_01.',
	'lpu_footer_legal_01'       => 'Footer legal. See doc-helper-lpu_footer_legal_01.',
	'lpu_trust_disclosure_01'   => 'Trust disclosure. See doc-helper-lpu_trust_disclosure_01.',
	'lpu_accessibility_help_01' => 'Accessibility help. See doc-helper-lpu_accessibility_help_01.',
	'cta_contact_02'            => 'Contact CTA. See doc-helper-cta_contact_02.',
);
$aio_pb_li       = '';
foreach ( $aio_pb_sections as $aio_pb_k => $aio_pb_v ) {
	$aio_pb_li .= '<li><strong>' . $aio_pb_k . '</strong>: ' . $aio_pb_v . '</li>';
}
$aio_pb_purpose = 'Utility page with legal links. Hero and terms TOC; privacy highlight and policy CTA; legal summary and contact panel; FAQ and support CTA; footer legal and trust disclosure; accessibility help; contact CTA.';
return array(
	'documentation_id'          => 'doc-onepager-pt_utility_legal_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>' . $aio_pb_purpose . '</p><h3>Page flow</h3><p>Legal links and utility; softer CTA direction for utility pages.</p><h3>Section-by-section (ordered)</h3><ol>' . $aio_pb_li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO:</strong> Container and spacing; map fields per section helper. <strong>Hierarchy/navigation:</strong> Utility top-level. <strong>Mistakes to avoid:</strong> No legal advice; do not stack adjacent CTAs. <strong>Accessibility:</strong> One H1; logical headings; landmarks and contrast.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'pt_utility_legal_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
