<?php
/**
 * Maps build-plan section_guidance rows onto section template field values for initial block render (spec §28.5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Section;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;

/**
 * Produces field value maps from structured guidance so create/replace jobs materialize copy in the editor.
 */
final class Section_Guidance_Field_Value_Applier {

	/** @var list<string> */
	private const FILLABLE_TYPES = array(
		Field_Blueprint_Schema::TYPE_TEXT,
		Field_Blueprint_Schema::TYPE_TEXTAREA,
		Field_Blueprint_Schema::TYPE_WYSIWYG,
		Field_Blueprint_Schema::TYPE_URL,
		Field_Blueprint_Schema::TYPE_EMAIL,
	);

	/**
	 * Parses section_guidance from a create/replace target_reference (array or JSON string).
	 *
	 * @param array<string, mixed> $target target_reference envelope fragment.
	 * @return list<array<string, string>>
	 */
	public static function parse_guidance_items( array $target ): array {
		$raw = $target['section_guidance'] ?? null;
		if ( $raw === null ) {
			return array();
		}
		if ( is_string( $raw ) ) {
			$t = trim( $raw );
			if ( $t === '' ) {
				return array();
			}
			$decoded = json_decode( $t, true );
			if ( is_array( $decoded ) ) {
				return self::normalize_guidance_list( $decoded );
			}
			return array(
				array(
					'section_key'       => '',
					'intent'            => '',
					'content_direction' => $t,
					'must_include'      => '',
					'must_avoid'        => '',
				),
			);
		}
		if ( is_array( $raw ) ) {
			return self::normalize_guidance_list( $raw );
		}
		return array();
	}

	/**
	 * @param array<int, mixed> $raw
	 * @return list<array<string, string>>
	 */
	private static function normalize_guidance_list( array $raw ): array {
		$out = array();
		foreach ( $raw as $row ) {
			if ( is_string( $row ) ) {
				$s = trim( $row );
				if ( $s === '' ) {
					continue;
				}
				$out[] = array(
					'section_key'       => '',
					'intent'            => '',
					'content_direction' => $s,
					'must_include'      => '',
					'must_avoid'        => '',
				);
				continue;
			}
			if ( ! is_array( $row ) ) {
				continue;
			}
			$out[] = array(
				'section_key'       => isset( $row['section_key'] ) && is_string( $row['section_key'] ) ? trim( $row['section_key'] ) : '',
				'intent'            => isset( $row['intent'] ) && is_string( $row['intent'] ) ? trim( $row['intent'] ) : '',
				'content_direction' => isset( $row['content_direction'] ) && is_string( $row['content_direction'] ) ? trim( $row['content_direction'] ) : '',
				'must_include'      => isset( $row['must_include'] ) && is_string( $row['must_include'] ) ? trim( $row['must_include'] ) : '',
				'must_avoid'        => isset( $row['must_avoid'] ) && is_string( $row['must_avoid'] ) ? trim( $row['must_avoid'] ) : '',
			);
		}
		return $out;
	}

	/**
	 * Picks the guidance row for the current section template key.
	 *
	 * @param list<array<string, string>> $items
	 */
	public static function find_guidance_for_section( array $items, string $section_key ): ?array {
		$section_key = trim( $section_key );
		foreach ( $items as $row ) {
			if ( ( $row['section_key'] ?? '' ) !== '' && $row['section_key'] === $section_key ) {
				return $row;
			}
		}
		foreach ( $items as $row ) {
			if ( ( $row['section_key'] ?? '' ) === '' ) {
				return $row;
			}
		}
		return null;
	}

	/**
	 * Builds ACF field values for one section from a single guidance row.
	 *
	 * @param array<string, mixed>       $section_definition Section registry definition.
	 * @param array<string, string>|null $guidance_row
	 * @return array<string, mixed>
	 */
	public static function field_values_for_section( array $section_definition, ?array $guidance_row ): array {
		if ( $guidance_row === null ) {
			return array();
		}
		$text = self::compose_guidance_text( $guidance_row );
		if ( $text === '' ) {
			return array();
		}
		$embedded = $section_definition['field_blueprint'] ?? null;
		if ( ! is_array( $embedded ) ) {
			return array();
		}
		$list = $embedded[ Field_Blueprint_Schema::FIELDS ] ?? null;
		if ( ! is_array( $list ) ) {
			return array();
		}
		$fillable = array();
		foreach ( $list as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$type = strtolower( (string) ( $field[ Field_Blueprint_Schema::FIELD_TYPE ] ?? '' ) );
			$name = (string) ( $field[ Field_Blueprint_Schema::FIELD_NAME ] ?? $field[ Field_Blueprint_Schema::FIELD_KEY ] ?? '' );
			if ( $name === '' || ! in_array( $type, self::FILLABLE_TYPES, true ) ) {
				continue;
			}
			$fillable[] = $name;
		}
		if ( $fillable === array() ) {
			return array();
		}
		$chunks = self::split_text_across_fields( $text, count( $fillable ) );
		$out    = array();
		foreach ( $fillable as $i => $name ) {
			$out[ $name ] = isset( $chunks[ $i ] ) ? $chunks[ $i ] : $text;
		}
		return $out;
	}

	/**
	 * @param array<string, string> $guidance_row
	 */
	private static function compose_guidance_text( array $guidance_row ): string {
		$parts = array();
		if ( ( $guidance_row['intent'] ?? '' ) !== '' ) {
			$parts[] = $guidance_row['intent'];
		}
		if ( ( $guidance_row['content_direction'] ?? '' ) !== '' ) {
			$parts[] = $guidance_row['content_direction'];
		}
		if ( ( $guidance_row['must_include'] ?? '' ) !== '' ) {
			$parts[] = __( 'Must include:', 'aio-page-builder' ) . ' ' . $guidance_row['must_include'];
		}
		if ( ( $guidance_row['must_avoid'] ?? '' ) !== '' ) {
			$parts[] = __( 'Avoid:', 'aio-page-builder' ) . ' ' . $guidance_row['must_avoid'];
		}
		return trim( implode( "\n\n", array_filter( $parts ) ) );
	}

	/**
	 * Splits guidance prose across N fields (paragraphs first; then word chunks).
	 *
	 * @return list<string>
	 */
	private static function split_text_across_fields( string $text, int $field_count ): array {
		if ( $field_count <= 1 ) {
			return array( $text );
		}
		$paras = preg_split( '/\r\n|\r|\n\s*\r?\n/', $text );
		if ( is_array( $paras ) && count( $paras ) >= $field_count ) {
			return array_values( array_slice( array_map( 'trim', $paras ), 0, $field_count ) );
		}
		$words = preg_split( '/\s+/', trim( $text ) );
		if ( ! is_array( $words ) || $words === array() ) {
			return array_fill( 0, $field_count, $text );
		}
		$per    = max( 1, (int) ceil( count( $words ) / $field_count ) );
		$chunks = array();
		for ( $i = 0; $i < $field_count; $i++ ) {
			$slice    = array_slice( $words, $i * $per, $per );
			$chunks[] = trim( implode( ' ', $slice ) );
		}
		return $chunks;
	}
}
