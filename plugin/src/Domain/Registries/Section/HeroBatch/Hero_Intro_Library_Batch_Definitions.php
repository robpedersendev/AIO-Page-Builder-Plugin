<?php
/**
 * Hero and intro section template definitions for SEC-01 library batch (spec §12, §15, §20, §55.8, Prompt 147).
 * Production-grade hero/intro sections with full metadata, field blueprints, preview and animation metadata.
 * Does not persist; callers save via Section_Template_Repository or Hero_Intro_Library_Batch_Seeder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\HeroBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Returns section definitions for the hero/intro library batch (SEC-01).
 * Each definition is schema-compliant and includes embedded field_blueprint, taxonomy, preview and animation metadata.
 */
final class Hero_Intro_Library_Batch_Definitions {

	/** Batch ID per template-library-inventory-manifest §3.1. */
	public const BATCH_ID = 'SEC-01';

	/** Section purpose family for all in this batch. */
	public const PURPOSE_FAMILY = 'hero';

	/** Industry keys for first launch verticals (section-industry-affinity-contract; Prompt 363). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Returns all hero/intro batch section definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::hero_conv_01(),
			self::hero_conv_02(),
			self::hero_cred_01(),
			self::hero_edu_01(),
			self::hero_local_01(),
			self::hero_dir_01(),
			self::hero_prod_01(),
			self::hero_legal_01(),
			self::hero_edit_01(),
			self::hero_compact_01(),
			self::hero_media_01(),
			self::hero_split_01(),
		);
	}

	/**
	 * Returns section keys in this batch (for listing and tests).
	 *
	 * @return list<string>
	 */
	public static function section_keys(): array {
		return array(
			'hero_conv_01',
			'hero_conv_02',
			'hero_cred_01',
			'hero_edu_01',
			'hero_local_01',
			'hero_dir_01',
			'hero_prod_01',
			'hero_legal_01',
			'hero_edit_01',
			'hero_compact_01',
			'hero_media_01',
			'hero_split_01',
		);
	}

	/**
	 * Builds a hero section definition with common structure and optional CTA classification.
	 *
	 * @param string               $key Internal key.
	 * @param string               $name Display name.
	 * @param string               $purpose_summary Purpose summary.
	 * @param string               $cta_classification primary_cta, contact_cta, navigation_cta, or empty.
	 * @param string               $variation_family_key Variation family (e.g. hero_primary, hero_compact).
	 * @param string               $preview_desc Preview description.
	 * @param array<string, mixed> $blueprint_fields Field definitions for embedded blueprint.
	 * @param array<string, mixed> $preview_defaults Synthetic ACF defaults for preview.
	 * @param array<string, mixed> $extra Optional extra keys (short_label, suggested_use_cases, etc.).
	 * @return array<string, mixed>
	 */
	private static function hero_definition(
		string $key,
		string $name,
		string $purpose_summary,
		string $cta_classification,
		string $variation_family_key,
		string $preview_desc,
		array $blueprint_fields,
		array $preview_defaults,
		array $extra = array()
	): array {
		$bp_id                   = 'acf_blueprint_' . $key;
		$base                    = array(
			Section_Schema::FIELD_INTERNAL_KEY             => $key,
			Section_Schema::FIELD_NAME                     => $name,
			Section_Schema::FIELD_PURPOSE_SUMMARY          => $purpose_summary,
			Section_Schema::FIELD_CATEGORY                 => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_' . $key,
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => $bp_id,
			Section_Schema::FIELD_HELPER_REF               => 'helper_' . $key,
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css_' . $key,
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array(
				'default' => array(
					'label'         => 'Default',
					'description'   => '',
					'css_modifiers' => array(),
				),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array(
				'may_precede'          => array(),
				'may_follow'           => array(),
				'avoid_adjacent'       => array(),
				'duplicate_purpose_of' => array(),
			),
			Section_Schema::FIELD_VERSION                  => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Section_Schema::FIELD_STATUS                   => 'active',
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'none' => true ),
			'section_purpose_family'                       => self::PURPOSE_FAMILY,
			'cta_classification'                           => $cta_classification,
			'variation_family_key'                         => $variation_family_key,
			'preview_description'                          => $preview_desc,
			'preview_image_ref'                            => '',
			'animation_tier'                               => 'subtle',
			'animation_families'                           => array( 'entrance', 'hover' ),
			'preview_defaults'                             => $preview_defaults,
			'accessibility_warnings_or_enhancements'       => 'Use one primary heading (h1 or h2 per page context). CTA links must have visible, descriptive text. Ensure sufficient contrast for hero text.',
		);
		$base['field_blueprint'] = array(
			'blueprint_id'    => $bp_id,
			'section_key'     => $key,
			'section_version' => '1',
			'label'           => $name . ' fields',
			'description'     => 'Hero/intro content fields.',
			'fields'          => $blueprint_fields,
		);
		return array_merge( $base, $extra );
	}

	/** Common hero blueprint fields: headline, subheadline, eyebrow, primary_cta, secondary_cta. */
	private static function common_hero_blueprint_fields(): array {
		return array(
			array(
				'key'          => 'field_hero_headline',
				'name'         => 'headline',
				'label'        => 'Headline',
				'type'         => 'text',
				'required'     => true,
				'instructions' => 'Main hero headline.',
			),
			array(
				'key'          => 'field_hero_subheadline',
				'name'         => 'subheadline',
				'label'        => 'Subheadline',
				'type'         => 'textarea',
				'required'     => false,
				'instructions' => 'Supporting line.',
			),
			array(
				'key'          => 'field_hero_eyebrow',
				'name'         => 'eyebrow',
				'label'        => 'Eyebrow',
				'type'         => 'text',
				'required'     => false,
				'instructions' => 'Optional eyebrow text.',
			),
			array(
				'key'          => 'field_hero_primary_cta',
				'name'         => 'primary_cta',
				'label'        => 'Primary CTA',
				'type'         => 'link',
				'required'     => false,
				'instructions' => 'Main call-to-action link.',
			),
			array(
				'key'          => 'field_hero_secondary_cta',
				'name'         => 'secondary_cta',
				'label'        => 'Secondary link',
				'type'         => 'link',
				'required'     => false,
				'instructions' => 'Optional secondary link.',
			),
		);
	}

	public static function hero_conv_01(): array {
		return self::hero_definition(
			'hero_conv_01',
			'Hero conversion primary',
			'Conversion-first hero with headline, subheadline, and primary CTA. Use for landing pages and sign-up flows.',
			'primary_cta',
			'hero_primary',
			'Conversion-focused hero with headline and primary CTA button.',
			self::common_hero_blueprint_fields(),
			array(
				'headline'      => 'Get started today',
				'subheadline'   => 'Supporting copy for your main offer.',
				'eyebrow'       => '',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'Sign up',
				),
				'secondary_cta' => array(),
			),
			array(
				'short_label'         => 'Hero CTA',
				'suggested_use_cases' => array( 'Landing page', 'Sign-up flow', 'Offer page opener' ),
			)
		);
	}

	public static function hero_conv_02(): array {
		return self::hero_definition(
			'hero_conv_02',
			'Hero conversion secondary',
			'Secondary conversion hero with dual CTA. Use when both primary action and secondary link are needed.',
			'primary_cta',
			'hero_primary',
			'Hero with headline and two CTA options.',
			self::common_hero_blueprint_fields(),
			array(
				'headline'      => 'Welcome to our service',
				'subheadline'   => 'Learn more or get in touch.',
				'eyebrow'       => 'Your choice',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'Get started',
				),
				'secondary_cta' => array(
					'url'   => '#',
					'title' => 'Contact us',
				),
			),
			array(
				'short_label'                           => 'Hero dual CTA',
				'suggested_use_cases'                   => array( 'Service page', 'Dual conversion', 'Hub opener' ),
				Section_Schema::FIELD_INDUSTRY_AFFINITY => self::LAUNCH_INDUSTRIES,
			)
		);
	}

	public static function hero_cred_01(): array {
		return self::hero_definition(
			'hero_cred_01',
			'Hero credibility-first',
			'Credibility-led hero: headline and subheadline with optional proof element. Use for trust-heavy openers.',
			'',
			'hero_cred',
			'Credibility-focused hero with space for trust messaging.',
			self::common_hero_blueprint_fields(),
			array(
				'headline'      => 'Trusted by teams everywhere',
				'subheadline'   => 'Supporting credibility message.',
				'eyebrow'       => 'Why choose us',
				'primary_cta'   => array(),
				'secondary_cta' => array(),
			),
			array(
				'short_label'                           => 'Hero trust',
				'suggested_use_cases'                   => array( 'About opener', 'Trust-led landing', 'Credibility page' ),
				Section_Schema::FIELD_INDUSTRY_AFFINITY => self::LAUNCH_INDUSTRIES,
			)
		);
	}

	public static function hero_edu_01(): array {
		return self::hero_definition(
			'hero_edu_01',
			'Hero educational',
			'Educational intro hero: headline and explanatory subheadline. Use for resource or how-to page openers.',
			'',
			'hero_edu',
			'Educational hero with clear headline and supporting copy.',
			self::common_hero_blueprint_fields(),
			array(
				'headline'      => 'How it works',
				'subheadline'   => 'A short explanation of what this page covers.',
				'eyebrow'       => 'Guide',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'Read more',
				),
				'secondary_cta' => array(),
			),
			array(
				'short_label'                           => 'Hero edu',
				'suggested_use_cases'                   => array( 'Resource page', 'How-to opener', 'Educational hub' ),
				Section_Schema::FIELD_INDUSTRY_AFFINITY => self::LAUNCH_INDUSTRIES,
			)
		);
	}

	public static function hero_local_01(): array {
		return self::hero_definition(
			'hero_local_01',
			'Hero local / service intro',
			'Local or service intro hero: headline and subheadline for location or service area pages.',
			'',
			'hero_local',
			'Local or service intro hero.',
			self::common_hero_blueprint_fields(),
			array(
				'headline'      => 'Serving your area',
				'subheadline'   => 'We are here to help.',
				'eyebrow'       => 'Local',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'Find a location',
				),
				'secondary_cta' => array(),
			),
			array(
				'short_label'                           => 'Hero local',
				'suggested_use_cases'                   => array( 'Location page', 'Service area', 'Regional hub' ),
				Section_Schema::FIELD_INDUSTRY_AFFINITY => self::LAUNCH_INDUSTRIES,
			)
		);
	}

	public static function hero_dir_01(): array {
		return self::hero_definition(
			'hero_dir_01',
			'Hero directory entry',
			'Directory entry hero: headline and brief intro for directory or listing page openers.',
			'',
			'hero_dir',
			'Directory entry hero with search or browse cue.',
			self::common_hero_blueprint_fields(),
			array(
				'headline'      => 'Browse our directory',
				'subheadline'   => 'Find what you need.',
				'eyebrow'       => 'Directory',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'Browse',
				),
				'secondary_cta' => array(),
			),
			array(
				'short_label'         => 'Hero directory',
				'suggested_use_cases' => array( 'Directory hub', 'Listing page', 'Catalog opener' ),
			)
		);
	}

	public static function hero_prod_01(): array {
		return self::hero_definition(
			'hero_prod_01',
			'Hero product entry',
			'Product entry hero: headline and subheadline for product or offering detail openers.',
			'primary_cta',
			'hero_prod',
			'Product-focused hero with CTA.',
			self::common_hero_blueprint_fields(),
			array(
				'headline'      => 'Introducing our product',
				'subheadline'   => 'Key benefit or tagline.',
				'eyebrow'       => 'Product',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'Learn more',
				),
				'secondary_cta' => array(),
			),
			array(
				'short_label'                           => 'Hero product',
				'suggested_use_cases'                   => array( 'Product page', 'Offering detail', 'Detail opener' ),
				Section_Schema::FIELD_INDUSTRY_AFFINITY => self::LAUNCH_INDUSTRIES,
			)
		);
	}

	public static function hero_legal_01(): array {
		return self::hero_definition(
			'hero_legal_01',
			'Hero legal / trust intro',
			'Legal or trust intro hero: minimal headline for legal or policy page openers. No strong CTA.',
			'',
			'hero_legal',
			'Legal or trust intro with minimal styling.',
			self::common_hero_blueprint_fields(),
			array(
				'headline'      => 'Legal information',
				'subheadline'   => 'Please read the following.',
				'eyebrow'       => '',
				'primary_cta'   => array(),
				'secondary_cta' => array(),
			),
			array(
				'short_label'                           => 'Hero legal',
				'suggested_use_cases'                   => array( 'Privacy page', 'Terms opener', 'Policy page' ),
				Section_Schema::FIELD_INDUSTRY_AFFINITY => self::LAUNCH_INDUSTRIES,
			)
		);
	}

	public static function hero_edit_01(): array {
		return self::hero_definition(
			'hero_edit_01',
			'Hero editorial / resource intro',
			'Editorial or resource intro hero: headline and subheadline for articles or resource pages.',
			'navigation_cta',
			'hero_edit',
			'Editorial hero with optional read-more CTA.',
			self::common_hero_blueprint_fields(),
			array(
				'headline'      => 'Article or resource title',
				'subheadline'   => 'Brief introduction or summary.',
				'eyebrow'       => 'Resource',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'Read more',
				),
				'secondary_cta' => array(),
			),
			array(
				'short_label'         => 'Hero editorial',
				'suggested_use_cases' => array( 'Article opener', 'Resource page', 'Blog post' ),
			)
		);
	}

	public static function hero_compact_01(): array {
		$fields = self::common_hero_blueprint_fields();
		return self::hero_definition(
			'hero_compact_01',
			'Hero compact',
			'Compact hero: reduced height and emphasis for dense layouts or secondary pages.',
			'primary_cta',
			'hero_compact',
			'Compact hero layout.',
			$fields,
			array(
				'headline'      => 'Short headline',
				'subheadline'   => '',
				'eyebrow'       => '',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'Go',
				),
				'secondary_cta' => array(),
			),
			array(
				'short_label'                           => 'Hero compact',
				'suggested_use_cases'                   => array( 'Sub-page', 'Dense layout', 'Secondary opener' ),
				Section_Schema::FIELD_INDUSTRY_AFFINITY => self::LAUNCH_INDUSTRIES,
				Section_Schema::FIELD_VARIANTS          => array(
					'default' => array(
						'label'         => 'Default',
						'description'   => 'Compact block.',
						'css_modifiers' => array(),
					),
					'tighter' => array(
						'label'         => 'Tighter',
						'description'   => 'Minimal padding.',
						'css_modifiers' => array( 'aio-s-hero_compact_01--tighter' ),
					),
				),
			)
		);
	}

	public static function hero_media_01(): array {
		$fields = array_merge(
			self::common_hero_blueprint_fields(),
			array(
				array(
					'key'          => 'field_hero_media_image',
					'name'         => 'hero_image',
					'label'        => 'Hero image',
					'type'         => 'image',
					'required'     => false,
					'instructions' => 'Optional hero image.',
				),
			)
		);
		return self::hero_definition(
			'hero_media_01',
			'Hero media-forward',
			'Media-forward hero: headline and subheadline with prominent image or media slot.',
			'primary_cta',
			'hero_media',
			'Hero with prominent image or media.',
			$fields,
			array(
				'headline'      => 'Headline with media',
				'subheadline'   => 'Supporting copy.',
				'eyebrow'       => '',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'Learn more',
				),
				'secondary_cta' => array(),
				'hero_image'    => array(),
			),
			array(
				'short_label'                           => 'Hero media',
				'suggested_use_cases'                   => array( 'Visual landing', 'Media-led opener', 'Brand hero' ),
				Section_Schema::FIELD_INDUSTRY_AFFINITY => self::LAUNCH_INDUSTRIES,
			)
		);
	}

	public static function hero_split_01(): array {
		$fields = array_merge(
			self::common_hero_blueprint_fields(),
			array(
				array(
					'key'          => 'field_hero_split_image',
					'name'         => 'split_image',
					'label'        => 'Split image',
					'type'         => 'image',
					'required'     => false,
					'instructions' => 'Image for split layout.',
				),
			)
		);
		return self::hero_definition(
			'hero_split_01',
			'Hero split layout',
			'Split-layout hero: text on one side, image on the other. Use for balanced openers.',
			'primary_cta',
			'hero_split',
			'Split hero with text and image.',
			$fields,
			array(
				'headline'      => 'Headline',
				'subheadline'   => 'Copy on one side.',
				'eyebrow'       => '',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'CTA',
				),
				'secondary_cta' => array(),
				'split_image'   => array(),
			),
			array(
				'short_label'                           => 'Hero split',
				'suggested_use_cases'                   => array( 'Balanced opener', 'Text and image', 'Split block' ),
				Section_Schema::FIELD_INDUSTRY_AFFINITY => self::LAUNCH_INDUSTRIES,
				Section_Schema::FIELD_VARIANTS          => array(
					'default'    => array(
						'label'         => 'Default',
						'description'   => 'Text left, image right.',
						'css_modifiers' => array(),
					),
					'image_left' => array(
						'label'         => 'Image left',
						'description'   => 'Image left, text right.',
						'css_modifiers' => array( 'aio-s-hero_split_01--image-left' ),
					),
				),
			)
		);
	}
}
