<?php
/**
 * Maps a validated AI composition draft array to canonical composition definition fields.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Translation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Statuses;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validation_Result;

final class Composition_AI_Draft_Translator {

	/**
	 * @param array<string, mixed> $draft Validated-ish draft; still untrusted until this translator accepts it.
	 */
	public function translate( array $draft ): Canonical_Translation_Result {
		$errors = array();
		foreach ( Composition_Schema::get_required_fields() as $field ) {
			if ( ! isset( $draft[ $field ] ) || ( is_string( $draft[ $field ] ) && $draft[ $field ] === '' ) ) {
				$errors[] = 'missing:' . $field;
			}
		}
		if ( $errors !== array() ) {
			return Canonical_Translation_Result::failure( $errors );
		}

		$comp_id = (string) $draft[ Composition_Schema::FIELD_COMPOSITION_ID ];
		if ( ! preg_match( Composition_Schema::COMPOSITION_ID_PATTERN, $comp_id ) || strlen( $comp_id ) > Composition_Schema::COMPOSITION_ID_MAX_LENGTH ) {
			return Canonical_Translation_Result::failure( array( 'invalid_composition_id' ) );
		}

		$status = (string) $draft[ Composition_Schema::FIELD_STATUS ];
		if ( ! Composition_Statuses::is_valid_lifecycle_status( $status ) ) {
			return Canonical_Translation_Result::failure( array( 'invalid_status' ) );
		}

		$val_status = (string) $draft[ Composition_Schema::FIELD_VALIDATION_STATUS ];
		if ( ! Composition_Validation_Result::is_valid( $val_status ) ) {
			return Canonical_Translation_Result::failure( array( 'invalid_validation_status' ) );
		}

		$list = $draft[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ];
		if ( ! is_array( $list ) || $list === array() ) {
			return Canonical_Translation_Result::failure( array( 'invalid_ordered_section_list' ) );
		}
		$normalized_list = array();
		foreach ( $list as $idx => $item ) {
			if ( ! is_array( $item ) ) {
				return Canonical_Translation_Result::failure( array( 'ordered_list_item_not_object' ) );
			}
			$sk = isset( $item[ Composition_Schema::SECTION_ITEM_KEY ] ) ? (string) $item[ Composition_Schema::SECTION_ITEM_KEY ] : '';
			if ( $sk === '' ) {
				return Canonical_Translation_Result::failure( array( 'missing_section_key_at_' . (string) $idx ) );
			}
			if ( ! isset( $item[ Composition_Schema::SECTION_ITEM_POSITION ] ) || ! is_numeric( $item[ Composition_Schema::SECTION_ITEM_POSITION ] ) ) {
				return Canonical_Translation_Result::failure( array( 'invalid_position_at_' . (string) $idx ) );
			}
			$row = array(
				Composition_Schema::SECTION_ITEM_KEY      => $sk,
				Composition_Schema::SECTION_ITEM_POSITION => (int) $item[ Composition_Schema::SECTION_ITEM_POSITION ],
			);
			if ( isset( $item[ Composition_Schema::SECTION_ITEM_VARIANT ] ) ) {
				$row[ Composition_Schema::SECTION_ITEM_VARIANT ] = (string) $item[ Composition_Schema::SECTION_ITEM_VARIANT ];
			}
			$normalized_list[] = $row;
		}

		$def = array(
			Composition_Schema::FIELD_COMPOSITION_ID       => $comp_id,
			Composition_Schema::FIELD_NAME                 => (string) $draft[ Composition_Schema::FIELD_NAME ],
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => $normalized_list,
			Composition_Schema::FIELD_STATUS               => $status,
			Composition_Schema::FIELD_VALIDATION_STATUS    => $val_status,
		);

		$optional = array(
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF,
			Composition_Schema::FIELD_DUPLICATED_FROM_COMPOSITION_ID,
			Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF,
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF,
			Composition_Schema::FIELD_VALIDATION_CODES,
		);
		foreach ( $optional as $ok ) {
			if ( array_key_exists( $ok, $draft ) ) {
				$def[ $ok ] = $draft[ $ok ];
			}
		}

		$snap = null;
		if ( isset( $draft['approved_snapshot_ref'] ) && is_array( $draft['approved_snapshot_ref'] ) ) {
			$snap = $draft['approved_snapshot_ref'];
		} elseif ( isset( $draft[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ) ) {
			$snap = $draft[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ];
		}
		if ( is_array( $snap ) ) {
			$existing = isset( $def[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ) && is_array( $def[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] )
				? $def[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ]
				: array();
			$def[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] = array_merge( $existing, $snap );
		}
		if ( isset( $draft['ai_run_post_id'] ) ) {
			$reg = $def[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ?? array();
			if ( ! is_array( $reg ) ) {
				$reg = array();
			}
			$reg['ai_run_post_id']                                  = (int) $draft['ai_run_post_id'];
			$def[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] = $reg;
		}

		return Canonical_Translation_Result::success( $def );
	}
}
