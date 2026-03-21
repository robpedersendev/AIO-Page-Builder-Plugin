<?php
/**
 * Scans an industry bundle against local state and produces explicit conflicts.
 * Conflict rule: same key + same content hash => no-op; same key + different content => conflict.
 *
 * @package AIOPageBuilder
 */
declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Import;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

final class Industry_Bundle_Conflict_Scanner {

	public const CONFLICT_SAME_KEY_DIFFERENT_CONTENT = 'same_key_different_content';

	/**
	 * @param array<string, mixed>                 $bundle Valid bundle (manifest + payload).
	 * @param array<string, array<string, string>> $local_hashes Map: category => (object_key => sha256 hash).
	 * @return list<array{category: string, object_key: string, conflict_type: string, incoming_hash: string, local_hash: string}>
	 */
	public function scan( array $bundle, array $local_hashes ): array {
		$included = $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] ?? array();
		if ( ! is_array( $included ) ) {
			return array();
		}

		$conflicts = array();
		foreach ( $included as $category ) {
			if ( ! is_string( $category ) || $category === '' ) {
				continue;
			}
			if ( ! isset( $bundle[ $category ] ) || ! is_array( $bundle[ $category ] ) ) {
				continue;
			}

			$cat_local = isset( $local_hashes[ $category ] ) && is_array( $local_hashes[ $category ] )
				? $local_hashes[ $category ]
				: array();

			foreach ( $bundle[ $category ] as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$key = $this->object_key_for_category( $category, $item );
				if ( $key === '' ) {
					continue;
				}
				$incoming_hash = $this->content_hash( $item );
				$local_hash    = $cat_local[ $key ] ?? '';

				if ( $local_hash === '' ) {
					continue;
				}
				if ( hash_equals( $local_hash, $incoming_hash ) ) {
					continue;
				}

				$conflicts[] = array(
					'category'      => $category,
					'object_key'    => $key,
					'conflict_type' => self::CONFLICT_SAME_KEY_DIFFERENT_CONTENT,
					'incoming_hash' => $incoming_hash,
					'local_hash'    => $local_hash,
				);
			}
		}

		return $conflicts;
	}

	/**
	 * @param array<string, mixed> $value
	 */
	public function content_hash( array $value ): string {
		$normalized = $this->normalize_for_hash( $value );
		$json       = wp_json_encode( $normalized );
		$json       = is_string( $json ) ? $json : '';
		return hash( 'sha256', $json );
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private function normalize_for_hash( $value ) {
		if ( is_array( $value ) ) {
			$is_list = array_keys( $value ) === range( 0, count( $value ) - 1 );
			if ( $is_list ) {
				$out = array();
				foreach ( $value as $v ) {
					$out[] = $this->normalize_for_hash( $v );
				}
				return $out;
			}
			$out  = array();
			$keys = array_keys( $value );
			sort( $keys );
			foreach ( $keys as $k ) {
				$out[ (string) $k ] = $this->normalize_for_hash( $value[ $k ] );
			}
			return $out;
		}
		return $value;
	}

	/**
	 * @param array<string, mixed> $item
	 */
	public function object_key_for_category( string $category, array $item ): string {
		switch ( $category ) {
			case Industry_Pack_Bundle_Service::PAYLOAD_PACKS:
				return isset( $item[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $item[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					? trim( $item[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					: '';
			case Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES:
				return isset( $item[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ) && is_string( $item[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
					? trim( $item[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
					: '';
			default:
				if ( isset( $item['style_preset_key'] ) && is_string( $item['style_preset_key'] ) ) {
					return trim( $item['style_preset_key'] );
				}
				if ( isset( $item['pattern_key'] ) && is_string( $item['pattern_key'] ) ) {
					return trim( $item['pattern_key'] );
				}
				if ( isset( $item['industry_key'] ) && is_string( $item['industry_key'] ) ) {
					$sec = isset( $item['section_key'] ) && is_string( $item['section_key'] ) ? trim( $item['section_key'] ) : '';
					$tpl = isset( $item['page_template_key'] ) && is_string( $item['page_template_key'] ) ? trim( $item['page_template_key'] ) : '';
					if ( $sec !== '' ) {
						return trim( $item['industry_key'] ) . '|' . $sec;
					}
					if ( $tpl !== '' ) {
						return trim( $item['industry_key'] ) . '|' . $tpl;
					}
					return trim( $item['industry_key'] );
				}
				return '';
		}
	}
}
