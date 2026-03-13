<?php
/**
 * CTA super-library section template definitions for SEC-08 (spec §12, §14, §17, §51, Prompt 153).
 * Large batch of CTA-classified sections across intent families and strength levels for page-template CTA-count compliance.
 * Does not persist; callers save via Section_Template_Repository or CTA_Super_Library_Batch_Seeder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\CtaSuperLibraryBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Returns section definitions for the CTA super-library (SEC-08).
 * All sections are category cta_conversion with cta_classification, cta_intent_family, cta_strength.
 */
final class CTA_Super_Library_Batch_Definitions {

	/** Batch ID for CTA super-library (spec §14.3, §14.4). */
	public const BATCH_ID = 'SEC-08';

	/**
	 * Returns all CTA super-library section definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::cta_consultation_01(),
			self::cta_consultation_02(),
			self::cta_booking_01(),
			self::cta_booking_02(),
			self::cta_purchase_01(),
			self::cta_purchase_02(),
			self::cta_inquiry_01(),
			self::cta_inquiry_02(),
			self::cta_contact_01(),
			self::cta_contact_02(),
			self::cta_quote_request_01(),
			self::cta_quote_request_02(),
			self::cta_directory_nav_01(),
			self::cta_compare_next_01(),
			self::cta_trust_confirm_01(),
			self::cta_trust_confirm_02(),
			self::cta_local_action_01(),
			self::cta_local_action_02(),
			self::cta_service_detail_01(),
			self::cta_service_detail_02(),
			self::cta_product_detail_01(),
			self::cta_product_detail_02(),
			self::cta_support_01(),
			self::cta_support_02(),
			self::cta_policy_utility_01(),
			self::cta_policy_utility_02(),
		);
	}

	/**
	 * Returns section keys in this batch (for listing and tests).
	 *
	 * @return list<string>
	 */
	public static function section_keys(): array {
		return array(
			'cta_consultation_01', 'cta_consultation_02', 'cta_booking_01', 'cta_booking_02',
			'cta_purchase_01', 'cta_purchase_02', 'cta_inquiry_01', 'cta_inquiry_02',
			'cta_contact_01', 'cta_contact_02', 'cta_quote_request_01', 'cta_quote_request_02',
			'cta_directory_nav_01', 'cta_compare_next_01', 'cta_trust_confirm_01', 'cta_trust_confirm_02',
			'cta_local_action_01', 'cta_local_action_02', 'cta_service_detail_01', 'cta_service_detail_02',
			'cta_product_detail_01', 'cta_product_detail_02', 'cta_support_01', 'cta_support_02',
			'cta_policy_utility_01', 'cta_policy_utility_02',
		);
	}

	/**
	 * Builds a CTA section definition.
	 *
	 * @param string $key Internal key.
	 * @param string $name Display name.
	 * @param string $purpose_summary Purpose summary.
	 * @param string $variation_family_key Variation family (e.g. subtle, strong, media_backed, proof_backed, minimalist).
	 * @param string $preview_desc Preview description.
	 * @param array<string, mixed> $blueprint_fields Field definitions for embedded blueprint.
	 * @param array<string, mixed> $preview_defaults Synthetic ACF defaults for preview.
	 * @param string $cta_intent_family CTA intent family (consultation, booking, purchase, inquiry, contact, quote_request, directory_nav, compare_next, trust_confirm, local_action, service_detail, product_detail, support, policy_utility).
	 * @param string $cta_strength CTA strength (subtle, strong, media_backed, proof_backed, minimalist).
	 * @param array<string, mixed> $extra Optional extra keys (short_label, suggested_use_cases, page_class_compatibility, animation_tier override).
	 * @return array<string, mixed>
	 */
	private static function cta_definition(
		string $key,
		string $name,
		string $purpose_summary,
		string $variation_family_key,
		string $preview_desc,
		array $blueprint_fields,
		array $preview_defaults,
		string $cta_intent_family,
		string $cta_strength,
		array $extra = array()
	): array {
		$bp_id = 'acf_blueprint_' . $key;
		$animation_tier = $extra['animation_tier'] ?? 'subtle';
		unset( $extra['animation_tier'] );
		$base = array(
			Section_Schema::FIELD_INTERNAL_KEY            => $key,
			Section_Schema::FIELD_NAME                   => $name,
			Section_Schema::FIELD_PURPOSE_SUMMARY        => $purpose_summary,
			Section_Schema::FIELD_CATEGORY               => 'cta_conversion',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_' . $key,
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF    => $bp_id,
			Section_Schema::FIELD_HELPER_REF             => 'helper_' . $key,
			Section_Schema::FIELD_CSS_CONTRACT_REF       => 'css_' . $key,
			Section_Schema::FIELD_DEFAULT_VARIANT        => 'default',
			Section_Schema::FIELD_VARIANTS               => array(
				'default' => array( 'label' => 'Default', 'description' => '', 'css_modifiers' => array() ),
			),
			Section_Schema::FIELD_COMPATIBILITY          => array(
				'may_precede'          => array(),
				'may_follow'            => array(),
				'avoid_adjacent'        => array(),
				'duplicate_purpose_of'  => array(),
			),
			Section_Schema::FIELD_VERSION                => array( 'version' => '1', 'stable_key_retained' => true ),
			Section_Schema::FIELD_STATUS                 => 'active',
			Section_Schema::FIELD_RENDER_MODE            => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION     => array( 'none' => true ),
			'cta_classification'                       => 'cta',
			'cta_intent_family'                         => $cta_intent_family,
			'cta_strength'                              => $cta_strength,
			'variation_family_key'                      => $variation_family_key,
			'preview_description'                       => $preview_desc,
			'preview_image_ref'                         => '',
			'animation_tier'                             => $animation_tier,
			'animation_families'                         => array( 'entrance', 'hover' ),
			'preview_defaults'                          => $preview_defaults,
			'accessibility_warnings_or_enhancements'     => 'Use clear, direct button and link labels. Do not rely on color alone (spec §51.3). Ensure sufficient contrast and visible focus. Omit secondary button when empty; progressive animation only with fallback.',
			'seo_relevance_notes'                       => 'CTA sections support conversion and next-step clarity; keep labels descriptive (spec §15.10).',
		);
		$base['field_blueprint'] = array(
			'blueprint_id'    => $bp_id,
			'section_key'     => $key,
			'section_version' => '1',
			'label'           => $name . ' fields',
			'description'     => 'CTA content fields.',
			'fields'          => $blueprint_fields,
		);
		return array_merge( $base, $extra );
	}

	/** Standard CTA fields: heading, body, primary button, optional secondary, optional image, optional trust line. */
	private static function standard_cta_fields( string $prefix ): array {
		return array(
			array( 'key' => 'field_' . $prefix . '_heading', 'name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_' . $prefix . '_body', 'name' => 'body', 'label' => 'Body', 'type' => 'textarea', 'required' => false ),
			array( 'key' => 'field_' . $prefix . '_primary_label', 'name' => 'primary_button_label', 'label' => 'Primary button label', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_' . $prefix . '_primary_link', 'name' => 'primary_button_link', 'label' => 'Primary button link', 'type' => 'link', 'required' => false ),
			array( 'key' => 'field_' . $prefix . '_secondary_label', 'name' => 'secondary_button_label', 'label' => 'Secondary button label', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_' . $prefix . '_secondary_link', 'name' => 'secondary_button_link', 'label' => 'Secondary button link', 'type' => 'link', 'required' => false ),
			array( 'key' => 'field_' . $prefix . '_image', 'name' => 'image', 'label' => 'Image', 'type' => 'image', 'required' => false ),
			array( 'key' => 'field_' . $prefix . '_trust_line', 'name' => 'trust_line', 'label' => 'Trust or proof line', 'type' => 'text', 'required' => false ),
		);
	}

	/** Minimal CTA fields: heading, body, primary button only. */
	private static function minimal_cta_fields( string $prefix ): array {
		return array(
			array( 'key' => 'field_' . $prefix . '_heading', 'name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_' . $prefix . '_body', 'name' => 'body', 'label' => 'Body', 'type' => 'textarea', 'required' => false ),
			array( 'key' => 'field_' . $prefix . '_primary_label', 'name' => 'primary_button_label', 'label' => 'Primary button label', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_' . $prefix . '_primary_link', 'name' => 'primary_button_link', 'label' => 'Primary button link', 'type' => 'link', 'required' => false ),
		);
	}

	public static function cta_consultation_01(): array {
		$key = 'cta_consultation_01';
		return self::cta_definition(
			$key,
			'Consultation CTA (subtle)',
			'Subtle CTA inviting consultation or discovery call. Clear primary button; omit secondary and image when empty.',
			'subtle',
			'Consultation CTA with optional body and trust line.',
			self::standard_cta_fields( 'cta_con_1' ),
			array( 'heading' => 'Book a consultation', 'body' => 'Discuss your needs with our team.', 'primary_button_label' => 'Schedule a call', 'primary_button_link' => array(), 'trust_line' => 'Free 15-minute call' ),
			'consultation',
			'subtle',
			array( 'short_label' => 'Consultation CTA', 'suggested_use_cases' => array( 'Top-level', 'Hub', 'Service' ) )
		);
	}

	public static function cta_consultation_02(): array {
		$key = 'cta_consultation_02';
		return self::cta_definition(
			$key,
			'Consultation CTA (strong)',
			'Strong consultation CTA with emphasis. Supports primary and secondary actions; omit when empty.',
			'strong',
			'Strong consultation CTA with optional image and trust line.',
			self::standard_cta_fields( 'cta_con_2' ),
			array( 'heading' => 'Ready to get started?', 'body' => 'Book your consultation today.', 'primary_button_label' => 'Book now', 'primary_button_link' => array(), 'secondary_button_label' => 'Learn more', 'secondary_button_link' => array(), 'trust_line' => 'Trusted by 1,000+ clients' ),
			'consultation',
			'strong',
			array( 'short_label' => 'Consultation CTA strong' )
		);
	}

	public static function cta_booking_01(): array {
		$key = 'cta_booking_01';
		return self::cta_definition(
			$key,
			'Booking CTA (minimalist)',
			'Minimalist booking CTA. Single primary action; clear label.',
			'minimalist',
			'Minimal booking CTA.',
			self::minimal_cta_fields( 'cta_book_1' ),
			array( 'heading' => 'Reserve your spot', 'body' => '', 'primary_button_label' => 'Book now', 'primary_button_link' => array() ),
			'booking',
			'minimalist',
			array( 'short_label' => 'Booking CTA', 'animation_tier' => 'none' )
		);
	}

	public static function cta_booking_02(): array {
		$key = 'cta_booking_02';
		return self::cta_definition(
			$key,
			'Booking CTA (media-backed)',
			'Booking CTA with optional image. Media-backed variant for visual emphasis.',
			'media_backed',
			'Booking CTA with optional image.',
			self::standard_cta_fields( 'cta_book_2' ),
			array( 'heading' => 'Book your appointment', 'body' => 'Choose a time that works for you.', 'primary_button_label' => 'See availability', 'primary_button_link' => array(), 'image' => array() ),
			'booking',
			'media_backed',
			array( 'short_label' => 'Booking CTA media' )
		);
	}

	public static function cta_purchase_01(): array {
		$key = 'cta_purchase_01';
		return self::cta_definition(
			$key,
			'Purchase CTA (subtle)',
			'Subtle purchase or add-to-cart CTA. Clear primary action; omit secondary when empty.',
			'subtle',
			'Subtle purchase CTA.',
			self::standard_cta_fields( 'cta_pur_1' ),
			array( 'heading' => 'Add to cart', 'body' => 'Complete your order in a few steps.', 'primary_button_label' => 'Add to cart', 'primary_button_link' => array() ),
			'purchase',
			'subtle',
			array( 'short_label' => 'Purchase CTA' )
		);
	}

	public static function cta_purchase_02(): array {
		$key = 'cta_purchase_02';
		return self::cta_definition(
			$key,
			'Purchase CTA (strong)',
			'Strong purchase CTA with optional trust line. For product or checkout emphasis.',
			'strong',
			'Strong purchase CTA with trust line.',
			self::standard_cta_fields( 'cta_pur_2' ),
			array( 'heading' => 'Get it today', 'body' => 'Fast delivery. Secure checkout.', 'primary_button_label' => 'Buy now', 'primary_button_link' => array(), 'trust_line' => 'Secure payment' ),
			'purchase',
			'strong',
			array( 'short_label' => 'Purchase CTA strong' )
		);
	}

	public static function cta_inquiry_01(): array {
		$key = 'cta_inquiry_01';
		return self::cta_definition(
			$key,
			'Inquiry CTA (minimalist)',
			'Minimalist inquiry CTA. Single primary action to contact or request info.',
			'minimalist',
			'Minimal inquiry CTA.',
			self::minimal_cta_fields( 'cta_inq_1' ),
			array( 'heading' => 'Have questions?', 'body' => '', 'primary_button_label' => 'Get in touch', 'primary_button_link' => array() ),
			'inquiry',
			'minimalist',
			array( 'short_label' => 'Inquiry CTA', 'animation_tier' => 'none' )
		);
	}

	public static function cta_inquiry_02(): array {
		$key = 'cta_inquiry_02';
		return self::cta_definition(
			$key,
			'Inquiry CTA (proof-backed)',
			'Inquiry CTA with optional trust/proof line. Proof-backed variant.',
			'proof_backed',
			'Inquiry CTA with trust line.',
			self::standard_cta_fields( 'cta_inq_2' ),
			array( 'heading' => 'Request more information', 'body' => 'We respond within 24 hours.', 'primary_button_label' => 'Send inquiry', 'primary_button_link' => array(), 'trust_line' => 'We reply within 24 hours' ),
			'inquiry',
			'proof_backed',
			array( 'short_label' => 'Inquiry CTA proof' )
		);
	}

	public static function cta_contact_01(): array {
		$key = 'cta_contact_01';
		return self::cta_definition(
			$key,
			'Contact CTA (subtle)',
			'Subtle contact CTA. Clear primary button; omit secondary when empty.',
			'subtle',
			'Subtle contact CTA.',
			self::standard_cta_fields( 'cta_cnt_1' ),
			array( 'heading' => 'Contact us', 'body' => 'We are here to help.', 'primary_button_label' => 'Contact', 'primary_button_link' => array() ),
			'contact',
			'subtle',
			array( 'short_label' => 'Contact CTA' )
		);
	}

	public static function cta_contact_02(): array {
		$key = 'cta_contact_02';
		return self::cta_definition(
			$key,
			'Contact CTA (strong)',
			'Strong contact CTA with optional secondary action and trust line.',
			'strong',
			'Strong contact CTA.',
			self::standard_cta_fields( 'cta_cnt_2' ),
			array( 'heading' => 'Get in touch', 'body' => 'Call, email, or visit.', 'primary_button_label' => 'Contact now', 'primary_button_link' => array(), 'secondary_button_label' => 'View locations', 'secondary_button_link' => array() ),
			'contact',
			'strong',
			array( 'short_label' => 'Contact CTA strong' )
		);
	}

	public static function cta_quote_request_01(): array {
		$key = 'cta_quote_request_01';
		return self::cta_definition(
			$key,
			'Quote request CTA (minimalist)',
			'Minimalist quote request CTA. Single primary action.',
			'minimalist',
			'Minimal quote request CTA.',
			self::minimal_cta_fields( 'cta_quo_1' ),
			array( 'heading' => 'Request a quote', 'body' => '', 'primary_button_label' => 'Get quote', 'primary_button_link' => array() ),
			'quote_request',
			'minimalist',
			array( 'short_label' => 'Quote CTA', 'animation_tier' => 'none' )
		);
	}

	public static function cta_quote_request_02(): array {
		$key = 'cta_quote_request_02';
		return self::cta_definition(
			$key,
			'Quote request CTA (proof-backed)',
			'Quote request CTA with optional trust line. Proof-backed variant.',
			'proof_backed',
			'Quote CTA with trust line.',
			self::standard_cta_fields( 'cta_quo_2' ),
			array( 'heading' => 'Get a free quote', 'body' => 'No obligation. Fast response.', 'primary_button_label' => 'Request quote', 'primary_button_link' => array(), 'trust_line' => 'Free, no obligation' ),
			'quote_request',
			'proof_backed',
			array( 'short_label' => 'Quote CTA proof' )
		);
	}

	public static function cta_directory_nav_01(): array {
		$key = 'cta_directory_nav_01';
		return self::cta_definition(
			$key,
			'Directory navigation CTA',
			'CTA for directory or listing navigation. Subtle; primary action to browse or filter.',
			'subtle',
			'Directory navigation CTA.',
			self::minimal_cta_fields( 'cta_dir_1' ),
			array( 'heading' => 'Browse directory', 'body' => 'Find what you need.', 'primary_button_label' => 'View all', 'primary_button_link' => array() ),
			'directory_nav',
			'subtle',
			array( 'short_label' => 'Directory nav CTA' )
		);
	}

	public static function cta_compare_next_01(): array {
		$key = 'cta_compare_next_01';
		return self::cta_definition(
			$key,
			'Compare / next step CTA',
			'Minimalist CTA for compare or next-step flow. Single primary action.',
			'minimalist',
			'Compare or next step CTA.',
			self::minimal_cta_fields( 'cta_cmp_1' ),
			array( 'heading' => 'Compare options', 'body' => '', 'primary_button_label' => 'Compare now', 'primary_button_link' => array() ),
			'compare_next',
			'minimalist',
			array( 'short_label' => 'Compare CTA', 'animation_tier' => 'none' )
		);
	}

	public static function cta_trust_confirm_01(): array {
		$key = 'cta_trust_confirm_01';
		return self::cta_definition(
			$key,
			'Trust-confirmation CTA (proof-backed)',
			'Trust-confirmation CTA with proof line. Reassurance and primary action.',
			'proof_backed',
			'Trust CTA with proof line.',
			self::standard_cta_fields( 'cta_trc_1' ),
			array( 'heading' => 'You are in good hands', 'body' => 'Join thousands of satisfied customers.', 'primary_button_label' => 'Get started', 'primary_button_link' => array(), 'trust_line' => 'Rated 4.9/5' ),
			'trust_confirm',
			'proof_backed',
			array( 'short_label' => 'Trust CTA' )
		);
	}

	public static function cta_trust_confirm_02(): array {
		$key = 'cta_trust_confirm_02';
		return self::cta_definition(
			$key,
			'Trust-confirmation CTA (strong)',
			'Strong trust-confirmation CTA. Emphasis on reassurance and conversion.',
			'strong',
			'Strong trust CTA.',
			self::standard_cta_fields( 'cta_trc_2' ),
			array( 'heading' => 'Ready to start?', 'body' => 'Secure and trusted.', 'primary_button_label' => 'Continue', 'primary_button_link' => array(), 'trust_line' => 'Trusted partner' ),
			'trust_confirm',
			'strong',
			array( 'short_label' => 'Trust CTA strong' )
		);
	}

	public static function cta_local_action_01(): array {
		$key = 'cta_local_action_01';
		return self::cta_definition(
			$key,
			'Local action CTA (media-backed)',
			'Local action CTA with optional image. For location or venue emphasis.',
			'media_backed',
			'Local CTA with optional image.',
			self::standard_cta_fields( 'cta_loc_1' ),
			array( 'heading' => 'Visit us', 'body' => 'Find us nearby.', 'primary_button_label' => 'Get directions', 'primary_button_link' => array(), 'image' => array() ),
			'local_action',
			'media_backed',
			array( 'short_label' => 'Local CTA' )
		);
	}

	public static function cta_local_action_02(): array {
		$key = 'cta_local_action_02';
		return self::cta_definition(
			$key,
			'Local action CTA (minimalist)',
			'Minimalist local action CTA. Single primary action (e.g. directions, visit).',
			'minimalist',
			'Minimal local CTA.',
			self::minimal_cta_fields( 'cta_loc_2' ),
			array( 'heading' => 'Find a location', 'body' => '', 'primary_button_label' => 'View map', 'primary_button_link' => array() ),
			'local_action',
			'minimalist',
			array( 'short_label' => 'Local CTA minimal', 'animation_tier' => 'none' )
		);
	}

	public static function cta_service_detail_01(): array {
		$key = 'cta_service_detail_01';
		return self::cta_definition(
			$key,
			'Service-detail CTA (strong)',
			'Strong CTA for service detail pages. Primary and optional secondary action.',
			'strong',
			'Strong service detail CTA.',
			self::standard_cta_fields( 'cta_svd_1' ),
			array( 'heading' => 'Book this service', 'body' => 'Schedule at your convenience.', 'primary_button_label' => 'Book now', 'primary_button_link' => array(), 'secondary_button_label' => 'Learn more', 'secondary_button_link' => array() ),
			'service_detail',
			'strong',
			array( 'short_label' => 'Service detail CTA' )
		);
	}

	public static function cta_service_detail_02(): array {
		$key = 'cta_service_detail_02';
		return self::cta_definition(
			$key,
			'Service-detail CTA (subtle)',
			'Subtle CTA for service detail. Single primary action.',
			'subtle',
			'Subtle service detail CTA.',
			self::standard_cta_fields( 'cta_svd_2' ),
			array( 'heading' => 'Request this service', 'body' => 'We will get back to you.', 'primary_button_label' => 'Request', 'primary_button_link' => array() ),
			'service_detail',
			'subtle',
			array( 'short_label' => 'Service detail CTA subtle' )
		);
	}

	public static function cta_product_detail_01(): array {
		$key = 'cta_product_detail_01';
		return self::cta_definition(
			$key,
			'Product-detail CTA (media-backed)',
			'Product-detail CTA with optional image. Media-backed variant.',
			'media_backed',
			'Product CTA with optional image.',
			self::standard_cta_fields( 'cta_prd_1' ),
			array( 'heading' => 'Add to cart', 'body' => 'Available for fast delivery.', 'primary_button_label' => 'Add to cart', 'primary_button_link' => array(), 'image' => array() ),
			'product_detail',
			'media_backed',
			array( 'short_label' => 'Product detail CTA' )
		);
	}

	public static function cta_product_detail_02(): array {
		$key = 'cta_product_detail_02';
		return self::cta_definition(
			$key,
			'Product-detail CTA (strong)',
			'Strong product-detail CTA. Emphasis on add-to-cart or buy.',
			'strong',
			'Strong product CTA.',
			self::standard_cta_fields( 'cta_prd_2' ),
			array( 'heading' => 'Buy now', 'body' => 'Secure checkout. Fast shipping.', 'primary_button_label' => 'Buy now', 'primary_button_link' => array(), 'trust_line' => 'Free returns' ),
			'product_detail',
			'strong',
			array( 'short_label' => 'Product detail CTA strong' )
		);
	}

	public static function cta_support_01(): array {
		$key = 'cta_support_01';
		return self::cta_definition(
			$key,
			'Support CTA (minimalist)',
			'Minimalist support CTA. Single primary action to help or contact support.',
			'minimalist',
			'Minimal support CTA.',
			self::minimal_cta_fields( 'cta_sup_1' ),
			array( 'heading' => 'Need help?', 'body' => '', 'primary_button_label' => 'Contact support', 'primary_button_link' => array() ),
			'support',
			'minimalist',
			array( 'short_label' => 'Support CTA', 'animation_tier' => 'none' )
		);
	}

	public static function cta_support_02(): array {
		$key = 'cta_support_02';
		return self::cta_definition(
			$key,
			'Support CTA (proof-backed)',
			'Support CTA with optional trust line. Proof-backed variant.',
			'proof_backed',
			'Support CTA with trust line.',
			self::standard_cta_fields( 'cta_sup_2' ),
			array( 'heading' => 'We are here to help', 'body' => 'Reach support 24/7.', 'primary_button_label' => 'Get support', 'primary_button_link' => array(), 'trust_line' => '24/7 support' ),
			'support',
			'proof_backed',
			array( 'short_label' => 'Support CTA proof' )
		);
	}

	public static function cta_policy_utility_01(): array {
		$key = 'cta_policy_utility_01';
		return self::cta_definition(
			$key,
			'Policy/utility CTA (subtle)',
			'Subtle CTA for policy or utility flows (e.g. view policy, contact, back to top).',
			'subtle',
			'Subtle policy/utility CTA.',
			self::minimal_cta_fields( 'cta_pol_1' ),
			array( 'heading' => 'Next steps', 'body' => '', 'primary_button_label' => 'Continue', 'primary_button_link' => array() ),
			'policy_utility',
			'subtle',
			array( 'short_label' => 'Policy CTA', 'animation_tier' => 'none' )
		);
	}

	public static function cta_policy_utility_02(): array {
		$key = 'cta_policy_utility_02';
		return self::cta_definition(
			$key,
			'Policy/utility CTA (minimalist)',
			'Minimalist policy/utility CTA. Single primary action.',
			'minimalist',
			'Minimal policy/utility CTA.',
			self::minimal_cta_fields( 'cta_pol_2' ),
			array( 'heading' => 'Contact', 'body' => '', 'primary_button_label' => 'Contact us', 'primary_button_link' => array() ),
			'policy_utility',
			'minimalist',
			array( 'short_label' => 'Policy CTA minimal', 'animation_tier' => 'none' )
		);
	}
}
