<?php
/**
 * Legal, policy, utility, contact, and form-support section template definitions for SEC-07 library batch (spec §12, §15, §17, §51, Prompt 152).
 * Presentational sections for privacy, terms, accessibility, contact, support, disclosure, consent, and utility flows.
 * Does not persist; callers save via Section_Template_Repository or Legal_Policy_Utility_Library_Batch_Seeder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\LegalPolicyUtilityBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Returns section definitions for the legal/policy/contact/utility library batch (SEC-07).
 * Each definition is schema-compliant with embedded field_blueprint and accessibility/form semantics (spec §51.9, §51.10).
 */
final class Legal_Policy_Utility_Library_Batch_Definitions {

	/** Batch ID per template-library-inventory (legal, policy, utility, contact scope). */
	public const BATCH_ID = 'SEC-07';

	/**
	 * Returns all legal/policy/utility batch section definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::lpu_disclosure_header_01(),
			self::lpu_policy_body_01(),
			self::lpu_legal_summary_01(),
			self::lpu_consent_note_01(),
			self::lpu_contact_panel_01(),
			self::lpu_contact_detail_01(),
			self::lpu_inquiry_support_01(),
			self::lpu_support_escalation_01(),
			self::lpu_accessibility_help_01(),
			self::lpu_utility_cta_01(),
			self::lpu_trust_disclosure_01(),
			self::lpu_form_intro_01(),
			self::lpu_privacy_highlight_01(),
			self::lpu_terms_toc_01(),
			self::lpu_footer_legal_01(),
		);
	}

	/**
	 * Returns section keys in this batch (for listing and tests).
	 *
	 * @return list<string>
	 */
	public static function section_keys(): array {
		return array(
			'lpu_disclosure_header_01',
			'lpu_policy_body_01',
			'lpu_legal_summary_01',
			'lpu_consent_note_01',
			'lpu_contact_panel_01',
			'lpu_contact_detail_01',
			'lpu_inquiry_support_01',
			'lpu_support_escalation_01',
			'lpu_accessibility_help_01',
			'lpu_utility_cta_01',
			'lpu_trust_disclosure_01',
			'lpu_form_intro_01',
			'lpu_privacy_highlight_01',
			'lpu_terms_toc_01',
			'lpu_footer_legal_01',
		);
	}

	/**
	 * Builds a legal/policy/utility section definition.
	 *
	 * @param string $key Internal key.
	 * @param string $name Display name.
	 * @param string $purpose_summary Purpose summary.
	 * @param string $category Section category (legal_disclaimer, utility_structural, form_embed, cta_conversion).
	 * @param string $purpose_family section_purpose_family (legal, policy, contact, utility, form_support).
	 * @param string $variation_family_key Variation family key.
	 * @param string $preview_desc Preview description.
	 * @param array<string, mixed> $blueprint_fields Field definitions for embedded blueprint.
	 * @param array<string, mixed> $preview_defaults Synthetic ACF defaults for preview.
	 * @param array<string, mixed> $extra Optional extra keys.
	 * @return array<string, mixed>
	 */
	private static function lpu_definition(
		string $key,
		string $name,
		string $purpose_summary,
		string $category,
		string $purpose_family,
		string $variation_family_key,
		string $preview_desc,
		array $blueprint_fields,
		array $preview_defaults,
		array $extra = array()
	): array {
		$bp_id = 'acf_blueprint_' . $key;
		$base = array(
			Section_Schema::FIELD_INTERNAL_KEY            => $key,
			Section_Schema::FIELD_NAME                    => $name,
			Section_Schema::FIELD_PURPOSE_SUMMARY         => $purpose_summary,
			Section_Schema::FIELD_CATEGORY                => $category,
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_' . $key,
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF     => $bp_id,
			Section_Schema::FIELD_HELPER_REF              => 'helper_' . $key,
			Section_Schema::FIELD_CSS_CONTRACT_REF        => 'css_' . $key,
			Section_Schema::FIELD_DEFAULT_VARIANT         => 'default',
			Section_Schema::FIELD_VARIANTS                => array(
				'default' => array( 'label' => 'Default', 'description' => '', 'css_modifiers' => array() ),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array(
				'may_precede'          => array(),
				'may_follow'           => array(),
				'avoid_adjacent'       => array(),
				'duplicate_purpose_of' => array(),
			),
			Section_Schema::FIELD_VERSION                 => array( 'version' => '1', 'stable_key_retained' => true ),
			Section_Schema::FIELD_STATUS                  => 'active',
			Section_Schema::FIELD_RENDER_MODE             => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION       => array( 'none' => true ),
			'section_purpose_family'                     => $purpose_family,
			'variation_family_key'                       => $variation_family_key,
			'preview_description'                        => $preview_desc,
			'preview_image_ref'                          => '',
			'animation_tier'                             => 'none',
			'animation_families'                         => array(),
			'preview_defaults'                           => $preview_defaults,
			'accessibility_warnings_or_enhancements'     => 'Use semantic headings and landmarks. Forms: visible labels, label-input association, required-field indication, no placeholder-only labeling (spec §51.9). Modals: keyboard open/close, focus trap, focus return (spec §51.10). Omit optional blocks when empty.',
			'seo_relevance_notes'                       => 'Legal and utility content benefits from clear headings and structure (spec §15.9).',
		);
		$base['field_blueprint'] = array(
			'blueprint_id'    => $bp_id,
			'section_key'     => $key,
			'section_version' => '1',
			'label'           => $name . ' fields',
			'description'     => 'Legal/policy/utility content fields.',
			'fields'          => $blueprint_fields,
		);
		return array_merge( $base, $extra );
	}

	/** Disclosure header: title + short notice. */
	public static function lpu_disclosure_header_01(): array {
		$key = 'lpu_disclosure_header_01';
		$fields = array(
			array( 'key' => 'field_lpu_dh_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_lpu_dh_notice', 'name' => 'notice', 'label' => 'Notice', 'type' => 'textarea', 'required' => false ),
		);
		return self::lpu_definition(
			$key,
			'Disclosure header',
			'Header band with title and short disclosure notice. For policy or consent context. Omit notice when empty.',
			'legal_disclaimer',
			'legal',
			'disclosure_header',
			'Disclosure header with title and optional notice.',
			$fields,
			array( 'title' => 'Important notice', 'notice' => 'This is sample disclosure text. Replace with your actual policy language.' ),
			array( 'short_label' => 'Disclosure header' )
		);
	}

	/** Policy body: heading + WYSIWYG body. */
	public static function lpu_policy_body_01(): array {
		$key = 'lpu_policy_body_01';
		$fields = array(
			array( 'key' => 'field_lpu_pb_heading', 'name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_pb_body', 'name' => 'body', 'label' => 'Body', 'type' => 'wysiwyg', 'required' => true ),
		);
		return self::lpu_definition(
			$key,
			'Policy body',
			'Policy or terms body with optional heading and WYSIWYG content. For privacy, terms, or accessibility policy pages.',
			'legal_disclaimer',
			'policy',
			'policy_body',
			'Policy body with optional heading and rich text.',
			$fields,
			array( 'heading' => 'Policy section', 'body' => '<p>Sample policy text. This is not legal advice. Replace with your actual policy content.</p>' ),
			array( 'short_label' => 'Policy body' )
		);
	}

	/** Legal summary: title + summary text + optional last_updated. */
	public static function lpu_legal_summary_01(): array {
		$key = 'lpu_legal_summary_01';
		$fields = array(
			array( 'key' => 'field_lpu_ls_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_lpu_ls_summary', 'name' => 'summary', 'label' => 'Summary', 'type' => 'textarea', 'required' => true ),
			array( 'key' => 'field_lpu_ls_updated', 'name' => 'last_updated', 'label' => 'Last updated', 'type' => 'text', 'required' => false ),
		);
		return self::lpu_definition(
			$key,
			'Legal summary block',
			'Short legal summary with title, summary text, and optional last-updated. Not a substitute for full policy.',
			'legal_disclaimer',
			'legal',
			'legal_summary',
			'Legal summary with title, summary, and optional date.',
			$fields,
			array( 'title' => 'Summary', 'summary' => 'This is a brief summary. See full policy for complete terms.', 'last_updated' => '' ),
			array( 'short_label' => 'Legal summary' )
		);
	}

	/** Consent note: heading + body + optional checkbox_label. */
	public static function lpu_consent_note_01(): array {
		$key = 'lpu_consent_note_01';
		$fields = array(
			array( 'key' => 'field_lpu_cn_heading', 'name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_cn_body', 'name' => 'body', 'label' => 'Body', 'type' => 'textarea', 'required' => true ),
			array( 'key' => 'field_lpu_cn_checkbox_label', 'name' => 'checkbox_label', 'label' => 'Checkbox label', 'type' => 'text', 'required' => false ),
		);
		return self::lpu_definition(
			$key,
			'Consent note',
			'Consent or acknowledgment note with optional checkbox label. Use with form-support; ensure visible labels (spec §51.9).',
			'legal_disclaimer',
			'legal',
			'consent_note',
			'Consent note with optional checkbox label.',
			$fields,
			array( 'heading' => 'Consent', 'body' => 'By continuing you agree to the terms above.', 'checkbox_label' => 'I have read and agree.' ),
			array( 'short_label' => 'Consent note' )
		);
	}

	/** Contact panel: heading + repeatable channels (label, value, type). */
	public static function lpu_contact_panel_01(): array {
		$key = 'lpu_contact_panel_01';
		$fields = array(
			array( 'key' => 'field_lpu_cp_heading', 'name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_lpu_cp_channels',
				'name'        => 'channels',
				'label'       => 'Contact channels',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_lpu_cp_label', 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_lpu_cp_value', 'name' => 'value', 'label' => 'Value (e.g. email, phone)', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_lpu_cp_type', 'name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => false, 'choices' => array( 'email' => 'Email', 'phone' => 'Phone', 'url' => 'URL', 'other' => 'Other' ) ),
				),
			),
		);
		return self::lpu_definition(
			$key,
			'Contact panel',
			'Contact panel with heading and repeatable channels (label, value, type). Semantic contact info; omit empty channels.',
			'utility_structural',
			'contact',
			'contact_panel',
			'Contact panel with channels list.',
			$fields,
			array( 'heading' => 'Contact us', 'channels' => array( array( 'label' => 'Email', 'value' => 'support@example.com', 'type' => 'email' ) ) ),
			array( 'short_label' => 'Contact panel' )
		);
	}

	/** Contact detail: structured block (title, address_line1, line2, city, region, postcode, country, phone, email). */
	public static function lpu_contact_detail_01(): array {
		$key = 'lpu_contact_detail_01';
		$fields = array(
			array( 'key' => 'field_lpu_cd_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_cd_address_line1', 'name' => 'address_line1', 'label' => 'Address line 1', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_cd_address_line2', 'name' => 'address_line2', 'label' => 'Address line 2', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_cd_city', 'name' => 'city', 'label' => 'City', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_cd_region', 'name' => 'region', 'label' => 'Region / state', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_cd_postcode', 'name' => 'postcode', 'label' => 'Postal code', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_cd_country', 'name' => 'country', 'label' => 'Country', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_cd_phone', 'name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_cd_email', 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => false ),
		);
		return self::lpu_definition(
			$key,
			'Structured contact detail',
			'Structured contact block: title and address/phone/email fields. Omit empty fields for clean output.',
			'utility_structural',
			'contact',
			'contact_detail',
			'Structured contact detail block.',
			$fields,
			array( 'title' => 'Head office', 'address_line1' => '123 Sample St', 'city' => 'Sample City', 'postcode' => 'AB1 2CD', 'country' => 'Country', 'phone' => '', 'email' => 'info@example.com' ),
			array( 'short_label' => 'Contact detail' )
		);
	}

	/** Inquiry support: heading + intro + form_embed_slot (placeholder for form shortcode/block ref). */
	public static function lpu_inquiry_support_01(): array {
		$key = 'lpu_inquiry_support_01';
		$fields = array(
			array( 'key' => 'field_lpu_is_heading', 'name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_is_intro', 'name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'required' => false ),
			array( 'key' => 'field_lpu_is_form_embed_slot', 'name' => 'form_embed_slot', 'label' => 'Form embed (shortcode or block identifier)', 'type' => 'text', 'required' => false ),
		);
		return self::lpu_definition(
			$key,
			'Inquiry support',
			'Inquiry or support section with heading, intro, and form-embed slot. Form provider supplies actual form; ensure labels and required-field indication (spec §51.9).',
			'form_embed',
			'form_support',
			'inquiry_support',
			'Inquiry support with optional form embed slot.',
			$fields,
			array( 'heading' => 'Send an inquiry', 'intro' => 'Use the form below. All fields marked required must be completed.', 'form_embed_slot' => '' ),
			array( 'short_label' => 'Inquiry support' )
		);
	}

	/** Support escalation: title + description + optional link. */
	public static function lpu_support_escalation_01(): array {
		$key = 'lpu_support_escalation_01';
		$fields = array(
			array( 'key' => 'field_lpu_se_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_lpu_se_description', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
			array( 'key' => 'field_lpu_se_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
		);
		return self::lpu_definition(
			$key,
			'Support escalation band',
			'Support or escalation band with title, description, and optional link. Omit link when empty.',
			'utility_structural',
			'utility',
			'support_escalation',
			'Support escalation band.',
			$fields,
			array( 'title' => 'Need more help?', 'description' => 'Contact support for further assistance.', 'link' => array() ),
			array( 'short_label' => 'Support escalation' )
		);
	}

	/** Accessibility help: heading + body + optional link. */
	public static function lpu_accessibility_help_01(): array {
		$key = 'lpu_accessibility_help_01';
		$fields = array(
			array( 'key' => 'field_lpu_ah_heading', 'name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_lpu_ah_body', 'name' => 'body', 'label' => 'Body', 'type' => 'textarea', 'required' => true ),
			array( 'key' => 'field_lpu_ah_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
		);
		return self::lpu_definition(
			$key,
			'Accessibility help section',
			'Accessibility help or statement section with heading, body, and optional link. Supports top-level accessibility pages.',
			'utility_structural',
			'utility',
			'accessibility_help',
			'Accessibility help section.',
			$fields,
			array( 'heading' => 'Accessibility', 'body' => 'We aim to make this site accessible. If you have difficulty, contact us.', 'link' => array() ),
			array( 'short_label' => 'Accessibility help' )
		);
	}

	/** Utility CTA: heading + text + button label + button link. */
	public static function lpu_utility_cta_01(): array {
		$key = 'lpu_utility_cta_01';
		$fields = array(
			array( 'key' => 'field_lpu_uc_heading', 'name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_uc_text', 'name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'required' => false ),
			array( 'key' => 'field_lpu_uc_button_label', 'name' => 'button_label', 'label' => 'Button label', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_uc_button_link', 'name' => 'button_link', 'label' => 'Button link', 'type' => 'link', 'required' => false ),
		);
		return self::lpu_definition(
			$key,
			'Utility CTA',
			'Utility CTA band (e.g. contact, support, back to top). CTA classification rules apply. Omit button when label/link empty.',
			'cta_conversion',
			'utility',
			'utility_cta',
			'Utility CTA with optional button.',
			$fields,
			array( 'heading' => 'Need help?', 'text' => 'Contact our team.', 'button_label' => 'Contact', 'button_link' => array() ),
			array( 'short_label' => 'Utility CTA' )
		);
	}

	/** Trust disclosure: title + body. */
	public static function lpu_trust_disclosure_01(): array {
		$key = 'lpu_trust_disclosure_01';
		$fields = array(
			array( 'key' => 'field_lpu_td_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_lpu_td_body', 'name' => 'body', 'label' => 'Body', 'type' => 'textarea', 'required' => true ),
		);
		return self::lpu_definition(
			$key,
			'Trust disclosure band',
			'Trust or disclosure band with title and body. For embedded disclosure in hub or detail contexts.',
			'legal_disclaimer',
			'legal',
			'trust_disclosure',
			'Trust disclosure band.',
			$fields,
			array( 'title' => 'Disclosure', 'body' => 'Sample disclosure text. Not legal advice.' ),
			array( 'short_label' => 'Trust disclosure' )
		);
	}

	/** Form intro: heading + body (helper text before form). */
	public static function lpu_form_intro_01(): array {
		$key = 'lpu_form_intro_01';
		$fields = array(
			array( 'key' => 'field_lpu_fi_heading', 'name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_lpu_fi_body', 'name' => 'body', 'label' => 'Body', 'type' => 'textarea', 'required' => false ),
		);
		return self::lpu_definition(
			$key,
			'Form intro',
			'Intro or helper text above a form. Accessible helper text (spec §51.9); do not use as sole labeling.',
			'form_embed',
			'form_support',
			'form_intro',
			'Form intro with optional heading and body.',
			$fields,
			array( 'heading' => 'Submit your request', 'body' => 'Complete the form below. Required fields are marked.' ),
			array( 'short_label' => 'Form intro' )
		);
	}

	/** Privacy highlight: title + short text. */
	public static function lpu_privacy_highlight_01(): array {
		$key = 'lpu_privacy_highlight_01';
		$fields = array(
			array( 'key' => 'field_lpu_ph_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_lpu_ph_text', 'name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'required' => true ),
		);
		return self::lpu_definition(
			$key,
			'Privacy highlight',
			'Short privacy highlight block for top-level or embedded use. Clearly synthetic placeholder; replace with your policy.',
			'legal_disclaimer',
			'policy',
			'privacy_highlight',
			'Privacy highlight block.',
			$fields,
			array( 'title' => 'Your privacy', 'text' => 'We process data as described in our privacy policy. This is sample text.' ),
			array( 'short_label' => 'Privacy highlight' )
		);
	}

	/** Terms TOC: heading + repeatable items (title, anchor_id). */
	public static function lpu_terms_toc_01(): array {
		$key = 'lpu_terms_toc_01';
		$fields = array(
			array( 'key' => 'field_lpu_tt_heading', 'name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_lpu_tt_items',
				'name'        => 'items',
				'label'       => 'Table of contents items',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_lpu_tt_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_lpu_tt_anchor_id', 'name' => 'anchor_id', 'label' => 'Anchor ID', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::lpu_definition(
			$key,
			'Terms / policy TOC',
			'Table of contents for terms or policy page: heading and repeatable title/anchor_id. Use semantic list and skip links.',
			'legal_disclaimer',
			'policy',
			'terms_toc',
			'Terms TOC with repeatable items.',
			$fields,
			array( 'heading' => 'Contents', 'items' => array( array( 'title' => 'Section one', 'anchor_id' => 'section-1' ), array( 'title' => 'Section two', 'anchor_id' => 'section-2' ) ) ),
			array( 'short_label' => 'Terms TOC' )
		);
	}

	/** Footer legal: single line or short text. */
	public static function lpu_footer_legal_01(): array {
		$key = 'lpu_footer_legal_01';
		$fields = array(
			array( 'key' => 'field_lpu_fl_text', 'name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'required' => true ),
		);
		return self::lpu_definition(
			$key,
			'Footer legal strip',
			'Footer legal or copyright strip. Single text field; keep short. Not legal advice.',
			'legal_disclaimer',
			'legal',
			'footer_legal',
			'Footer legal strip.',
			$fields,
			array( 'text' => '© 2025 Example. All rights reserved. Sample disclaimer.' ),
			array( 'short_label' => 'Footer legal' )
		);
	}
}
