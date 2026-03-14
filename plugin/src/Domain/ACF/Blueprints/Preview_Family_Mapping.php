<?php
/**
 * Maps registry metadata (section_purpose_family, template category/family) to preview family keys used by Synthetic_Preview_Data_Generator (large-scale-acf-lpagery-binding-contract §3, §5.2).
 * Keeps directory/detail metadata and synthetic generator families in sync; supports aliases (e.g. contact → cta).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Blueprints;

defined( 'ABSPATH' ) || exit;

/**
 * Section and page registry purpose/category families map to generator family slugs.
 * Generator families: hero, proof, cta, contact, faq, offer, explainer, legal, listing, comparison, profile, stats, timeline, locations, location, other.
 */
final class Preview_Family_Mapping {

	/** Alias: section_purpose_family => preview family (generator switch). */
	private const SECTION_PURPOSE_TO_PREVIEW = array(
		'hero'          => 'hero',
		'proof'         => 'proof',
		'cta'           => 'cta',
		'contact'       => 'cta',
		'faq'           => 'faq',
		'offer'         => 'offer',
		'explainer'     => 'explainer',
		'legal'         => 'legal',
		'policy'        => 'legal',
		'listing'       => 'listing',
		'media'         => 'listing',
		'comparison'    => 'comparison',
		'profile'       => 'profile',
		'detail'        => 'profile',
		'stats'         => 'stats',
		'timeline'      => 'timeline',
		'process'       => 'timeline',
		'locations'     => 'locations',
		'location'      => 'locations',
		'utility'       => 'other',
		'form_support'  => 'cta',
		'related'       => 'other',
	);

	/**
	 * Returns the preview family key for synthetic data generation for a section.
	 *
	 * @param string $section_purpose_family section_purpose_family from section definition.
	 * @param string $variation_family_key   Optional; not used for generator switch but available for future refinement.
	 * @return string One of: hero, proof, cta, faq, offer, explainer, legal, listing, comparison, profile, stats, timeline, locations, other.
	 */
	public function get_preview_family_for_section( string $section_purpose_family, string $variation_family_key = '' ): string {
		$key = \sanitize_key( $section_purpose_family );
		if ( $key === '' ) {
			return 'other';
		}
		return (string) ( self::SECTION_PURPOSE_TO_PREVIEW[ $key ] ?? 'other' );
	}

	/**
	 * Returns the preview family key for a page template (e.g. for full-page preview context).
	 * Page-level preview typically uses 'other' unless template category/family implies a specific generator family.
	 *
	 * @param string $template_category_class Template category class (e.g. top_level, hub).
	 * @param string $template_family         Template family (e.g. home, landing).
	 * @return string Preview family for page-level synthetic data.
	 */
	public function get_preview_family_for_page( string $template_category_class, string $template_family = '' ): string {
		$cat = \sanitize_key( $template_category_class );
		$fam = \sanitize_key( $template_family );
		// * Page-level generator currently uses per-section purpose_family; page context is 'other' unless we add page-specific families.
		if ( $cat !== '' && $fam !== '' && $fam === 'landing' ) {
			return 'cta';
		}
		return 'other';
	}
}
