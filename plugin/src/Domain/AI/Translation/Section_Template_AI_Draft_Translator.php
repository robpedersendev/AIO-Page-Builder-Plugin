<?php
/**
 * Maps a validated AI section-template draft to canonical section definition fields.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Translation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Status_Families;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

final class Section_Template_AI_Draft_Translator {

	/**
	 * @param array<string, mixed> $draft
	 */
	public function translate( array $draft ): Canonical_Translation_Result {
		$errors = array();
		foreach ( Section_Schema::get_required_fields() as $field ) {
			if ( ! array_key_exists( $field, $draft ) ) {
				$errors[] = 'missing:' . $field;
			}
		}
		if ( $errors !== array() ) {
			return Canonical_Translation_Result::failure( $errors );
		}

		$key = (string) $draft[ Section_Schema::FIELD_INTERNAL_KEY ];
		if ( ! preg_match( Section_Schema::INTERNAL_KEY_PATTERN, $key ) || strlen( $key ) > Section_Schema::INTERNAL_KEY_MAX_LENGTH ) {
			return Canonical_Translation_Result::failure( array( 'invalid_internal_key' ) );
		}

		$cat = (string) $draft[ Section_Schema::FIELD_CATEGORY ];
		if ( ! Section_Schema::is_allowed_category( $cat ) ) {
			return Canonical_Translation_Result::failure( array( 'invalid_category' ) );
		}

		$render = (string) $draft[ Section_Schema::FIELD_RENDER_MODE ];
		if ( ! Section_Schema::is_allowed_render_mode( $render ) ) {
			return Canonical_Translation_Result::failure( array( 'invalid_render_mode' ) );
		}

		$status = (string) $draft[ Section_Schema::FIELD_STATUS ];
		$fam    = Object_Status_Families::get_statuses_for( Object_Type_Keys::SECTION_TEMPLATE );
		if ( ! in_array( $status, $fam, true ) ) {
			return Canonical_Translation_Result::failure( array( 'invalid_status' ) );
		}

		foreach (
			array(
				Section_Schema::FIELD_VARIANTS,
				Section_Schema::FIELD_COMPATIBILITY,
				Section_Schema::FIELD_VERSION,
				Section_Schema::FIELD_ASSET_DECLARATION,
			) as $struct
		) {
			if ( ! isset( $draft[ $struct ] ) || ! is_array( $draft[ $struct ] ) ) {
				return Canonical_Translation_Result::failure( array( 'invalid_structure:' . $struct ) );
			}
		}

		$variants = $draft[ Section_Schema::FIELD_VARIANTS ];
		$default  = (string) $draft[ Section_Schema::FIELD_DEFAULT_VARIANT ];
		if ( $default === '' || ! array_key_exists( $default, $variants ) ) {
			return Canonical_Translation_Result::failure( array( 'default_variant_mismatch' ) );
		}

		$def = array(
			Section_Schema::FIELD_INTERNAL_KEY             => $key,
			Section_Schema::FIELD_NAME                     => (string) $draft[ Section_Schema::FIELD_NAME ],
			Section_Schema::FIELD_PURPOSE_SUMMARY          => (string) $draft[ Section_Schema::FIELD_PURPOSE_SUMMARY ],
			Section_Schema::FIELD_CATEGORY                 => $cat,
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => (string) $draft[ Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF ],
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => (string) $draft[ Section_Schema::FIELD_FIELD_BLUEPRINT_REF ],
			Section_Schema::FIELD_HELPER_REF               => (string) $draft[ Section_Schema::FIELD_HELPER_REF ],
			Section_Schema::FIELD_CSS_CONTRACT_REF         => (string) $draft[ Section_Schema::FIELD_CSS_CONTRACT_REF ],
			Section_Schema::FIELD_DEFAULT_VARIANT          => $default,
			Section_Schema::FIELD_VARIANTS                 => $variants,
			Section_Schema::FIELD_COMPATIBILITY            => $draft[ Section_Schema::FIELD_COMPATIBILITY ],
			Section_Schema::FIELD_VERSION                  => $draft[ Section_Schema::FIELD_VERSION ],
			Section_Schema::FIELD_STATUS                   => $status,
			Section_Schema::FIELD_RENDER_MODE              => $render,
			Section_Schema::FIELD_ASSET_DECLARATION        => $draft[ Section_Schema::FIELD_ASSET_DECLARATION ],
		);

		foreach ( Section_Schema::get_optional_fields() as $opt ) {
			if ( array_key_exists( $opt, $draft ) ) {
				$def[ $opt ] = $draft[ $opt ];
			}
		}

		if ( isset( $draft['approved_snapshot_ref'] ) && is_array( $draft['approved_snapshot_ref'] ) ) {
			$def['provenance_approved_snapshot_ref'] = $draft['approved_snapshot_ref'];
		}
		if ( isset( $draft['ai_run_post_id'] ) ) {
			$def['provenance_ai_run_post_id'] = (int) $draft['ai_run_post_id'];
		}

		return Canonical_Translation_Result::success( $def );
	}
}
