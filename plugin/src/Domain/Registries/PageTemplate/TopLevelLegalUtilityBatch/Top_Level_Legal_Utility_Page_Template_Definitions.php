<?php
/**
 * Top-level legal, trust, policy, accessibility, and utility page template definitions (spec §13, §15.10, §51, Prompt 156).
 * Privacy, Terms, Accessibility, Support, Disclosure, Contact-utility. Each template: ~10 non-CTA + ≥3 CTA,
 * last section CTA, no adjacent CTA. Uses section library (hero_legal, lpu_*, ptf_*, tp_*, cta_*).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelLegalUtilityBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Returns page template definitions for the top-level legal/utility batch (PT-02 scope).
 * template_category_class = top_level; template_family = privacy, terms, accessibility, support, disclosure, utility.
 */
final class Top_Level_Legal_Utility_Page_Template_Definitions {

	/** Batch ID for top-level legal/utility (template-library-inventory-manifest PT-02). */
	public const BATCH_ID = 'PT-02';

	/** Industry keys for first launch verticals (page-template-industry-affinity-contract; Prompt 364). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Returns all top-level legal/utility page template definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::pt_privacy_overview_01(),
			self::pt_privacy_detail_01(),
			self::pt_terms_overview_01(),
			self::pt_terms_structure_01(),
			self::pt_accessibility_commitment_01(),
			self::pt_accessibility_help_01(),
			self::pt_support_help_01(),
			self::pt_support_escalation_01(),
			self::pt_contact_utility_01(),
			self::pt_disclosure_utility_01(),
			self::pt_trust_disclosure_01(),
			self::pt_utility_legal_01(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'pt_privacy_overview_01',
			'pt_privacy_detail_01',
			'pt_terms_overview_01',
			'pt_terms_structure_01',
			'pt_accessibility_commitment_01',
			'pt_accessibility_help_01',
			'pt_support_help_01',
			'pt_support_escalation_01',
			'pt_contact_utility_01',
			'pt_disclosure_utility_01',
			'pt_trust_disclosure_01',
			'pt_utility_legal_01',
		);
	}

	/**
	 * Builds ordered_sections and section_requirements from a list of section keys.
	 *
	 * @param list<string> $section_keys Section internal keys in order (no adjacent CTA; last must be CTA).
	 * @return array{ ordered: list<array<string, mixed>>, requirements: array<string, array{required: bool}> }
	 */
	private static function ordered_and_requirements( array $section_keys ): array {
		$ordered      = array();
		$requirements = array();
		foreach ( $section_keys as $pos => $key ) {
			$ordered[]            = array(
				Page_Template_Schema::SECTION_ITEM_KEY => $key,
				Page_Template_Schema::SECTION_ITEM_POSITION => $pos,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			);
			$requirements[ $key ] = array( 'required' => true );
		}
		return array(
			'ordered'      => $ordered,
			'requirements' => $requirements,
		);
	}

	/**
	 * Base page template shape for top-level legal/utility batch.
	 *
	 * @param string $internal_key
	 * @param string $name
	 * @param string $purpose_summary
	 * @param string $archetype
	 * @param string $template_family
	 * @param array  $ordered
	 * @param array  $section_requirements
	 * @param array  $one_pager
	 * @param string $endpoint_notes
	 * @param array  $extra
	 * @return array<string, mixed>
	 */
	private static function base_template(
		string $internal_key,
		string $name,
		string $purpose_summary,
		string $archetype,
		string $template_family,
		array $ordered,
		array $section_requirements,
		array $one_pager,
		string $endpoint_notes,
		array $extra = array()
	): array {
		$def = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => $internal_key,
			Page_Template_Schema::FIELD_NAME             => $name,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => $purpose_summary,
			Page_Template_Schema::FIELD_ARCHETYPE        => $archetype,
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $section_requirements,
			Page_Template_Schema::FIELD_COMPATIBILITY    => array(),
			Page_Template_Schema::FIELD_ONE_PAGER        => $one_pager,
			Page_Template_Schema::FIELD_VERSION          => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Page_Template_Schema::FIELD_STATUS           => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => $endpoint_notes,
			'template_category_class'                    => 'top_level',
			'template_family'                            => $template_family,
		);
		if ( ! isset( $extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] ) ) {
			$extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] = self::LAUNCH_INDUSTRIES;
		}
		return array_merge( $def, $extra );
	}

	public static function pt_privacy_overview_01(): array {
		$keys = array(
			'hero_legal_01',
			'lpu_privacy_highlight_01',
			'lpu_legal_summary_01',
			'cta_policy_utility_01',
			'lpu_policy_body_01',
			'ptf_policy_explainer_01',
			'tp_reassurance_01',
			'cta_contact_01',
			'lpu_consent_note_01',
			'lpu_trust_disclosure_01',
			'lpu_footer_legal_01',
			'cta_policy_utility_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_privacy_overview_01',
			'Privacy (overview)',
			'Privacy page overview: legal hero, privacy highlight, legal summary, policy CTA, policy body, policy explainer, reassurance, contact CTA, consent note, trust disclosure, footer legal, policy CTA. Top-level.',
			'informational_detail',
			'privacy',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Privacy page overview. Hero and privacy highlight; legal summary and policy CTA; policy body and explainer; reassurance and contact CTA; consent and trust disclosure; footer legal; close with policy CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Policy-safe semantics. Semantic headings and landmarks; form/label accessibility where applicable (spec §51.9). Softer CTA direction appropriate for utility pages.',
			),
			'Requires section library (hero, lpu, ptf, tp, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_privacy_detail_01(): array {
		$keys = array(
			'hero_compact_01',
			'lpu_disclosure_header_01',
			'lpu_privacy_highlight_01',
			'cta_policy_utility_01',
			'lpu_policy_body_01',
			'lpu_legal_summary_01',
			'ptf_faq_01',
			'cta_support_01',
			'lpu_consent_note_01',
			'lpu_terms_toc_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_privacy_detail_01',
			'Privacy (detail)',
			'Privacy page detail-led: compact hero, disclosure header, privacy highlight, policy CTA, policy body, legal summary, FAQ, support CTA, consent note, terms TOC, contact panel, contact CTA. Top-level.',
			'informational_detail',
			'privacy',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Privacy page detail-led. Compact hero and disclosure; privacy highlight and policy CTA; policy body and legal summary; FAQ and support CTA; consent and terms TOC; contact panel; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Structured policy content with accessible headings; CTAs for policy and support/contact. Synthetic preview only.',
			),
			'Requires section library (hero, lpu, ptf, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_terms_overview_01(): array {
		$keys = array(
			'hero_legal_01',
			'lpu_terms_toc_01',
			'lpu_legal_summary_01',
			'cta_policy_utility_01',
			'lpu_policy_body_01',
			'ptf_policy_explainer_01',
			'lpu_disclosure_header_01',
			'cta_contact_01',
			'tp_reassurance_01',
			'lpu_consent_note_01',
			'lpu_footer_legal_01',
			'cta_policy_utility_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_terms_overview_01',
			'Terms (overview)',
			'Terms/policy overview: legal hero, terms TOC, legal summary, policy CTA, policy body, policy explainer, disclosure header, contact CTA, reassurance, consent note, footer legal, policy CTA. Top-level.',
			'informational_detail',
			'terms',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Terms page overview. Hero and terms TOC; legal summary and policy CTA; policy body and explainer; disclosure and contact CTA; reassurance and consent; footer legal; policy CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Terms structure with TOC and policy body; appropriate utility CTAs. No legal counsel; synthetic content only.',
			),
			'Requires section library (hero, lpu, ptf, tp, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_terms_structure_01(): array {
		$keys = array(
			'hero_compact_01',
			'lpu_disclosure_header_01',
			'lpu_terms_toc_01',
			'cta_policy_utility_02',
			'lpu_policy_body_01',
			'lpu_legal_summary_01',
			'ptf_faq_01',
			'cta_support_02',
			'lpu_trust_disclosure_01',
			'lpu_contact_panel_01',
			'lpu_footer_legal_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_terms_structure_01',
			'Terms (structured)',
			'Terms page structure-led: compact hero, disclosure header, terms TOC, policy CTA, policy body, legal summary, FAQ, support CTA, trust disclosure, contact panel, footer legal, contact CTA. Top-level.',
			'informational_detail',
			'terms',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Terms page structured. Compact hero and disclosure; terms TOC and policy CTA; policy body and legal summary; FAQ and support CTA; trust disclosure and contact panel; footer legal; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Structured terms with TOC; policy and support/contact CTAs. Accessibility-compliant headings and landmarks.',
			),
			'Requires section library (hero, lpu, ptf, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_accessibility_commitment_01(): array {
		$keys = array(
			'hero_edu_01',
			'lpu_accessibility_help_01',
			'ptf_policy_explainer_01',
			'cta_support_01',
			'lpu_trust_disclosure_01',
			'tp_reassurance_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
			'lpu_support_escalation_01',
			'lpu_form_intro_01',
			'ptf_faq_01',
			'cta_policy_utility_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_accessibility_commitment_01',
			'Accessibility (commitment)',
			'Accessibility commitment page: education hero, accessibility help, policy explainer, support CTA, trust disclosure, reassurance, contact panel, contact CTA, support escalation, form intro, FAQ, policy CTA. Top-level.',
			'informational_detail',
			'accessibility',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Accessibility commitment page. Hero and accessibility help; policy explainer and support CTA; trust disclosure and reassurance; contact panel and contact CTA; support escalation and form intro; FAQ; policy CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Accessibility-first structure; semantic headings and landmarks (spec §51.3, §51.9). Softer CTAs for support and contact.',
			),
			'Requires section library (hero, lpu, ptf, tp, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_accessibility_help_01(): array {
		$keys = array(
			'hero_compact_01',
			'lpu_accessibility_help_01',
			'lpu_support_escalation_01',
			'cta_contact_02',
			'ptf_faq_01',
			'lpu_contact_panel_01',
			'lpu_inquiry_support_01',
			'cta_support_02',
			'tp_reassurance_01',
			'lpu_form_intro_01',
			'lpu_trust_disclosure_01',
			'cta_support_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_accessibility_help_01',
			'Accessibility (help)',
			'Accessibility help page: compact hero, accessibility help, support escalation, contact CTA, FAQ, contact panel, inquiry support, support CTA, reassurance, form intro, trust disclosure, support CTA. Top-level.',
			'informational_detail',
			'accessibility',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Accessibility help page. Compact hero and accessibility help; support escalation and contact CTA; FAQ and contact panel; inquiry support and support CTA; reassurance and form intro; trust disclosure; support CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Help-focused accessibility content; form and contact accessibility (spec §51.9).',
			),
			'Requires section library (hero, lpu, ptf, tp, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_support_help_01(): array {
		$keys = array(
			'hero_edu_01',
			'lpu_support_escalation_01',
			'lpu_inquiry_support_01',
			'cta_support_01',
			'ptf_faq_01',
			'lpu_contact_panel_01',
			'tp_reassurance_01',
			'cta_contact_01',
			'lpu_form_intro_01',
			'lpu_accessibility_help_01',
			'lpu_contact_detail_01',
			'cta_support_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_support_help_01',
			'Support (help)',
			'Support help page: education hero, support escalation, inquiry support, support CTA, FAQ, contact panel, reassurance, contact CTA, form intro, accessibility help, contact detail, support CTA. Top-level.',
			'request_page',
			'support',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Support help page. Hero and support escalation; inquiry support and support CTA; FAQ and contact panel; reassurance and contact CTA; form intro and accessibility help; contact detail; support CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Support and contact utility; form accessibility (spec §51.9).',
			),
			'Requires section library (hero, lpu, ptf, tp, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_support_escalation_01(): array {
		$keys = array(
			'hero_compact_01',
			'lpu_inquiry_support_01',
			'lpu_support_escalation_01',
			'cta_contact_02',
			'lpu_contact_panel_01',
			'ptf_faq_01',
			'lpu_form_intro_01',
			'cta_support_01',
			'tp_reassurance_01',
			'lpu_contact_detail_01',
			'lpu_accessibility_help_01',
			'cta_support_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_support_escalation_01',
			'Support (escalation)',
			'Support escalation page: compact hero, inquiry support, support escalation, contact CTA, contact panel, FAQ, form intro, support CTA, reassurance, contact detail, accessibility help, support CTA. Top-level.',
			'request_page',
			'support',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Support escalation page. Hero and inquiry support; support escalation and contact CTA; contact panel and FAQ; form intro and support CTA; reassurance and contact detail; accessibility help; support CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Escalation path and contact; accessible forms and labels.',
			),
			'Requires section library (hero, lpu, ptf, tp, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_contact_utility_01(): array {
		$keys = array(
			'hero_compact_01',
			'lpu_contact_panel_01',
			'lpu_contact_detail_01',
			'cta_contact_01',
			'lpu_form_intro_01',
			'lpu_inquiry_support_01',
			'tp_reassurance_01',
			'cta_support_01',
			'lpu_support_escalation_01',
			'lpu_accessibility_help_01',
			'lpu_trust_disclosure_01',
			'cta_contact_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_contact_utility_01',
			'Contact (utility)',
			'Contact utility page: compact hero, contact panel, contact detail, contact CTA, form intro, inquiry support, reassurance, support CTA, support escalation, accessibility help, trust disclosure, contact CTA. Top-level.',
			'request_page',
			'utility',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Contact utility page. Hero and contact panel/detail; contact CTA; form intro and inquiry support; reassurance and support CTA; support escalation and accessibility help; trust disclosure; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Contact-first utility; form and label accessibility.',
			),
			'Requires section library (hero, lpu, tp, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_disclosure_utility_01(): array {
		$keys = array(
			'hero_legal_01',
			'lpu_disclosure_header_01',
			'lpu_trust_disclosure_01',
			'cta_policy_utility_01',
			'lpu_legal_summary_01',
			'lpu_policy_body_01',
			'ptf_policy_explainer_01',
			'cta_contact_01',
			'lpu_consent_note_01',
			'lpu_privacy_highlight_01',
			'lpu_footer_legal_01',
			'cta_policy_utility_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_disclosure_utility_01',
			'Disclosure (utility)',
			'Disclosure utility page: legal hero, disclosure header, trust disclosure, policy CTA, legal summary, policy body, policy explainer, contact CTA, consent note, privacy highlight, footer legal, policy CTA. Top-level.',
			'informational_detail',
			'disclosure',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Disclosure utility page. Hero and disclosure header; trust disclosure and policy CTA; legal summary and policy body; policy explainer and contact CTA; consent and privacy highlight; footer legal; policy CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Disclosure and policy structure; appropriate utility CTAs. Synthetic content only.',
			),
			'Requires section library (hero, lpu, ptf, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_trust_disclosure_01(): array {
		$keys = array(
			'hero_compact_01',
			'lpu_trust_disclosure_01',
			'lpu_disclosure_header_01',
			'cta_policy_utility_02',
			'lpu_legal_summary_01',
			'tp_reassurance_01',
			'lpu_contact_panel_01',
			'cta_support_02',
			'lpu_privacy_highlight_01',
			'lpu_consent_note_01',
			'lpu_footer_legal_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_trust_disclosure_01',
			'Trust & disclosure',
			'Trust and disclosure page: compact hero, trust disclosure, disclosure header, policy CTA, legal summary, reassurance, contact panel, support CTA, privacy highlight, consent note, footer legal, contact CTA. Top-level.',
			'informational_detail',
			'disclosure',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Trust and disclosure page. Hero and trust disclosure; disclosure header and policy CTA; legal summary and reassurance; contact panel and support CTA; privacy highlight and consent; footer legal; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Trust and disclosure focus; policy and support/contact CTAs.',
			),
			'Requires section library (hero, lpu, tp, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_utility_legal_01(): array {
		$keys = array(
			'hero_compact_01',
			'lpu_terms_toc_01',
			'lpu_privacy_highlight_01',
			'cta_policy_utility_01',
			'lpu_legal_summary_01',
			'lpu_contact_panel_01',
			'ptf_faq_01',
			'cta_support_01',
			'lpu_footer_legal_01',
			'lpu_trust_disclosure_01',
			'lpu_accessibility_help_01',
			'cta_contact_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_utility_legal_01',
			'Utility (legal links)',
			'Utility legal links page: compact hero, terms TOC, privacy highlight, policy CTA, legal summary, contact panel, FAQ, support CTA, footer legal, trust disclosure, accessibility help, contact CTA. Top-level.',
			'informational_detail',
			'utility',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Utility page with legal links. Hero and terms TOC; privacy highlight and policy CTA; legal summary and contact panel; FAQ and support CTA; footer legal and trust disclosure; accessibility help; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Legal and utility hub-style; policy, support, contact CTAs.',
			),
			'Requires section library (hero, lpu, ptf, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}
}
