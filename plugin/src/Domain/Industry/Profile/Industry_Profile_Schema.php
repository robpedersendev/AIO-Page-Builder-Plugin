<?php
/**
 * Site-level industry profile schema (industry-pack-extension-contract, industry-profile-schema.md).
 * Required/optional fields, default empty state, and validation helpers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Schema for site-level industry profile. Additive to brand/business profile.
 */
final class Industry_Profile_Schema {

	/** Schema version for migration and validation. */
	public const FIELD_SCHEMA_VERSION = 'schema_version';

	/** Primary industry pack key (e.g. legal, healthcare). */
	public const FIELD_PRIMARY_INDUSTRY_KEY = 'primary_industry_key';

	/** Secondary industry pack keys. */
	public const FIELD_SECONDARY_INDUSTRY_KEYS = 'secondary_industry_keys';

	/** Optional industry subtype. */
	public const FIELD_SUBTYPE = 'subtype';

	/** Optional service model hint. */
	public const FIELD_SERVICE_MODEL = 'service_model';

	/** Optional geo model hint. */
	public const FIELD_GEO_MODEL = 'geo_model';

	/** Optional derived flags (reserved for subsystems). */
	public const FIELD_DERIVED_FLAGS = 'derived_flags';

	/** Supported schema version. */
	public const SUPPORTED_SCHEMA_VERSION = '1';

	/**
	 * Returns default empty profile shape.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_empty_profile(): array {
		return array(
			self::FIELD_SCHEMA_VERSION       => self::SUPPORTED_SCHEMA_VERSION,
			self::FIELD_PRIMARY_INDUSTRY_KEY => '',
			self::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
			self::FIELD_SUBTYPE              => '',
			self::FIELD_SERVICE_MODEL        => '',
			self::FIELD_GEO_MODEL            => '',
			self::FIELD_DERIVED_FLAGS         => array(),
		);
	}

	/**
	 * Whether the given schema version is supported.
	 */
	public static function is_supported_version( string $version ): bool {
		return $version === self::SUPPORTED_SCHEMA_VERSION;
	}

	/**
	 * Normalizes raw option value into a valid profile shape. Safe: returns default on corrupt/invalid.
	 *
	 * @param mixed $raw Raw option value.
	 * @return array<string, mixed>
	 */
	public static function normalize( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return self::get_empty_profile();
		}
		$version = isset( $raw[ self::FIELD_SCHEMA_VERSION ] ) && is_string( $raw[ self::FIELD_SCHEMA_VERSION ] )
			? trim( $raw[ self::FIELD_SCHEMA_VERSION ] )
			: '';
		if ( $version !== '' && ! self::is_supported_version( $version ) ) {
			return self::get_empty_profile();
		}
		$primary = isset( $raw[ self::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $raw[ self::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $raw[ self::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$secondary = isset( $raw[ self::FIELD_SECONDARY_INDUSTRY_KEYS ] ) && is_array( $raw[ self::FIELD_SECONDARY_INDUSTRY_KEYS ] )
			? $raw[ self::FIELD_SECONDARY_INDUSTRY_KEYS ]
			: array();
		$secondary = array_values( array_unique( array_filter( array_map( function ( $v ) {
			return is_string( $v ) ? trim( $v ) : '';
		}, $secondary ) ) ) );
		$subtype = isset( $raw[ self::FIELD_SUBTYPE ] ) && is_string( $raw[ self::FIELD_SUBTYPE ] )
			? trim( $raw[ self::FIELD_SUBTYPE ] )
			: '';
		$service_model = isset( $raw[ self::FIELD_SERVICE_MODEL ] ) && is_string( $raw[ self::FIELD_SERVICE_MODEL ] )
			? trim( $raw[ self::FIELD_SERVICE_MODEL ] )
			: '';
		$geo_model = isset( $raw[ self::FIELD_GEO_MODEL ] ) && is_string( $raw[ self::FIELD_GEO_MODEL ] )
			? trim( $raw[ self::FIELD_GEO_MODEL ] )
			: '';
		$derived = isset( $raw[ self::FIELD_DERIVED_FLAGS ] ) && is_array( $raw[ self::FIELD_DERIVED_FLAGS ] )
			? $raw[ self::FIELD_DERIVED_FLAGS ]
			: array();
		return array(
			self::FIELD_SCHEMA_VERSION        => $version !== '' ? $version : self::SUPPORTED_SCHEMA_VERSION,
			self::FIELD_PRIMARY_INDUSTRY_KEY   => $primary,
			self::FIELD_SECONDARY_INDUSTRY_KEYS => $secondary,
			self::FIELD_SUBTYPE                => $subtype,
			self::FIELD_SERVICE_MODEL           => $service_model,
			self::FIELD_GEO_MODEL               => $geo_model,
			self::FIELD_DERIVED_FLAGS           => $derived,
		);
	}
}
