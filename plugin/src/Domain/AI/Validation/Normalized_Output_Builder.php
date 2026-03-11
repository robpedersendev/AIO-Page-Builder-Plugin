<?php
/**
 * Builds plugin-owned normalized output from validated AI payload (spec §28.14, §29.4, ai-output-validation-contract.md).
 * No Build Plan creation; output is the internal structure consumed later by Build Plan generation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Validation;

defined( 'ABSPATH' ) || exit;

/**
 * Consumes validated payload (full or partial-accepted) and produces normalized array. No secrets in output.
 */
final class Normalized_Output_Builder {

	/**
	 * Builds normalized output from a validated payload (passed or partial). Only known top-level keys are copied.
	 *
	 * @param array<string, mixed> $validated_payload Parsed, top-level valid, with invalid item-level records already removed.
	 * @param string               $schema_ref        Schema reference (e.g. aio/build-plan-draft-v1).
	 * @return array<string, mixed> Plugin-owned structure for later Build Plan consumption.
	 */
	public function build( array $validated_payload, string $schema_ref ): array {
		if ( $schema_ref !== Build_Plan_Draft_Schema::SCHEMA_REF ) {
			return array();
		}
		$allowed = Build_Plan_Draft_Schema::REQUIRED_TOP_LEVEL_KEYS;
		$out     = array();
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $validated_payload ) ) {
				continue;
			}
			$v = $validated_payload[ $key ];
			if ( in_array( $key, Build_Plan_Draft_Schema::ARRAY_SECTIONS, true ) ) {
				$out[ $key ] = is_array( $v ) ? array_values( $v ) : array();
			} else {
				$out[ $key ] = is_array( $v ) ? $v : ( is_string( $v ) ? $v : ( is_scalar( $v ) ? $v : array() ) );
			}
		}
		return $out;
	}
}
