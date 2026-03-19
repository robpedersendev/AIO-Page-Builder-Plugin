<?php
/**
 * Gap-closing page template definitions for PT-14 (Prompt 183, spec §13, §62.12, template-library-coverage-matrix).
 * Fills remaining page-library gaps to reach 500 minimum with balanced class and family coverage.
 * CTA law: min CTA by class, 8–14 non-CTA, last CTA, no adjacent CTA. Uses only registered section keys.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\GapClosingSuperBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Returns page template definitions for the gap-closing super-batch (PT-14).
 * Balanced spread across top_level, hub, nested_hub, child_detail and template_family.
 */
final class Page_Template_Gap_Closing_Super_Batch_Definitions {

	/** Batch ID per template-library-inventory-manifest (balance to 500 total). */
	public const BATCH_ID = 'PT-14';

	/** Target total page template count (template-library-coverage-matrix §3.1). */
	public const PAGE_TARGET = 500;

	/** Industry keys for first launch verticals (page-template-industry-affinity-contract; Prompt 364). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/** Section keys that are CTA-classified (must exist in section registry). */
	private const CTA_SECTION_KEYS = array(
		'cta_consultation_01',
		'cta_contact_01',
		'cta_booking_01',
		'cta_inquiry_01',
		'cta_quote_request_01',
		'cta_service_detail_01',
		'cta_contact_02',
		'cta_support_01',
		'cta_inquiry_02',
		'cta_consultation_02',
		'gc_cta_inline_01',
		'gc_cta_inline_02',
	);

	/** Section keys that are non-CTA (hero, proof, offer, explainer, faq, listing, etc.). */
	private const NON_CTA_SECTION_KEYS = array(
		'hero_conv_01',
		'hero_cred_01',
		'hero_compact_01',
		'gc_hero_compact_02',
		'tp_trust_band_01',
		'tp_testimonial_01',
		'fb_value_prop_01',
		'fb_why_choose_01',
		'fb_service_offering_01',
		'ptf_how_it_works_01',
		'ptf_faq_01',
		'ptf_service_flow_01',
		'ptf_expectations_01',
		'gc_offer_01',
		'gc_explain_01',
		'gc_faq_general_01',
		'gc_listing_01',
		'gc_profile_01',
		'gc_contact_01',
		'gc_related_01',
		'gc_stats_highlights_01',
		'lpu_contact_panel_01',
		'mlp_card_grid_01',
		'mlp_listing_01',
	);

	/**
	 * Minimum CTA count by template_category_class (cta-sequencing-and-placement-contract §3).
	 *
	 * @var array<string, int>
	 */
	private const MIN_CTA_BY_CLASS = array(
		'top_level'    => 3,
		'hub'          => 4,
		'nested_hub'   => 4,
		'child_detail' => 5,
	);

	/**
	 * Builds ordered_sections and section_requirements from a list of section keys.
	 *
	 * @param array<int, string> $section_keys Section internal keys in order (no adjacent CTA; last must be CTA).
	 * @return array{ ordered: array<int, array<string, mixed>>, requirements: array<string, array{required: bool}> }
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
	 * Generates a CTA-compliant section sequence: 8–10 non-CTA, min CTA by class, last CTA, no adjacent CTA.
	 *
	 * @param string $template_category_class top_level, hub, nested_hub, child_detail.
	 * @param int    $seed Optional seed for variation (used to pick different CTA/non-CTA from pools).
	 * @return array<int, string>
	 */
	private static function build_section_sequence( string $template_category_class, int $seed = 0 ): array {
		$min_cta       = self::MIN_CTA_BY_CLASS[ $template_category_class ] ?? 3;
		$num_non_cta   = 8 + ( $seed % 3 );
		$cta_pool      = self::CTA_SECTION_KEYS;
		$non_cta_pool  = self::NON_CTA_SECTION_KEYS;
		$total         = $num_non_cta + $min_cta;
		$out           = array_fill( 0, $total, '' );
		$cta_positions = self::place_ctas( $total, $min_cta );
		$n_idx         = $seed % count( $non_cta_pool );
		$c_idx         = $seed % count( $cta_pool );
		foreach ( $cta_positions as $pos ) {
			$out[ $pos ] = $cta_pool[ $c_idx % count( $cta_pool ) ];
			++$c_idx;
		}
		for ( $i = 0; $i < $total; $i++ ) {
			if ( $out[ $i ] === '' ) {
				$out[ $i ] = $non_cta_pool[ $n_idx % count( $non_cta_pool ) ];
				++$n_idx;
			}
		}
		return $out;
	}

	/**
	 * Returns positions for CTA sections: last position included, no two adjacent (cta-sequencing §6).
	 * Places from end: last, last-3, last-6, ... so each CTA has at least one non-CTA between.
	 *
	 * @param int $total Total section count.
	 * @param int $min_cta Number of CTA positions needed.
	 * @return array<int, int>
	 */
	private static function place_ctas( int $total, int $min_cta ): array {
		if ( $total < $min_cta || $min_cta <= 0 ) {
			return array();
		}
		$last      = $total - 1;
		$positions = array();
		for ( $i = 0; $i < $min_cta; $i++ ) {
			$pos = $last - $i * 3;
			if ( $pos >= 0 ) {
				$positions[] = $pos;
			}
		}
		sort( $positions );
		return array_values( array_unique( $positions ) );
	}

	/**
	 * Returns spec rows for gap-closing templates: key, name, purpose, class, family, archetype.
	 *
	 * @return array<int, array{key: string, name: string, purpose: string, class: string, family: string, archetype: string}>
	 */
	private static function specs(): array {
		$specs              = array();
		$n                  = 1;
		$families_by_class  = array(
			'top_level'    => array( 'home', 'about', 'contact', 'informational', 'faq', 'privacy', 'terms', 'services', 'offerings' ),
			'hub'          => array( 'services', 'products', 'offerings', 'directories', 'locations', 'about', 'events', 'profiles', 'faq', 'informational' ),
			'nested_hub'   => array( 'services', 'products', 'offerings', 'directories', 'locations', 'events', 'profiles' ),
			'child_detail' => array( 'services', 'offerings', 'locations', 'products', 'profiles', 'directories', 'events', 'informational', 'faq', 'contact', 'comparison' ),
		);
		$archetype_by_class = array(
			'top_level'    => 'landing_page',
			'hub'          => 'hub_page',
			'nested_hub'   => 'sub_hub_page',
			'child_detail' => 'service_page',
		);
		$targets            = array(
			'top_level'    => 70,
			'hub'          => 80,
			'nested_hub'   => 75,
			'child_detail' => 130,
		);
		foreach ( $targets as $class => $count ) {
			$families  = $families_by_class[ $class ];
			$archetype = $archetype_by_class[ $class ];
			for ( $i = 0; $i < $count; $i++ ) {
				$family  = $families[ $i % count( $families ) ];
				$num     = $i + 1;
				$specs[] = array(
					'key'       => 'pt_gap_' . $class . '_' . str_pad( (string) $num, 3, '0', STR_PAD_LEFT ),
					'name'      => 'Gap ' . $class . ' ' . $family . ' ' . $num,
					'purpose'   => 'Gap-closing page template for ' . $class . ' / ' . $family . '. Balanced content and CTA flow per cta-sequencing contract.',
					'class'     => $class,
					'family'    => $family,
					'archetype' => $archetype,
				);
				++$n;
			}
		}
		return $specs;
	}

	/**
	 * Builds a single page template definition from a spec row.
	 *
	 * @param array{key: string, name: string, purpose: string, class: string, family: string, archetype: string} $spec
	 * @param int                                                                                                 $index 0-based index (used as seed for section sequence).
	 * @return array<string, mixed>
	 */
	private static function build_definition( array $spec, int $index ): array {
		$seq = self::build_section_sequence( $spec['class'], $index );
		$r   = self::ordered_and_requirements( $seq );
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY      => $spec['key'],
			Page_Template_Schema::FIELD_NAME              => $spec['name'],
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY   => $spec['purpose'],
			Page_Template_Schema::FIELD_ARCHETYPE         => $spec['archetype'],
			Page_Template_Schema::FIELD_ORDERED_SECTIONS  => $r['ordered'],
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $r['requirements'],
			Page_Template_Schema::FIELD_COMPATIBILITY     => array(),
			Page_Template_Schema::FIELD_ONE_PAGER         => array(
				'page_purpose_summary'  => $spec['purpose'],
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Gap-closing template with CTA-compliant section order. Semantic headings per section.',
			),
			Page_Template_Schema::FIELD_VERSION           => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Page_Template_Schema::FIELD_STATUS            => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => 'PT-14 gap-closing batch. Requires section library (SEC-01–SEC-09).',
			'template_category_class'                     => $spec['class'],
			'template_family'                             => $spec['family'],
			'differentiation_notes'                       => 'Fills ' . $spec['family'] . ' / ' . $spec['class'] . ' coverage for 500-template minimum.',
			'preview_metadata'                            => array( 'synthetic' => true ),
			Page_Template_Schema::FIELD_INDUSTRY_AFFINITY => self::LAUNCH_INDUSTRIES,
		);
	}

	/**
	 * Returns all gap-closing page template definitions (order preserved for seeding).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all_definitions(): array {
		$specs = self::specs();
		$out   = array();
		foreach ( $specs as $i => $spec ) {
			$out[] = self::build_definition( $spec, $i );
		}
		return $out;
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return array<int, string>
	 */
	public static function template_keys(): array {
		$keys = array();
		foreach ( self::specs() as $spec ) {
			$keys[] = $spec['key'];
		}
		return $keys;
	}
}
