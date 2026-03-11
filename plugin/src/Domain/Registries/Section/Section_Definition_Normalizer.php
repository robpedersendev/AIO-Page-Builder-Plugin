<?php
/**
 * Normalizes and sanitizes section definition input to schema shape (spec §12, section-registry-schema.md).
 * No persistence; produces code-level normalized section definition payload for registry use.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section;

defined( 'ABSPATH' ) || exit;

/**
 * Enforces section-registry-schema.md shape: required keys, allowed enums, nested structures.
 * Strips unknown keys at top level; optional fields use schema defaults when missing.
 */
final class Section_Definition_Normalizer {

	/** Max length for internal_key (spec §12.4). */
	private const MAX_INTERNAL_KEY = 64;

	/** Max length for name. */
	private const MAX_NAME = 255;

	/** Max length for purpose_summary. */
	private const MAX_PURPOSE = 1024;

	/** Max length for string refs (blueprint, helper, css). */
	private const MAX_REF = 255;

	/** Default compatibility shape (section-registry-schema §4). */
	private const DEFAULT_COMPATIBILITY = array(
		'may_precede'             => array(),
		'may_follow'              => array(),
		'avoid_adjacent'          => array(),
		'duplicate_purpose_of'    => array(),
		'variant_conflicts'       => array(),
		'requires_page_context'  => '',
		'requires_token_surface'  => false,
		'requires_content_availability' => '',
	);

	/** Default version shape (section-registry-schema §5). */
	private const DEFAULT_VERSION = array(
		'version'              => '1',
		'changelog_ref'        => '',
		'breaking_change'     => false,
		'migration_notes_ref'  => '',
		'stable_key_retained' => true,
	);

	/** Default asset declaration when none (section-registry-schema §3). */
	private const DEFAULT_ASSET_NONE = array( 'none' => true );

	/**
	 * Normalizes section definition input to full schema shape.
	 *
	 * @param array<string, mixed> $input Raw or partial section definition.
	 * @return array<string, mixed> Normalized definition; may still fail validation if required values are empty.
	 */
	public function normalize( array $input ): array {
		$input = $this->filter_known_keys( $input );

		$variants     = $this->normalize_variants( $input[ Section_Schema::FIELD_VARIANTS ] ?? array() );
		$default_var  = $this->pick_default_variant( $input[ Section_Schema::FIELD_DEFAULT_VARIANT ] ?? '', $variants );
		$compat       = $this->normalize_compatibility( $input[ Section_Schema::FIELD_COMPATIBILITY ] ?? array() );
		$version      = $this->normalize_version( $input[ Section_Schema::FIELD_VERSION ] ?? array() );
		$asset        = $this->normalize_asset_declaration( $input[ Section_Schema::FIELD_ASSET_DECLARATION ] ?? array() );
		$deprecation  = $this->normalize_deprecation( $input['deprecation'] ?? array(), $input[ Section_Schema::FIELD_STATUS ] ?? '' );

		$out = array(
			Section_Schema::FIELD_INTERNAL_KEY           => $this->sanitize_key( (string) ( $input[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ) ),
			Section_Schema::FIELD_NAME                   => $this->sanitize_string( (string) ( $input[ Section_Schema::FIELD_NAME ] ?? '' ), self::MAX_NAME ),
			Section_Schema::FIELD_PURPOSE_SUMMARY        => $this->sanitize_string( (string) ( $input[ Section_Schema::FIELD_PURPOSE_SUMMARY ] ?? '' ), self::MAX_PURPOSE ),
			Section_Schema::FIELD_CATEGORY               => $this->sanitize_category( (string) ( $input[ Section_Schema::FIELD_CATEGORY ] ?? '' ) ),
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => $this->sanitize_string( (string) ( $input[ Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF ] ?? '' ), self::MAX_REF ),
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF    => $this->sanitize_string( (string) ( $input[ Section_Schema::FIELD_FIELD_BLUEPRINT_REF ] ?? '' ), self::MAX_REF ),
			Section_Schema::FIELD_HELPER_REF             => $this->sanitize_string( (string) ( $input[ Section_Schema::FIELD_HELPER_REF ] ?? '' ), self::MAX_REF ),
			Section_Schema::FIELD_CSS_CONTRACT_REF      => $this->sanitize_string( (string) ( $input[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' ), self::MAX_REF ),
			Section_Schema::FIELD_DEFAULT_VARIANT        => $default_var,
			Section_Schema::FIELD_VARIANTS               => $variants,
			Section_Schema::FIELD_COMPATIBILITY          => $compat,
			Section_Schema::FIELD_VERSION                 => $version,
			Section_Schema::FIELD_STATUS                 => $this->sanitize_status( (string) ( $input[ Section_Schema::FIELD_STATUS ] ?? 'draft' ) ),
			Section_Schema::FIELD_RENDER_MODE            => $this->sanitize_render_mode( (string) ( $input[ Section_Schema::FIELD_RENDER_MODE ] ?? 'block' ) ),
			Section_Schema::FIELD_ASSET_DECLARATION      => $asset,
		);

		// * Optional fields (section-registry-schema §10).
		foreach ( Section_Schema::get_optional_fields() as $opt ) {
			if ( $opt === 'deprecation' ) {
				continue;
			}
			if ( array_key_exists( $opt, $input ) ) {
				$out[ $opt ] = $this->normalize_optional_field( $opt, $input[ $opt ] );
			}
		}

		if ( ! empty( $deprecation ) ) {
			$out['deprecation'] = $deprecation;
		}

		return $out;
	}

	/**
	 * Keeps only known required + optional keys; strips unknown keys.
	 *
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	private function filter_known_keys( array $input ): array {
		$allowed = array_merge( Section_Schema::get_required_fields(), Section_Schema::get_optional_fields() );
		$out     = array();
		foreach ( $input as $k => $v ) {
			if ( in_array( $k, $allowed, true ) ) {
				$out[ $k ] = $v;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $raw
	 * @return array<string, array<string, mixed>>
	 */
	private function normalize_variants( array $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array( 'default' => array( 'label' => 'Default' ) );
		}
		$out = array();
		foreach ( $raw as $key => $desc ) {
			if ( ! is_string( $key ) || $key === '' ) {
				continue;
			}
			$key = $this->sanitize_variant_key( $key );
			if ( $key === '' ) {
				continue;
			}
			$desc = is_array( $desc ) ? $desc : array();
			$out[ $key ] = array(
				'label'       => $this->sanitize_string( (string) ( $desc['label'] ?? $key ), 128 ),
				'description' => $this->sanitize_string( (string) ( $desc['description'] ?? '' ), 512 ),
				'css_modifiers' => isset( $desc['css_modifiers'] ) && is_array( $desc['css_modifiers'] )
					? array_map( fn( $m ) => $this->sanitize_string( (string) $m, 64 ), $desc['css_modifiers'] )
					: array(),
			);
		}
		if ( empty( $out ) ) {
			return array( 'default' => array( 'label' => 'Default', 'description' => '', 'css_modifiers' => array() ) );
		}
		return $out;
	}

	/**
	 * @param string                $default
	 * @param array<string, mixed>  $variants
	 * @return string
	 */
	private function pick_default_variant( string $default, array $variants ): string {
		$default = $this->sanitize_variant_key( $default );
		if ( $default !== '' && isset( $variants[ $default ] ) ) {
			return $default;
		}
		$keys = array_keys( $variants );
		return $keys[0] ?? 'default';
	}

	/**
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	private function normalize_compatibility( array $raw ): array {
		if ( ! is_array( $raw ) ) {
			return self::DEFAULT_COMPATIBILITY;
		}
		$arr_sanitize = function ( $arr, int $max_len = 64 ): array {
			if ( ! is_array( $arr ) ) {
				return array();
			}
			$out = array();
			foreach ( $arr as $v ) {
				if ( is_string( $v ) && $v !== '' ) {
					$out[] = $this->sanitize_string( $v, $max_len );
				}
			}
			return array_values( array_unique( $out ) );
		};

		return array(
			'may_precede'             => $arr_sanitize( $raw['may_precede'] ?? array() ),
			'may_follow'              => $arr_sanitize( $raw['may_follow'] ?? array() ),
			'avoid_adjacent'          => $arr_sanitize( $raw['avoid_adjacent'] ?? array() ),
			'duplicate_purpose_of'    => $arr_sanitize( $raw['duplicate_purpose_of'] ?? array() ),
			'variant_conflicts'       => $arr_sanitize( $raw['variant_conflicts'] ?? array() ),
			'requires_page_context'  => $this->sanitize_string( (string) ( $raw['requires_page_context'] ?? '' ), 128 ),
			'requires_token_surface'  => (bool) ( $raw['requires_token_surface'] ?? false ),
			'requires_content_availability' => $this->sanitize_string( (string) ( $raw['requires_content_availability'] ?? '' ), 256 ),
		);
	}

	/**
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	private function normalize_version( array $raw ): array {
		if ( ! is_array( $raw ) ) {
			return self::DEFAULT_VERSION;
		}
		return array(
			'version'              => $this->sanitize_string( (string) ( $raw['version'] ?? '1' ), 32 ),
			'changelog_ref'        => $this->sanitize_string( (string) ( $raw['changelog_ref'] ?? '' ), 255 ),
			'breaking_change'      => (bool) ( $raw['breaking_change'] ?? false ),
			'migration_notes_ref'  => $this->sanitize_string( (string) ( $raw['migration_notes_ref'] ?? '' ), 255 ),
			'stable_key_retained'   => (bool) ( $raw['stable_key_retained'] ?? true ),
		);
	}

	/**
	 * @param array<string, mixed> $raw
	 * @return array<string, mixed>
	 */
	private function normalize_asset_declaration( array $raw ): array {
		if ( ! is_array( $raw ) ) {
			return self::DEFAULT_ASSET_NONE;
		}
		if ( ! empty( $raw['none'] ) ) {
			return array( 'none' => true );
		}
		$shared = isset( $raw['shared_resources'] ) && is_array( $raw['shared_resources'] )
			? array_map( fn( $s ) => $this->sanitize_string( (string) $s, 128 ), $raw['shared_resources'] )
			: array();

		return array(
			'none'           => false,
			'frontend_css'   => (bool) ( $raw['frontend_css'] ?? false ),
			'admin_css'      => (bool) ( $raw['admin_css'] ?? false ),
			'frontend_js'    => (bool) ( $raw['frontend_js'] ?? false ),
			'admin_js'       => (bool) ( $raw['admin_js'] ?? false ),
			'icons'          => (bool) ( $raw['icons'] ?? false ),
			'media_patterns' => (bool) ( $raw['media_patterns'] ?? false ),
			'shared_resources' => array_values( array_filter( $shared ) ),
		);
	}

	/**
	 * @param array<string, mixed> $raw
	 * @param string               $status
	 * @return array<string, mixed>
	 */
	private function normalize_deprecation( array $raw, string $status ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		if ( $status !== 'deprecated' ) {
			return array();
		}
		return array(
			'deprecated'                 => (bool) ( $raw['deprecated'] ?? true ),
			'reason'                     => $this->sanitize_string( (string) ( $raw['reason'] ?? '' ), 512 ),
			'replacement_section_key'    => $this->sanitize_key( (string) ( $raw['replacement_section_key'] ?? '' ) ),
			'retain_existing_references' => (bool) ( $raw['retain_existing_references'] ?? true ),
			'exclude_from_new_selection' => (bool) ( $raw['exclude_from_new_selection'] ?? true ),
			'preserve_rendered_pages'    => (bool) ( $raw['preserve_rendered_pages'] ?? true ),
		);
	}

	/**
	 * @param string $name
	 * @param mixed  $value
	 * @return mixed
	 */
	private function normalize_optional_field( string $name, mixed $value ): mixed {
		$limits = array(
			'short_label'                         => 64,
			'preview_description'                  => 512,
			'preview_image_ref'                   => 255,
			'notes_for_ai_planning'                => 1024,
			'hierarchy_role_hints'                 => 256,
			'seo_relevance_notes'                  => 512,
			'token_affinity_notes'                 => 512,
			'lpagery_mapping_notes'                => 512,
			'accessibility_warnings_or_enhancements' => 512,
			'migration_notes'                      => 512,
			'deprecation_notes'                    => 512,
			'accessibility_contract_ref'           => 255,
		);
		if ( $name === 'suggested_use_cases' || $name === 'prohibited_use_cases' || $name === 'dependencies_sections_or_context' ) {
			return is_array( $value )
				? array_map( fn( $v ) => $this->sanitize_string( (string) $v, 256 ), $value )
				: array();
		}
		if ( $name === 'replacement_section_suggestions' ) {
			return is_array( $value )
				? array_values( array_filter( array_map( fn( $v ) => $this->sanitize_key( (string) $v ), $value ) ) )
				: array();
		}
		if ( isset( $limits[ $name ] ) ) {
			return $this->sanitize_string( (string) $value, $limits[ $name ] );
		}
		if ( $name === 'deprecation' ) {
			return $value;
		}
		return $value;
	}

	private function sanitize_key( string $key ): string {
		$key = \sanitize_text_field( strtolower( $key ) );
		$key = preg_replace( '/[^a-z0-9_]/', '', $key );
		return substr( $key, 0, self::MAX_INTERNAL_KEY );
	}

	private function sanitize_variant_key( string $key ): string {
		$key = \sanitize_text_field( strtolower( $key ) );
		$key = preg_replace( '/[^a-z0-9_]/', '', $key );
		return substr( $key, 0, 64 );
	}

	private function sanitize_string( string $s, int $max ): string {
		$s = \wp_strip_all_tags( $s );
		$s = \sanitize_text_field( $s );
		return substr( $s, 0, $max );
	}

	private function sanitize_category( string $cat ): string {
		return Section_Schema::is_allowed_category( $cat ) ? $cat : '';
	}

	private function sanitize_status( string $status ): string {
		$allowed = array( 'draft', 'active', 'inactive', 'deprecated' );
		return in_array( $status, $allowed, true ) ? $status : 'draft';
	}

	private function sanitize_render_mode( string $mode ): string {
		return Section_Schema::is_allowed_render_mode( $mode ) ? $mode : 'block';
	}
}
