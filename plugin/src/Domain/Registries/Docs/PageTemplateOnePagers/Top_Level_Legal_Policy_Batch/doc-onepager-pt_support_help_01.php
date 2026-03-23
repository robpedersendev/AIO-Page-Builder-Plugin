<?php
/**
 * Page template one-pager documentation: pt_support_help_01 (Support help). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$aio_pb_sections = array(
	'hero_edu_01'               => 'Education hero. See doc-helper-hero_edu_01.',
	'lpu_support_escalation_01' => 'Support escalation. See doc-helper-lpu_support_escalation_01.',
	'lpu_inquiry_support_01'    => 'Inquiry support. See doc-helper-lpu_inquiry_support_01.',
	'cta_support_01'            => 'Support CTA. See doc-helper-cta_support_01.',
	'ptf_faq_01'                => 'FAQ. See doc-helper-ptf_faq_01.',
	'lpu_contact_panel_01'      => 'Contact panel. See doc-helper-lpu_contact_panel_01.',
	'tp_reassurance_01'         => 'Reassurance. See doc-helper-tp_reassurance_01.',
	'cta_contact_01'            => 'Contact CTA. See doc-helper-cta_contact_01.',
	'lpu_form_intro_01'         => 'Form intro. See doc-helper-lpu_form_intro_01.',
	'lpu_accessibility_help_01' => 'Accessibility help. See doc-helper-lpu_accessibility_help_01.',
	'lpu_contact_detail_01'     => 'Contact detail. See doc-helper-lpu_contact_detail_01.',
	'cta_support_02'            => 'Support CTA. See doc-helper-cta_support_02.',
);
$aio_pb_li       = '';
foreach ( $aio_pb_sections as $aio_pb_k => $aio_pb_v ) {
	$aio_pb_li .= '<li><strong>' . $aio_pb_k . '</strong>: ' . $aio_pb_v . '</li>';
}
$aio_pb_purpose = 'Support help page. Hero and support escalation; inquiry support and support CTA; FAQ and contact panel; reassurance and contact CTA; form intro and accessibility help; contact detail; support CTA.';
return array(
	'documentation_id'          => 'doc-onepager-pt_support_help_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>' . $aio_pb_purpose . '</p><h3>Page flow</h3><p>Support and contact options; softer CTA direction for utility pages.</p><h3>Section-by-section (ordered)</h3><ol>' . $aio_pb_li . '</ol><h3>Page-wide notes</h3><p><strong>GeneratePress/ACF/AIOSEO:</strong> Container and spacing; map fields per section helper. <strong>Hierarchy/navigation:</strong> Support top-level. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs. <strong>Accessibility:</strong> One H1; logical headings; landmarks and contrast.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'pt_support_help_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
