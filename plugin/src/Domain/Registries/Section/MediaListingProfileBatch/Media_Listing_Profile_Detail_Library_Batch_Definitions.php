<?php
/**
 * Media, gallery, listing, directory, profile, and detail-support section template definitions for SEC-06 library batch (spec §12, §17, §20, §51, Prompt 151).
 * Production-grade media/listing/profile/detail sections with full metadata, field blueprints, and smart-omission behavior.
 * Does not persist; callers save via Section_Template_Repository or Media_Listing_Profile_Detail_Library_Batch_Seeder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\MediaListingProfileBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Returns section definitions for the media/listing/profile/detail library batch (SEC-06).
 * Each definition is schema-compliant with embedded field_blueprint, category, and accessibility/omission metadata.
 */
final class Media_Listing_Profile_Detail_Library_Batch_Definitions {

	/** Batch ID per template-library-inventory-manifest §3.1 (listing, comparison, media, profile scope). */
	public const BATCH_ID = 'SEC-06';

	/**
	 * Returns all media/listing/profile/detail batch section definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::mlp_card_grid_01(),
			self::mlp_listing_01(),
			self::mlp_profile_summary_01(),
			self::mlp_profile_cards_01(),
			self::mlp_place_highlight_01(),
			self::mlp_recommendation_band_01(),
			self::mlp_gallery_01(),
			self::mlp_media_band_01(),
			self::mlp_detail_spec_01(),
			self::mlp_comparison_cards_01(),
			self::mlp_related_content_01(),
			self::mlp_location_info_01(),
			self::mlp_directory_entry_01(),
			self::mlp_team_grid_01(),
			self::mlp_product_cards_01(),
		);
	}

	/**
	 * Returns section keys in this batch (for listing and tests).
	 *
	 * @return list<string>
	 */
	public static function section_keys(): array {
		return array(
			'mlp_card_grid_01',
			'mlp_listing_01',
			'mlp_profile_summary_01',
			'mlp_profile_cards_01',
			'mlp_place_highlight_01',
			'mlp_recommendation_band_01',
			'mlp_gallery_01',
			'mlp_media_band_01',
			'mlp_detail_spec_01',
			'mlp_comparison_cards_01',
			'mlp_related_content_01',
			'mlp_location_info_01',
			'mlp_directory_entry_01',
			'mlp_team_grid_01',
			'mlp_product_cards_01',
		);
	}

	/**
	 * Builds a media/listing/profile/detail section definition.
	 *
	 * @param string $key Internal key.
	 * @param string $name Display name.
	 * @param string $purpose_summary Purpose summary.
	 * @param string $category Section category (directory_listing, media_gallery, profile_bio, comparison, related_recommended).
	 * @param string $purpose_family section_purpose_family (listing, media, profile, detail, related).
	 * @param string $variation_family_key Variation family key.
	 * @param string $preview_desc Preview description.
	 * @param array<string, mixed> $blueprint_fields Field definitions for embedded blueprint.
	 * @param array<string, mixed> $preview_defaults Synthetic ACF defaults for preview.
	 * @param array<string, mixed> $extra Optional extra keys.
	 * @return array<string, mixed>
	 */
	private static function mlp_definition(
		string $key,
		string $name,
		string $purpose_summary,
		string $category,
		string $purpose_family,
		string $variation_family_key,
		string $preview_desc,
		array $blueprint_fields,
		array $preview_defaults,
		array $extra = array()
	): array {
		$bp_id = 'acf_blueprint_' . $key;
		$base = array(
			Section_Schema::FIELD_INTERNAL_KEY            => $key,
			Section_Schema::FIELD_NAME                    => $name,
			Section_Schema::FIELD_PURPOSE_SUMMARY         => $purpose_summary,
			Section_Schema::FIELD_CATEGORY                => $category,
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_' . $key,
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF     => $bp_id,
			Section_Schema::FIELD_HELPER_REF              => 'helper_' . $key,
			Section_Schema::FIELD_CSS_CONTRACT_REF        => 'css_' . $key,
			Section_Schema::FIELD_DEFAULT_VARIANT         => 'default',
			Section_Schema::FIELD_VARIANTS                => array(
				'default' => array( 'label' => 'Default', 'description' => '', 'css_modifiers' => array() ),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array(
				'may_precede'          => array(),
				'may_follow'           => array(),
				'avoid_adjacent'       => array(),
				'duplicate_purpose_of' => array(),
			),
			Section_Schema::FIELD_VERSION                 => array( 'version' => '1', 'stable_key_retained' => true ),
			Section_Schema::FIELD_STATUS                  => 'active',
			Section_Schema::FIELD_RENDER_MODE             => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION       => array( 'none' => true ),
			'section_purpose_family'                     => $purpose_family,
			'variation_family_key'                       => $variation_family_key,
			'preview_description'                        => $preview_desc,
			'preview_image_ref'                          => '',
			'animation_tier'                             => 'subtle',
			'animation_families'                         => array( 'entrance', 'hover' ),
			'preview_defaults'                           => $preview_defaults,
			'accessibility_warnings_or_enhancements'     => 'Use semantic list or grid for repeated items. Do not rely on color alone (spec §51.3, §51.8). Omit optional media, captions, and secondary labels when empty; ensure sufficient contrast for text.',
			'seo_relevance_notes'                       => 'Listing and profile content support entity and directory signals; keep headings and labels descriptive (spec §15.9).',
		);
		$base['field_blueprint'] = array(
			'blueprint_id'    => $bp_id,
			'section_key'     => $key,
			'section_version' => '1',
			'label'           => $name . ' fields',
			'description'     => 'Media/listing/profile content fields.',
			'fields'          => $blueprint_fields,
		);
		return array_merge( $base, $extra );
	}

	/** Card grid: headline + repeatable cards (title, description, image, link). */
	public static function mlp_card_grid_01(): array {
		$key = 'mlp_card_grid_01';
		$fields = array(
			array( 'key' => 'field_mlp_cg_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_cg_cards',
				'name'        => 'cards',
				'label'       => 'Cards',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_cg_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_mlp_cg_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_mlp_cg_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => false ),
					array( 'key' => 'field_mlp_cg_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Card grid',
			'Grid of cards with title, optional description, image, and link. Use for directory, product, or service cards. Omit image/link when empty.',
			'directory_listing',
			'listing',
			'card_grid',
			'Card grid with optional image and link.',
			$fields,
			array( 'headline' => 'Items', 'cards' => array( array( 'title' => 'Card one', 'description' => 'Synthetic.', 'image' => array(), 'link' => array() ) ) ),
			array( 'short_label' => 'Card grid', 'suggested_use_cases' => array( 'Directory hub', 'Product grid', 'Service cards' ) )
		);
	}

	/** Listing: headline + repeatable items (title, description, optional image/link). */
	public static function mlp_listing_01(): array {
		$key = 'mlp_listing_01';
		$fields = array(
			array( 'key' => 'field_mlp_ls_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_ls_items',
				'name'        => 'items',
				'label'       => 'Items',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_ls_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_mlp_ls_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_mlp_ls_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => false ),
					array( 'key' => 'field_mlp_ls_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Listing',
			'List of items with title, optional description, image, and link. Use for directory or content lists. Omit optional fields when empty.',
			'directory_listing',
			'listing',
			'listing',
			'Listing with optional image and link.',
			$fields,
			array( 'headline' => 'List', 'items' => array( array( 'title' => 'Item one', 'description' => '', 'image' => array(), 'link' => array() ) ) ),
			array( 'short_label' => 'Listing', 'suggested_use_cases' => array( 'Directory', 'Resource list', 'Hub' ) )
		);
	}

	/** Profile summary: single profile (name, role, bio, image, link). */
	public static function mlp_profile_summary_01(): array {
		$key = 'mlp_profile_summary_01';
		$fields = array(
			array( 'key' => 'field_mlp_ps_name', 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_mlp_ps_role', 'name' => 'role', 'label' => 'Role / title', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_mlp_ps_bio', 'name' => 'bio', 'label' => 'Bio', 'type' => 'textarea', 'required' => false ),
			array( 'key' => 'field_mlp_ps_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => false ),
			array( 'key' => 'field_mlp_ps_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
		);
		return self::mlp_definition(
			$key,
			'Profile summary',
			'Single profile block with name, role, bio, optional image and link. Use for team or author detail. Omit image/link when empty.',
			'profile_bio',
			'profile',
			'profile_summary',
			'Single profile with bio and optional image.',
			$fields,
			array( 'name' => 'Preview Name', 'role' => 'Role', 'bio' => 'Synthetic bio.', 'image' => array(), 'link' => array() ),
			array( 'short_label' => 'Profile summary', 'suggested_use_cases' => array( 'Team detail', 'Author', 'About' ) )
		);
	}

	/** Profile cards grid: headline + repeatable (name, role, image, short_bio). */
	public static function mlp_profile_cards_01(): array {
		$key = 'mlp_profile_cards_01';
		$fields = array(
			array( 'key' => 'field_mlp_pc_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_pc_profiles',
				'name'        => 'profiles',
				'label'       => 'Profiles',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_pc_name', 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_mlp_pc_role', 'name' => 'role', 'label' => 'Role', 'type' => 'text', 'required' => false ),
					array( 'key' => 'field_mlp_pc_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => false ),
					array( 'key' => 'field_mlp_pc_bio', 'name' => 'short_bio', 'label' => 'Short bio', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Profile cards',
			'Grid of profile cards with name, role, optional image and short bio. Use for team or member listing. Omit image when empty.',
			'profile_bio',
			'profile',
			'profile_cards',
			'Profile cards grid.',
			$fields,
			array( 'headline' => 'Our team', 'profiles' => array( array( 'name' => 'Name', 'role' => 'Role', 'image' => array(), 'short_bio' => '' ) ) ),
			array( 'short_label' => 'Profile cards', 'suggested_use_cases' => array( 'Team page', 'About', 'Directory' ) )
		);
	}

	/** Place highlight: headline + repeatable (name, address_line, description, link). */
	public static function mlp_place_highlight_01(): array {
		$key = 'mlp_place_highlight_01';
		$fields = array(
			array( 'key' => 'field_mlp_ph_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_ph_places',
				'name'        => 'places',
				'label'       => 'Places',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_ph_name', 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_mlp_ph_address', 'name' => 'address_line', 'label' => 'Address line', 'type' => 'text', 'required' => false ),
					array( 'key' => 'field_mlp_ph_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_mlp_ph_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Place / location highlight',
			'Place or location highlights with name, optional address, description, and link. Use for locations or venues. Omit address/link when empty.',
			'directory_listing',
			'listing',
			'place_highlight',
			'Place highlights with optional address.',
			$fields,
			array( 'headline' => 'Locations', 'places' => array( array( 'name' => 'Place one', 'address_line' => 'Synthetic address', 'description' => '', 'link' => array() ) ) ),
			array( 'short_label' => 'Place highlight', 'suggested_use_cases' => array( 'Location page', 'Directory', 'Venues' ) )
		);
	}

	/** Recommendation band: headline + repeatable (title, description, link). */
	public static function mlp_recommendation_band_01(): array {
		$key = 'mlp_recommendation_band_01';
		$fields = array(
			array( 'key' => 'field_mlp_rb_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_rb_items',
				'name'        => 'recommendations',
				'label'       => 'Recommendations',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_rb_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_mlp_rb_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_mlp_rb_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Recommendation band',
			'Band of recommendations with title, description, and optional link. Use for related or recommended content. Omit link when empty.',
			'related_recommended',
			'related',
			'recommendation_band',
			'Recommendation band.',
			$fields,
			array( 'headline' => 'Recommended', 'recommendations' => array( array( 'title' => 'Item', 'description' => 'Synthetic.', 'link' => array() ) ) ),
			array( 'short_label' => 'Recommendations', 'suggested_use_cases' => array( 'Detail page', 'Hub', 'Related' ) )
		);
	}

	/** Gallery: headline + repeatable (image, caption). */
	public static function mlp_gallery_01(): array {
		$key = 'mlp_gallery_01';
		$fields = array(
			array( 'key' => 'field_mlp_gl_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_gl_items',
				'name'        => 'gallery_items',
				'label'       => 'Gallery items',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_gl_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => true ),
					array( 'key' => 'field_mlp_gl_caption', 'name' => 'caption', 'label' => 'Caption', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Gallery',
			'Gallery with headline and repeatable image plus optional caption. Use for media groups. Omit caption when empty.',
			'media_gallery',
			'media',
			'gallery',
			'Gallery with optional captions.',
			$fields,
			array( 'headline' => 'Gallery', 'gallery_items' => array( array( 'image' => array(), 'caption' => '' ) ) ),
			array( 'short_label' => 'Gallery', 'suggested_use_cases' => array( 'Product', 'Portfolio', 'Media' ) )
		);
	}

	/** Media band: headline + repeatable (image, caption). */
	public static function mlp_media_band_01(): array {
		$key = 'mlp_media_band_01';
		$fields = array(
			array( 'key' => 'field_mlp_mb_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_mb_items',
				'name'        => 'media_items',
				'label'       => 'Media items',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_mb_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => false ),
					array( 'key' => 'field_mlp_mb_caption', 'name' => 'caption', 'label' => 'Caption', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Media band',
			'Band of media items with optional image and caption. Use for image strips or media highlights. Omit image/caption when empty.',
			'media_gallery',
			'media',
			'media_band',
			'Media band with optional captions.',
			$fields,
			array( 'headline' => 'Media', 'media_items' => array( array( 'image' => array(), 'caption' => '' ) ) ),
			array( 'short_label' => 'Media band', 'suggested_use_cases' => array( 'Product', 'Service', 'Media' ) )
		);
	}

	/** Detail spec: headline + repeatable (label, value). */
	public static function mlp_detail_spec_01(): array {
		$key = 'mlp_detail_spec_01';
		$fields = array(
			array( 'key' => 'field_mlp_ds_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_ds_rows',
				'name'        => 'spec_rows',
				'label'       => 'Spec rows',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_ds_label', 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_mlp_ds_value', 'name' => 'value', 'label' => 'Value', 'type' => 'text', 'required' => true ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Detail spec block',
			'Specification table or list with label/value rows. Use for product or detail specs. Omit rows when empty.',
			'directory_listing',
			'detail',
			'detail_spec',
			'Spec table with label and value.',
			$fields,
			array( 'headline' => 'Specifications', 'spec_rows' => array( array( 'label' => 'Spec A', 'value' => 'Value' ) ) ),
			array( 'short_label' => 'Detail spec', 'suggested_use_cases' => array( 'Product detail', 'Service detail', 'Spec table' ) )
		);
	}

	/** Comparison cards: headline + repeatable (title, features, link). */
	public static function mlp_comparison_cards_01(): array {
		$key = 'mlp_comparison_cards_01';
		$fields = array(
			array( 'key' => 'field_mlp_cc_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_cc_cards',
				'name'        => 'cards',
				'label'       => 'Cards',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_cc_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_mlp_cc_features', 'name' => 'features', 'label' => 'Features (e.g. one per line)', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_mlp_cc_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Comparison cards',
			'Comparison cards with title, optional features list, and link. Use for plan or option comparison. Omit link when empty.',
			'comparison',
			'detail',
			'comparison_cards',
			'Comparison cards.',
			$fields,
			array( 'headline' => 'Compare', 'cards' => array( array( 'title' => 'Option A', 'features' => '', 'link' => array() ) ) ),
			array( 'short_label' => 'Comparison cards', 'suggested_use_cases' => array( 'Pricing', 'Plans', 'Options' ) )
		);
	}

	/** Related content: headline + repeatable (title, excerpt, link). */
	public static function mlp_related_content_01(): array {
		$key = 'mlp_related_content_01';
		$fields = array(
			array( 'key' => 'field_mlp_rc_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_rc_items',
				'name'        => 'items',
				'label'       => 'Items',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_rc_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_mlp_rc_excerpt', 'name' => 'excerpt', 'label' => 'Excerpt', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_mlp_rc_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Related content',
			'Related or recommended content with title, optional excerpt, and link. Use for related articles or content. Omit excerpt/link when empty.',
			'related_recommended',
			'related',
			'related_content',
			'Related content list.',
			$fields,
			array( 'headline' => 'Related', 'items' => array( array( 'title' => 'Related one', 'excerpt' => 'Synthetic.', 'link' => array() ) ) ),
			array( 'short_label' => 'Related content', 'suggested_use_cases' => array( 'Detail page', 'Blog', 'Hub' ) )
		);
	}

	/** Location info: headline, address, optional hours/contact. */
	public static function mlp_location_info_01(): array {
		$key = 'mlp_location_info_01';
		$fields = array(
			array( 'key' => 'field_mlp_li_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_mlp_li_address', 'name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'required' => false ),
			array( 'key' => 'field_mlp_li_hours', 'name' => 'hours', 'label' => 'Hours or availability', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_mlp_li_contact', 'name' => 'contact_label', 'label' => 'Contact label or phone', 'type' => 'text', 'required' => false ),
		);
		return self::mlp_definition(
			$key,
			'Location info',
			'Location or map-like info block with address and optional hours/contact. Use for place or venue detail. Omit hours/contact when empty.',
			'directory_listing',
			'listing',
			'location_info',
			'Location info block.',
			$fields,
			array( 'headline' => 'Find us', 'address' => 'Synthetic address.', 'hours' => '', 'contact_label' => '' ),
			array( 'short_label' => 'Location info', 'suggested_use_cases' => array( 'Location page', 'Contact', 'Venue' ) )
		);
	}

	/** Directory entry: single entry card (title, description, meta fields, link). */
	public static function mlp_directory_entry_01(): array {
		$key = 'mlp_directory_entry_01';
		$fields = array(
			array( 'key' => 'field_mlp_de_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_mlp_de_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
			array( 'key' => 'field_mlp_de_meta', 'name' => 'meta_label', 'label' => 'Meta (e.g. category or type)', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_mlp_de_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => false ),
			array( 'key' => 'field_mlp_de_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
		);
		return self::mlp_definition(
			$key,
			'Directory entry card',
			'Single directory entry with title, description, optional meta, image, and link. Use for directory detail or card. Omit meta/image/link when empty.',
			'directory_listing',
			'listing',
			'directory_entry',
			'Directory entry card.',
			$fields,
			array( 'title' => 'Entry title', 'description' => 'Synthetic.', 'meta_label' => '', 'image' => array(), 'link' => array() ),
			array( 'short_label' => 'Directory entry', 'suggested_use_cases' => array( 'Directory detail', 'Listing card', 'Hub' ) )
		);
	}

	/** Team grid: headline + repeatable (name, role, image, short_bio). */
	public static function mlp_team_grid_01(): array {
		$key = 'mlp_team_grid_01';
		$fields = array(
			array( 'key' => 'field_mlp_tg_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_tg_members',
				'name'        => 'members',
				'label'       => 'Members',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_tg_name', 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_mlp_tg_role', 'name' => 'role', 'label' => 'Role', 'type' => 'text', 'required' => false ),
					array( 'key' => 'field_mlp_tg_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => false ),
					array( 'key' => 'field_mlp_tg_bio', 'name' => 'short_bio', 'label' => 'Short bio', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Team grid',
			'Team or member grid with name, role, optional image and short bio. Use for team or staff listing. Omit image/bio when empty.',
			'profile_bio',
			'profile',
			'team_grid',
			'Team grid.',
			$fields,
			array( 'headline' => 'Our team', 'members' => array( array( 'name' => 'Member', 'role' => 'Role', 'image' => array(), 'short_bio' => '' ) ) ),
			array( 'short_label' => 'Team grid', 'suggested_use_cases' => array( 'Team page', 'About', 'Staff' ) )
		);
	}

	/** Product cards: headline + repeatable (name, description, image, price_label, link). */
	public static function mlp_product_cards_01(): array {
		$key = 'mlp_product_cards_01';
		$fields = array(
			array( 'key' => 'field_mlp_pd_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_mlp_pd_cards',
				'name'        => 'products',
				'label'       => 'Products',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_mlp_pd_name', 'name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_mlp_pd_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_mlp_pd_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => false ),
					array( 'key' => 'field_mlp_pd_price', 'name' => 'price_label', 'label' => 'Price or label', 'type' => 'text', 'required' => false ),
					array( 'key' => 'field_mlp_pd_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::mlp_definition(
			$key,
			'Product / service cards',
			'Product or service cards with name, description, optional image, price label, and link. Use for product grid or offering list. Omit image/price/link when empty.',
			'directory_listing',
			'listing',
			'product_cards',
			'Product cards with optional price.',
			$fields,
			array( 'headline' => 'Products', 'products' => array( array( 'name' => 'Product one', 'description' => '', 'image' => array(), 'price_label' => '', 'link' => array() ) ) ),
			array( 'short_label' => 'Product cards', 'suggested_use_cases' => array( 'Product hub', 'Service list', 'Offering grid' ) )
		);
	}
}
