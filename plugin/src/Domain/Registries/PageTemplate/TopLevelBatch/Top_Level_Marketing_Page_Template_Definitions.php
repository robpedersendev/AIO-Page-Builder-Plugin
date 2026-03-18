<?php
/**
 * Top-level marketing and core business page template definitions (spec §13, §14, §16, Prompt 155).
 * Home, About, FAQ, Contact, Services overview, Offerings overview. Each template: ~10 non-CTA + ≥3 CTA,
 * last section CTA, no adjacent CTA. Uses section library from Prompts 147–153.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Returns page template definitions for the top-level marketing batch (PT-01 scope).
 * template_category_class = top_level; template_family = home, about, faq, contact, services, offerings.
 */
final class Top_Level_Marketing_Page_Template_Definitions {

	/** Batch ID for top-level marketing (template-library-inventory-manifest PT-01). */
	public const BATCH_ID = 'PT-01';

	/** Industry keys for first launch verticals (page-template-industry-affinity-contract; Prompt 364). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Returns all top-level marketing page template definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::pt_home_conversion_01(),
			self::pt_home_trust_01(),
			self::pt_about_story_01(),
			self::pt_about_team_01(),
			self::pt_faq_support_01(),
			self::pt_faq_category_01(),
			self::pt_contact_request_01(),
			self::pt_contact_directions_01(),
			self::pt_services_overview_01(),
			self::pt_services_value_01(),
			self::pt_offerings_overview_01(),
			self::pt_offerings_compare_01(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'pt_home_conversion_01',
			'pt_home_trust_01',
			'pt_about_story_01',
			'pt_about_team_01',
			'pt_faq_support_01',
			'pt_faq_category_01',
			'pt_contact_request_01',
			'pt_contact_directions_01',
			'pt_services_overview_01',
			'pt_services_value_01',
			'pt_offerings_overview_01',
			'pt_offerings_compare_01',
		);
	}

	/**
	 * Builds ordered_sections and section_requirements from a list of section keys.
	 * CTA keys: any key starting with 'cta_' or equal to 'st_cta_conversion'.
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
	 * Base page template shape for top-level batch.
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

	public static function pt_home_conversion_01(): array {
		$keys = array(
			'hero_conv_01',
			'tp_testimonial_01',
			'fb_value_prop_01',
			'cta_consultation_01',
			'ptf_how_it_works_01',
			'fb_benefit_band_01',
			'tp_client_logo_01',
			'cta_contact_01',
			'lpu_contact_panel_01',
			'fb_feature_grid_01',
			'ptf_steps_01',
			'cta_trust_confirm_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_home_conversion_01',
			'Home (conversion-led)',
			'Home page with conversion emphasis: hero, proof, value prop, consultation CTA, how-it-works, benefits, logos, contact CTA, contact panel, features, steps, trust CTA. Top-level.',
			'landing_page',
			'home',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Home page conversion-led. Lead with hero and proof, then consultation CTA; mid-page benefits and logos; contact CTA and panel; features and steps; close with trust CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Opener establishes offer; proof and value prop build trust; first CTA captures interest; how-it-works and benefits explain; second CTA and contact panel support conversion; features and steps reinforce; final CTA confirms.',
			),
			'Requires section library (hero, trust, feature/benefit, process, legal/utility, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_home_trust_01(): array {
		$keys = array(
			'hero_cred_01',
			'tp_trust_band_01',
			'fb_why_choose_01',
			'cta_booking_01',
			'ptf_faq_01',
			'mlp_card_grid_01',
			'tp_testimonial_02',
			'cta_inquiry_01',
			'lpu_support_escalation_01',
			'fb_differentiator_01',
			'ptf_expectations_01',
			'cta_support_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_home_trust_01',
			'Home (trust-led)',
			'Home page with trust emphasis: credibility hero, trust band, why choose, booking CTA, FAQ, cards, testimonial, inquiry CTA, support band, differentiator, expectations, support CTA. Top-level.',
			'landing_page',
			'home',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Home page trust-led. Lead with credibility and trust band; why choose and booking CTA; FAQ and cards; testimonial and inquiry CTA; support and differentiator; expectations; close with support CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Trust-first opener; why choose and booking CTA; FAQ and cards reduce friction; testimonial and inquiry CTA; support and differentiator; expectations set scope; final CTA for help.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_about_story_01(): array {
		$keys = array(
			'hero_edit_01',
			'tp_quote_01',
			'fb_value_prop_01',
			'cta_contact_01',
			'ptf_timeline_01',
			'tp_authority_01',
			'lpu_trust_disclosure_01',
			'cta_consultation_02',
			'fb_benefit_detail_01',
			'ptf_steps_01',
			'cta_policy_utility_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_about_story_01',
			'About (story)',
			'About page story-led: editorial hero, quote, value prop, contact CTA, timeline, authority, trust disclosure, consultation CTA, benefit detail, steps, utility CTA. Top-level.',
			'about_page',
			'about',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'About page story-led. Editorial hero and quote; value prop and contact CTA; timeline and authority; trust disclosure and consultation CTA; benefit detail and steps; utility CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Story opener; value and contact CTA; timeline and authority build credibility; disclosure and consultation CTA; benefits and steps; close with utility CTA.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_about_team_01(): array {
		$keys = array(
			'hero_cred_01',
			'mlp_team_grid_01',
			'fb_why_choose_01',
			'cta_contact_02',
			'tp_testimonial_01',
			'ptf_faq_01',
			'tp_partner_01',
			'cta_inquiry_02',
			'lpu_contact_panel_01',
			'fb_differentiator_01',
			'ptf_how_it_works_01',
			'cta_support_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_about_team_01',
			'About (team)',
			'About page team-led: credibility hero, team grid, why choose, contact CTA, testimonial, FAQ, partners, inquiry CTA, contact panel, differentiator, how-it-works, support CTA. Top-level.',
			'about_page',
			'about',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'About page team-led. Hero and team grid; why choose and contact CTA; testimonial and FAQ; partners and inquiry CTA; contact panel and differentiator; how-it-works; support CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Team and credibility; why choose and contact CTA; social proof and FAQ; partners and inquiry CTA; contact and differentiator; how-it-works; final CTA.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_faq_support_01(): array {
		$keys = array(
			'hero_edu_01',
			'ptf_faq_01',
			'ptf_faq_accordion_01',
			'cta_support_01',
			'tp_faq_microproof_01',
			'ptf_faq_by_category_01',
			'lpu_support_escalation_01',
			'cta_contact_01',
			'fb_resource_explainer_01',
			'ptf_policy_explainer_01',
			'cta_support_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_faq_support_01',
			'FAQ (support)',
			'FAQ page support-focused: education hero, FAQ, accordion FAQ, support CTA, microproof, FAQ by category, escalation band, contact CTA, resource explainer, policy explainer, support CTA. Top-level.',
			'faq_page',
			'faq',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'FAQ page support-focused. Hero and FAQ sections; support CTA; microproof and FAQ by category; escalation and contact CTA; explainers; final support CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Educational opener; FAQ content; support CTA; microproof and categorized FAQ; escalation and contact CTA; explainers; close with support CTA.',
			),
			'Requires section library (hero, trust, ptf, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_faq_category_01(): array {
		$keys = array(
			'hero_compact_01',
			'ptf_faq_by_category_01',
			'ptf_faq_01',
			'cta_inquiry_01',
			'tp_reassurance_01',
			'ptf_expectations_01',
			'lpu_form_intro_01',
			'cta_quote_request_01',
			'fb_value_prop_01',
			'ptf_how_it_works_01',
			'cta_contact_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_faq_category_01',
			'FAQ (by category)',
			'FAQ page category-led: compact hero, FAQ by category, FAQ block, inquiry CTA, reassurance, expectations, form intro, quote CTA, value prop, how-it-works, contact CTA. Top-level.',
			'faq_page',
			'faq',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'FAQ page category-led. Compact hero; FAQ by category and standard FAQ; inquiry CTA; reassurance and expectations; form intro and quote CTA; value and how-it-works; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Compact opener; categorized FAQ; inquiry CTA; reassurance and expectations; form and quote CTA; value and process; final contact CTA.',
			),
			'Requires section library (hero, trust, ptf, lpu, fb, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_contact_request_01(): array {
		$keys = array(
			'hero_conv_02',
			'lpu_contact_panel_01',
			'lpu_contact_detail_01',
			'cta_contact_02',
			'lpu_form_intro_01',
			'lpu_inquiry_support_01',
			'tp_reassurance_01',
			'cta_quote_request_02',
			'fb_value_prop_01',
			'lpu_support_escalation_01',
			'cta_support_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_contact_request_01',
			'Contact (request)',
			'Contact page request-focused: hero, contact panel, contact detail, contact CTA, form intro, inquiry support, reassurance, quote CTA, value prop, support escalation, support CTA. Top-level.',
			'request_page',
			'contact',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Contact page request-focused. Hero and contact panel/detail; contact CTA; form intro and inquiry support; reassurance and quote CTA; value prop and support band; support CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Hero and contact info; contact CTA; form and inquiry; reassurance and quote CTA; value and support; final CTA.',
			),
			'Requires section library (hero, trust, lpu, fb, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_contact_directions_01(): array {
		$keys = array(
			'hero_local_01',
			'lpu_contact_detail_01',
			'mlp_location_info_01',
			'cta_local_action_01',
			'lpu_contact_panel_01',
			'tp_trust_band_01',
			'lpu_accessibility_help_01',
			'cta_contact_01',
			'fb_local_value_01',
			'ptf_expectations_01',
			'cta_local_action_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_contact_directions_01',
			'Contact (directions)',
			'Contact page location-led: local hero, contact detail, location info, local CTA, contact panel, trust band, accessibility help, contact CTA, local value, expectations, local CTA. Top-level.',
			'request_page',
			'contact',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Contact page directions-led. Local hero and contact/location info; local CTA; contact panel and trust band; accessibility and contact CTA; local value and expectations; local CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Location-focused opener; contact and location; local CTA; panel and trust; accessibility and contact CTA; value and expectations; final local CTA.',
			),
			'Requires section library (hero, trust, lpu, mlp, fb, ptf, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_services_overview_01(): array {
		$keys = array(
			'hero_conv_01',
			'fb_service_offering_01',
			'ptf_service_flow_01',
			'cta_service_detail_01',
			'mlp_card_grid_01',
			'tp_testimonial_01',
			'fb_benefit_band_01',
			'cta_consultation_01',
			'ptf_how_it_works_01',
			'tp_trust_band_01',
			'lpu_contact_panel_01',
			'cta_booking_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_services_overview_01',
			'Services overview',
			'Services overview page: hero, service offering, service flow, service CTA, card grid, testimonial, benefit band, consultation CTA, how-it-works, trust band, contact panel, booking CTA. Top-level.',
			'hub_page',
			'services',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Services overview. Hero and service offering/flow; service CTA; cards and testimonial; benefits and consultation CTA; how-it-works and trust; contact panel; booking CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Service opener; flow and service CTA; proof and benefits; consultation CTA; process and trust; contact; final booking CTA.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_services_value_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_why_choose_01',
			'ptf_steps_01',
			'cta_quote_request_01',
			'mlp_listing_01',
			'tp_case_teaser_01',
			'fb_differentiator_01',
			'cta_service_detail_02',
			'ptf_expectations_01',
			'tp_client_logo_01',
			'lpu_support_escalation_01',
			'cta_contact_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_services_value_01',
			'Services (value-led)',
			'Services overview value-led: credibility hero, why choose, steps, quote CTA, listing, case teaser, differentiator, service CTA, expectations, logos, support band, contact CTA. Top-level.',
			'hub_page',
			'services',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Services overview value-led. Credibility and why choose; steps and quote CTA; listing and case; differentiator and service CTA; expectations and logos; support; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Value opener; steps and quote CTA; listing and case; differentiator and service CTA; expectations and proof; support; final contact CTA.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_offerings_overview_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_package_summary_01',
			'fb_offer_compare_01',
			'cta_purchase_01',
			'mlp_product_cards_01',
			'tp_testimonial_02',
			'fb_benefit_detail_01',
			'cta_product_detail_01',
			'ptf_buying_process_01',
			'tp_guarantee_01',
			'lpu_consent_note_01',
			'cta_purchase_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_offerings_overview_01',
			'Offerings overview',
			'Offerings overview: product hero, package summary, offer compare, purchase CTA, product cards, testimonial, benefit detail, product CTA, buying process, guarantee, consent note, purchase CTA. Top-level.',
			'hub_page',
			'offerings',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Offerings overview. Product hero and packages; offer compare and purchase CTA; product cards and testimonial; benefit and product CTA; buying process and guarantee; consent; purchase CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Product opener; packages and purchase CTA; cards and proof; benefit and product CTA; process and guarantee; consent; final purchase CTA.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function pt_offerings_compare_01(): array {
		$keys = array(
			'hero_compact_01',
			'fb_offer_compare_01',
			'ptf_comparison_steps_01',
			'cta_compare_next_01',
			'mlp_comparison_cards_01',
			'fb_differentiator_01',
			'tp_rating_01',
			'cta_product_detail_02',
			'ptf_buying_process_01',
			'fb_offer_highlight_01',
			'lpu_utility_cta_01',
			'cta_quote_request_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_offerings_compare_01',
			'Offerings (compare)',
			'Offerings compare-led: compact hero, offer compare, comparison steps, compare CTA, comparison cards, differentiator, rating, product CTA, buying process, offer highlight, utility CTA, quote CTA. Top-level.',
			'hub_page',
			'offerings',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Offerings compare-led. Compact hero and offer compare; comparison steps and compare CTA; comparison cards and differentiator; rating and product CTA; process and highlight; utility CTA; quote CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Compare opener; steps and compare CTA; cards and differentiator; rating and product CTA; process and highlight; utility; final quote CTA.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}
}
