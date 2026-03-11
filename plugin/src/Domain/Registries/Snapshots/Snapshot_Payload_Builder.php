<?php
/**
 * Builds schema-aligned snapshot payloads and excludes prohibited fields (spec §10.8, §12, §13).
 * No secrets or raw prohibited data in payloads.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Snapshots;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Normalized payload builders for registry snapshots. Payloads are explicit and schema-aligned.
 */
final class Snapshot_Payload_Builder {

	/** Field names that must never appear in snapshot payloads (secrets, tokens, etc.). */
	private const PROHIBITED_FIELD_PATTERNS = array(
		'api_key',
		'api_secret',
		'secret_key',
		'password',
		'token',
		'access_token',
		'auth_token',
		'credential',
	);

	/**
	 * Builds section-registry snapshot payload: list of section summaries (keys, status, compatibility).
	 *
	 * @param list<array<string, mixed>> $section_definitions From Section_Registry_Service::list_by_status.
	 * @return array{sections: list<array<string, mixed>>, captured_at: string}
	 */
	public static function build_section_registry_payload( array $section_definitions ): array {
		$allowed = array(
			Section_Schema::FIELD_INTERNAL_KEY,
			Section_Schema::FIELD_NAME,
			Section_Schema::FIELD_CATEGORY,
			Section_Schema::FIELD_STATUS,
			Section_Schema::FIELD_COMPATIBILITY,
			Section_Schema::FIELD_VERSION,
			Section_Schema::FIELD_RENDER_MODE,
			'deprecation',
			'replacement_section_suggestions',
		);
		$sections = array();
		foreach ( $section_definitions as $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			$sections[] = self::filter_to_allowed( $def, $allowed );
		}
		return array(
			'sections'     => $sections,
			'captured_at'  => self::iso8601_now(),
		);
	}

	/**
	 * Builds page-template-registry snapshot payload: list of template summaries.
	 *
	 * @param list<array<string, mixed>> $template_definitions From Page_Template_Registry_Service::list_by_status etc.
	 * @return array{templates: list<array<string, mixed>>, captured_at: string}
	 */
	public static function build_page_template_registry_payload( array $template_definitions ): array {
		$allowed = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY,
			Page_Template_Schema::FIELD_NAME,
			Page_Template_Schema::FIELD_ARCHETYPE,
			Page_Template_Schema::FIELD_ORDERED_SECTIONS,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS,
			Page_Template_Schema::FIELD_STATUS,
			Page_Template_Schema::FIELD_COMPATIBILITY,
			Page_Template_Schema::FIELD_VERSION,
			'deprecation',
		);
		$templates = array();
		foreach ( $template_definitions as $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			$templates[] = self::filter_to_allowed( $def, $allowed );
		}
		return array(
			'templates'    => $templates,
			'captured_at'  => self::iso8601_now(),
		);
	}

	/**
	 * Builds composition-context snapshot payload: composition state for validation traceability.
	 *
	 * @param array<string, mixed> $composition_definition Normalized composition definition.
	 * @return array{composition_id: string, ordered_section_list: array, validation_status: string, source_refs: array, captured_at: string}
	 */
	public static function build_composition_context_payload( array $composition_definition ): array {
		$allowed = array(
			Composition_Schema::FIELD_COMPOSITION_ID,
			Composition_Schema::FIELD_NAME,
			Composition_Schema::FIELD_ORDERED_SECTION_LIST,
			Composition_Schema::FIELD_STATUS,
			Composition_Schema::FIELD_VALIDATION_STATUS,
			Composition_Schema::FIELD_VALIDATION_CODES,
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF,
			Composition_Schema::FIELD_DUPLICATED_FROM_COMPOSITION_ID,
		);
		$filtered = self::filter_to_allowed( $composition_definition, $allowed );
		$filtered['captured_at'] = self::iso8601_now();
		$filtered['source_refs'] = array(
			'source_template_ref'              => (string) ( $composition_definition[ Composition_Schema::FIELD_SOURCE_TEMPLATE_REF ] ?? '' ),
			'duplicated_from_composition_id'  => (string) ( $composition_definition[ Composition_Schema::FIELD_DUPLICATED_FROM_COMPOSITION_ID ] ?? '' ),
		);
		return $filtered;
	}

	/**
	 * Verifies payload contains no prohibited fields. Returns true if safe.
	 *
	 * @param array<string, mixed> $payload
	 * @return bool
	 */
	public static function has_no_prohibited_fields( array $payload ): bool {
		return self::collect_prohibited_keys( $payload ) === array();
	}

	/**
	 * Returns list of keys that match prohibited patterns (for tests/validation).
	 *
	 * @param array<string, mixed> $payload
	 * @return list<string>
	 */
	public static function collect_prohibited_keys( array $payload ): array {
		$found = array();
		self::collect_prohibited_recursive( $payload, $found, '' );
		return array_values( array_unique( $found ) );
	}

	/**
	 * @param array<string, mixed> $data
	 * @param list<string>         $found
	 * @param string               $prefix
	 */
	private static function collect_prohibited_recursive( array $data, array &$found, string $prefix ): void {
		foreach ( array_keys( $data ) as $key ) {
			$lower = strtolower( (string) $key );
			foreach ( self::PROHIBITED_FIELD_PATTERNS as $pattern ) {
				if ( strpos( $lower, $pattern ) !== false ) {
					$path = $prefix !== '' ? $prefix . '.' . $key : $key;
					$found[] = $path;
					break;
				}
			}
			if ( is_array( $data[ $key ] ) && ! self::is_list_of_primitives( $data[ $key ] ) ) {
				$next_prefix = $prefix !== '' ? $prefix . '.' . $key : $key;
				self::collect_prohibited_recursive( $data[ $key ], $found, $next_prefix );
			}
		}
	}

	/**
	 * @param array $arr
	 * @return bool
	 */
	private static function is_list_of_primitives( array $arr ): bool {
		if ( $arr === array() ) {
			return true;
		}
		$i = 0;
		foreach ( $arr as $k => $v ) {
			if ( $k !== $i ) {
				return false;
			}
			if ( is_array( $v ) ) {
				return false;
			}
			$i++;
		}
		return true;
	}

	/**
	 * @param array<string, mixed> $data
	 * @param list<string>         $allowed_keys
	 * @return array<string, mixed>
	 */
	private static function filter_to_allowed( array $data, array $allowed_keys ): array {
		$out = array();
		foreach ( $allowed_keys as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$val = $data[ $key ];
				if ( is_array( $val ) ) {
					$val = self::sanitize_nested( $val );
				}
				$out[ $key ] = $val;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $arr
	 * @return array<string, mixed>
	 */
	private static function sanitize_nested( array $arr ): array {
		$out = array();
		foreach ( $arr as $k => $v ) {
			if ( is_array( $v ) ) {
				$out[ $k ] = self::sanitize_nested( $v );
			} else {
				$out[ $k ] = $v;
			}
		}
		return $out;
	}

	private static function iso8601_now(): string {
		return gmdate( 'Y-m-d\TH:i:s\Z' );
	}
}
