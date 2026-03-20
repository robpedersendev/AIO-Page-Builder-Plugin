<?php
/**
 * Persistence for current editable brand and business profile (spec §22, §8.5). Option root: aio_page_builder_profile_current.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Profile\Template_Preference_Profile;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Normalized current profile payload shape: { brand_profile: array, business_profile: array }.
 * All writes must be capability- and nonce-gated by callers; this service does not enforce permissions.
 */
final class Profile_Store {

	private const OPTION_KEY = Option_Names::PROFILE_CURRENT;

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Profile_Normalizer */
	private Profile_Normalizer $normalizer;

	public function __construct( Settings_Service $settings, Profile_Normalizer $normalizer ) {
		$this->settings   = $settings;
		$this->normalizer = $normalizer;
	}

	/**
	 * Returns normalized brand profile (default shape when empty).
	 *
	 * @return array<string, mixed>
	 */
	public function get_brand_profile(): array {
		$full  = $this->get_full_profile_raw();
		$brand = $full[ Profile_Schema::ROOT_BRAND ] ?? array();
		return is_array( $brand )
			? $this->normalizer->normalize_brand_profile( $brand )
			: $this->normalizer->normalize_brand_profile( array() );
	}

	/**
	 * Returns normalized business profile (default shape when empty).
	 *
	 * @return array<string, mixed>
	 */
	public function get_business_profile(): array {
		$full     = $this->get_full_profile_raw();
		$business = $full[ Profile_Schema::ROOT_BUSINESS ] ?? array();
		return is_array( $business )
			? $this->normalizer->normalize_business_profile( $business )
			: $this->normalizer->normalize_business_profile( array() );
	}

	/**
	 * Returns full current profile with brand, business, and template_preference_profile normalized.
	 *
	 * @return array{brand_profile: array<string, mixed>, business_profile: array<string, mixed>, template_preference_profile: array<string, mixed>}
	 */
	public function get_full_profile(): array {
		return array(
			Profile_Schema::ROOT_BRAND    => $this->get_brand_profile(),
			Profile_Schema::ROOT_BUSINESS => $this->get_business_profile(),
			Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE => $this->get_template_preference_profile_array(),
		);
	}

	/**
	 * Returns template preference profile as array (template_preference_profile payload). Advisory only; no secrets.
	 *
	 * @return array<string, mixed>
	 */
	public function get_template_preference_profile_array(): array {
		$raw   = $this->get_full_profile_raw();
		$block = $raw[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] ?? null;
		if ( ! is_array( $block ) ) {
			return ( new Template_Preference_Profile() )->to_array();
		}
		return ( Template_Preference_Profile::from_array( $block ) )->to_array();
	}

	/**
	 * Returns template preference profile as value object.
	 */
	public function get_template_preference_profile(): Template_Preference_Profile {
		$raw   = $this->get_full_profile_raw();
		$block = $raw[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] ?? null;
		if ( ! is_array( $block ) ) {
			return new Template_Preference_Profile();
		}
		return Template_Preference_Profile::from_array( $block );
	}

	/**
	 * Replaces template preference profile with normalized input. Callers must enforce capability and nonce.
	 *
	 * @param array<string, mixed> $preferences Raw or partial template_preference_profile.
	 * @return void
	 */
	public function set_template_preference_profile( array $preferences ): void {
		$profile = Template_Preference_Profile::from_array( $preferences );
		$full    = $this->get_full_profile_raw();
		$full[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] = $profile->to_array();
		if ( ! isset( $full[ Profile_Schema::ROOT_BRAND ] ) || ! is_array( $full[ Profile_Schema::ROOT_BRAND ] ) ) {
			$full[ Profile_Schema::ROOT_BRAND ] = $this->normalizer->normalize_brand_profile( array() );
		}
		if ( ! isset( $full[ Profile_Schema::ROOT_BUSINESS ] ) || ! is_array( $full[ Profile_Schema::ROOT_BUSINESS ] ) ) {
			$full[ Profile_Schema::ROOT_BUSINESS ] = $this->normalizer->normalize_business_profile( array() );
		}
		$this->settings->set( self::OPTION_KEY, $full );
	}

	/**
	 * Merges partial template preference profile into current; only keys present in $partial update.
	 *
	 * @param array<string, mixed> $partial
	 * @return void
	 */
	public function merge_template_preference_profile( array $partial ): void {
		$current = $this->get_template_preference_profile_array();
		$merged  = $current;
		$keys    = array( 'page_emphasis', 'conversion_posture', 'proof_style', 'content_density', 'animation_preference', 'cta_intensity_preference' );
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $partial ) ) {
				$merged[ $key ] = $partial[ $key ];
			}
		}
		if ( array_key_exists( 'reduced_motion_preference', $partial ) ) {
			$merged['reduced_motion_preference'] = (bool) $partial['reduced_motion_preference'];
		}
		$this->set_template_preference_profile( $merged );
	}

	/**
	 * Replaces brand profile with normalized input; business profile unchanged.
	 *
	 * @param array<string, mixed> $brand Raw or partial brand profile.
	 * @return void
	 */
	public function set_brand_profile( array $brand ): void {
		$full                               = $this->get_full_profile_raw();
		$normal                             = $this->normalizer->normalize_brand_profile( $brand );
		$full[ Profile_Schema::ROOT_BRAND ] = $normal;
		if ( ! isset( $full[ Profile_Schema::ROOT_BUSINESS ] ) || ! is_array( $full[ Profile_Schema::ROOT_BUSINESS ] ) ) {
			$full[ Profile_Schema::ROOT_BUSINESS ] = $this->normalizer->normalize_business_profile( array() );
		}
		$this->settings->set( self::OPTION_KEY, $full );
	}

	/**
	 * Replaces business profile with normalized input; brand profile unchanged.
	 *
	 * @param array<string, mixed> $business Raw or partial business profile.
	 * @return void
	 */
	public function set_business_profile( array $business ): void {
		$full                                  = $this->get_full_profile_raw();
		$normal                                = $this->normalizer->normalize_business_profile( $business );
		$full[ Profile_Schema::ROOT_BUSINESS ] = $normal;
		if ( ! isset( $full[ Profile_Schema::ROOT_BRAND ] ) || ! is_array( $full[ Profile_Schema::ROOT_BRAND ] ) ) {
			$full[ Profile_Schema::ROOT_BRAND ] = $this->normalizer->normalize_brand_profile( array() );
		}
		$this->settings->set( self::OPTION_KEY, $full );
	}

	/**
	 * Replaces entire current profile with normalized brand and business. Preserves template_preference_profile when not supplied.
	 *
	 * @param array<string, mixed> $full Must contain brand_profile and/or business_profile keys; may include template_preference_profile.
	 * @return void
	 */
	public function set_full_profile( array $full ): void {
		$brand    = isset( $full[ Profile_Schema::ROOT_BRAND ] ) && is_array( $full[ Profile_Schema::ROOT_BRAND ] )
			? $full[ Profile_Schema::ROOT_BRAND ] : array();
		$business = isset( $full[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $full[ Profile_Schema::ROOT_BUSINESS ] )
			? $full[ Profile_Schema::ROOT_BUSINESS ] : array();
		$payload  = array(
			Profile_Schema::ROOT_BRAND    => $this->normalizer->normalize_brand_profile( $brand ),
			Profile_Schema::ROOT_BUSINESS => $this->normalizer->normalize_business_profile( $business ),
		);
		if ( isset( $full[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] ) && is_array( $full[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] ) ) {
			$payload[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] = ( Template_Preference_Profile::from_array( $full[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] ) )->to_array();
		} else {
			$payload[ Profile_Schema::ROOT_TEMPLATE_PREFERENCE_PROFILE ] = $this->get_template_preference_profile_array();
		}
		$this->settings->set( self::OPTION_KEY, $payload );
	}

	/**
	 * Merges partial brand into current brand; only keys present in $partial update; then normalizes for storage.
	 * Fires aio_pb_brand_profile_merged with $this after the write so snapshot services can react.
	 *
	 * @param array<string, mixed> $partial Keys to update (nested keys replace at that level).
	 * @return void
	 */
	public function merge_brand_profile( array $partial ): void {
		$current = $this->get_brand_profile();
		$merged  = $this->array_merge_deep( $current, $partial );
		$this->set_brand_profile( $merged );
		\do_action( 'aio_pb_brand_profile_merged', $this );
	}

	/**
	 * Merges partial business into current business; only keys present in $partial update; then normalizes for storage.
	 * Fires aio_pb_business_profile_merged with $this after the write so snapshot services can react.
	 *
	 * @param array<string, mixed> $partial Keys to update.
	 * @return void
	 */
	public function merge_business_profile( array $partial ): void {
		$current = $this->get_business_profile();
		$merged  = $this->array_merge_deep( $current, $partial );
		$this->set_business_profile( $merged );
		\do_action( 'aio_pb_business_profile_merged', $this );
	}

	/**
	 * Raw option value for current profile (no normalization). Internal use.
	 *
	 * @return array<string, mixed>
	 */
	private function get_full_profile_raw(): array {
		$raw = $this->settings->get( self::OPTION_KEY );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Deep merge: second array overwrites first for same keys; numeric arrays replaced by second.
	 *
	 * @param array<string, mixed> $a
	 * @param array<string, mixed> $b
	 * @return array<string, mixed>
	 */
	private function array_merge_deep( array $a, array $b ): array {
		foreach ( $b as $k => $v ) {
			if ( is_array( $v ) && isset( $a[ $k ] ) && is_array( $a[ $k ] ) && ! $this->is_numeric_array( $a[ $k ] ) && ! $this->is_numeric_array( $v ) ) {
				$a[ $k ] = $this->array_merge_deep( $a[ $k ], $v );
			} else {
				$a[ $k ] = $v;
			}
		}
		return $a;
	}

	/** @param array<mixed> $arr */
	private function is_numeric_array( array $arr ): bool {
		if ( $arr === array() ) {
			return true;
		}
		return array_keys( $arr ) === range( 0, count( $arr ) - 1 );
	}
}
