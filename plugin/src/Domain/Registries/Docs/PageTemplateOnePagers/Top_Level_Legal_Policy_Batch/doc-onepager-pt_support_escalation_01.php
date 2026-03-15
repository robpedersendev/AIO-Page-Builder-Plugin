<?php
/**
 * Page template one-pager documentation: pt_support_escalation_01 (Support escalation). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_compact_01' => 'Compact hero. See doc-helper-hero_compact_01.', 'lpu_inquiry_support_01' => 'Inquiry support. See doc-helper-lpu_inquiry_support_01.', 'lpu_support_escalation_01' => 'Support escalation. See doc-helper-lpu_support_escalation_01.', 'cta_contact_02' => 'Contact CTA. See doc-helper-cta_contact_02.', 'lpu_contact_panel_01' => 'Contact panel. See doc-helper-lpu_contact_panel_01.', 'ptf_faq_01' => 'FAQ. See doc-helper-ptf_faq_01.', 'lpu_form_intro_01' => 'Form intro. See doc-helper-lpu_form_intro_01.', 'cta_support_01' => 'Support CTA. See doc-helper-cta_support_01.', 'tp_reassurance_01' => 'Reassurance. See doc-helper-tp_reassurance_01.', 'lpu_contact_detail_01' => 'Contact detail. See doc-helper-lpu_contact_detail_01.', 'lpu_accessibility_help_01' => 'Accessibility help. See doc-helper-lpu_accessibility_help_01.', 'cta_support_02' => 'Support CTA. See doc-helper-cta_support_02.',
);
$li = '';
foreach ( $sections as $k => $v ) {
	$li .= '<li><strong>' . $k . '</strong>: ' . $v . '</li>';
}
$purpose = 'Support escalation page. Hero and inquiry support; support escalation and contact CTA; contact panel and FAQ; form intro and support CTA; reassurance and contact detail; accessibility help; support CTA.';
return array(
	'documentation_id'   => 'doc-onepager-pt_support_escalation_01',
	'documentation_type' => 'page_template_one_pager',
	'content_body'       => '<h3>Page purpose</h3><p>' . $purpose . '</p><h3>Page flow</h3><p>Escalation and contact options; softer CTA direction for utility pages.</p><h3>Section-by-section (ordered)</h3><ol>' . $li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO:</strong> Container and spacing; map fields per section helper. <strong>Hierarchy/navigation:</strong> Support top-level. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs. <strong>Accessibility:</strong> One H1; logical headings; landmarks and contrast.</p>',
	'status'             => 'active',
	'source_reference'   => array( 'page_template_key' => 'pt_support_escalation_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'     => '1',
	'export_metadata'   => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
