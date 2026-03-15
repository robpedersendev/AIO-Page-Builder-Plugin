<?php
/**
 * Page template one-pager documentation: pt_contact_request_01 (Contact request-led). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$sections = array(
	'hero_conv_02'           => 'Conversion hero. See doc-helper-hero_conv_02. Lead with request intent.',
	'lpu_contact_panel_01'   => 'Contact panel. See doc-helper-lpu_contact_panel_01. Channels and contact methods.',
	'lpu_contact_detail_01'  => 'Contact detail. See doc-helper-lpu_contact_detail_01. Address, phone, email.',
	'cta_contact_02'        => 'Contact CTA. See doc-helper-cta_contact_02. Primary contact conversion point.',
	'lpu_form_intro_01'     => 'Form intro. See doc-helper-lpu_form_intro_01. Form support copy.',
	'lpu_inquiry_support_01' => 'Inquiry support. See doc-helper-lpu_inquiry_support_01. Inquiry and form context.',
	'tp_reassurance_01'     => 'Reassurance. See doc-helper-tp_reassurance_01. Reduce friction.',
	'cta_quote_request_02'  => 'Quote request CTA. See doc-helper-cta_quote_request_02. Second conversion point.',
	'fb_value_prop_01'      => 'Value proposition. See doc-helper-fb_value_prop_01. Reinforce value.',
	'lpu_support_escalation_01' => 'Support escalation. See doc-helper-lpu_support_escalation_01. Support and next steps.',
	'cta_support_02'        => 'Support CTA. See doc-helper-cta_support_02. Final CTA.',
);
$section_list = '';
foreach ( $sections as $key => $guidance ) {
	$section_list .= '<li><strong>' . $key . '</strong>: ' . $guidance . '</li>';
}

return array(
	'documentation_id'   => 'doc-onepager-pt_contact_request_01',
	'documentation_type' => 'page_template_one_pager',
	'content_body'       => '<h3>Page purpose</h3><p>Contact page request-focused. Hero and contact panel/detail; contact CTA; form intro and inquiry support; reassurance and quote CTA; value prop and support band; support CTA.</p>'
		. '<h3>Page flow</h3><p>Hero and contact info; contact CTA; form and inquiry; reassurance and quote CTA; value and support; final CTA.</p>'
		. '<h3>CTA direction</h3><p>Primary path: contact CTA → quote request CTA → support CTA. One primary action per CTA block; present contact methods and reassurance before each conversion point. Do not make implementation promises about form processing or data handling.</p>'
		. '<h3>Section-by-section (ordered)</h3><ol>' . $section_list . '</ol>'
		. '<h3>Page-wide notes</h3><p><strong>GeneratePress:</strong> Use for container and spacing; keep section order as defined. <strong>ACF:</strong> Map fields per section helper. <strong>AIOSEO:</strong> Set focus keyphrase and meta for contact. <strong>FIFU:</strong> Use for hero/section images where applicable. <strong>Navigation/hierarchy:</strong> Contact is top-level. <strong>Contact-method notes:</strong> Present channels and detail clearly; avoid vague "Get in touch" without clear next step. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; do not repeat the same CTA copy; ensure last section is the final CTA. <strong>Accessibility:</strong> One H1 per page; logical heading order; sufficient contrast and focus order for all CTAs and contact links.</p>',
	'status'             => 'active',
	'source_reference'   => array( 'page_template_key' => 'pt_contact_request_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'     => '1',
	'export_metadata'   => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
