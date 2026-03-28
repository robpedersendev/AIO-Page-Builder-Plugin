<?php
/**
 * Deep semantic checks for section-template definitions before canonical save (variants, render mode, contract refs).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section;

defined( 'ABSPATH' ) || exit;

final class Section_Template_Canonical_Semantic_Validator {

	/**
	 * @param array<string, mixed> $definition
	 * @return list<string>
	 */
	public function validate( array $definition ): array {
		$errs = array();
		foreach ( Section_Schema::get_required_fields() as $f ) {
			if ( ! array_key_exists( $f, $definition ) ) {
				$errs[] = 'missing:' . $f;
			}
		}
		if ( $errs !== array() ) {
			return $errs;
		}
		$cat = (string) ( $definition[ Section_Schema::FIELD_CATEGORY ] ?? '' );
		if ( ! Section_Schema::is_allowed_category( $cat ) ) {
			$errs[] = 'invalid_category';
		}
		$rm = (string) ( $definition[ Section_Schema::FIELD_RENDER_MODE ] ?? '' );
		if ( ! Section_Schema::is_allowed_render_mode( $rm ) ) {
			$errs[] = 'invalid_render_mode';
		}
		$vars = $definition[ Section_Schema::FIELD_VARIANTS ] ?? null;
		if ( ! is_array( $vars ) || $vars === array() ) {
			$errs[] = 'variants_empty';
		}
		$defv = (string) ( $definition[ Section_Schema::FIELD_DEFAULT_VARIANT ] ?? '' );
		if ( $defv === '' ) {
			$errs[] = 'default_variant_empty';
		} elseif ( is_array( $vars ) && ! array_key_exists( $defv, $vars ) ) {
			$errs[] = 'default_variant_not_in_variants';
		}
		foreach (
			array(
				Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF,
				Section_Schema::FIELD_FIELD_BLUEPRINT_REF,
				Section_Schema::FIELD_HELPER_REF,
				Section_Schema::FIELD_CSS_CONTRACT_REF,
			) as $ref_field
		) {
			$rv = $definition[ $ref_field ] ?? '';
			if ( ! is_string( $rv ) || trim( $rv ) === '' ) {
				$errs[] = 'empty_ref:' . $ref_field;
			}
		}
		$comp = $definition[ Section_Schema::FIELD_COMPATIBILITY ] ?? null;
		if ( ! is_array( $comp ) || $comp === array() ) {
			$errs[] = 'compatibility_invalid';
		}
		$assets = $definition[ Section_Schema::FIELD_ASSET_DECLARATION ] ?? null;
		if ( ! is_array( $assets ) ) {
			$errs[] = 'asset_declaration_invalid';
		}
		return $errs;
	}
}
