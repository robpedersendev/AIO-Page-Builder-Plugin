<?php
/**
 * Child/detail page template definitions for services, offerings, locations, informational (spec §13, §14.3, §16, §17.7, Prompt 160).
 * template_category_class = child_detail; archetype = service_page | offer_page | location_page | informational_detail.
 * ~10 non-CTA (8–14) + ≥5 CTA sections, mandatory bottom CTA, no adjacent CTA. Detail-rich, conversion-capable.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Returns page template definitions for the child/detail batch (PT-07 scope).
 * Singular service, offering, treatment, local-place, or informational detail pages (e.g. Gel Manicure, Signature Massage, Salt Lake City).
 */
final class Child_Detail_Page_Template_Definitions {

	/** Batch ID for child/detail pages (template-library-inventory-manifest PT-07). */
	public const BATCH_ID = 'PT-07';

	/** Industry keys for first launch verticals (page-template-industry-affinity-contract; Prompt 364). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Allowed template families for child_detail (page-template-category-taxonomy-contract).
	 *
	 * @var list<string>
	 */
	public const ALLOWED_FAMILIES = array(
		'services',
		'offerings',
		'locations',
		'informational',
	);

	/**
	 * Returns all child/detail page template definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			// Services / treatment detail.
			self::child_detail_service_conversion_01(),
			self::child_detail_service_educational_01(),
			self::child_detail_service_proof_dense_01(),
			self::child_detail_service_booking_01(),
			self::child_detail_treatment_detail_01(),
			self::child_detail_service_trust_01(),
			self::child_detail_service_process_01(),
			// Offerings detail.
			self::child_detail_offer_value_01(),
			self::child_detail_offer_package_01(),
			self::child_detail_offer_educational_01(),
			self::child_detail_offer_consultation_01(),
			// Locations / local-place detail.
			self::child_detail_location_local_01(),
			self::child_detail_location_trust_01(),
			self::child_detail_location_contact_01(),
			self::child_detail_location_visit_01(),
			self::child_detail_location_service_01(),
			// Informational detail.
			self::child_detail_informational_01(),
			self::child_detail_informational_educational_01(),
			self::child_detail_informational_proof_01(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'child_detail_service_conversion_01',
			'child_detail_service_educational_01',
			'child_detail_service_proof_dense_01',
			'child_detail_service_booking_01',
			'child_detail_treatment_detail_01',
			'child_detail_service_trust_01',
			'child_detail_service_process_01',
			'child_detail_offer_value_01',
			'child_detail_offer_package_01',
			'child_detail_offer_educational_01',
			'child_detail_offer_consultation_01',
			'child_detail_location_local_01',
			'child_detail_location_trust_01',
			'child_detail_location_contact_01',
			'child_detail_location_visit_01',
			'child_detail_location_service_01',
			'child_detail_informational_01',
			'child_detail_informational_educational_01',
			'child_detail_informational_proof_01',
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
	 * Base page template shape for child/detail batch.
	 *
	 * @param string       $internal_key
	 * @param string       $name
	 * @param string       $purpose_summary
	 * @param string       $archetype
	 * @param string       $template_family
	 * @param list<string> $parent_family_compatibility
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
		array $parent_family_compatibility,
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
			'template_category_class'                    => 'child_detail',
			'template_family'                            => $template_family,
			'parent_family_compatibility'                => $parent_family_compatibility,
			'hierarchy_hints'                            => array(
				'common_parent_page_types' => 'hub, nested_hub',
				'hierarchy_role'           => 'leaf',
			),
		);
		if ( ! isset( $extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] ) ) {
			$extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] = self::LAUNCH_INDUSTRIES;
		}
		return array_merge( $def, $extra );
	}

	// --- Services / treatment detail ---

	public static function child_detail_service_conversion_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_service_offering_01',
			'tp_trust_band_01',
			'cta_service_detail_01',
			'tp_testimonial_01',
			'fb_why_choose_01',
			'ptf_how_it_works_01',
			'cta_consultation_01',
			'ptf_expectations_01',
			'tp_guarantee_01',
			'cta_quote_request_01',
			'ptf_service_flow_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_service_conversion_01',
			'Service detail (conversion-led)',
			'Child/detail page for a single service with high conversion emphasis: credibility hero, offering, trust band, service CTA, testimonial, why choose, how-it-works, consultation CTA, expectations, guarantee, quote CTA, service flow, booking CTA, contact panel, contact CTA.',
			'service_page',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single service detail with conversion-led structure. Offering and trust; early and mid CTAs; proof and process; booking and contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Detail-specific: one service entity. Conversion intensity via multiple CTAs and proof layering; mandatory bottom CTA.',
				'cta_direction_summary' => 'Service detail, consultation, quote, booking, contact; last section is contact CTA.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Conversion-led; proof and trust early; booking and contact emphasis.',
			)
		);
	}

	public static function child_detail_service_educational_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_service_offering_01',
			'ptf_how_it_works_01',
			'cta_service_detail_02',
			'ptf_expectations_01',
			'tp_testimonial_01',
			'fb_why_choose_01',
			'ptf_service_flow_01',
			'cta_consultation_01',
			'tp_trust_band_01',
			'tp_guarantee_01',
			'cta_quote_request_01',
			'fb_benefit_band_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_service_educational_01',
			'Service detail (educational)',
			'Child/detail page for a single service with educational depth: hero, offering, how-it-works, service CTA, expectations, testimonial, why choose, service flow, consultation CTA, trust band, guarantee, quote CTA, benefit band, booking CTA, contact panel, contact CTA.',
			'service_page',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single service detail with educational depth. How-it-works and expectations before CTAs; trust and guarantee; quote, booking and contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Informational depth first; conversion after education. Suited to considered purchases.',
				'cta_direction_summary' => 'Service detail, consultation, quote, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Educational; process and expectations before conversion.',
			)
		);
	}

	public static function child_detail_service_proof_dense_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_service_offering_01',
			'tp_trust_band_01',
			'cta_consultation_01',
			'tp_testimonial_01',
			'tp_guarantee_01',
			'tp_testimonial_02',
			'cta_booking_01',
			'fb_why_choose_01',
			'tp_client_logo_01',
			'ptf_expectations_01',
			'cta_quote_request_01',
			'ptf_how_it_works_01',
			'cta_service_detail_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_service_proof_dense_01',
			'Service detail (proof-dense)',
			'Child/detail page for a single service with dense proof layering: hero, offering, trust band, consultation CTA, testimonials, guarantee, booking CTA, why choose, client logos, expectations, quote CTA, how-it-works, service CTA, contact panel, contact CTA.',
			'service_page',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single service detail with proof density. Multiple trust and testimonial blocks; guarantee and logos; quote and contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Proof-heavy; builds trust before each CTA. Suited to high-consideration services.',
				'cta_direction_summary' => 'Consultation, booking, quote, service detail, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Proof-dense; testimonials and guarantee prominent.',
			)
		);
	}

	public static function child_detail_service_booking_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_service_offering_01',
			'cta_booking_01',
			'ptf_how_it_works_01',
			'tp_testimonial_01',
			'cta_consultation_01',
			'tp_trust_band_01',
			'fb_why_choose_01',
			'ptf_expectations_01',
			'cta_service_detail_01',
			'lpu_contact_panel_01',
			'tp_guarantee_01',
			'cta_quote_request_01',
			'ptf_service_flow_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_service_booking_01',
			'Service detail (booking emphasis)',
			'Child/detail page for a single service with booking emphasis: hero, offering, booking CTA early, how-it-works, testimonial, consultation CTA, trust, why choose, expectations, service CTA, contact panel, guarantee, quote CTA, service flow, contact CTA.',
			'service_page',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single service detail with booking emphasis. Early booking CTA; consultation and service CTAs; quote and contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Booking and contact path prominent; proof supports conversion.',
				'cta_direction_summary' => 'Booking, consultation, service detail, quote, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'                         => array( 'synthetic' => true ),
				'differentiation_notes'                    => 'Booking-first; early and repeated booking CTAs.',
				Page_Template_Schema::FIELD_INDUSTRY_NOTES => array(
					'cosmetology_nail'  => 'Strong fit for service or treatment booking flow.',
					'realtor'           => 'Good for consultation or valuation booking.',
					'plumber'           => 'Strong fit for schedule or callback booking.',
					'disaster_recovery' => 'Good for assessment or non-emergency booking.',
				),
			)
		);
	}

	public static function child_detail_treatment_detail_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_service_offering_01',
			'tp_trust_band_01',
			'cta_service_detail_02',
			'ptf_how_it_works_01',
			'ptf_service_flow_01',
			'tp_testimonial_01',
			'cta_consultation_01',
			'fb_why_choose_01',
			'ptf_expectations_01',
			'cta_booking_01',
			'tp_guarantee_01',
			'cta_quote_request_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_treatment_detail_01',
			'Treatment detail (e.g. Gel Manicure)',
			'Child/detail page for a single treatment (e.g. Gel Manicure, Signature Massage): hero, offering, trust, service CTA, how-it-works, service flow, testimonial, consultation CTA, why choose, expectations, booking CTA, guarantee, quote CTA, contact panel, contact CTA.',
			'service_page',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single treatment detail. Process and flow prominent; consultation and booking; contact CTA last.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Treatment-specific: one named treatment entity. Process and expectations support conversion.',
				'cta_direction_summary' => 'Service detail, consultation, booking, quote, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Treatment-specific; process and flow emphasis.',
			)
		);
	}

	public static function child_detail_service_trust_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_service_offering_01',
			'tp_trust_band_01',
			'tp_guarantee_01',
			'cta_consultation_01',
			'tp_testimonial_01',
			'fb_why_choose_01',
			'ptf_how_it_works_01',
			'cta_booking_01',
			'tp_reassurance_01',
			'ptf_expectations_01',
			'cta_service_detail_01',
			'fb_benefit_band_01',
			'cta_quote_request_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_service_trust_01',
			'Service detail (trust-led)',
			'Child/detail page for a single service with trust emphasis: hero, offering, trust band, guarantee, consultation CTA, testimonial, why choose, how-it-works, booking CTA, reassurance, expectations, service CTA, benefit band, quote CTA, contact panel, contact CTA.',
			'service_page',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single service detail with trust emphasis. Trust band, guarantee, reassurance before CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Trust-first; guarantee and reassurance support conversion.',
				'cta_direction_summary' => 'Consultation, booking, service detail, quote, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Trust-led; guarantee and reassurance prominent.',
			)
		);
	}

	public static function child_detail_service_process_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_service_offering_01',
			'ptf_how_it_works_01',
			'ptf_service_flow_01',
			'cta_consultation_01',
			'ptf_expectations_01',
			'tp_testimonial_01',
			'fb_why_choose_01',
			'cta_booking_01',
			'tp_trust_band_01',
			'cta_quote_request_01',
			'fb_benefit_band_01',
			'cta_service_detail_02',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_service_process_01',
			'Service detail (process-led)',
			'Child/detail page for a single service with process emphasis: hero, offering, how-it-works, service flow, consultation CTA, expectations, testimonial, why choose, booking CTA, trust band, quote CTA, benefit band, service CTA, contact panel, contact CTA.',
			'service_page',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single service detail with process emphasis. Multiple process/flow sections; consultation, booking, service CTA, contact.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Process and expectations lead; conversion after clarity.',
				'cta_direction_summary' => 'Consultation, booking, quote, service detail, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Process-led; how-it-works and flow prominent.',
			)
		);
	}

	// --- Offerings detail ---

	public static function child_detail_offer_value_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_value_prop_01',
			'fb_offer_compare_01',
			'cta_purchase_01',
			'tp_testimonial_01',
			'fb_benefit_band_01',
			'cta_quote_request_01',
			'ptf_buying_process_01',
			'tp_guarantee_01',
			'cta_consultation_01',
			'fb_why_choose_01',
			'tp_trust_band_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_offer_value_01',
			'Offer detail (value-led)',
			'Child/detail page for a single offering with value emphasis: product hero, value prop, offer compare, purchase CTA, testimonial, benefit band, quote CTA, buying process, guarantee, consultation CTA, why choose, trust band, booking CTA, contact panel, contact CTA.',
			'offer_page',
			'offerings',
			array( 'offerings' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single offer detail value-led. Value prop and offer compare; purchase and quote CTAs; consultation and booking; contact CTA last.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Offer-specific entity; value and proof support conversion.',
				'cta_direction_summary' => 'Purchase, quote, consultation, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Value-led; offer compare and benefit band.',
			)
		);
	}

	public static function child_detail_offer_package_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_package_summary_01',
			'fb_offer_compare_01',
			'cta_purchase_01',
			'tp_testimonial_02',
			'fb_benefit_detail_01',
			'cta_quote_request_02',
			'ptf_buying_process_01',
			'tp_guarantee_01',
			'cta_consultation_01',
			'fb_offer_highlight_01',
			'tp_trust_band_01',
			'cta_booking_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_offer_package_01',
			'Offer detail (package)',
			'Child/detail page for a package/offering: product hero, package summary, offer compare, purchase CTA, testimonial, benefit detail, quote CTA, buying process, guarantee, consultation CTA, offer highlight, trust band, booking CTA, consent note, contact CTA.',
			'offer_page',
			'offerings',
			array( 'offerings' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single offer/package detail. Package summary and benefit detail; purchase, quote, consultation, booking, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Package-specific; offer highlight and consent support conversion.',
				'cta_direction_summary' => 'Purchase, quote, consultation, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Package/pricing emphasis; benefit detail and consent.',
			)
		);
	}

	public static function child_detail_offer_educational_01(): array {
		$keys = array(
			'hero_compact_01',
			'fb_offer_compare_01',
			'ptf_comparison_steps_01',
			'cta_compare_next_01',
			'fb_differentiator_01',
			'tp_rating_01',
			'fb_benefit_band_01',
			'cta_quote_request_01',
			'ptf_buying_process_01',
			'tp_guarantee_01',
			'cta_purchase_01',
			'fb_why_choose_01',
			'cta_consultation_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_offer_educational_01',
			'Offer detail (educational)',
			'Child/detail page for an offering with educational emphasis: compact hero, offer compare, comparison steps, compare CTA, differentiator, rating, benefit band, quote CTA, buying process, guarantee, purchase CTA, why choose, consultation CTA, contact panel, contact CTA.',
			'offer_page',
			'offerings',
			array( 'offerings' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single offer detail educational. Comparison steps and differentiator; quote, purchase, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Educational; comparison and buying process before conversion.',
				'cta_direction_summary' => 'Compare, quote, purchase, consultation, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Educational; comparison steps and differentiator.',
			)
		);
	}

	public static function child_detail_offer_consultation_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_value_prop_01',
			'cta_consultation_01',
			'fb_offer_compare_01',
			'tp_testimonial_01',
			'cta_quote_request_01',
			'ptf_buying_process_01',
			'fb_benefit_band_01',
			'cta_booking_01',
			'tp_guarantee_01',
			'fb_why_choose_01',
			'cta_purchase_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_offer_consultation_01',
			'Offer detail (consultation emphasis)',
			'Child/detail page for an offering with consultation emphasis: product hero, value prop, consultation CTA, offer compare, testimonial, quote CTA, buying process, benefit band, booking CTA, guarantee, why choose, purchase CTA, contact panel, contact CTA.',
			'offer_page',
			'offerings',
			array( 'offerings' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single offer detail with consultation emphasis. Early consultation CTA; quote, booking, purchase, contact.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Consultation and quote path prominent; purchase and contact follow.',
				'cta_direction_summary' => 'Consultation, quote, booking, purchase, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Consultation-first; quote and booking CTAs.',
			)
		);
	}

	// --- Locations / local-place detail ---

	public static function child_detail_location_local_01(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'mlp_location_info_01',
			'cta_local_action_01',
			'mlp_place_highlight_01',
			'tp_trust_band_01',
			'cta_contact_01',
			'lpu_contact_detail_01',
			'mlp_card_grid_01',
			'cta_directory_nav_01',
			'tp_reassurance_01',
			'lpu_contact_panel_01',
			'cta_local_action_02',
			'ptf_expectations_01',
			'cta_booking_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_location_local_01',
			'Location detail (local-place, e.g. Salt Lake City)',
			'Child/detail page for a single location/local-place (e.g. Salt Lake City service page): local hero, local value, location info, local CTA, place highlight, trust band, contact CTA, contact detail, card grid, directory nav CTA, reassurance, contact panel, local CTA, expectations, booking CTA.',
			'location_page',
			'locations',
			array( 'locations' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single location/local-place detail. Local value and place highlight; local, contact, directory, booking CTAs; synthetic preview only.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Location-specific entity (e.g. city, branch). Local trust and contact emphasis; mandatory bottom CTA.',
				'cta_direction_summary' => 'Local action, contact, directory nav, local action, booking; last CTA booking.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Local-place; no real addresses; local value and place highlight.',
			)
		);
	}

	public static function child_detail_location_trust_01(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'tp_trust_band_01',
			'tp_reassurance_01',
			'cta_local_action_01',
			'mlp_location_info_01',
			'mlp_place_highlight_01',
			'cta_contact_02',
			'tp_guarantee_01',
			'lpu_contact_detail_01',
			'cta_quote_request_01',
			'mlp_card_grid_01',
			'cta_directory_nav_01',
			'ptf_expectations_01',
			'lpu_contact_panel_01',
			'cta_booking_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_location_trust_01',
			'Location detail (local trust emphasis)',
			'Child/detail page for a single location with local trust emphasis: local hero, local value, trust band, reassurance, local CTA, location info, place highlight, contact CTA, guarantee, contact detail, quote CTA, card grid, directory nav CTA, expectations, contact panel, booking CTA.',
			'location_page',
			'locations',
			array( 'locations' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single location detail with local trust. Trust band, reassurance, guarantee before CTAs; contact and booking last.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Local trust emphasis; reassurance and guarantee support visit/contact.',
				'cta_direction_summary' => 'Local action, contact, quote, directory nav, booking; last CTA booking.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Local trust; reassurance and guarantee prominent.',
			)
		);
	}

	public static function child_detail_location_contact_01(): array {
		$keys = array(
			'hero_local_01',
			'mlp_location_info_01',
			'lpu_contact_detail_01',
			'cta_contact_01',
			'fb_local_value_01',
			'mlp_place_highlight_01',
			'cta_local_action_01',
			'tp_trust_band_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
			'ptf_expectations_01',
			'mlp_card_grid_01',
			'cta_directory_nav_01',
			'tp_reassurance_01',
			'cta_booking_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_location_contact_01',
			'Location detail (contact/visit emphasis)',
			'Child/detail page for a single location with contact/visit emphasis: local hero, location info, contact detail, contact CTA, local value, place highlight, local CTA, trust band, contact panel, contact CTA, expectations, card grid, directory nav CTA, reassurance, booking CTA.',
			'location_page',
			'locations',
			array( 'locations' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single location detail with contact/visit emphasis. Contact detail and panel early; multiple contact and local CTAs; booking last.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Contact and visit path prominent; directory and booking support.',
				'cta_direction_summary' => 'Contact, local action, contact, directory nav, booking; last CTA booking.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Contact/visit emphasis; contact detail and panel prominent.',
			)
		);
	}

	public static function child_detail_location_visit_01(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'mlp_place_highlight_01',
			'cta_booking_01',
			'mlp_location_info_01',
			'tp_reassurance_01',
			'cta_local_action_01',
			'lpu_contact_detail_01',
			'tp_trust_band_01',
			'cta_contact_01',
			'ptf_expectations_01',
			'lpu_contact_panel_01',
			'cta_directory_nav_01',
			'mlp_card_grid_01',
			'cta_local_action_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_location_visit_01',
			'Location detail (visit/booking emphasis)',
			'Child/detail page for a single location with visit/booking emphasis: local hero, local value, place highlight, booking CTA, location info, reassurance, local CTA, contact detail, trust band, contact CTA, expectations, contact panel, directory nav CTA, card grid, local CTA.',
			'location_page',
			'locations',
			array( 'locations' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single location detail with visit/booking emphasis. Early booking CTA; local and contact CTAs; local CTA last.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Visit and booking path prominent; local action last.',
				'cta_direction_summary' => 'Booking, local action, contact, directory nav, local action; last CTA local action.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Visit/booking emphasis; booking CTA early.',
			)
		);
	}

	public static function child_detail_location_service_01(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'fb_service_offering_01',
			'cta_service_detail_01',
			'mlp_location_info_01',
			'tp_trust_band_01',
			'cta_consultation_01',
			'mlp_place_highlight_01',
			'ptf_expectations_01',
			'cta_booking_01',
			'lpu_contact_detail_01',
			'tp_reassurance_01',
			'cta_contact_01',
			'lpu_contact_panel_01',
			'cta_local_action_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_location_service_01',
			'Location detail (location + service)',
			'Child/detail page for a location with linked service (e.g. Salt Lake City service page): local hero, local value, service offering, service CTA, location info, trust band, consultation CTA, place highlight, expectations, booking CTA, contact detail, reassurance, contact CTA, contact panel, local CTA.',
			'location_page',
			'locations',
			array( 'locations', 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single location with service link. Service offering and service CTA; consultation, booking, contact, local CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Location + service combination; service and local conversion paths.',
				'cta_direction_summary' => 'Service detail, consultation, booking, contact, local action; last CTA local.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Location + service; service offering and local action.',
			)
		);
	}

	// --- Informational detail ---

	public static function child_detail_informational_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_value_prop_01',
			'ptf_how_it_works_01',
			'cta_consultation_01',
			'fb_why_choose_01',
			'tp_testimonial_01',
			'ptf_expectations_01',
			'cta_service_detail_01',
			'tp_trust_band_01',
			'fb_benefit_band_01',
			'cta_quote_request_01',
			'lpu_contact_panel_01',
			'ptf_service_flow_01',
			'cta_booking_01',
			'tp_guarantee_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_informational_01',
			'Informational detail',
			'Child/detail page for an informational entity: credibility hero, value prop, how-it-works, consultation CTA, why choose, testimonial, expectations, service CTA, trust band, benefit band, quote CTA, contact panel, service flow, booking CTA, guarantee, contact CTA.',
			'informational_detail',
			'informational',
			array( 'informational', 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Informational detail page. Value and process; consultation, service, quote, booking, contact CTAs; guarantee before final CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Informational entity detail; conversion path after education.',
				'cta_direction_summary' => 'Consultation, service detail, quote, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Informational; value and process balanced.',
			)
		);
	}

	public static function child_detail_informational_educational_01(): array {
		$keys = array(
			'hero_compact_01',
			'fb_value_prop_01',
			'ptf_how_it_works_01',
			'ptf_expectations_01',
			'cta_consultation_01',
			'fb_why_choose_01',
			'tp_testimonial_01',
			'ptf_service_flow_01',
			'cta_quote_request_01',
			'tp_trust_band_01',
			'fb_benefit_band_01',
			'cta_booking_01',
			'tp_guarantee_01',
			'cta_service_detail_02',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_informational_educational_01',
			'Informational detail (educational)',
			'Child/detail page for an informational entity with educational emphasis: compact hero, value prop, how-it-works, expectations, consultation CTA, why choose, testimonial, service flow, quote CTA, trust band, benefit band, booking CTA, guarantee, service CTA, contact panel, contact CTA.',
			'informational_detail',
			'informational',
			array( 'informational' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Informational detail educational. Process and expectations before CTAs; consultation, quote, service, contact.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Educational emphasis; conversion after learning.',
				'cta_direction_summary' => 'Consultation, quote, booking, service detail, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Educational; process and expectations lead.',
			)
		);
	}

	public static function child_detail_informational_proof_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_value_prop_01',
			'tp_trust_band_01',
			'tp_testimonial_01',
			'cta_consultation_01',
			'fb_why_choose_01',
			'tp_guarantee_01',
			'ptf_how_it_works_01',
			'cta_quote_request_01',
			'tp_client_logo_01',
			'fb_benefit_band_01',
			'cta_service_detail_01',
			'ptf_expectations_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_informational_proof_01',
			'Informational detail (proof-led)',
			'Child/detail page for an informational entity with proof emphasis: credibility hero, value prop, trust band, testimonial, consultation CTA, why choose, guarantee, how-it-works, quote CTA, client logos, benefit band, service CTA, expectations, booking CTA, contact panel, contact CTA.',
			'informational_detail',
			'informational',
			array( 'informational' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Informational detail proof-led. Trust, testimonial, guarantee, logos before CTAs; consultation, quote, service, contact.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Proof-first; trust and guarantee support conversion.',
				'cta_direction_summary' => 'Consultation, quote, service detail, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Proof-led; trust, testimonial, guarantee, logos.',
			)
		);
	}
}
