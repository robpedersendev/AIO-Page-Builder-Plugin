<?php
/**
 * Maps a validated AI page-template draft to canonical page template definition fields.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Translation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Status_Families;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;

final class Page_Template_AI_Draft_Translator {

	/**
	 * @param array<string, mixed> $draft
	 */
	public function translate( array $draft ): Canonical_Translation_Result {
		$errors = array();
		foreach ( Page_Template_Schema::get_required_fields() as $field ) {
			if ( ! array_key_exists( $field, $draft ) ) {
				$errors[] = 'missing:' . $field;
			}
		}
		if ( $errors !== array() ) {
			return Canonical_Translation_Result::failure( $errors );
		}

		$key = (string) $draft[ Page_Template_Schema::FIELD_INTERNAL_KEY ];
		if ( ! preg_match( Page_Template_Schema::INTERNAL_KEY_PATTERN, $key ) || strlen( $key ) > Page_Template_Schema::INTERNAL_KEY_MAX_LENGTH ) {
			return Canonical_Translation_Result::failure( array( 'invalid_internal_key' ) );
		}

		$arch = (string) $draft[ Page_Template_Schema::FIELD_ARCHETYPE ];
		if ( ! Page_Template_Schema::is_allowed_archetype( $arch ) ) {
			return Canonical_Translation_Result::failure( array( 'invalid_archetype' ) );
		}

		$status = (string) $draft[ Page_Template_Schema::FIELD_STATUS ];
		$fam    = Object_Status_Families::get_statuses_for( Object_Type_Keys::PAGE_TEMPLATE );
		if ( ! in_array( $status, $fam, true ) ) {
			return Canonical_Translation_Result::failure( array( 'invalid_status' ) );
		}

		foreach (
			array(
				Page_Template_Schema::FIELD_ORDERED_SECTIONS,
				Page_Template_Schema::FIELD_SECTION_REQUIREMENTS,
				Page_Template_Schema::FIELD_COMPATIBILITY,
				Page_Template_Schema::FIELD_ONE_PAGER,
				Page_Template_Schema::FIELD_VERSION,
				Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS,
			) as $struct
		) {
			if ( ! isset( $draft[ $struct ] ) || ! is_array( $draft[ $struct ] ) ) {
				return Canonical_Translation_Result::failure( array( 'invalid_structure:' . $struct ) );
			}
		}

		$ordered = $draft[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ];
		$norm    = array();
		foreach ( $ordered as $i => $item ) {
			if ( ! is_array( $item ) ) {
				return Canonical_Translation_Result::failure( array( 'ordered_section_not_object' ) );
			}
			foreach ( Page_Template_Schema::get_ordered_section_item_keys() as $req ) {
				if ( ! array_key_exists( $req, $item ) ) {
					return Canonical_Translation_Result::failure( array( 'ordered_section_missing_' . $req . '_at_' . (string) $i ) );
				}
			}
			$norm[] = array(
				Page_Template_Schema::SECTION_ITEM_KEY => (string) $item[ Page_Template_Schema::SECTION_ITEM_KEY ],
				Page_Template_Schema::SECTION_ITEM_POSITION => (int) $item[ Page_Template_Schema::SECTION_ITEM_POSITION ],
				Page_Template_Schema::SECTION_ITEM_REQUIRED => (bool) $item[ Page_Template_Schema::SECTION_ITEM_REQUIRED ],
			);
		}

		$def = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => $key,
			Page_Template_Schema::FIELD_NAME             => (string) $draft[ Page_Template_Schema::FIELD_NAME ],
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => (string) $draft[ Page_Template_Schema::FIELD_PURPOSE_SUMMARY ],
			Page_Template_Schema::FIELD_ARCHETYPE        => $arch,
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => $norm,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $draft[ Page_Template_Schema::FIELD_SECTION_REQUIREMENTS ],
			Page_Template_Schema::FIELD_COMPATIBILITY    => $draft[ Page_Template_Schema::FIELD_COMPATIBILITY ],
			Page_Template_Schema::FIELD_ONE_PAGER        => $draft[ Page_Template_Schema::FIELD_ONE_PAGER ],
			Page_Template_Schema::FIELD_VERSION          => $draft[ Page_Template_Schema::FIELD_VERSION ],
			Page_Template_Schema::FIELD_STATUS           => $status,
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => $draft[ Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS ],
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => (string) $draft[ Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES ],
		);

		foreach ( Page_Template_Schema::get_optional_fields() as $opt ) {
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
