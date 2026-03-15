<?php
/**
 * Top-level educational, resource, authority, and FAQ-heavy page template definitions (spec §13, §14.3, §16, Prompt 163).
 * Resource hubs, learning pages, authority/editorial, comparison-led, FAQ-rich, buyer-guide. Each template: ~10 non-CTA + ≥3 CTA,
 * last section CTA, no adjacent CTA. Uses section library from Prompts 147–154. Synthetic preview only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelEducationalResourceAuthorityBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Returns page template definitions for the top-level educational/resource/authority batch (PT-10 scope).
 * template_category_class = top_level; template_family = resource, authority, educational, comparison, faq, buyer_guide.
 */
final class Top_Level_Educational_Resource_Authority_Page_Template_Definitions {

	/** Batch ID for top-level educational/resource/authority (template-library-inventory-manifest PT-10). */
	public const BATCH_ID = 'PT-10';

	/** Allowed template_family values for this batch. */
	public const ALLOWED_FAMILIES = array( 'resource', 'authority', 'educational', 'comparison', 'faq', 'buyer_guide' );

	/** Industry keys for first launch verticals (page-template-industry-affinity-contract; Prompt 364). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Returns all top-level educational/resource/authority page template definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::pt_resource_overview_01(),
			self::pt_resource_learning_01(),
			self::pt_authority_explanatory_01(),
			self::pt_authority_editorial_01(),
			self::pt_comparison_decision_01(),
			self::pt_comparison_buyer_guide_01(),
			self::pt_faq_educational_01(),
			self::pt_faq_reassurance_01(),
			self::pt_buyer_guide_01(),
			self::pt_buyer_guide_compare_01(),
			self::pt_informational_landing_01(),
			self::pt_informational_landing_soft_cta_01(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'pt_resource_overview_01',
			'pt_resource_learning_01',
			'pt_authority_explanatory_01',
			'pt_authority_editorial_01',
			'pt_comparison_decision_01',
			'pt_comparison_buyer_guide_01',
			'pt_faq_educational_01',
			'pt_faq_reassurance_01',
			'pt_buyer_guide_01',
			'pt_buyer_guide_compare_01',
			'pt_informational_landing_01',
			'pt_informational_landing_soft_cta_01',
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
		$ordered    = array();
		$requirements = array();
		foreach ( $section_keys as $pos => $key ) {
			$ordered[] = array(
				Page_Template_Schema::SECTION_ITEM_KEY      => $key,
				Page_Template_Schema::SECTION_ITEM_POSITION => $pos,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			);
			$requirements[ $key ] = array( 'required' => true );
		}
		return array( 'ordered' => $ordered, 'requirements' => $requirements );
	}

	/**
	 * Base page template shape for top-level educational/resource/authority batch.
	 *
	 * @param string       $internal_key
	 * @param string       $name
	 * @param string       $purpose_summary
	 * @param string       $archetype
	 * @param string       $template_family
	 * @param array        $ordered
	 * @param array        $section_requirements
	 * @param array        $one_pager
	 * @param string       $endpoint_notes
	 * @param array        $extra
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
			Page_Template_Schema::FIELD_INTERNAL_KEY             => $internal_key,
			Page_Template_Schema::FIELD_NAME                     => $name,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY          => $purpose_summary,
			Page_Template_Schema::FIELD_ARCHETYPE                 => $archetype,
			Page_Template_Schema::FIELD_ORDERED_SECTIONS         => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS     => $section_requirements,
			Page_Template_Schema::FIELD_COMPATIBILITY             => array(),
			Page_Template_Schema::FIELD_ONE_PAGER                => $one_pager,
			Page_Template_Schema::FIELD_VERSION                  => array( 'version' => '1', 'stable_key_retained' => true ),
			Page_Template_Schema::FIELD_STATUS                   => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES  => $endpoint_notes,
			'template_category_class'                           => 'top_level',
			'template_family'                                    => $template_family,
		);
		if ( ! isset( $extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] ) ) {
			$extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] = self::LAUNCH_INDUSTRIES;
		}
		return array_merge( $def, $extra );
	}

	public static function pt_resource_overview_01(): array {
		$keys = array(
			'hero_edu_01',
			'fb_resource_explainer_01',
			'mlp_card_grid_01',
			'cta_service_detail_01',
			'ptf_how_it_works_01',
			'tp_trust_band_01',
			'fb_value_prop_01',
			'cta_inquiry_01',
			'ptf_faq_01',
			'mlp_related_content_01',
			'lpu_support_escalation_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_resource_overview_01',
			'Resource overview',
			'Top-level resource overview: education hero, resource explainer, card grid, service CTA, how-it-works, trust band, value prop, inquiry CTA, FAQ, related content, support band, contact CTA.',
			'landing_page',
			'resource',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Resource hub overview. Educate then guide to next step; CTAs spaced after explainer, after value prop, and at bottom.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Educational opener; explainer and cards; first CTA; process and trust; value and inquiry CTA; FAQ and related content; support and final CTA.',
				'cta_direction_summary'  => 'Service/inquiry/contact CTAs; soft conversion before stronger bottom CTA.',
			),
			'Requires section library (hero, fb, ptf, tp, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'Resource-first flow; moderate FAQ; clear progression to contact.',
			)
		);
	}

	public static function pt_resource_learning_01(): array {
		$keys = array(
			'hero_edu_01',
			'ptf_steps_01',
			'fb_benefit_band_01',
			'cta_consultation_01',
			'ptf_faq_01',
			'tp_reassurance_01',
			'fb_resource_explainer_01',
			'cta_booking_01',
			'ptf_expectations_01',
			'mlp_card_grid_01',
			'lpu_form_intro_01',
			'cta_quote_request_01',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_resource_learning_01',
			'Resource (learning flow)',
			'Learning-led resource page: education hero, steps, benefits, consultation CTA, FAQ, reassurance, explainer, booking CTA, expectations, cards, form intro, quote CTA.',
			'landing_page',
			'educational',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Learning-first resource. Steps and benefits before first CTA; FAQ and explainer deepen understanding; booking and quote CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Educational hero; steps and benefits; consultation CTA; FAQ and reassurance; explainer and booking CTA; expectations and cards; form and quote CTA.',
				'cta_direction_summary'  => 'Consultation, booking, quote request; education supports conversion.',
			),
			'Requires section library (hero, ptf, fb, tp, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'Step-by-step learning; single FAQ block; form intro before final CTA.',
			)
		);
	}

	public static function pt_authority_explanatory_01(): array {
		$keys = array(
			'hero_cred_01',
			'tp_authority_01',
			'fb_value_prop_01',
			'cta_contact_01',
			'ptf_timeline_01',
			'tp_quote_01',
			'fb_differentiator_01',
			'cta_consultation_02',
			'ptf_policy_explainer_01',
			'lpu_trust_disclosure_01',
			'tp_credential_01',
			'cta_trust_confirm_01',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_authority_explanatory_01',
			'Authority (explanatory)',
			'Authority-led explanatory page: credibility hero, authority band, value prop, contact CTA, timeline, quote, differentiator, consultation CTA, policy explainer, trust disclosure, credentials, trust CTA.',
			'about_page',
			'authority',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Authority-first explanatory. Build credibility and explain; contact and consultation CTAs; close with trust CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Credibility and authority; value and contact CTA; timeline and quote; differentiator and consultation CTA; explainer and disclosure; credentials and trust CTA.',
				'cta_direction_summary'  => 'Contact, consultation, trust confirm; authority supports conversion.',
			),
			'Requires section library (hero, tp, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'Authority and credentials heavy; policy explainer; soft then direct CTAs.',
			)
		);
	}

	public static function pt_authority_editorial_01(): array {
		$keys = array(
			'hero_edit_01',
			'tp_quote_01',
			'ptf_timeline_01',
			'fb_benefit_detail_01',
			'cta_inquiry_01',
			'tp_authority_01',
			'ptf_how_it_works_01',
			'lpu_trust_disclosure_01',
			'cta_contact_02',
			'fb_value_prop_01',
			'tp_testimonial_01',
			'cta_support_01',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_authority_editorial_01',
			'Authority (editorial)',
			'Editorial-style authority page: editorial hero, quote, timeline, benefit detail, inquiry CTA, authority band, how-it-works, trust disclosure, contact CTA, value prop, testimonial, support CTA.',
			'about_page',
			'authority',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Editorial authority. Story and proof before first CTA; process and disclosure; contact and support CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Editorial opener; quote and timeline; benefit detail and inquiry CTA; authority and process; disclosure and contact CTA; value and testimonial; support CTA.',
				'cta_direction_summary'  => 'Inquiry, contact, support; editorial tone with clear CTAs.',
			),
			'Requires section library (hero, tp, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'Editorial flow; timeline and benefit detail; three CTAs spaced.',
			)
		);
	}

	public static function pt_comparison_decision_01(): array {
		$keys = array(
			'hero_compact_01',
			'fb_offer_compare_01',
			'ptf_comparison_steps_01',
			'cta_product_detail_01',
			'tp_reassurance_01',
			'fb_why_choose_01',
			'ptf_faq_01',
			'cta_compare_next_01',
			'mlp_comparison_cards_01',
			'tp_trust_band_01',
			'fb_differentiator_01',
			'cta_consultation_01',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_comparison_decision_01',
			'Comparison (decision support)',
			'Comparison-led decision page: compact hero, offer compare, comparison steps, product CTA, reassurance, why choose, FAQ, compare-next CTA, comparison cards, trust band, differentiator, consultation CTA.',
			'comparison_page',
			'comparison',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Decision-support comparison. Compare options then product and compare-next CTAs; FAQ and trust; consultation CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Compact opener; offer compare and steps; product CTA; reassurance and why choose; FAQ and compare-next CTA; cards and trust; differentiator and consultation CTA.',
				'cta_direction_summary'  => 'Product detail, compare next, consultation; comparison supports decision.',
			),
			'Requires section library (hero, fb, ptf, tp, mlp, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'Comparison-heavy; FAQ and reassurance; multiple decision CTAs.',
			)
		);
	}

	public static function pt_comparison_buyer_guide_01(): array {
		$keys = array(
			'hero_edu_01',
			'ptf_buying_process_01',
			'fb_offer_compare_01',
			'cta_inquiry_01',
			'ptf_faq_by_category_01',
			'tp_faq_microproof_01',
			'fb_value_prop_01',
			'cta_quote_request_01',
			'ptf_expectations_01',
			'mlp_card_grid_01',
			'lpu_support_escalation_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_comparison_buyer_guide_01',
			'Comparison (buyer guide)',
			'Buyer-guide comparison: education hero, buying process, offer compare, inquiry CTA, FAQ by category, microproof, value prop, quote CTA, expectations, cards, support band, contact CTA.',
			'comparison_page',
			'buyer_guide',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Buyer-guide style comparison. Process and compare; FAQ by category; inquiry and quote CTAs; contact at bottom.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Educational hero; buying process and compare; inquiry CTA; FAQ by category and microproof; value and quote CTA; expectations and cards; support and contact CTA.',
				'cta_direction_summary'  => 'Inquiry, quote request, contact; educational then conversion.',
			),
			'Requires section library (hero, ptf, fb, tp, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'FAQ by category; buying process; buyer-guide flow.',
			)
		);
	}

	public static function pt_faq_educational_01(): array {
		$keys = array(
			'hero_edu_01',
			'ptf_faq_by_category_01',
			'ptf_faq_01',
			'cta_support_01',
			'fb_resource_explainer_01',
			'ptf_faq_accordion_01',
			'tp_reassurance_01',
			'cta_contact_01',
			'ptf_policy_explainer_01',
			'lpu_support_escalation_01',
			'mlp_related_content_01',
			'cta_inquiry_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_faq_educational_01',
			'FAQ (educational)',
			'FAQ-heavy educational page: education hero, FAQ by category, FAQ block, support CTA, resource explainer, FAQ accordion, reassurance, contact CTA, policy explainer, support escalation, related content, inquiry CTA.',
			'faq_page',
			'faq',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'FAQ-dense educational. Multiple FAQ sections; explainers; support, contact, inquiry CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Educational hero; FAQ by category and standard FAQ; support CTA; explainer and accordion FAQ; reassurance and contact CTA; policy explainer and support; related content and inquiry CTA.',
				'cta_direction_summary'  => 'Support, contact, inquiry; FAQ reduces friction before conversion.',
			),
			'Requires section library (hero, ptf, fb, tp, lpu, mlp, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'High FAQ density; resource and policy explainers; three CTAs.',
			)
		);
	}

	public static function pt_faq_reassurance_01(): array {
		$keys = array(
			'hero_compact_01',
			'ptf_faq_01',
			'tp_reassurance_01',
			'cta_booking_01',
			'ptf_faq_accordion_01',
			'fb_why_choose_01',
			'tp_trust_band_01',
			'cta_contact_02',
			'ptf_expectations_01',
			'lpu_form_intro_01',
			'fb_value_prop_01',
			'cta_support_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_faq_reassurance_01',
			'FAQ (reassurance)',
			'FAQ-focused reassurance page: compact hero, FAQ, reassurance, booking CTA, FAQ accordion, why choose, trust band, contact CTA, expectations, form intro, value prop, support CTA.',
			'faq_page',
			'faq',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'FAQ with reassurance emphasis. Trust and why-choose between FAQ blocks; booking, contact, support CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Compact hero; FAQ and reassurance; booking CTA; accordion FAQ and why choose; trust band and contact CTA; expectations and form; value and support CTA.',
				'cta_direction_summary'  => 'Booking, contact, support; reassurance supports conversion.',
			),
			'Requires section library (hero, ptf, fb, tp, lpu, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'Reassurance and trust heavy; two FAQ blocks; form intro before final CTA.',
			)
		);
	}

	public static function pt_buyer_guide_01(): array {
		$keys = array(
			'hero_edu_01',
			'ptf_buying_process_01',
			'fb_why_choose_01',
			'cta_consultation_01',
			'ptf_faq_01',
			'fb_offer_highlight_01',
			'tp_guarantee_01',
			'cta_quote_request_01',
			'ptf_how_it_works_01',
			'mlp_card_grid_01',
			'tp_reassurance_01',
			'cta_contact_01',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_buyer_guide_01',
			'Buyer guide',
			'Buyer guide page: education hero, buying process, why choose, consultation CTA, FAQ, offer highlight, guarantee, quote CTA, how-it-works, cards, reassurance, contact CTA.',
			'landing_page',
			'buyer_guide',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Buyer guide. Process and why-choose; consultation and quote CTAs; guarantee and reassurance; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Educational hero; buying process and why choose; consultation CTA; FAQ and offer highlight; guarantee and quote CTA; how-it-works and cards; reassurance and contact CTA.',
				'cta_direction_summary'  => 'Consultation, quote request, contact; guide supports decision.',
			),
			'Requires section library (hero, ptf, fb, tp, mlp, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'Buying process and guarantee; single FAQ; three CTAs.',
			)
		);
	}

	public static function pt_buyer_guide_compare_01(): array {
		$keys = array(
			'hero_compact_01',
			'fb_offer_compare_01',
			'ptf_comparison_steps_01',
			'cta_product_detail_01',
			'ptf_faq_by_category_01',
			'tp_rating_01',
			'fb_differentiator_01',
			'cta_inquiry_01',
			'ptf_expectations_01',
			'mlp_comparison_cards_01',
			'lpu_trust_disclosure_01',
			'cta_consultation_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_buyer_guide_compare_01',
			'Buyer guide (comparison)',
			'Buyer guide with comparison: compact hero, offer compare, comparison steps, product CTA, FAQ by category, rating, differentiator, inquiry CTA, expectations, comparison cards, trust disclosure, consultation CTA.',
			'comparison_page',
			'buyer_guide',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Buyer guide with comparison. Compare and steps; product CTA; FAQ by category; inquiry and consultation CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Compact hero; offer compare and steps; product CTA; FAQ by category and rating; differentiator and inquiry CTA; expectations and cards; disclosure and consultation CTA.',
				'cta_direction_summary'  => 'Product detail, inquiry, consultation; comparison supports choice.',
			),
			'Requires section library (hero, fb, ptf, tp, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'Comparison steps and FAQ by category; rating and disclosure.',
			)
		);
	}

	public static function pt_informational_landing_01(): array {
		$keys = array(
			'hero_edu_01',
			'fb_value_prop_01',
			'ptf_how_it_works_01',
			'cta_contact_01',
			'tp_trust_band_01',
			'fb_benefit_band_01',
			'ptf_faq_01',
			'cta_booking_01',
			'mlp_card_grid_01',
			'tp_testimonial_01',
			'lpu_contact_panel_01',
			'cta_inquiry_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_informational_landing_01',
			'Informational landing',
			'Informational landing: education hero, value prop, how-it-works, contact CTA, trust band, benefits, FAQ, booking CTA, cards, testimonial, contact panel, inquiry CTA.',
			'landing_page',
			'resource',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Informational landing with clear CTAs. Value and process; contact and booking CTAs; proof and panel; inquiry CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Educational hero; value and how-it-works; contact CTA; trust and benefits; FAQ and booking CTA; cards and testimonial; contact panel and inquiry CTA.',
				'cta_direction_summary'  => 'Contact, booking, inquiry; balanced education and conversion.',
			),
			'Requires section library (hero, fb, ptf, tp, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'Balanced info and conversion; single FAQ; contact panel.',
			)
		);
	}

	public static function pt_informational_landing_soft_cta_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_resource_explainer_01',
			'ptf_timeline_01',
			'tp_authority_01',
			'cta_support_01',
			'ptf_faq_01',
			'fb_differentiator_01',
			'mlp_related_content_01',
			'cta_inquiry_01',
			'ptf_expectations_01',
			'tp_reassurance_01',
			'lpu_support_escalation_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'pt_informational_landing_soft_cta_01',
			'Informational landing (soft CTA)',
			'Informational landing with softer CTA posture: credibility hero, resource explainer, timeline, authority, support CTA, FAQ, differentiator, related content, inquiry CTA, expectations, reassurance, support band, contact CTA.',
			'landing_page',
			'educational',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Informational with softer CTA emphasis. More content before first CTA; support, inquiry, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation'  => 'Credibility and explainer; timeline and authority; support CTA; FAQ and differentiator; related content and inquiry CTA; expectations and reassurance; support band and contact CTA.',
				'cta_direction_summary'  => 'Support, inquiry, contact; education-first then conversion.',
			),
			'Requires section library (hero, fb, ptf, tp, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'       => array( 'synthetic' => true ),
				'differentiation_notes' => 'Softer CTA posture; four content blocks before first CTA; support then contact.',
			)
		);
	}
}
