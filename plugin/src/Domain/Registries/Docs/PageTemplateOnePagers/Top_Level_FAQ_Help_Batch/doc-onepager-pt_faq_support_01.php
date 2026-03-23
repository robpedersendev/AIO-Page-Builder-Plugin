<?php
/**
 * Page template one-pager documentation: pt_faq_support_01 (FAQ support-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$aio_pb_sections     = array(
	'hero_edu_01'               => 'Education hero. See doc-helper-hero_edu_01. Support-focused opener.',
	'ptf_faq_01'                => 'FAQ block. See doc-helper-ptf_faq_01. Core FAQ content.',
	'ptf_faq_accordion_01'      => 'Accordion FAQ. See doc-helper-ptf_faq_accordion_01. Expandable Q&A.',
	'cta_support_01'            => 'Support CTA. See doc-helper-cta_support_01. First conversion point.',
	'tp_faq_microproof_01'      => 'FAQ microproof. See doc-helper-tp_faq_microproof_01. Reassurance.',
	'ptf_faq_by_category_01'    => 'FAQ by category. See doc-helper-ptf_faq_by_category_01. Question grouping.',
	'lpu_support_escalation_01' => 'Support escalation. See doc-helper-lpu_support_escalation_01. Escalation and next steps.',
	'cta_contact_01'            => 'Contact CTA. See doc-helper-cta_contact_01. Contact conversion point.',
	'fb_resource_explainer_01'  => 'Resource explainer. See doc-helper-fb_resource_explainer_01. Explanatory content.',
	'ptf_policy_explainer_01'   => 'Policy explainer. See doc-helper-ptf_policy_explainer_01. Policy context.',
	'cta_support_02'            => 'Support CTA. See doc-helper-cta_support_02. Final CTA.',
);
$aio_pb_section_list = '';
foreach ( $aio_pb_sections as $aio_pb_key => $aio_pb_guidance ) {
	$aio_pb_section_list .= '<li><strong>' . $aio_pb_key . '</strong>: ' . $aio_pb_guidance . '</li>';
}

return array(
	'documentation_id'          => 'doc-onepager-pt_faq_support_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>FAQ page support-focused. Hero and FAQ sections; support CTA; microproof and FAQ by category; escalation and contact CTA; explainers; final support CTA.</p>'
		. '<h3>Page flow</h3><p>Educational opener; FAQ content; support CTA; microproof and categorized FAQ; escalation and contact CTA; explainers; close with support CTA.</p>'
		. '<h3>CTA direction</h3><p>Primary path: support CTA → contact CTA → support CTA. One primary action per CTA block; explanatory flow and question grouping support help-seeking; avoid conflating with marketing conversion pages.</p>'
		. '<h3>Section-by-section (ordered)</h3><ol>' . $aio_pb_section_list . '</ol>'
		. '<h3>Page-wide notes</h3><p><strong>GeneratePress:</strong> Use for container and spacing; keep section order as defined. <strong>ACF:</strong> Map fields per section helper. <strong>AIOSEO:</strong> Set focus keyphrase and meta for FAQ/support. <strong>FIFU:</strong> Use for hero/section images where applicable. <strong>Hierarchy:</strong> FAQ/help is top-level; ensure nav reflects support/help family. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; keep question grouping clear; ensure last section is the final CTA. <strong>Accessibility:</strong> One H1 per page; logical heading order; sufficient contrast and focus order for all CTAs and accordions.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'pt_faq_support_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
