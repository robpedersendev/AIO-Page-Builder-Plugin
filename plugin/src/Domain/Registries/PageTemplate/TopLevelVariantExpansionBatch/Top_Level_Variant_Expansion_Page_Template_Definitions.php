<?php
/**
 * Top-level variant expansion super-batch (spec §13, §14.3, §16, Prompt 164).
 * Expands Home, About, Contact, FAQ, services, offerings, legal/utility, resource/authority families with
 * materially distinct variants: different flow, proof density, CTA distribution, media hierarchy. ~10 non-CTA + ≥3 CTA,
 * last section CTA, no adjacent CTA. Synthetic preview only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelVariantExpansionBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Top-level variant expansion (PT-11). Adds many variants to PT-01, PT-02, PT-10 families.
 * template_category_class = top_level; template_family = home, about, faq, contact, services, offerings, privacy, terms, support, utility, resource, authority, educational, comparison, buyer_guide.
 */
final class Top_Level_Variant_Expansion_Page_Template_Definitions {

	/** Batch ID for top-level variant expansion (template-library-inventory-manifest PT-11). */
	public const BATCH_ID = 'PT-11';

	/**
	 * Returns all variant expansion definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			// Home variants.
			self::pt_home_conversion_02(),
			self::pt_home_trust_02(),
			self::pt_home_edu_01(),
			self::pt_home_media_01(),
			self::pt_home_proof_heavy_01(),
			// About variants.
			self::pt_about_story_02(),
			self::pt_about_team_02(),
			self::pt_about_authority_01(),
			self::pt_about_timeline_01(),
			// FAQ variants.
			self::pt_faq_support_02(),
			self::pt_faq_category_02(),
			self::pt_faq_dense_01(),
			self::pt_faq_accordion_lead_01(),
			self::pt_faq_reassurance_02(),
			// Contact variants.
			self::pt_contact_request_02(),
			self::pt_contact_directions_02(),
			self::pt_contact_form_lead_01(),
			// Services variants.
			self::pt_services_overview_02(),
			self::pt_services_value_02(),
			// Offerings variants.
			self::pt_offerings_overview_02(),
			self::pt_offerings_compare_02(),
			// Legal/utility variants.
			self::pt_privacy_overview_02(),
			self::pt_privacy_detail_02(),
			self::pt_terms_overview_02(),
			self::pt_terms_structure_02(),
			self::pt_support_help_02(),
			self::pt_accessibility_help_02(),
			self::pt_trust_disclosure_02(),
			self::pt_contact_utility_02(),
			// Resource/educational/authority variants.
			self::pt_resource_overview_02(),
			self::pt_resource_learning_02(),
			self::pt_authority_explanatory_02(),
			self::pt_authority_editorial_02(),
			self::pt_comparison_decision_02(),
			self::pt_comparison_buyer_guide_02(),
			self::pt_faq_educational_02(),
			self::pt_buyer_guide_02(),
			self::pt_buyer_guide_compare_02(),
			self::pt_informational_landing_02(),
			self::pt_informational_landing_03(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'pt_home_conversion_02',
			'pt_home_trust_02',
			'pt_home_edu_01',
			'pt_home_media_01',
			'pt_home_proof_heavy_01',
			'pt_about_story_02',
			'pt_about_team_02',
			'pt_about_authority_01',
			'pt_about_timeline_01',
			'pt_faq_support_02',
			'pt_faq_category_02',
			'pt_faq_dense_01',
			'pt_faq_accordion_lead_01',
			'pt_faq_reassurance_02',
			'pt_contact_request_02',
			'pt_contact_directions_02',
			'pt_contact_form_lead_01',
			'pt_services_overview_02',
			'pt_services_value_02',
			'pt_offerings_overview_02',
			'pt_offerings_compare_02',
			'pt_privacy_overview_02',
			'pt_privacy_detail_02',
			'pt_terms_overview_02',
			'pt_terms_structure_02',
			'pt_support_help_02',
			'pt_accessibility_help_02',
			'pt_trust_disclosure_02',
			'pt_contact_utility_02',
			'pt_resource_overview_02',
			'pt_resource_learning_02',
			'pt_authority_explanatory_02',
			'pt_authority_editorial_02',
			'pt_comparison_decision_02',
			'pt_comparison_buyer_guide_02',
			'pt_faq_educational_02',
			'pt_buyer_guide_02',
			'pt_buyer_guide_compare_02',
			'pt_informational_landing_02',
			'pt_informational_landing_03',
		);
	}

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

	private static function base(
		string $internal_key,
		string $name,
		string $purpose_summary,
		string $archetype,
		string $template_family,
		array $ordered,
		array $section_requirements,
		array $one_pager,
		string $endpoint_notes,
		string $differentiation_notes
	): array {
		return array(
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
			'preview_metadata'                           => array( 'synthetic' => true ),
			'differentiation_notes'                      => $differentiation_notes,
			'variation_family'                           => $template_family,
		);
	}

	// ---------- Home variants ----------

	public static function pt_home_conversion_02(): array {
		$keys = array( 'hero_conv_02', 'fb_benefit_band_01', 'tp_testimonial_02', 'cta_booking_01', 'ptf_how_it_works_01', 'mlp_card_grid_01', 'fb_value_prop_01', 'cta_consultation_01', 'tp_client_logo_01', 'ptf_steps_01', 'lpu_contact_panel_01', 'cta_trust_confirm_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_home_conversion_02',
			'Home (conversion v2)',
			'Home conversion variant: hero, benefits first, testimonial, booking CTA, how-it-works, cards, value prop, consultation CTA, logos, steps, contact panel, trust CTA.',
			'landing_page',
			'home',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Conversion-led home v2. Benefits and testimonial before first CTA; process and cards; value and consultation CTA; proof and steps; panel and trust CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Benefit-first flow; earlier booking CTA; process mid-page.',
				'cta_direction_summary' => 'Booking, consultation, trust confirm.',
			),
			'Requires section library.',
			'Benefits-first flow; testimonial before first CTA; different CTA spacing than PT-01 home_conversion_01.'
		);
	}

	public static function pt_home_trust_02(): array {
		$keys = array( 'hero_cred_01', 'tp_trust_band_01', 'tp_testimonial_01', 'cta_inquiry_01', 'fb_why_choose_01', 'ptf_faq_01', 'mlp_team_grid_01', 'cta_contact_02', 'ptf_expectations_01', 'tp_partner_01', 'lpu_support_escalation_01', 'cta_support_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_home_trust_02',
			'Home (trust v2)',
			'Home trust variant: cred hero, trust band, testimonial, inquiry CTA, why choose, FAQ, team grid, contact CTA, expectations, partners, support band, support CTA.',
			'landing_page',
			'home',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Trust-led home v2. Double proof before first CTA; FAQ and team; contact and support CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Trust band and testimonial before CTA; team grid mid-page.',
				'cta_direction_summary' => 'Inquiry, contact, support.',
			),
			'Requires section library.',
			'Trust band and testimonial before first CTA; team grid; different sequence than PT-01 home_trust_01.'
		);
	}

	public static function pt_home_edu_01(): array {
		$keys = array( 'hero_edu_01', 'ptf_how_it_works_01', 'fb_value_prop_01', 'cta_consultation_01', 'ptf_faq_01', 'tp_reassurance_01', 'fb_benefit_band_01', 'cta_booking_01', 'mlp_card_grid_01', 'ptf_expectations_01', 'lpu_form_intro_01', 'cta_contact_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_home_edu_01',
			'Home (education-led)',
			'Home education-led: edu hero, how-it-works, value prop, consultation CTA, FAQ, reassurance, benefits, booking CTA, cards, expectations, form intro, contact CTA.',
			'landing_page',
			'home',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Education-first home. Process and value before CTA; FAQ and reassurance; benefits and booking CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Educational opener; FAQ early; form intro before final CTA.',
				'cta_direction_summary' => 'Consultation, booking, contact.',
			),
			'Requires section library.',
			'Education-led; FAQ and how-it-works before first CTA; form intro before contact CTA.'
		);
	}

	public static function pt_home_media_01(): array {
		$keys = array( 'hero_media_01', 'mlp_media_band_01', 'fb_value_prop_01', 'cta_contact_01', 'mlp_gallery_01', 'tp_testimonial_01', 'ptf_steps_01', 'cta_booking_01', 'fb_benefit_band_01', 'tp_client_logo_01', 'lpu_contact_panel_01', 'cta_inquiry_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_home_media_01',
			'Home (media-led)',
			'Home media-led: media hero, media band, value prop, contact CTA, gallery, testimonial, steps, booking CTA, benefits, logos, contact panel, inquiry CTA.',
			'landing_page',
			'home',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Media-first home. Gallery and media band before process; booking and inquiry CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Media hierarchy; gallery mid-page; steps after proof.',
				'cta_direction_summary' => 'Contact, booking, inquiry.',
			),
			'Requires section library.',
			'Media hero and gallery; media band early; different visual hierarchy than conversion/trust variants.'
		);
	}

	public static function pt_home_proof_heavy_01(): array {
		$keys = array( 'hero_cred_01', 'tp_testimonial_01', 'tp_client_logo_01', 'tp_trust_band_01', 'cta_consultation_01', 'fb_why_choose_01', 'tp_case_teaser_01', 'ptf_how_it_works_01', 'cta_booking_01', 'tp_rating_01', 'fb_benefit_band_01', 'lpu_contact_panel_01', 'cta_trust_confirm_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_home_proof_heavy_01',
			'Home (proof-heavy)',
			'Home proof-heavy: cred hero, testimonial, logos, trust band, consultation CTA, why choose, case teaser, how-it-works, booking CTA, rating, benefits, contact panel, trust CTA.',
			'landing_page',
			'home',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Proof-dense home. Four proof blocks before first CTA; case teaser and rating; two CTAs before final.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Maximum proof density; CTAs spaced after proof clusters.',
				'cta_direction_summary' => 'Consultation, booking, trust confirm.',
			),
			'Requires section library.',
			'Proof-heavy: testimonial, logos, trust band, case teaser, rating; CTAs after proof clusters.'
		);
	}

	// ---------- About variants ----------

	public static function pt_about_story_02(): array {
		$keys = array( 'hero_edit_01', 'ptf_timeline_01', 'tp_quote_01', 'cta_contact_01', 'fb_benefit_detail_01', 'tp_authority_01', 'ptf_steps_01', 'cta_consultation_02', 'fb_value_prop_01', 'lpu_trust_disclosure_01', 'tp_testimonial_01', 'cta_policy_utility_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_about_story_02',
			'About (story v2)',
			'About story variant: editorial hero, timeline first, quote, contact CTA, benefit detail, authority, steps, consultation CTA, value prop, trust disclosure, testimonial, utility CTA.',
			'about_page',
			'about',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Story-led about v2. Timeline before quote; benefit detail and authority; disclosure and testimonial.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Timeline-first narrative; authority and steps mid-page.',
				'cta_direction_summary' => 'Contact, consultation, policy utility.',
			),
			'Requires section library.',
			'Timeline before quote; benefit detail and authority block; different flow than PT-01 about_story_01.'
		);
	}

	public static function pt_about_team_02(): array {
		$keys = array( 'hero_cred_01', 'fb_why_choose_01', 'mlp_team_grid_01', 'cta_contact_02', 'ptf_faq_01', 'tp_partner_01', 'tp_testimonial_02', 'cta_inquiry_01', 'ptf_how_it_works_01', 'fb_differentiator_01', 'lpu_contact_panel_01', 'cta_support_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_about_team_02',
			'About (team v2)',
			'About team variant: cred hero, why choose first, team grid, contact CTA, FAQ, partners, testimonial, inquiry CTA, how-it-works, differentiator, contact panel, support CTA.',
			'about_page',
			'about',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Team-led about v2. Why-choose before team; FAQ and partners; inquiry and support CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Why-choose then team; FAQ mid-page; differentiator before final CTA.',
				'cta_direction_summary' => 'Contact, inquiry, support.',
			),
			'Requires section library.',
			'Why-choose before team grid; FAQ and partners block; different CTA order than PT-01 about_team_01.'
		);
	}

	public static function pt_about_authority_01(): array {
		$keys = array( 'hero_cred_01', 'tp_authority_01', 'tp_credential_01', 'cta_contact_01', 'ptf_timeline_01', 'fb_value_prop_01', 'tp_quote_01', 'cta_consultation_01', 'fb_differentiator_01', 'lpu_trust_disclosure_01', 'ptf_steps_01', 'cta_trust_confirm_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_about_authority_01',
			'About (authority-led)',
			'About authority-led: cred hero, authority band, credentials, contact CTA, timeline, value prop, quote, consultation CTA, differentiator, trust disclosure, steps, trust CTA.',
			'about_page',
			'about',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Authority-first about. Credentials and authority before CTA; quote and differentiator; trust CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Authority and credentials early; timeline and quote; disclosure before final CTA.',
				'cta_direction_summary' => 'Contact, consultation, trust confirm.',
			),
			'Requires section library.',
			'Authority and credentials block; quote mid-page; trust disclosure and steps.'
		);
	}

	public static function pt_about_timeline_01(): array {
		$keys = array( 'hero_edit_01', 'ptf_timeline_01', 'fb_benefit_detail_01', 'cta_inquiry_01', 'tp_quote_01', 'ptf_steps_01', 'tp_authority_01', 'cta_contact_02', 'fb_value_prop_01', 'lpu_trust_disclosure_01', 'tp_testimonial_01', 'cta_support_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_about_timeline_01',
			'About (timeline-led)',
			'About timeline-led: editorial hero, timeline first, benefit detail, inquiry CTA, quote, steps, authority, contact CTA, value prop, trust disclosure, testimonial, support CTA.',
			'about_page',
			'about',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Timeline-first about. Timeline and benefit detail before CTA; steps and authority; support CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Timeline opens narrative; steps and authority cluster; disclosure and testimonial.',
				'cta_direction_summary' => 'Inquiry, contact, support.',
			),
			'Requires section library.',
			'Timeline opens; steps and authority block; inquiry then contact then support CTAs.'
		);
	}

	// ---------- FAQ variants ----------

	public static function pt_faq_support_02(): array {
		$keys = array( 'hero_edu_01', 'ptf_faq_by_category_01', 'ptf_faq_01', 'cta_support_01', 'fb_resource_explainer_01', 'tp_faq_microproof_01', 'lpu_support_escalation_01', 'cta_contact_01', 'ptf_faq_accordion_01', 'tp_reassurance_01', 'mlp_related_content_01', 'cta_inquiry_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_faq_support_02',
			'FAQ (support v2)',
			'FAQ support variant: edu hero, FAQ by category, FAQ block, support CTA, explainer, microproof, escalation, contact CTA, accordion FAQ, reassurance, related content, inquiry CTA.',
			'faq_page',
			'faq',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Support FAQ v2. FAQ by category first; explainer and microproof; accordion and related content.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Categorized FAQ then standard; escalation and contact CTA; accordion and related.',
				'cta_direction_summary' => 'Support, contact, inquiry.',
			),
			'Requires section library.',
			'FAQ by category then standard FAQ; accordion late; different order than PT-01 faq_support_01.'
		);
	}

	public static function pt_faq_category_02(): array {
		$keys = array( 'hero_compact_01', 'ptf_faq_01', 'ptf_faq_by_category_01', 'cta_inquiry_01', 'tp_reassurance_01', 'fb_value_prop_01', 'lpu_form_intro_01', 'cta_quote_request_02', 'ptf_expectations_01', 'ptf_how_it_works_01', 'tp_trust_band_01', 'cta_contact_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_faq_category_02',
			'FAQ (by category v2)',
			'FAQ category variant: compact hero, standard FAQ first, FAQ by category, inquiry CTA, reassurance, value prop, form intro, quote CTA, expectations, how-it-works, trust band, contact CTA.',
			'faq_page',
			'faq',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Category FAQ v2. Standard FAQ before categorized; form intro and quote CTA; expectations and how-it-works.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'FAQ order reversed vs category_01; form and quote CTA; process before final CTA.',
				'cta_direction_summary' => 'Inquiry, quote request, contact.',
			),
			'Requires section library.',
			'Standard FAQ before by-category; form intro before quote CTA; different sequence than PT-01 faq_category_01.'
		);
	}

	public static function pt_faq_dense_01(): array {
		$keys = array( 'hero_edu_01', 'ptf_faq_01', 'ptf_faq_accordion_01', 'ptf_faq_by_category_01', 'cta_support_01', 'fb_resource_explainer_01', 'tp_faq_microproof_01', 'ptf_policy_explainer_01', 'cta_contact_01', 'lpu_support_escalation_01', 'mlp_related_content_01', 'cta_support_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_faq_dense_01',
			'FAQ (dense)',
			'FAQ dense: edu hero, three FAQ blocks (standard, accordion, by category), support CTA, explainer, microproof, policy explainer, contact CTA, escalation, related content, support CTA.',
			'faq_page',
			'faq',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Maximum FAQ density. Three FAQ blocks before first CTA; explainers and microproof; support and contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'FAQ-heavy; explainers mid-page; two support CTAs.',
				'cta_direction_summary' => 'Support, contact, support.',
			),
			'Requires section library.',
			'Three FAQ blocks before first CTA; policy explainer; high information density.'
		);
	}

	public static function pt_faq_accordion_lead_01(): array {
		$keys = array( 'hero_compact_01', 'ptf_faq_accordion_01', 'ptf_faq_01', 'cta_support_01', 'tp_reassurance_01', 'ptf_faq_by_category_01', 'fb_value_prop_01', 'cta_contact_02', 'lpu_form_intro_01', 'ptf_expectations_01', 'tp_trust_band_01', 'cta_inquiry_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_faq_accordion_lead_01',
			'FAQ (accordion-led)',
			'FAQ accordion-led: compact hero, accordion FAQ first, standard FAQ, support CTA, reassurance, FAQ by category, value prop, contact CTA, form intro, expectations, trust band, inquiry CTA.',
			'faq_page',
			'faq',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Accordion-first FAQ. Accordion then standard FAQ; by-category and value; inquiry CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Accordion leads; categorized FAQ mid-page; form intro before final CTA.',
				'cta_direction_summary' => 'Support, contact, inquiry.',
			),
			'Requires section library.',
			'Accordion FAQ first; different information hierarchy; form intro before inquiry CTA.'
		);
	}

	public static function pt_faq_reassurance_02(): array {
		$keys = array( 'hero_cred_01', 'tp_reassurance_01', 'ptf_faq_01', 'cta_booking_01', 'tp_trust_band_01', 'fb_why_choose_01', 'ptf_faq_accordion_01', 'cta_contact_01', 'ptf_expectations_01', 'lpu_form_intro_01', 'tp_testimonial_01', 'cta_support_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_faq_reassurance_02',
			'FAQ (reassurance v2)',
			'FAQ reassurance variant: cred hero, reassurance first, FAQ, booking CTA, trust band, why choose, accordion FAQ, contact CTA, expectations, form intro, testimonial, support CTA.',
			'faq_page',
			'faq',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Reassurance-first FAQ v2. Reassurance before FAQ; trust and why-choose; testimonial before support CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Reassurance and trust early; accordion FAQ; form and testimonial.',
				'cta_direction_summary' => 'Booking, contact, support.',
			),
			'Requires section library.',
			'Reassurance and trust band before FAQ; why-choose and accordion; different flow than PT-10 faq_reassurance_01.'
		);
	}

	// ---------- Contact variants ----------

	public static function pt_contact_request_02(): array {
		$keys = array( 'hero_conv_01', 'lpu_form_intro_01', 'lpu_contact_panel_01', 'cta_contact_02', 'lpu_contact_detail_01', 'tp_reassurance_01', 'lpu_inquiry_support_01', 'cta_quote_request_01', 'fb_value_prop_01', 'lpu_support_escalation_01', 'ptf_expectations_01', 'cta_support_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_contact_request_02',
			'Contact (request v2)',
			'Contact request variant: hero, form intro first, contact panel, contact CTA, contact detail, reassurance, inquiry support, quote CTA, value prop, support band, expectations, support CTA.',
			'request_page',
			'contact',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Request contact v2. Form intro before panel; contact detail and reassurance; quote and support CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Form-first flow; detail and inquiry support; value prop before final CTA.',
				'cta_direction_summary' => 'Contact, quote request, support.',
			),
			'Requires section library.',
			'Form intro before contact panel; different CTA order than PT-01 contact_request_01.'
		);
	}

	public static function pt_contact_directions_02(): array {
		$keys = array( 'hero_local_01', 'mlp_location_info_01', 'lpu_contact_detail_01', 'cta_local_action_01', 'lpu_contact_panel_01', 'fb_local_value_01', 'tp_trust_band_01', 'cta_contact_01', 'lpu_accessibility_help_01', 'ptf_expectations_01', 'mlp_place_highlight_01', 'cta_local_action_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_contact_directions_02',
			'Contact (directions v2)',
			'Contact directions variant: local hero, location info first, contact detail, local CTA, contact panel, local value, trust band, contact CTA, accessibility, expectations, place highlight, local CTA.',
			'request_page',
			'contact',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Directions contact v2. Location info before contact detail; local value and place highlight.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Location-first; local value and place highlight; accessibility mid-page.',
				'cta_direction_summary' => 'Local action, contact, local action.',
			),
			'Requires section library.',
			'Location info before contact detail; place highlight; different sequence than PT-01 contact_directions_01.'
		);
	}

	public static function pt_contact_form_lead_01(): array {
		$keys = array( 'hero_conv_02', 'lpu_form_intro_01', 'fb_value_prop_01', 'cta_quote_request_01', 'lpu_contact_panel_01', 'tp_reassurance_01', 'lpu_inquiry_support_01', 'cta_contact_01', 'ptf_expectations_01', 'lpu_contact_detail_01', 'lpu_support_escalation_01', 'cta_support_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_contact_form_lead_01',
			'Contact (form-led)',
			'Contact form-led: hero, form intro first, value prop, quote CTA, contact panel, reassurance, inquiry support, contact CTA, expectations, contact detail, support band, support CTA.',
			'request_page',
			'contact',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Form-first contact. Form intro and value before CTA; panel and inquiry; contact and support CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Form-led flow; value prop early; expectations and detail before support CTA.',
				'cta_direction_summary' => 'Quote request, contact, support.',
			),
			'Requires section library.',
			'Form intro and value prop before first CTA; form-led conversion path.'
		);
	}

	// ---------- Services variants ----------

	public static function pt_services_overview_02(): array {
		$keys = array( 'hero_cred_01', 'ptf_service_flow_01', 'fb_service_offering_01', 'cta_service_detail_01', 'tp_testimonial_01', 'mlp_card_grid_01', 'fb_benefit_band_01', 'cta_consultation_01', 'ptf_how_it_works_01', 'tp_trust_band_01', 'lpu_contact_panel_01', 'cta_booking_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_services_overview_02',
			'Services overview v2',
			'Services overview variant: cred hero, service flow first, service offering, service CTA, testimonial, cards, benefit band, consultation CTA, how-it-works, trust band, contact panel, booking CTA.',
			'hub_page',
			'services',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Services overview v2. Flow before offering; testimonial before cards; same CTAs, different order.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Process-first; proof and benefits; panel and booking CTA.',
				'cta_direction_summary' => 'Service detail, consultation, booking.',
			),
			'Requires section library.',
			'Service flow before offering; testimonial before card grid; different section order than PT-01 services_overview_01.'
		);
	}

	public static function pt_services_value_02(): array {
		$keys = array( 'hero_conv_01', 'fb_why_choose_01', 'ptf_steps_01', 'cta_quote_request_01', 'tp_case_teaser_01', 'mlp_listing_01', 'fb_differentiator_01', 'cta_service_detail_02', 'ptf_expectations_01', 'tp_client_logo_01', 'lpu_support_escalation_01', 'cta_contact_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_services_value_02',
			'Services (value v2)',
			'Services value variant: conv hero, why choose first, steps, quote CTA, case teaser, listing, differentiator, service CTA, expectations, logos, support band, contact CTA.',
			'hub_page',
			'services',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Services value v2. Why-choose and steps before CTA; case teaser and listing; differentiator and service CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Value-first; case and listing; expectations and logos.',
				'cta_direction_summary' => 'Quote request, service detail, contact.',
			),
			'Requires section library.',
			'Why-choose and steps first; case teaser and listing block; different sequence than PT-01 services_value_01.'
		);
	}

	// ---------- Offerings variants ----------

	public static function pt_offerings_overview_02(): array {
		$keys = array( 'hero_prod_01', 'fb_offer_compare_01', 'fb_package_summary_01', 'cta_purchase_01', 'mlp_product_cards_01', 'ptf_buying_process_01', 'tp_guarantee_01', 'cta_product_detail_01', 'fb_benefit_detail_01', 'tp_testimonial_02', 'lpu_consent_note_01', 'cta_purchase_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_offerings_overview_02',
			'Offerings overview v2',
			'Offerings overview variant: product hero, offer compare first, package summary, purchase CTA, product cards, buying process, guarantee, product CTA, benefit detail, testimonial, consent, purchase CTA.',
			'hub_page',
			'offerings',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Offerings overview v2. Offer compare before packages; buying process and guarantee before product CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Compare-first; process and guarantee; benefit and testimonial.',
				'cta_direction_summary' => 'Purchase, product detail, purchase.',
			),
			'Requires section library.',
			'Offer compare before package summary; buying process and guarantee block; different order than PT-01 offerings_overview_01.'
		);
	}

	public static function pt_offerings_compare_02(): array {
		$keys = array( 'hero_compact_01', 'mlp_comparison_cards_01', 'fb_offer_compare_01', 'cta_compare_next_01', 'ptf_comparison_steps_01', 'tp_rating_01', 'fb_differentiator_01', 'cta_product_detail_01', 'ptf_buying_process_01', 'fb_offer_highlight_01', 'lpu_utility_cta_01', 'cta_quote_request_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_offerings_compare_02',
			'Offerings (compare v2)',
			'Offerings compare variant: compact hero, comparison cards first, offer compare, compare CTA, comparison steps, rating, differentiator, product CTA, buying process, offer highlight, utility CTA, quote CTA.',
			'hub_page',
			'offerings',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Offerings compare v2. Comparison cards before offer compare; rating and differentiator; quote CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Cards-first comparison; steps and rating; process and highlight.',
				'cta_direction_summary' => 'Compare next, product detail, quote request.',
			),
			'Requires section library.',
			'Comparison cards before offer compare; rating block; different flow than PT-01 offerings_compare_01.'
		);
	}

	// ---------- Legal/utility variants ----------

	public static function pt_privacy_overview_02(): array {
		$keys = array( 'hero_compact_01', 'lpu_legal_summary_01', 'lpu_privacy_highlight_01', 'cta_policy_utility_01', 'lpu_policy_body_01', 'ptf_policy_explainer_01', 'tp_reassurance_01', 'cta_contact_01', 'lpu_consent_note_01', 'lpu_trust_disclosure_01', 'lpu_footer_legal_01', 'cta_policy_utility_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_privacy_overview_02',
			'Privacy (overview v2)',
			'Privacy overview variant: compact hero, legal summary first, privacy highlight, policy CTA, policy body, explainer, reassurance, contact CTA, consent, trust disclosure, footer legal, policy CTA.',
			'informational_detail',
			'privacy',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Privacy overview v2. Legal summary before highlight; reassurance mid-page; same CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Summary-first; policy body and explainer; consent and disclosure.',
				'cta_direction_summary' => 'Policy utility, contact, policy utility.',
			),
			'Requires section library.',
			'Legal summary before privacy highlight; compact hero; different order than PT-02 privacy_overview_01.'
		);
	}

	public static function pt_privacy_detail_02(): array {
		$keys = array( 'hero_legal_01', 'lpu_disclosure_header_01', 'lpu_policy_body_01', 'cta_policy_utility_01', 'lpu_privacy_highlight_01', 'ptf_faq_01', 'lpu_legal_summary_01', 'cta_support_01', 'lpu_consent_note_01', 'lpu_terms_toc_01', 'lpu_contact_panel_01', 'cta_contact_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_privacy_detail_02',
			'Privacy (detail v2)',
			'Privacy detail variant: legal hero, disclosure header, policy body, policy CTA, privacy highlight, FAQ, legal summary, support CTA, consent, terms TOC, contact panel, contact CTA.',
			'informational_detail',
			'privacy',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Privacy detail v2. Policy body before highlight; FAQ and summary; support and contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Body-first detail flow; FAQ mid-page; TOC and panel.',
				'cta_direction_summary' => 'Policy utility, support, contact.',
			),
			'Requires section library.',
			'Policy body before highlight; FAQ in middle; different sequence than PT-02 privacy_detail_01.'
		);
	}

	public static function pt_terms_overview_02(): array {
		$keys = array( 'hero_compact_01', 'lpu_terms_toc_01', 'lpu_policy_body_01', 'cta_policy_utility_02', 'lpu_legal_summary_01', 'ptf_policy_explainer_01', 'lpu_disclosure_header_01', 'cta_contact_01', 'tp_reassurance_01', 'lpu_consent_note_01', 'lpu_footer_legal_01', 'cta_policy_utility_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_terms_overview_02',
			'Terms (overview v2)',
			'Terms overview variant: compact hero, terms TOC, policy body, policy CTA, legal summary, explainer, disclosure, contact CTA, reassurance, consent, footer legal, policy CTA.',
			'informational_detail',
			'terms',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Terms overview v2. TOC and policy body before summary; disclosure and contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'TOC and body first; explainer and disclosure; policy CTA close.',
				'cta_direction_summary' => 'Policy utility, contact, policy utility.',
			),
			'Requires section library.',
			'TOC and policy body before legal summary; compact hero; different flow than PT-02 terms_overview_01.'
		);
	}

	public static function pt_terms_structure_02(): array {
		$keys = array( 'hero_legal_01', 'lpu_disclosure_header_01', 'lpu_terms_toc_01', 'cta_policy_utility_02', 'lpu_policy_body_01', 'lpu_legal_summary_01', 'ptf_faq_01', 'cta_support_02', 'lpu_trust_disclosure_01', 'lpu_contact_panel_01', 'lpu_footer_legal_01', 'cta_contact_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_terms_structure_02',
			'Terms (structure v2)',
			'Terms structure variant: legal hero, disclosure, terms TOC, policy CTA, policy body, legal summary, FAQ, support CTA, trust disclosure, contact panel, footer legal, contact CTA.',
			'informational_detail',
			'terms',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Terms structure v2. Disclosure and TOC first; FAQ and support CTA; trust disclosure and panel.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Structure-first; FAQ mid-page; disclosure and panel before contact CTA.',
				'cta_direction_summary' => 'Policy utility, support, contact.',
			),
			'Requires section library.',
			'Legal hero; disclosure and TOC first; different order than PT-02 terms_structure_01.'
		);
	}

	public static function pt_support_help_02(): array {
		$keys = array( 'hero_compact_01', 'lpu_support_escalation_01', 'ptf_faq_01', 'cta_support_01', 'lpu_contact_panel_01', 'tp_reassurance_01', 'lpu_inquiry_support_01', 'cta_contact_01', 'ptf_policy_explainer_01', 'lpu_accessibility_help_01', 'fb_resource_explainer_01', 'cta_support_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_support_help_02',
			'Support (help v2)',
			'Support help variant: compact hero, support escalation first, FAQ, support CTA, contact panel, reassurance, inquiry support, contact CTA, policy explainer, accessibility, resource explainer, support CTA.',
			'informational_detail',
			'support',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Support help v2. Escalation and FAQ before CTA; policy and accessibility explainers; support and contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Escalation-first; panel and inquiry; explainers before final CTA.',
				'cta_direction_summary' => 'Support, contact, support.',
			),
			'Requires section library.',
			'Support escalation first; policy and accessibility explainers; different flow than PT-02 support_help_01.'
		);
	}

	public static function pt_accessibility_help_02(): array {
		$keys = array( 'hero_legal_01', 'lpu_accessibility_help_01', 'ptf_faq_01', 'cta_contact_01', 'tp_reassurance_01', 'lpu_support_escalation_01', 'lpu_contact_panel_01', 'cta_support_01', 'ptf_policy_explainer_01', 'lpu_trust_disclosure_01', 'fb_resource_explainer_01', 'cta_policy_utility_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_accessibility_help_02',
			'Accessibility (help v2)',
			'Accessibility help variant: legal hero, accessibility help first, FAQ, contact CTA, reassurance, support escalation, contact panel, support CTA, policy explainer, trust disclosure, resource explainer, policy CTA.',
			'informational_detail',
			'accessibility',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Accessibility help v2. Accessibility and FAQ before CTA; escalation and panel; policy CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Accessibility content first; support and panel; explainers and policy CTA.',
				'cta_direction_summary' => 'Contact, support, policy utility.',
			),
			'Requires section library.',
			'Accessibility block first; support escalation and panel; different sequence than PT-02 accessibility_help_01.'
		);
	}

	public static function pt_trust_disclosure_02(): array {
		$keys = array( 'hero_compact_01', 'lpu_trust_disclosure_01', 'lpu_legal_summary_01', 'cta_policy_utility_01', 'tp_reassurance_01', 'lpu_disclosure_header_01', 'ptf_policy_explainer_01', 'cta_contact_01', 'lpu_consent_note_01', 'lpu_footer_legal_01', 'lpu_contact_panel_01', 'cta_support_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_trust_disclosure_02',
			'Trust disclosure v2',
			'Trust disclosure variant: compact hero, trust disclosure first, legal summary, policy CTA, reassurance, disclosure header, policy explainer, contact CTA, consent, footer legal, contact panel, support CTA.',
			'informational_detail',
			'disclosure',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Trust disclosure v2. Trust disclosure and legal summary before CTA; disclosure header and explainer.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Trust disclosure leads; reassurance and disclosure; consent and footer.',
				'cta_direction_summary' => 'Policy utility, contact, support.',
			),
			'Requires section library.',
			'Trust disclosure first; disclosure header and policy explainer; different order than PT-02 trust_disclosure_01.'
		);
	}

	public static function pt_contact_utility_02(): array {
		$keys = array( 'hero_compact_01', 'lpu_contact_detail_01', 'lpu_contact_panel_01', 'cta_contact_02', 'lpu_form_intro_01', 'lpu_inquiry_support_01', 'tp_reassurance_01', 'cta_quote_request_01', 'lpu_support_escalation_01', 'fb_value_prop_01', 'ptf_expectations_01', 'cta_support_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_contact_utility_02',
			'Contact utility v2',
			'Contact utility variant: compact hero, contact detail first, contact panel, contact CTA, form intro, inquiry support, reassurance, quote CTA, support escalation, value prop, expectations, support CTA.',
			'informational_detail',
			'utility',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Contact utility v2. Contact detail and panel before CTA; form and inquiry; quote and support CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Detail and panel first; form and inquiry; value and expectations.',
				'cta_direction_summary' => 'Contact, quote request, support.',
			),
			'Requires section library.',
			'Contact detail and panel first; form intro and inquiry support block; different flow than PT-02 contact_utility_01.'
		);
	}

	// ---------- Resource/educational/authority variants ----------

	public static function pt_resource_overview_02(): array {
		$keys = array( 'hero_edu_01', 'mlp_card_grid_01', 'fb_resource_explainer_01', 'cta_service_detail_01', 'ptf_how_it_works_01', 'tp_trust_band_01', 'mlp_related_content_01', 'cta_inquiry_01', 'ptf_faq_01', 'fb_value_prop_01', 'lpu_support_escalation_01', 'cta_contact_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_resource_overview_02',
			'Resource overview v2',
			'Resource overview variant: edu hero, card grid first, resource explainer, service CTA, how-it-works, trust band, related content, inquiry CTA, FAQ, value prop, support band, contact CTA.',
			'landing_page',
			'resource',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Resource overview v2. Cards before explainer; related content and FAQ; inquiry and contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Cards-first; related content mid-page; FAQ and value.',
				'cta_direction_summary' => 'Service detail, inquiry, contact.',
			),
			'Requires section library.',
			'Card grid before explainer; related content block; different sequence than PT-10 resource_overview_01.'
		);
	}

	public static function pt_resource_learning_02(): array {
		$keys = array( 'hero_edu_01', 'ptf_faq_01', 'ptf_steps_01', 'cta_consultation_01', 'fb_benefit_band_01', 'tp_reassurance_01', 'fb_resource_explainer_01', 'cta_booking_01', 'ptf_expectations_01', 'mlp_card_grid_01', 'lpu_form_intro_01', 'cta_quote_request_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_resource_learning_02',
			'Resource (learning v2)',
			'Resource learning variant: edu hero, FAQ first, steps, consultation CTA, benefits, reassurance, explainer, booking CTA, expectations, cards, form intro, quote CTA.',
			'landing_page',
			'educational',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Resource learning v2. FAQ before steps; benefits and reassurance; form intro before quote CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'FAQ-first learning; explainer and booking CTA; expectations and cards.',
				'cta_direction_summary' => 'Consultation, booking, quote request.',
			),
			'Requires section library.',
			'FAQ before steps; benefits and reassurance block; different flow than PT-10 resource_learning_01.'
		);
	}

	public static function pt_authority_explanatory_02(): array {
		$keys = array( 'hero_cred_01', 'fb_value_prop_01', 'tp_authority_01', 'cta_contact_01', 'ptf_timeline_01', 'tp_quote_01', 'fb_differentiator_01', 'cta_consultation_02', 'ptf_policy_explainer_01', 'tp_credential_01', 'lpu_trust_disclosure_01', 'cta_trust_confirm_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_authority_explanatory_02',
			'Authority (explanatory v2)',
			'Authority explanatory variant: cred hero, value prop first, authority, contact CTA, timeline, quote, differentiator, consultation CTA, policy explainer, credentials, trust disclosure, trust CTA.',
			'about_page',
			'authority',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Authority explanatory v2. Value prop before authority; timeline and quote; credentials and disclosure.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Value-first; timeline and quote; policy explainer and credentials.',
				'cta_direction_summary' => 'Contact, consultation, trust confirm.',
			),
			'Requires section library.',
			'Value prop before authority; credentials block; different order than PT-10 authority_explanatory_01.'
		);
	}

	public static function pt_authority_editorial_02(): array {
		$keys = array( 'hero_edit_01', 'fb_benefit_detail_01', 'tp_quote_01', 'cta_inquiry_01', 'ptf_timeline_01', 'tp_authority_01', 'ptf_how_it_works_01', 'cta_contact_02', 'lpu_trust_disclosure_01', 'fb_value_prop_01', 'tp_testimonial_01', 'cta_support_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_authority_editorial_02',
			'Authority (editorial v2)',
			'Authority editorial variant: editorial hero, benefit detail first, quote, inquiry CTA, timeline, authority, how-it-works, contact CTA, trust disclosure, value prop, testimonial, support CTA.',
			'about_page',
			'authority',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Authority editorial v2. Benefit detail before quote; how-it-works and disclosure; support CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Benefit-first editorial; timeline and authority; disclosure and value.',
				'cta_direction_summary' => 'Inquiry, contact, support.',
			),
			'Requires section library.',
			'Benefit detail before quote; how-it-works mid-page; different sequence than PT-10 authority_editorial_01.'
		);
	}

	public static function pt_comparison_decision_02(): array {
		$keys = array( 'hero_compact_01', 'fb_why_choose_01', 'fb_offer_compare_01', 'cta_product_detail_01', 'ptf_comparison_steps_01', 'tp_reassurance_01', 'mlp_comparison_cards_01', 'cta_compare_next_01', 'ptf_faq_01', 'tp_trust_band_01', 'fb_differentiator_01', 'cta_consultation_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_comparison_decision_02',
			'Comparison (decision v2)',
			'Comparison decision variant: compact hero, why-choose first, offer compare, product CTA, comparison steps, reassurance, comparison cards, compare-next CTA, FAQ, trust band, differentiator, consultation CTA.',
			'comparison_page',
			'comparison',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Comparison decision v2. Why-choose before compare; steps and cards; FAQ and trust; consultation CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Why-choose leads; comparison steps and cards; FAQ before final CTA.',
				'cta_direction_summary' => 'Product detail, compare next, consultation.',
			),
			'Requires section library.',
			'Why-choose before offer compare; FAQ block; different flow than PT-10 comparison_decision_01.'
		);
	}

	public static function pt_comparison_buyer_guide_02(): array {
		$keys = array( 'hero_edu_01', 'ptf_faq_by_category_01', 'ptf_buying_process_01', 'cta_inquiry_01', 'fb_offer_compare_01', 'tp_faq_microproof_01', 'fb_value_prop_01', 'cta_quote_request_01', 'ptf_expectations_01', 'mlp_comparison_cards_01', 'lpu_support_escalation_01', 'cta_contact_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_comparison_buyer_guide_02',
			'Comparison (buyer guide v2)',
			'Comparison buyer guide variant: edu hero, FAQ by category first, buying process, inquiry CTA, offer compare, microproof, value prop, quote CTA, expectations, comparison cards, support band, contact CTA.',
			'comparison_page',
			'buyer_guide',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Comparison buyer guide v2. FAQ by category before process; offer compare and microproof; comparison cards.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'FAQ by category leads; compare and microproof; cards and support.',
				'cta_direction_summary' => 'Inquiry, quote request, contact.',
			),
			'Requires section library.',
			'FAQ by category first; comparison cards late; different order than PT-10 comparison_buyer_guide_01.'
		);
	}

	public static function pt_faq_educational_02(): array {
		$keys = array( 'hero_edu_01', 'ptf_faq_01', 'ptf_faq_by_category_01', 'cta_support_01', 'fb_resource_explainer_01', 'tp_reassurance_01', 'ptf_faq_accordion_01', 'cta_contact_01', 'ptf_policy_explainer_01', 'lpu_support_escalation_01', 'mlp_related_content_01', 'cta_inquiry_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_faq_educational_02',
			'FAQ (educational v2)',
			'FAQ educational variant: edu hero, standard FAQ first, FAQ by category, support CTA, explainer, reassurance, accordion FAQ, contact CTA, policy explainer, escalation, related content, inquiry CTA.',
			'faq_page',
			'faq',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'FAQ educational v2. Standard and by-category before CTA; accordion and policy explainer; inquiry CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Two FAQ blocks first; explainer and reassurance; accordion and policy.',
				'cta_direction_summary' => 'Support, contact, inquiry.',
			),
			'Requires section library.',
			'Standard FAQ before by-category; accordion and policy explainer; different sequence than PT-10 faq_educational_01.'
		);
	}

	public static function pt_buyer_guide_02(): array {
		$keys = array( 'hero_edu_01', 'fb_why_choose_01', 'ptf_buying_process_01', 'cta_consultation_01', 'ptf_faq_01', 'tp_guarantee_01', 'fb_offer_highlight_01', 'cta_quote_request_01', 'ptf_how_it_works_01', 'mlp_card_grid_01', 'tp_reassurance_01', 'cta_contact_01' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_buyer_guide_02',
			'Buyer guide v2',
			'Buyer guide variant: edu hero, why-choose first, buying process, consultation CTA, FAQ, guarantee, offer highlight, quote CTA, how-it-works, cards, reassurance, contact CTA.',
			'landing_page',
			'buyer_guide',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Buyer guide v2. Why-choose before process; guarantee and offer highlight; how-it-works and cards.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Why-choose leads; FAQ and guarantee; offer highlight and quote CTA.',
				'cta_direction_summary' => 'Consultation, quote request, contact.',
			),
			'Requires section library.',
			'Why-choose before buying process; offer highlight block; different flow than PT-10 buyer_guide_01.'
		);
	}

	public static function pt_buyer_guide_compare_02(): array {
		$keys = array( 'hero_compact_01', 'ptf_comparison_steps_01', 'fb_offer_compare_01', 'cta_product_detail_01', 'ptf_faq_by_category_01', 'fb_differentiator_01', 'tp_rating_01', 'cta_inquiry_01', 'ptf_expectations_01', 'mlp_comparison_cards_01', 'lpu_trust_disclosure_01', 'cta_consultation_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_buyer_guide_compare_02',
			'Buyer guide (compare v2)',
			'Buyer guide compare variant: compact hero, comparison steps first, offer compare, product CTA, FAQ by category, differentiator, rating, inquiry CTA, expectations, comparison cards, trust disclosure, consultation CTA.',
			'comparison_page',
			'buyer_guide',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Buyer guide compare v2. Comparison steps before offer compare; FAQ by category and rating; consultation CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Steps-first; FAQ by category and differentiator; cards and disclosure.',
				'cta_direction_summary' => 'Product detail, inquiry, consultation.',
			),
			'Requires section library.',
			'Comparison steps first; FAQ by category and rating; different order than PT-10 buyer_guide_compare_01.'
		);
	}

	public static function pt_informational_landing_02(): array {
		$keys = array( 'hero_edu_01', 'ptf_how_it_works_01', 'fb_value_prop_01', 'cta_contact_01', 'tp_trust_band_01', 'ptf_faq_01', 'fb_benefit_band_01', 'cta_booking_01', 'mlp_card_grid_01', 'tp_testimonial_01', 'lpu_contact_panel_01', 'cta_inquiry_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_informational_landing_02',
			'Informational landing v2',
			'Informational landing variant: edu hero, how-it-works first, value prop, contact CTA, trust band, FAQ, benefits, booking CTA, cards, testimonial, contact panel, inquiry CTA.',
			'landing_page',
			'resource',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Informational landing v2. How-it-works before value; FAQ and benefits; booking and inquiry CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Process-first; trust and FAQ; cards and testimonial.',
				'cta_direction_summary' => 'Contact, booking, inquiry.',
			),
			'Requires section library.',
			'How-it-works before value prop; FAQ mid-page; different sequence than PT-10 informational_landing_01.'
		);
	}

	public static function pt_informational_landing_03(): array {
		$keys = array( 'hero_cred_01', 'tp_authority_01', 'fb_resource_explainer_01', 'cta_support_01', 'ptf_timeline_01', 'fb_differentiator_01', 'ptf_faq_01', 'cta_inquiry_01', 'mlp_related_content_01', 'ptf_expectations_01', 'tp_reassurance_01', 'lpu_support_escalation_01', 'cta_contact_02' );
		$r    = self::ordered_and_requirements( $keys );
		return self::base(
			'pt_informational_landing_03',
			'Informational landing v3',
			'Informational landing v3: cred hero, authority first, resource explainer, support CTA, timeline, differentiator, FAQ, inquiry CTA, related content, expectations, reassurance, support band, contact CTA.',
			'landing_page',
			'educational',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Informational landing v3. Authority and explainer before CTA; timeline and differentiator; related content and expectations.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Authority-first; timeline and FAQ; related content and reassurance.',
				'cta_direction_summary' => 'Support, inquiry, contact.',
			),
			'Requires section library.',
			'Authority and explainer first; four content blocks before first CTA; soft CTA posture.'
		);
	}
}
