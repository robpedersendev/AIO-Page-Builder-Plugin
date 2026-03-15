<?php
/**
 * Child/detail page template definitions for directory profiles, entities, and resource detail pages (spec §13, §14.3, §16, §17.7, §51.3, Prompt 162).
 * template_category_class = child_detail; archetype = profile_page | directory_page | event_page | informational_detail.
 * ~10 non-CTA (8–14) + ≥5 CTA sections, mandatory bottom CTA, no adjacent CTA. Profile/entity/resource families.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailProfileEntityBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Returns page template definitions for the directory/profile/entity/resource child/detail batch (PT-09 scope).
 * Singular directory member, staff/provider profile, place/entity detail, organization profile, article/resource detail.
 */
final class Child_Detail_Profile_Entity_Page_Template_Definitions {

	/** Batch ID for directory/profile/entity/resource child/detail pages (template-library-inventory-manifest PT-09). */
	public const BATCH_ID = 'PT-09';

	/** Industry keys for first launch verticals (page-template-industry-affinity-contract; Prompt 364). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Allowed template families for this batch (page-template-category-taxonomy-contract).
	 *
	 * @var list<string>
	 */
	public const ALLOWED_FAMILIES = array(
		'directories',
		'profiles',
		'events',
		'informational',
	);

	/**
	 * Returns all profile/entity/resource child/detail page template definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::child_detail_profile_first_01(),
			self::child_detail_profile_proof_01(),
			self::child_detail_directory_member_01(),
			self::child_detail_directory_entity_01(),
			self::child_detail_staff_provider_01(),
			self::child_detail_organization_profile_01(),
			self::child_detail_place_entity_01(),
			self::child_detail_resource_article_01(),
			self::child_detail_resource_educational_01(),
			self::child_detail_profile_media_01(),
			self::child_detail_entity_local_01(),
			self::child_detail_authority_detail_01(),
			self::child_detail_profile_comparison_01(),
			self::child_detail_event_detail_01(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'child_detail_profile_first_01',
			'child_detail_profile_proof_01',
			'child_detail_directory_member_01',
			'child_detail_directory_entity_01',
			'child_detail_staff_provider_01',
			'child_detail_organization_profile_01',
			'child_detail_place_entity_01',
			'child_detail_resource_article_01',
			'child_detail_resource_educational_01',
			'child_detail_profile_media_01',
			'child_detail_entity_local_01',
			'child_detail_authority_detail_01',
			'child_detail_profile_comparison_01',
			'child_detail_event_detail_01',
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
	 * Base page template shape for profile/entity/resource child/detail batch.
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
			Page_Template_Schema::FIELD_INTERNAL_KEY               => $internal_key,
			Page_Template_Schema::FIELD_NAME                       => $name,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY            => $purpose_summary,
			Page_Template_Schema::FIELD_ARCHETYPE                  => $archetype,
			Page_Template_Schema::FIELD_ORDERED_SECTIONS           => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS       => $section_requirements,
			Page_Template_Schema::FIELD_COMPATIBILITY              => array(),
			Page_Template_Schema::FIELD_ONE_PAGER                  => $one_pager,
			Page_Template_Schema::FIELD_VERSION                    => array( 'version' => '1', 'stable_key_retained' => true ),
			Page_Template_Schema::FIELD_STATUS                     => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES    => $endpoint_notes,
			'template_category_class'                             => 'child_detail',
			'template_family'                                      => $template_family,
			'parent_family_compatibility'                          => $parent_family_compatibility,
			'hierarchy_hints'                                      => array(
				'common_parent_page_types' => 'hub, nested_hub',
				'hierarchy_role'           => 'leaf',
			),
		);
		if ( ! isset( $extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] ) ) {
			$extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] = self::LAUNCH_INDUSTRIES;
		}
		return array_merge( $def, $extra );
	}

	public static function child_detail_profile_first_01(): array {
		$keys = array(
			'hero_cred_01',
			'mlp_profile_summary_01',
			'fb_value_prop_01',
			'cta_consultation_01',
			'tp_testimonial_01',
			'fb_why_choose_01',
			'ptf_how_it_works_01',
			'cta_contact_01',
			'mlp_team_grid_01',
			'tp_trust_band_01',
			'cta_directory_nav_01',
			'ptf_expectations_01',
			'lpu_contact_panel_01',
			'cta_booking_01',
			'mlp_related_content_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_profile_first_01',
			'Profile detail (profile-first)',
			'Child/detail page for a singular profile with profile-first flow: credibility hero, profile summary, value prop, consultation CTA, testimonial, why choose, how-it-works, contact CTA, team grid, trust band, directory nav CTA, expectations, contact panel, booking CTA, related content, contact CTA.',
			'profile_page',
			'profiles',
			array( 'profiles', 'directories' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single profile with profile-first structure. Profile summary and team grid; consultation, contact, directory nav, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Profile-first; bio and team support authority. Synthetic preview only; no real staff data.',
				'cta_direction_summary'  => 'Consultation, contact, directory nav, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Profile-first; profile summary and team grid lead.' )
		);
	}

	public static function child_detail_profile_proof_01(): array {
		$keys = array(
			'hero_cred_01',
			'mlp_profile_summary_01',
			'tp_trust_band_01',
			'tp_testimonial_01',
			'cta_consultation_01',
			'tp_guarantee_01',
			'fb_why_choose_01',
			'tp_client_logo_01',
			'cta_contact_01',
			'ptf_how_it_works_01',
			'mlp_profile_cards_01',
			'cta_directory_nav_01',
			'tp_testimonial_02',
			'ptf_expectations_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_profile_proof_01',
			'Profile detail (proof-heavy)',
			'Child/detail page for a singular profile with proof density: credibility hero, profile summary, trust band, testimonials, consultation CTA, guarantee, why choose, client logos, contact CTA, how-it-works, profile cards, directory nav CTA, testimonial, expectations, booking CTA, contact panel, contact CTA.',
			'profile_page',
			'profiles',
			array( 'profiles' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single profile proof-heavy. Trust band, testimonials, guarantee, logos; consultation, contact, directory nav, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Proof-dense; authority and social proof before CTAs.',
				'cta_direction_summary'  => 'Consultation, contact, directory nav, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Proof-heavy; testimonials, guarantee, logos.' )
		);
	}

	public static function child_detail_directory_member_01(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_directory_entry_01',
			'mlp_profile_summary_01',
			'cta_directory_nav_01',
			'fb_directory_value_01',
			'tp_testimonial_01',
			'ptf_how_it_works_01',
			'cta_contact_01',
			'mlp_card_grid_01',
			'tp_trust_band_01',
			'cta_consultation_01',
			'mlp_related_content_01',
			'ptf_expectations_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_directory_member_01',
			'Directory member / provider detail',
			'Child/detail page for a singular directory member or provider: directory hero, directory entry, profile summary, directory nav CTA, directory value, testimonial, how-it-works, contact CTA, card grid, trust band, consultation CTA, related content, expectations, booking CTA, contact panel, contact CTA.',
			'directory_page',
			'directories',
			array( 'directories', 'profiles' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single directory member/provider. Directory entry and profile summary; directory nav, contact, consultation, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Directory member detail; no dynamic directory engine. Synthetic preview only.',
				'cta_direction_summary'  => 'Directory nav, contact, consultation, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Directory member; directory entry and value.' )
		);
	}

	public static function child_detail_directory_entity_01(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_directory_entry_01',
			'fb_directory_value_01',
			'cta_directory_nav_01',
			'mlp_detail_spec_01',
			'tp_trust_band_01',
			'ptf_how_it_works_01',
			'cta_contact_01',
			'mlp_place_highlight_01',
			'mlp_listing_01',
			'cta_consultation_01',
			'tp_testimonial_01',
			'mlp_related_content_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_directory_entity_01',
			'Directory entity detail',
			'Child/detail page for a singular directory entity (place or organization): directory hero, directory entry, directory value, directory nav CTA, detail spec, trust band, how-it-works, contact CTA, place highlight, listing, consultation CTA, testimonial, related content, booking CTA, contact panel, contact CTA.',
			'directory_page',
			'directories',
			array( 'directories' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single directory entity. Detail spec and place highlight; directory nav, contact, consultation, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Entity detail beneath directory hub; spec and listing support.',
				'cta_direction_summary'  => 'Directory nav, contact, consultation, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Directory entity; detail spec and place highlight.' )
		);
	}

	public static function child_detail_staff_provider_01(): array {
		$keys = array(
			'hero_cred_01',
			'mlp_profile_summary_01',
			'fb_value_prop_01',
			'cta_consultation_01',
			'mlp_team_grid_01',
			'tp_testimonial_01',
			'ptf_expectations_01',
			'cta_contact_01',
			'fb_why_choose_01',
			'tp_trust_band_01',
			'cta_directory_nav_01',
			'mlp_profile_cards_01',
			'ptf_how_it_works_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_staff_provider_01',
			'Staff / provider profile detail',
			'Child/detail page for a singular staff or provider profile: credibility hero, profile summary, value prop, consultation CTA, team grid, testimonial, expectations, contact CTA, why choose, trust band, directory nav CTA, profile cards, how-it-works, booking CTA, contact panel, contact CTA.',
			'profile_page',
			'profiles',
			array( 'profiles', 'directories' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single staff/provider profile. Profile summary and team/profile cards; consultation, contact, directory nav, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Staff/provider profile; no user account system. Synthetic preview only.',
				'cta_direction_summary'  => 'Consultation, contact, directory nav, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Staff/provider profile; team and profile cards.' )
		);
	}

	public static function child_detail_organization_profile_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_value_prop_01',
			'mlp_profile_summary_01',
			'cta_contact_01',
			'tp_trust_band_01',
			'fb_why_choose_01',
			'mlp_team_grid_01',
			'cta_directory_nav_01',
			'ptf_how_it_works_01',
			'tp_testimonial_01',
			'cta_consultation_01',
			'mlp_related_content_01',
			'ptf_expectations_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_organization_profile_01',
			'Organization profile detail',
			'Child/detail page for a singular organization profile: credibility hero, value prop, profile summary, contact CTA, trust band, why choose, team grid, directory nav CTA, how-it-works, testimonial, consultation CTA, related content, expectations, booking CTA, contact panel, contact CTA.',
			'profile_page',
			'directories',
			array( 'directories', 'about' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single organization profile. Value prop and team grid; contact, directory nav, consultation, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Organization profile; authority and team. Synthetic preview only.',
				'cta_direction_summary'  => 'Contact, directory nav, consultation, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Organization profile; value and team.' )
		);
	}

	public static function child_detail_place_entity_01(): array {
		$keys = array(
			'hero_local_01',
			'mlp_place_highlight_01',
			'mlp_location_info_01',
			'cta_local_action_01',
			'fb_local_value_01',
			'tp_trust_band_01',
			'ptf_expectations_01',
			'cta_contact_01',
			'mlp_detail_spec_01',
			'mlp_related_content_01',
			'cta_directory_nav_01',
			'tp_reassurance_01',
			'lpu_contact_detail_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_place_entity_01',
			'Place / entity detail',
			'Child/detail page for a singular place or entity: local hero, place highlight, location info, local CTA, local value, trust band, expectations, contact CTA, detail spec, related content, directory nav CTA, reassurance, contact detail, booking CTA, contact panel, local CTA.',
			'location_page',
			'directories',
			array( 'directories', 'locations' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single place/entity detail. Place highlight and location info; local, contact, directory nav, booking, local CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Place/entity beneath hub; no real addresses. Synthetic preview only.',
				'cta_direction_summary'  => 'Local action, contact, directory nav, booking, local action; last CTA local.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Place/entity; location and detail spec.' )
		);
	}

	public static function child_detail_resource_article_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_value_prop_01',
			'ptf_how_it_works_01',
			'cta_consultation_01',
			'mlp_related_content_01',
			'tp_testimonial_01',
			'ptf_expectations_01',
			'cta_contact_01',
			'fb_why_choose_01',
			'mlp_detail_spec_01',
			'cta_directory_nav_01',
			'tp_trust_band_01',
			'mlp_recommendation_band_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_resource_article_01',
			'Article / resource detail',
			'Child/detail page for a singular article or resource: credibility hero, value prop, how-it-works, consultation CTA, related content, testimonial, expectations, contact CTA, why choose, detail spec, directory nav CTA, trust band, recommendation band, booking CTA, contact panel, contact CTA.',
			'informational_detail',
			'informational',
			array( 'informational', 'directories' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single article/resource detail. Related content and recommendation band; consultation, contact, directory nav, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Resource/article detail; no live publishing workflow. Synthetic preview only.',
				'cta_direction_summary'  => 'Consultation, contact, directory nav, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Article/resource; related content and recommendation.' )
		);
	}

	public static function child_detail_resource_educational_01(): array {
		$keys = array(
			'hero_compact_01',
			'fb_value_prop_01',
			'ptf_how_it_works_01',
			'ptf_expectations_01',
			'cta_consultation_01',
			'fb_why_choose_01',
			'mlp_detail_spec_01',
			'ptf_service_flow_01',
			'cta_contact_01',
			'tp_trust_band_01',
			'mlp_related_content_01',
			'cta_directory_nav_01',
			'tp_testimonial_01',
			'mlp_recommendation_band_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_resource_educational_01',
			'Resource detail (educational deep-dive)',
			'Child/detail page for a singular resource with educational depth: compact hero, value prop, how-it-works, expectations, consultation CTA, why choose, detail spec, service flow, contact CTA, trust band, related content, directory nav CTA, testimonial, recommendation band, booking CTA, contact panel, contact CTA.',
			'informational_detail',
			'informational',
			array( 'informational' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single resource educational deep-dive. How-it-works, expectations, detail spec, service flow; consultation, contact, directory nav, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Educational emphasis; process and spec before CTAs.',
				'cta_direction_summary'  => 'Consultation, contact, directory nav, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Educational deep-dive; process and spec lead.' )
		);
	}

	public static function child_detail_profile_media_01(): array {
		$keys = array(
			'hero_cred_01',
			'mlp_profile_summary_01',
			'mlp_gallery_01',
			'cta_consultation_01',
			'mlp_media_band_01',
			'fb_value_prop_01',
			'tp_testimonial_01',
			'cta_contact_01',
			'mlp_profile_cards_01',
			'ptf_how_it_works_01',
			'cta_directory_nav_01',
			'tp_trust_band_01',
			'mlp_related_content_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_profile_media_01',
			'Profile detail (media-led)',
			'Child/detail page for a singular profile with media emphasis: credibility hero, profile summary, gallery, consultation CTA, media band, value prop, testimonial, contact CTA, profile cards, how-it-works, directory nav CTA, trust band, related content, booking CTA, contact panel, contact CTA.',
			'profile_page',
			'profiles',
			array( 'profiles' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single profile media-led. Gallery and media band; consultation, contact, directory nav, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Media-led; gallery and media band support profile.',
				'cta_direction_summary'  => 'Consultation, contact, directory nav, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Media-led; gallery and media band.' )
		);
	}

	public static function child_detail_entity_local_01(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'mlp_location_info_01',
			'cta_local_action_01',
			'mlp_place_highlight_01',
			'tp_reassurance_01',
			'ptf_expectations_01',
			'cta_contact_01',
			'mlp_detail_spec_01',
			'tp_trust_band_01',
			'cta_directory_nav_01',
			'lpu_contact_detail_01',
			'mlp_related_content_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_local_action_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_entity_local_01',
			'Entity detail (local-detail emphasis)',
			'Child/detail page for a singular local entity with local-detail emphasis: local hero, local value, location info, local CTA, place highlight, reassurance, expectations, contact CTA, detail spec, trust band, directory nav CTA, contact detail, related content, booking CTA, contact panel, local CTA.',
			'location_page',
			'directories',
			array( 'directories', 'locations' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single local entity. Local value and place highlight; local, contact, directory nav, booking, local CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Local-detail emphasis; reassurance and contact detail. Synthetic only.',
				'cta_direction_summary'  => 'Local action, contact, directory nav, booking, local action; last CTA local.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Local-detail; location and place highlight.' )
		);
	}

	public static function child_detail_authority_detail_01(): array {
		$keys = array(
			'hero_cred_01',
			'mlp_profile_summary_01',
			'tp_trust_band_01',
			'fb_value_prop_01',
			'cta_consultation_01',
			'tp_client_logo_01',
			'ptf_how_it_works_01',
			'tp_guarantee_01',
			'cta_contact_01',
			'fb_why_choose_01',
			'mlp_team_grid_01',
			'cta_directory_nav_01',
			'tp_testimonial_01',
			'ptf_expectations_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_authority_detail_01',
			'Authority / expert profile detail',
			'Child/detail page for a singular authority or expert profile: credibility hero, profile summary, trust band, value prop, consultation CTA, client logos, how-it-works, guarantee, contact CTA, why choose, team grid, directory nav CTA, testimonial, expectations, booking CTA, contact panel, contact CTA.',
			'profile_page',
			'profiles',
			array( 'profiles' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single authority/expert profile. Trust band, logos, guarantee; consultation, contact, directory nav, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Authority-detail; credentials and proof lead.',
				'cta_direction_summary'  => 'Consultation, contact, directory nav, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Authority-detail; trust, logos, guarantee.' )
		);
	}

	public static function child_detail_profile_comparison_01(): array {
		$keys = array(
			'hero_cred_01',
			'mlp_profile_summary_01',
			'fb_value_prop_01',
			'cta_directory_nav_01',
			'mlp_comparison_cards_01',
			'fb_why_choose_01',
			'tp_testimonial_01',
			'cta_consultation_01',
			'ptf_how_it_works_01',
			'mlp_profile_cards_01',
			'cta_compare_next_01',
			'tp_trust_band_01',
			'ptf_expectations_01',
			'cta_contact_01',
			'mlp_related_content_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_profile_comparison_01',
			'Profile detail (comparison-adjacent)',
			'Child/detail page for a singular profile with comparison-adjacent structure: credibility hero, profile summary, value prop, directory nav CTA, comparison cards, why choose, testimonial, consultation CTA, how-it-works, profile cards, compare CTA, trust band, expectations, contact CTA, related content, booking CTA, contact panel, contact CTA.',
			'profile_page',
			'profiles',
			array( 'profiles', 'directories' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single profile comparison-adjacent. Comparison cards and compare CTA; directory nav, consultation, contact, booking, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Comparison-adjacent; supports decision vs alternatives.',
				'cta_direction_summary'  => 'Directory nav, consultation, compare, contact, booking, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Comparison-adjacent; comparison cards.' )
		);
	}

	public static function child_detail_event_detail_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_value_prop_01',
			'mlp_detail_spec_01',
			'cta_booking_01',
			'ptf_how_it_works_01',
			'ptf_expectations_01',
			'tp_testimonial_01',
			'cta_consultation_01',
			'mlp_place_highlight_01',
			'tp_trust_band_01',
			'cta_contact_01',
			'mlp_related_content_01',
			'fb_why_choose_01',
			'cta_directory_nav_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
		);
		$r = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_event_detail_01',
			'Event detail',
			'Child/detail page for a singular event: credibility hero, value prop, detail spec, booking CTA, how-it-works, expectations, testimonial, consultation CTA, place highlight, trust band, contact CTA, related content, why choose, directory nav CTA, contact panel, contact CTA.',
			'event_page',
			'events',
			array( 'events', 'directories' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'   => 'Single event detail. Detail spec and place highlight; booking, consultation, contact, directory nav, contact CTAs.',
				'section_helper_order'   => 'same_as_template',
				'page_flow_explanation'  => 'Event detail beneath events hub; no live event engine. Synthetic preview only.',
				'cta_direction_summary'  => 'Booking, consultation, contact, directory nav, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ), 'differentiation_notes' => 'Event detail; spec and place.' )
		);
	}
}
