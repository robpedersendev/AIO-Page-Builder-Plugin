<?php
/**
 * Page template one-pager documentation: pt_faq_category_01 (FAQ by category). Spec §16; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$aio_pb_sections     = array(
	'hero_compact_01'        => 'Compact hero. See doc-helper-hero_compact_01. Category-led opener.',
	'ptf_faq_by_category_01' => 'FAQ by category. See doc-helper-ptf_faq_by_category_01. Question grouping first.',
	'ptf_faq_01'             => 'FAQ block. See doc-helper-ptf_faq_01. Standard FAQ content.',
	'cta_inquiry_01'         => 'Inquiry CTA. See doc-helper-cta_inquiry_01. First conversion point.',
	'tp_reassurance_01'      => 'Reassurance. See doc-helper-tp_reassurance_01. Reduce friction.',
	'ptf_expectations_01'    => 'Expectations. See doc-helper-ptf_expectations_01. Set scope.',
	'lpu_form_intro_01'      => 'Form intro. See doc-helper-lpu_form_intro_01. Form support copy.',
	'cta_quote_request_01'   => 'Quote request CTA. See doc-helper-cta_quote_request_01. Second conversion point.',
	'fb_value_prop_01'       => 'Value proposition. See doc-helper-fb_value_prop_01. Reinforce value.',
	'ptf_how_it_works_01'    => 'How it works. See doc-helper-ptf_how_it_works_01. Process.',
	'cta_contact_02'         => 'Contact CTA. See doc-helper-cta_contact_02. Final CTA.',
);
$aio_pb_section_list = '';
foreach ( $aio_pb_sections as $aio_pb_key => $aio_pb_guidance ) {
	$aio_pb_section_list .= '<li><strong>' . $aio_pb_key . '</strong>: ' . $aio_pb_guidance . '</li>';
}

return array(
	'documentation_id'          => 'doc-onepager-pt_faq_category_01',
	'documentation_type'        => 'page_template_one_pager',
	'content_body'              => '<h3>Page purpose</h3><p>FAQ page category-led. Compact hero; FAQ by category and standard FAQ; inquiry CTA; reassurance and expectations; form intro and quote CTA; value and how-it-works; contact CTA.</p>'
		. '<h3>Page flow</h3><p>Compact opener; categorized FAQ; inquiry CTA; reassurance and expectations; form and quote CTA; value and process; final contact CTA.</p>'
		. '<h3>CTA direction</h3><p>Primary path: inquiry CTA → quote request CTA → contact CTA. One primary action per CTA block; question grouping supports scannability; keep support/help distinct from pure conversion.</p>'
		. '<h3>Section-by-section (ordered)</h3><ol>' . $aio_pb_section_list . '</ol>'
		. '<h3>Page-wide notes</h3><p><strong>GeneratePress:</strong> Use for container and spacing; keep section order as defined. <strong>ACF:</strong> Map fields per section helper. <strong>AIOSEO:</strong> Set focus keyphrase and meta for FAQ. <strong>FIFU:</strong> Use for hero/section images where applicable. <strong>Hierarchy:</strong> FAQ is top-level. <strong>Mistakes to avoid:</strong> Do not stack adjacent CTAs; keep category grouping clear; ensure last section is the final CTA. <strong>Accessibility:</strong> One H1 per page; logical heading order; sufficient contrast and focus order for all CTAs.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'page_template_key' => 'pt_faq_category_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
