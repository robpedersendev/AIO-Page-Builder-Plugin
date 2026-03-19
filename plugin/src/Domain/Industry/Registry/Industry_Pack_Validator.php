<?php
/**
 * Validates industry pack definitions (industry-pack-schema.md). Single-pack and bulk validation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Validates industry pack objects and bulk lists; detects duplicate keys.
 */
final class Industry_Pack_Validator {

	/**
	 * Validates a single pack. Returns list of errors; empty means valid.
	 *
	 * @param array<string, mixed> $pack Pack definition.
	 * @return array<int, array{code: string, field?: string}> Empty if valid.
	 */
	public function validate_pack( array $pack ): array {
		return Industry_Pack_Schema::validate_pack( $pack );
	}

	/**
	 * Validates a list of packs. Returns per-pack errors and duplicate-key report.
	 * Packs with validation errors are reported; duplicate industry_key (first wins) reported as duplicate_key.
	 *
	 * @param array<int, array<string, mixed>> $packs List of pack definitions.
	 * @return array{valid: array<int, array<string, mixed>>, invalid: array<int, array{index: int, errors: array<int, array{code: string, field?: string}>}>, duplicate_keys: array<int, string>}
	 */
	public function validate_bulk( array $packs ): array {
		$valid          = array();
		$invalid        = array();
		$seen_keys      = array();
		$duplicate_keys = array();
		foreach ( $packs as $index => $pack ) {
			if ( ! is_array( $pack ) ) {
				$invalid[] = array(
					'index'  => $index,
					'errors' => array( array( 'code' => 'invalid_payload' ) ),
				);
				continue;
			}
			$errors = $this->validate_pack( $pack );
			if ( $errors !== array() ) {
				$invalid[] = array(
					'index'  => $index,
					'errors' => $errors,
				);
				continue;
			}
			$key = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				? trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $key === '' ) {
				$invalid[] = array(
					'index'  => $index,
					'errors' => array(
						array(
							'code'  => 'missing_required',
							'field' => Industry_Pack_Schema::FIELD_INDUSTRY_KEY,
						),
					),
				);
				continue;
			}
			if ( isset( $seen_keys[ $key ] ) ) {
				$duplicate_keys[] = $key;
				continue;
			}
			$seen_keys[ $key ] = true;
			$valid[]           = $pack;
		}
		return array(
			'valid'          => $valid,
			'invalid'        => $invalid,
			'duplicate_keys' => array_values( array_unique( $duplicate_keys ) ),
		);
	}
}
