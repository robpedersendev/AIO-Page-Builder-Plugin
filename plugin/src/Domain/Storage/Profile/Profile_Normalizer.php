<?php
/**
 * Normalizes and sanitizes profile input to schema shape (spec §22.12). No persistence; no secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Enforces profile-schema.md shape: defaults for missing keys, enum sanitization, string/URL sanitization.
 * Prohibited keys (password, api_key, etc.) are stripped; invalid enums coerced to empty string.
 */
final class Profile_Normalizer {

	/** Keys that must never be stored in profile (secrets/auth). */
	private const PROHIBITED_KEYS = array(
		'password',
		'passwd',
		'pwd',
		'api_key',
		'apikey',
		'api_secret',
		'secret_key',
		'bearer_token',
		'access_token',
		'auth_token',
		'session_id',
		'csrf_token',
	);

	/**
	 * Normalizes brand profile input to full schema shape with defaults.
	 *
	 * @param array<string, mixed> $input Raw or partial brand profile.
	 * @return array<string, mixed> Normalized brand_profile shape; never null branches.
	 */
	public function normalize_brand_profile( array $input ): array {
		$input  = $this->strip_prohibited( $input );
		$voice  = isset( $input[ Profile_Schema::BRAND_VOICE_TONE ] ) && is_array( $input[ Profile_Schema::BRAND_VOICE_TONE ] )
			? $this->normalize_voice_tone( $input[ Profile_Schema::BRAND_VOICE_TONE ] )
			: $this->default_voice_tone();
		$assets = isset( $input[ Profile_Schema::BRAND_ASSET_REFERENCES ] ) && is_array( $input[ Profile_Schema::BRAND_ASSET_REFERENCES ] )
			? $this->normalize_asset_references( $input[ Profile_Schema::BRAND_ASSET_REFERENCES ] )
			: array();

		return array(
			'brand_positioning_summary'            => $this->sanitize_string( $input['brand_positioning_summary'] ?? '' ),
			'brand_voice_summary'                  => $this->sanitize_string( $input['brand_voice_summary'] ?? '' ),
			Profile_Schema::BRAND_VOICE_TONE       => $voice,
			'preferred_cta_style'                  => $this->sanitize_string( $input['preferred_cta_style'] ?? '' ),
			Profile_Schema::BRAND_ASSET_REFERENCES => $assets,
			'additional_brand_rules'               => $this->sanitize_string( $input['additional_brand_rules'] ?? '' ),
			'content_restrictions'                 => $this->sanitize_string( $input['content_restrictions'] ?? '' ),
		);
	}

	/**
	 * Normalizes business profile input to full schema shape with defaults.
	 *
	 * @param array<string, mixed> $input Raw or partial business profile.
	 * @return array<string, mixed> Normalized business_profile shape.
	 */
	public function normalize_business_profile( array $input ): array {
		$input       = $this->strip_prohibited( $input );
		$personas    = isset( $input[ Profile_Schema::BUSINESS_PERSONAS ] ) && is_array( $input[ Profile_Schema::BUSINESS_PERSONAS ] )
			? $this->normalize_personas( $input[ Profile_Schema::BUSINESS_PERSONAS ] )
			: array();
		$services    = isset( $input[ Profile_Schema::BUSINESS_SERVICES_OFFERS ] ) && is_array( $input[ Profile_Schema::BUSINESS_SERVICES_OFFERS ] )
			? $this->normalize_services_offers( $input[ Profile_Schema::BUSINESS_SERVICES_OFFERS ] )
			: array();
		$competitors = isset( $input[ Profile_Schema::BUSINESS_COMPETITORS ] ) && is_array( $input[ Profile_Schema::BUSINESS_COMPETITORS ] )
			? $this->normalize_competitors( $input[ Profile_Schema::BUSINESS_COMPETITORS ] )
			: array();
		$geography   = isset( $input[ Profile_Schema::BUSINESS_GEOGRAPHY ] ) && is_array( $input[ Profile_Schema::BUSINESS_GEOGRAPHY ] )
			? $this->normalize_geography( $input[ Profile_Schema::BUSINESS_GEOGRAPHY ] )
			: array();

		$url = $input['current_site_url'] ?? '';
		if ( is_string( $url ) && $url !== '' && ! $this->is_valid_url( $url ) ) {
			$url = '';
		} elseif ( is_string( $url ) ) {
			$url = \esc_url_raw( $url );
		} else {
			$url = '';
		}

		return array(
			'business_name'                          => $this->sanitize_string( $input['business_name'] ?? '' ),
			'business_type'                          => $this->sanitize_string( $input['business_type'] ?? '' ),
			'current_site_url'                       => $url,
			'preferred_contact_or_conversion_goals'  => $this->sanitize_string( $input['preferred_contact_or_conversion_goals'] ?? '' ),
			'primary_offers_summary'                 => $this->sanitize_string( $input['primary_offers_summary'] ?? '' ),
			'target_audience_summary'                => $this->sanitize_string( $input['target_audience_summary'] ?? '' ),
			'core_geographic_market'                 => $this->sanitize_string( $input['core_geographic_market'] ?? '' ),
			Profile_Schema::BUSINESS_PERSONAS        => $personas,
			Profile_Schema::BUSINESS_SERVICES_OFFERS => $services,
			Profile_Schema::BUSINESS_COMPETITORS     => $competitors,
			Profile_Schema::BUSINESS_GEOGRAPHY       => $geography,
			'value_proposition_notes'                => $this->sanitize_string( $input['value_proposition_notes'] ?? '' ),
			'strategic_priorities'                   => $this->sanitize_string( $input['strategic_priorities'] ?? '' ),
			'major_differentiators'                  => $this->sanitize_string( $input['major_differentiators'] ?? '' ),
			'seasonality'                            => $this->sanitize_string( $input['seasonality'] ?? '' ),
			'compliance_or_legal_notes'              => $this->sanitize_string( $input['compliance_or_legal_notes'] ?? '' ),
			'preferred_content_emphasis'             => $this->sanitize_string( $input['preferred_content_emphasis'] ?? '' ),
			'existing_marketing_language'            => $this->sanitize_string( $input['existing_marketing_language'] ?? '' ),
			'internal_sales_process_notes'           => $this->sanitize_string( $input['internal_sales_process_notes'] ?? '' ),
			'visual_inspiration_references'          => $this->sanitize_string( $input['visual_inspiration_references'] ?? '' ),
		);
	}

	/**
	 * Validates and optionally returns sanitized brand profile.
	 *
	 * @param array<string, mixed> $input Raw brand profile.
	 * @return Profile_Validation_Result
	 */
	public function validate_brand_profile( array $input ): Profile_Validation_Result {
		$sanitized = $this->normalize_brand_profile( $input );
		return Profile_Validation_Result::success( $sanitized );
	}

	/**
	 * Validates and optionally returns sanitized business profile; invalid URL adds error but sanitized payload still safe to store.
	 *
	 * @param array<string, mixed> $input Raw business profile.
	 * @return Profile_Validation_Result
	 */
	public function validate_business_profile( array $input ): Profile_Validation_Result {
		$errors = array();
		$url    = $input['current_site_url'] ?? '';
		if ( is_string( $url ) && $url !== '' && ! $this->is_valid_url( $url ) ) {
			$errors[] = 'current_site_url is not a valid URL';
		}
		$competitors = $input[ Profile_Schema::BUSINESS_COMPETITORS ] ?? array();
		if ( is_array( $competitors ) ) {
			foreach ( $competitors as $i => $c ) {
				if ( ! is_array( $c ) ) {
					continue;
				}
				$cu = $c['competitor_url'] ?? '';
				if ( is_string( $cu ) && $cu !== '' && ! $this->is_valid_url( $cu ) ) {
					$errors[] = sprintf( 'competitors[%d].competitor_url is not a valid URL', $i );
				}
			}
		}
		$sanitized = $this->normalize_business_profile( $input );
		return empty( $errors )
			? Profile_Validation_Result::success( $sanitized )
			: Profile_Validation_Result::failure( $errors, $sanitized );
	}

	/** @return array<string, mixed> */
	private function default_voice_tone(): array {
		return array(
			'core_tone_descriptors'            => array(),
			'prohibited_tone_descriptors'      => array(),
			'formality_level'                  => '',
			'emotional_positioning'            => '',
			'clarity_vs_sophistication'        => '',
			'audience_style_notes'             => '',
			'copy_restrictions_or_preferences' => '',
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	private function normalize_voice_tone( array $input ): array {
		$input      = $this->strip_prohibited( $input );
		$core       = isset( $input['core_tone_descriptors'] ) && is_array( $input['core_tone_descriptors'] )
			? array_map( fn( $v ) => $this->sanitize_string( $v ), $input['core_tone_descriptors'] )
			: array();
		$prohibited = isset( $input['prohibited_tone_descriptors'] ) && is_array( $input['prohibited_tone_descriptors'] )
			? array_map( fn( $v ) => $this->sanitize_string( $v ), $input['prohibited_tone_descriptors'] )
			: array();

		return array(
			'core_tone_descriptors'            => array_values( $core ),
			'prohibited_tone_descriptors'      => array_values( $prohibited ),
			'formality_level'                  => $this->sanitize_enum( $input['formality_level'] ?? '', Profile_Schema::FORMALITY_LEVELS ),
			'emotional_positioning'            => $this->sanitize_string( $input['emotional_positioning'] ?? '' ),
			'clarity_vs_sophistication'        => $this->sanitize_enum( $input['clarity_vs_sophistication'] ?? '', Profile_Schema::CLARITY_VS_SOPHISTICATION ),
			'audience_style_notes'             => $this->sanitize_string( $input['audience_style_notes'] ?? '' ),
			'copy_restrictions_or_preferences' => $this->sanitize_string( $input['copy_restrictions_or_preferences'] ?? '' ),
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $list
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_asset_references( array $list ): array {
		$out = array();
		foreach ( $list as $item ) {
			$item = $this->strip_prohibited( $item );
			$role = $this->sanitize_enum( $item['role'] ?? '', Profile_Schema::ASSET_ROLES );
			$att  = $item['attachment_id'] ?? null;
			if ( $att !== null && $att !== '' ) {
				$att = is_numeric( $att ) ? (int) $att : $this->sanitize_string( (string) $att );
			} else {
				$att = null;
			}
			$path = isset( $item['path_or_url'] ) ? $this->sanitize_string( (string) $item['path_or_url'] ) : '';
			if ( $path !== '' && ! $this->is_valid_url( $path ) ) {
				$path = \sanitize_text_field( $path );
			}
			$out[] = array(
				'role'          => $role,
				'attachment_id' => $att,
				'path_or_url'   => $path,
				'notes'         => $this->sanitize_string( $item['notes'] ?? '' ),
			);
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $list
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_personas( array $list ): array {
		$out = array();
		foreach ( $list as $item ) {
			$item  = $this->strip_prohibited( $item );
			$out[] = array(
				'persona_name_or_role'                     => $this->sanitize_string( $item['persona_name_or_role'] ?? '' ),
				'demographic_or_market_description'        => $this->sanitize_string( $item['demographic_or_market_description'] ?? '' ),
				'goals'                                    => $this->sanitize_string( $item['goals'] ?? '' ),
				'pain_points'                              => $this->sanitize_string( $item['pain_points'] ?? '' ),
				'buying_motivations'                       => $this->sanitize_string( $item['buying_motivations'] ?? '' ),
				'objections'                               => $this->sanitize_string( $item['objections'] ?? '' ),
				'service_relevance'                        => $this->sanitize_string( $item['service_relevance'] ?? '' ),
				'conversion_expectations'                  => $this->sanitize_string( $item['conversion_expectations'] ?? '' ),
				'tone_sensitivity_or_messaging_preference' => $this->sanitize_string( $item['tone_sensitivity_or_messaging_preference'] ?? '' ),
			);
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $list
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_services_offers( array $list ): array {
		$out = array();
		foreach ( $list as $item ) {
			$item  = $this->strip_prohibited( $item );
			$out[] = array(
				'name'                     => $this->sanitize_string( $item['name'] ?? '' ),
				'category'                 => $this->sanitize_string( $item['category'] ?? '' ),
				'short_description'        => $this->sanitize_string( $item['short_description'] ?? '' ),
				'strategic_importance'     => $this->sanitize_string( $item['strategic_importance'] ?? '' ),
				'target_audience'          => $this->sanitize_string( $item['target_audience'] ?? '' ),
				'geographic_applicability' => $this->sanitize_string( $item['geographic_applicability'] ?? '' ),
				'offer_relationships'      => $this->sanitize_string( $item['offer_relationships'] ?? '' ),
				'hierarchy_hints'          => $this->sanitize_string( $item['hierarchy_hints'] ?? '' ),
				'dedicated_pages_likely'   => $this->sanitize_enum( $item['dedicated_pages_likely'] ?? '', Profile_Schema::DEDICATED_PAGES_LIKELY ),
			);
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $list
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_competitors( array $list ): array {
		$out = array();
		foreach ( $list as $item ) {
			$item = $this->strip_prohibited( $item );
			$url  = $item['competitor_url'] ?? '';
			if ( is_string( $url ) && $url !== '' ) {
				$url = $this->is_valid_url( $url ) ? \esc_url_raw( $url ) : '';
			} else {
				$url = '';
			}
			$out[] = array(
				'competitor_name'               => $this->sanitize_string( $item['competitor_name'] ?? '' ),
				'competitor_url'                => $url,
				'market_relevance'              => $this->sanitize_string( $item['market_relevance'] ?? '' ),
				'competitive_positioning_notes' => $this->sanitize_string( $item['competitive_positioning_notes'] ?? '' ),
				'differentiation_observations'  => $this->sanitize_string( $item['differentiation_observations'] ?? '' ),
				'strengths_weaknesses_notes'    => $this->sanitize_string( $item['strengths_weaknesses_notes'] ?? '' ),
			);
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $list
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_geography( array $list ): array {
		$out = array();
		foreach ( $list as $item ) {
			$item  = $this->strip_prohibited( $item );
			$out[] = array(
				'primary_location'              => $this->sanitize_string( $item['primary_location'] ?? '' ),
				'secondary_locations'           => $this->sanitize_string( $item['secondary_locations'] ?? '' ),
				'service_area'                  => $this->sanitize_string( $item['service_area'] ?? '' ),
				'shipping_area'                 => $this->sanitize_string( $item['shipping_area'] ?? '' ),
				'region_type'                   => $this->sanitize_string( $item['region_type'] ?? '' ),
				'in_person_vs_remote'           => $this->sanitize_enum( $item['in_person_vs_remote'] ?? '', Profile_Schema::IN_PERSON_VS_REMOTE ),
				'location_specific_offer_notes' => $this->sanitize_string( $item['location_specific_offer_notes'] ?? '' ),
			);
		}
		return $out;
	}

	private function sanitize_string( mixed $v ): string {
		if ( ! is_scalar( $v ) ) {
			return '';
		}
		$s = (string) $v;
		$s = \wp_strip_all_tags( $s );
		return \sanitize_text_field( $s );
	}

	/**
	 * @param array<string> $allowed
	 */
	private function sanitize_enum( string $value, array $allowed ): string {
		$value = trim( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : '';
	}

	private function is_valid_url( string $url ): bool {
		if ( $url === '' ) {
			return true;
		}
		$u = \esc_url_raw( $url );
		return $u !== '' && preg_match( '#^https?://#i', $u ) === 1;
	}

	/**
	 * Recursively strip prohibited keys (secrets) from array. Does not alter list values.
	 *
	 * @param array<string, mixed> $arr
	 * @return array<string, mixed>
	 */
	private function strip_prohibited( array $arr ): array {
		$out = array();
		foreach ( $arr as $k => $v ) {
			$key_lower = strtolower( (string) $k );
			$skip      = false;
			foreach ( self::PROHIBITED_KEYS as $prohibited ) {
				if ( strpos( $key_lower, $prohibited ) !== false ) {
					$skip = true;
					break;
				}
			}
			if ( $skip ) {
				continue;
			}
			$out[ $k ] = is_array( $v ) ? $this->strip_prohibited( $v ) : $v;
		}
		return $out;
	}
}
