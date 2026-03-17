<?php
/**
 * Geographic hub page template definitions for locations, areas, service areas, regional coverage (spec §13, §14, §16, Prompt 158).
 * template_category_class = hub; template_family = service_area, regional, city_directory, location_overview, coverage_listing, neighborhood, campus.
 * ~10 non-CTA + ≥4 CTA sections, last CTA, no adjacent CTA. Local relevance structure, synthetic preview only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\GeographicHubBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Returns page template definitions for the geographic hub batch (PT-04 scope).
 * Guides visitors into nested hub or child pages (cities, regions, neighborhoods, campuses, service areas).
 */
final class Geographic_Hub_Page_Template_Definitions {

	/** Batch ID for geographic hub pages (template-library-inventory-manifest PT-04). */
	public const BATCH_ID = 'PT-04';

	/** Industry keys for first launch verticals (page-template-industry-affinity-contract; Prompt 364). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Returns all geographic hub page template definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::hub_geo_service_area_01(),
			self::hub_geo_service_area_02(),
			self::hub_geo_regional_01(),
			self::hub_geo_regional_02(),
			self::hub_geo_city_directory_01(),
			self::hub_geo_city_directory_02(),
			self::hub_geo_location_overview_01(),
			self::hub_geo_location_overview_02(),
			self::hub_geo_coverage_listing_01(),
			self::hub_geo_coverage_listing_02(),
			self::hub_geo_neighborhood_01(),
			self::hub_geo_campus_01(),
			self::hub_geo_area_trust_01(),
			self::hub_geo_location_directory_01(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'hub_geo_service_area_01',
			'hub_geo_service_area_02',
			'hub_geo_regional_01',
			'hub_geo_regional_02',
			'hub_geo_city_directory_01',
			'hub_geo_city_directory_02',
			'hub_geo_location_overview_01',
			'hub_geo_location_overview_02',
			'hub_geo_coverage_listing_01',
			'hub_geo_coverage_listing_02',
			'hub_geo_neighborhood_01',
			'hub_geo_campus_01',
			'hub_geo_area_trust_01',
			'hub_geo_location_directory_01',
		);
	}

	/**
	 * Builds ordered_sections and section_requirements from a list of section keys.
	 *
	 * @param list<string> $section_keys Section internal keys in order (no adjacent CTA; last must be CTA).
	 * @return array{ ordered: list<array<string, mixed>>, requirements: array<string, array{required: bool}> }
	 */
	private static function ordered_and_requirements( array $section_keys ): array {
		$ordered = array();
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
	 * Base page template shape for geographic hub batch (template_category_class = hub).
	 *
	 * @param string $internal_key
	 * @param string $name
	 * @param string $purpose_summary
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
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY           => $purpose_summary,
			Page_Template_Schema::FIELD_ARCHETYPE                 => 'hub_page',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS          => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS      => $section_requirements,
			Page_Template_Schema::FIELD_COMPATIBILITY             => array(),
			Page_Template_Schema::FIELD_ONE_PAGER                 => $one_pager,
			Page_Template_Schema::FIELD_VERSION                   => array( 'version' => '1', 'stable_key_retained' => true ),
			Page_Template_Schema::FIELD_STATUS                    => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES   => $endpoint_notes,
			'template_category_class'                            => 'hub',
			'template_family'                                     => $template_family,
		);
		if ( ! isset( $extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] ) ) {
			$extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] = self::LAUNCH_INDUSTRIES;
		}
		return array_merge( $def, $extra );
	}

	public static function hub_geo_service_area_01(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'mlp_location_info_01',
			'cta_local_action_01',
			'mlp_place_highlight_01',
			'mlp_card_grid_01',
			'tp_trust_band_01',
			'cta_contact_01',
			'lpu_contact_detail_01',
			'ptf_expectations_01',
			'lpu_contact_panel_01',
			'cta_local_action_02',
			'mlp_listing_01',
			'cta_directory_nav_01',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_service_area_01',
			'Service area hub',
			'Geographic hub for service area: local hero, local value, location info, local CTA, place highlight, card grid, trust band, contact CTA, contact detail, expectations, contact panel, local CTA, listing, directory nav CTA. Guides to nested areas.',
			'service_area',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Service area hub. Local value and location info; local CTA; place highlight and card grid; trust band and contact CTA; contact detail and expectations; contact panel and local CTA; listing; directory nav CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Service area framing; local and contact and directory CTAs. Semantic headings (spec §51.6).',
				'drill_down_intent'    => 'Local action and directory nav CTAs support drill-down to cities, regions, or sub–service areas. No real addresses; synthetic preview only.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata' => array( 'synthetic' => true ),
				Page_Template_Schema::FIELD_INDUSTRY_NOTES => array(
					'cosmetology_nail' => 'Good for multi-location or area coverage.',
					'realtor'          => 'Strong fit for service-area and coverage hierarchy.',
					'plumber'          => 'Strong fit for service-area and 24/7 coverage.',
					'disaster_recovery' => 'Strong fit for service-area and response coverage.',
				),
			)
		);
	}

	public static function hub_geo_service_area_02(): array {
		$keys = array(
			'hero_local_01',
			'mlp_location_info_01',
			'mlp_place_highlight_01',
			'cta_local_action_01',
			'tp_reassurance_01',
			'fb_local_value_01',
			'mlp_listing_01',
			'cta_directory_nav_01',
			'lpu_contact_detail_01',
			'mlp_card_grid_01',
			'tp_trust_band_01',
			'cta_contact_02',
			'lpu_contact_panel_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_service_area_02',
			'Service area hub (listing-led)',
			'Service area hub listing-led: local hero, location info, place highlight, local CTA, reassurance, local value, listing, directory nav CTA, contact detail, card grid, trust band, contact CTA, contact panel, local CTA. Listing density emphasis.',
			'service_area',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Service area hub listing-led. Location info and place highlight; local CTA; reassurance and local value; listing and directory nav CTA; contact detail and card grid; trust band and contact CTA; contact panel; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Listing density; coverage navigation. Local CTA direction notes in one-pager.',
				'drill_down_intent'    => 'Directory nav and local CTAs support area-specific listing and nested hub drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_regional_01(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'mlp_location_info_01',
			'cta_local_action_01',
			'mlp_place_highlight_01',
			'mlp_card_grid_01',
			'tp_reassurance_01',
			'cta_local_action_02',
			'lpu_contact_detail_01',
			'lpu_contact_panel_01',
			'ptf_expectations_01',
			'cta_contact_01',
			'mlp_listing_01',
			'cta_directory_nav_01',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_regional_01',
			'Regional hub',
			'Regional geographic hub: local hero, local value, location info, local CTA, place highlight, card grid, reassurance, local CTA, contact detail, contact panel, expectations, contact CTA, listing, directory nav CTA. Regional coverage intro.',
			'regional',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Regional hub. Local value and location info; local CTA; place highlight and card grid; reassurance and local CTA; contact detail and panel; expectations and contact CTA; listing; directory nav CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Regional emphasis; local and contact and directory CTAs for drill-down to cities or sub-regions.',
				'drill_down_intent'    => 'Guides into nested hub or child pages (e.g. state/region to cities). Hierarchy role: broad geographic hub.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_regional_02(): array {
		$keys = array(
			'hero_local_01',
			'mlp_place_highlight_01',
			'mlp_location_info_01',
			'cta_directory_nav_01',
			'fb_local_value_01',
			'mlp_listing_01',
			'tp_trust_band_01',
			'cta_local_action_01',
			'lpu_contact_detail_01',
			'mlp_card_grid_01',
			'ptf_expectations_01',
			'cta_contact_02',
			'tp_reassurance_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_regional_02',
			'Regional hub (coverage-led)',
			'Regional hub coverage-led: local hero, place highlight, location info, directory nav CTA, local value, listing, trust band, local CTA, contact detail, card grid, expectations, contact CTA, reassurance, local CTA. Coverage navigation emphasis.',
			'regional',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Regional hub coverage-led. Place highlight and location info; directory nav CTA; local value and listing; trust band and local CTA; contact detail and card grid; expectations and contact CTA; reassurance; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Coverage and area navigation; local CTA orientation. SEO-relevant structure (spec §15.9).',
				'drill_down_intent'    => 'Directory nav and local CTAs support coverage map/list drill-down to cities or regions.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_city_directory_01(): array {
		$keys = array(
			'hero_local_01',
			'mlp_card_grid_01',
			'mlp_listing_01',
			'cta_directory_nav_01',
			'fb_local_value_01',
			'mlp_location_info_01',
			'tp_reassurance_01',
			'cta_local_action_01',
			'mlp_place_highlight_01',
			'lpu_contact_detail_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
			'ptf_expectations_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_city_directory_01',
			'City directory hub',
			'City/region directory hub: local hero, card grid, listing, directory nav CTA, local value, location info, reassurance, local CTA, place highlight, contact detail, contact panel, contact CTA, expectations, local CTA. City directory use case.',
			'city_directory',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'City directory hub. Card grid and listing; directory nav CTA; local value and location info; reassurance and local CTA; place highlight and contact detail; contact panel and contact CTA; expectations; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'City/region directory structure; related-location navigation. Accessibility (spec §51.3).',
				'drill_down_intent'    => 'Guides into city or region child/detail pages. Listing and cards support area-specific directory.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_city_directory_02(): array {
		$keys = array(
			'hero_local_01',
			'mlp_listing_01',
			'fb_local_value_01',
			'cta_local_action_01',
			'mlp_card_grid_01',
			'mlp_place_highlight_01',
			'tp_trust_band_01',
			'cta_directory_nav_01',
			'lpu_contact_detail_01',
			'ptf_expectations_01',
			'mlp_location_info_01',
			'cta_contact_02',
			'tp_reassurance_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_city_directory_02',
			'City directory hub (educational)',
			'City directory hub educational balance: local hero, listing, local value, local CTA, card grid, place highlight, trust band, directory nav CTA, contact detail, expectations, location info, contact CTA, reassurance, local CTA. Educational vs conversion balance.',
			'city_directory',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'City directory hub educational. Listing and local value; local CTA; card grid and place highlight; trust band and directory nav CTA; contact detail and expectations; location info and contact CTA; reassurance; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Educational versus conversion balance; local relevance structure.',
				'drill_down_intent'    => 'Supports geographic category introduction and city/region drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_location_overview_01(): array {
		$keys = array(
			'hero_local_01',
			'mlp_location_info_01',
			'mlp_place_highlight_01',
			'cta_local_action_01',
			'fb_local_value_01',
			'mlp_card_grid_01',
			'tp_trust_band_01',
			'cta_contact_01',
			'lpu_contact_detail_01',
			'ptf_expectations_01',
			'lpu_contact_panel_01',
			'cta_local_action_02',
			'mlp_listing_01',
			'cta_directory_nav_01',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_location_overview_01',
			'Location overview hub',
			'Location overview hub: local hero, location info, place highlight, local CTA, local value, card grid, trust band, contact CTA, contact detail, expectations, contact panel, local CTA, listing, directory nav CTA. Location-overview style.',
			'location_overview',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Location overview hub. Location info and place highlight; local CTA; local value and card grid; trust band and contact CTA; contact detail and expectations; contact panel and local CTA; listing; directory nav CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Location-overview; local and contact and directory CTAs. Hierarchy role: broad location landing.',
				'drill_down_intent'    => 'Guides into nested hub or child location pages (areas, campuses, neighborhoods).',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_location_overview_02(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'mlp_place_highlight_01',
			'cta_directory_nav_01',
			'mlp_location_info_01',
			'mlp_card_grid_01',
			'tp_reassurance_01',
			'cta_local_action_01',
			'lpu_contact_detail_01',
			'mlp_listing_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
			'ptf_expectations_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_location_overview_02',
			'Location overview hub (conversion-led)',
			'Location overview hub conversion-led: local hero, local value, place highlight, directory nav CTA, location info, card grid, reassurance, local CTA, contact detail, listing, contact panel, contact CTA, expectations, local CTA. Local CTA orientation.',
			'location_overview',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Location overview hub conversion-led. Local value and place highlight; directory nav CTA; location info and card grid; reassurance and local CTA; contact detail and listing; contact panel and contact CTA; expectations; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Local CTA orientation; conversion balance. Preview realism with synthetic data only.',
				'drill_down_intent'    => 'Local action and contact CTAs support area-specific conversion and drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_coverage_listing_01(): array {
		$keys = array(
			'hero_local_01',
			'mlp_listing_01',
			'mlp_card_grid_01',
			'cta_directory_nav_01',
			'fb_local_value_01',
			'mlp_location_info_01',
			'tp_trust_band_01',
			'cta_local_action_01',
			'mlp_place_highlight_01',
			'lpu_contact_detail_01',
			'ptf_expectations_01',
			'cta_contact_02',
			'tp_reassurance_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_coverage_listing_01',
			'Coverage listing hub',
			'Coverage map/listing hub: local hero, listing, card grid, directory nav CTA, local value, location info, trust band, local CTA, place highlight, contact detail, expectations, contact CTA, reassurance, local CTA. Coverage listings emphasis.',
			'coverage_listing',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Coverage listing hub. Listing and card grid; directory nav CTA; local value and location info; trust band and local CTA; place highlight and contact detail; expectations and contact CTA; reassurance; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Coverage listings; area-specific listing structures. No real addresses; synthetic only.',
				'drill_down_intent'    => 'Directory nav and local CTAs support coverage drill-down to regions or locations.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_coverage_listing_02(): array {
		$keys = array(
			'hero_local_01',
			'mlp_card_grid_01',
			'mlp_listing_01',
			'cta_local_action_01',
			'mlp_place_highlight_01',
			'fb_local_value_01',
			'tp_reassurance_01',
			'cta_directory_nav_01',
			'mlp_location_info_01',
			'lpu_contact_panel_01',
			'lpu_contact_detail_01',
			'cta_contact_01',
			'ptf_expectations_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_coverage_listing_02',
			'Coverage listing hub (proof-led)',
			'Coverage listing hub proof-led: local hero, card grid, listing, local CTA, place highlight, local value, reassurance, directory nav CTA, location info, contact panel, contact detail, contact CTA, expectations, local CTA. Local proof density.',
			'coverage_listing',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Coverage listing hub proof-led. Card grid and listing; local CTA; place highlight and local value; reassurance and directory nav CTA; location info and contact panel; contact detail and contact CTA; expectations; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Proof density and local trust emphasis; coverage navigation.',
				'drill_down_intent'    => 'Supports geographic category landing with proof and listing drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_neighborhood_01(): array {
		$keys = array(
			'hero_local_01',
			'mlp_place_highlight_01',
			'fb_local_value_01',
			'cta_local_action_01',
			'mlp_location_info_01',
			'mlp_card_grid_01',
			'tp_trust_band_01',
			'cta_directory_nav_01',
			'lpu_contact_detail_01',
			'mlp_listing_01',
			'tp_reassurance_01',
			'cta_contact_01',
			'lpu_contact_panel_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_neighborhood_01',
			'Neighborhood hub',
			'Neighborhood geographic hub: local hero, place highlight, local value, local CTA, location info, card grid, trust band, directory nav CTA, contact detail, listing, reassurance, contact CTA, contact panel, local CTA. Neighborhood use case.',
			'neighborhood',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Neighborhood hub. Place highlight and local value; local CTA; location info and card grid; trust band and directory nav CTA; contact detail and listing; reassurance and contact CTA; contact panel; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Neighborhood framing; locality and area-specific structure. Semantic clarity (spec §51.6).',
				'drill_down_intent'    => 'Guides into neighborhood or sub-area pages. No real addresses; synthetic preview only.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata' => array( 'synthetic' => true ),
				Page_Template_Schema::FIELD_INDUSTRY_NOTES => array(
					'cosmetology_nail' => 'Optional for neighborhood or area pages.',
					'realtor'          => 'Strong fit for neighborhood and market-area hierarchy.',
					'plumber'          => 'Good for area or coverage pages.',
					'disaster_recovery' => 'Good for area or coverage pages.',
				),
			)
		);
	}

	public static function hub_geo_campus_01(): array {
		$keys = array(
			'hero_local_01',
			'mlp_location_info_01',
			'mlp_place_highlight_01',
			'cta_local_action_01',
			'fb_local_value_01',
			'mlp_card_grid_01',
			'tp_reassurance_01',
			'cta_contact_01',
			'lpu_contact_detail_01',
			'mlp_listing_01',
			'lpu_contact_panel_01',
			'cta_directory_nav_01',
			'ptf_expectations_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_campus_01',
			'Campus hub',
			'Campus geographic hub: local hero, location info, place highlight, local CTA, local value, card grid, reassurance, contact CTA, contact detail, listing, contact panel, directory nav CTA, expectations, local CTA. Campus use case.',
			'campus',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Campus hub. Location info and place highlight; local CTA; local value and card grid; reassurance and contact CTA; contact detail and listing; contact panel and directory nav CTA; expectations; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Campus framing; location summary and related-location navigation. Accessibility (spec §51.3).',
				'drill_down_intent'    => 'Guides into campus or facility sub-pages. Synthetic preview only; no private location data.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_area_trust_01(): array {
		$keys = array(
			'hero_local_01',
			'tp_trust_band_01',
			'tp_reassurance_01',
			'cta_local_action_01',
			'fb_local_value_01',
			'mlp_location_info_01',
			'mlp_place_highlight_01',
			'cta_contact_01',
			'mlp_card_grid_01',
			'lpu_contact_detail_01',
			'ptf_expectations_01',
			'cta_directory_nav_01',
			'mlp_listing_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_area_trust_01',
			'Area hub (trust-led)',
			'Geographic hub trust-led: local hero, trust band, reassurance, local CTA, local value, location info, place highlight, contact CTA, card grid, contact detail, expectations, directory nav CTA, listing, local CTA. Local trust emphasis.',
			'regional',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Area hub trust-led. Trust band and reassurance; local CTA; local value and location info; place highlight and contact CTA; card grid and contact detail; expectations and directory nav CTA; listing; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Local trust emphasis; proof pattern distinct from generic services hub. Differentiation notes in one-pager.',
				'drill_down_intent'    => 'Regional/category hub with strong local proof; supports drill-down to areas or locations.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_geo_location_directory_01(): array {
		$keys = array(
			'hero_local_01',
			'mlp_card_grid_01',
			'mlp_location_info_01',
			'cta_directory_nav_01',
			'mlp_listing_01',
			'fb_local_value_01',
			'mlp_place_highlight_01',
			'cta_local_action_01',
			'lpu_contact_detail_01',
			'tp_trust_band_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
			'ptf_expectations_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_geo_location_directory_01',
			'Location directory hub',
			'Location directory hub: local hero, card grid, location info, directory nav CTA, listing, local value, place highlight, local CTA, contact detail, trust band, contact panel, contact CTA, expectations, local CTA. Geographic category introduction.',
			'location_directory',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary' => 'Location directory hub. Card grid and location info; directory nav CTA; listing and local value; place highlight and local CTA; contact detail and trust band; contact panel and contact CTA; expectations; local CTA.',
				'section_helper_order' => 'same_as_template',
				'page_flow_explanation' => 'Geographic category introduction; location-directory structure. Hierarchy role metadata for drill-down.',
				'drill_down_intent'    => 'Guides into location-directory child pages (cities, regions, service areas). Synthetic data only.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}
}
