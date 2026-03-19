<?php
/**
 * Validates compositions against state machine (composition-validation-state-machine.md).
 * Runs validation checks and produces explainable codes. Server-authoritative.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Composition;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service;

/**
 * Validates composition structure and section references. Does not generate helpers or one-pagers.
 */
final class Composition_Validator {

	/** @var Section_Registry_Service */
	private Section_Registry_Service $section_registry;

	/** @var Page_Template_Registry_Service */
	private Page_Template_Registry_Service $page_template_registry;

	public function __construct(
		Section_Registry_Service $section_registry,
		Page_Template_Registry_Service $page_template_registry
	) {
		$this->section_registry       = $section_registry;
		$this->page_template_registry = $page_template_registry;
	}

	/**
	 * Runs full validation and returns structured result with codes.
	 *
	 * @param array<string, mixed> $composition Normalized composition definition.
	 * @return array{result: string, codes: list<string>} result = validation result constant, codes = validation codes emitted.
	 */
	public function validate( array $composition ): array {
		$codes = array();

		$ordered = $composition[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? array();
		if ( ! is_array( $ordered ) || empty( $ordered ) ) {
			$codes[] = Composition_Validation_Codes::EMPTY_SECTION_LIST;
			return $this->result_from_codes( $codes );
		}

		$source_ref = (string) ( $composition[ Composition_Schema::FIELD_SOURCE_TEMPLATE_REF ] ?? '' );
		if ( $source_ref !== '' ) {
			$tpl = $this->page_template_registry->get_by_key( $source_ref );
			if ( $tpl === null ) {
				$codes[] = Composition_Validation_Codes::SOURCE_TEMPLATE_UNAVAILABLE;
			} elseif ( ( (string) ( $tpl['status'] ?? '' ) ) === 'deprecated' ) {
				$codes[] = Composition_Validation_Codes::SOURCE_TEMPLATE_UNAVAILABLE;
			}
		}

		$snapshot_ref = (string) ( $composition[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ?? '' );
		if ( $snapshot_ref === '' ) {
			$codes[] = Composition_Validation_Codes::SNAPSHOT_MISSING;
		}

		$positions                  = array();
		$deprecated_no_replacement  = array();
		$deprecated_has_replacement = array();

		foreach ( $ordered as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$key = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
			if ( $key === '' ) {
				$codes[] = Composition_Validation_Codes::ORDERING_INVALID;
				continue;
			}

			$pos         = isset( $item[ Composition_Schema::SECTION_ITEM_POSITION ] ) ? (int) $item[ Composition_Schema::SECTION_ITEM_POSITION ] : $i;
			$positions[] = $pos;

			$section = $this->section_registry->get_by_key( $key );
			if ( $section === null ) {
				$codes[] = Composition_Validation_Codes::SECTION_MISSING;
				continue;
			}

			$status = (string) ( $section['status'] ?? '' );
			if ( $status === 'deprecated' ) {
				$dep         = $section['deprecation'] ?? array();
				$replacement = (string) ( $dep['replacement_section_key'] ?? '' );
				if ( $replacement === '' ) {
					$deprecated_no_replacement[] = $key;
				} else {
					$deprecated_has_replacement[] = $key;
				}
			}
		}

		if ( ! empty( $deprecated_no_replacement ) ) {
			$codes[] = Composition_Validation_Codes::SECTION_DEPRECATED_NO_REPLACEMENT;
		}
		if ( ! empty( $deprecated_has_replacement ) ) {
			$codes[] = Composition_Validation_Codes::SECTION_DEPRECATED_HAS_REPLACEMENT;
		}

		$unique_pos = array_unique( $positions );
		if ( count( $unique_pos ) !== count( $positions ) ) {
			$codes[] = Composition_Validation_Codes::ORDERING_INVALID;
		}

		$adjacency_codes = $this->check_compatibility_adjacency( $ordered );
		foreach ( $adjacency_codes as $c ) {
			$codes[] = $c;
		}

		return $this->result_from_codes( $codes );
	}

	/**
	 * Checks adjacent sections for avoid_adjacent / duplicate_purpose violations.
	 *
	 * @param list<array<string, mixed>> $ordered
	 * @return list<string> Validation codes found.
	 */
	private function check_compatibility_adjacency( array $ordered ): array {
		$found    = array();
		$prev_key = null;
		foreach ( $ordered as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$key = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
			if ( $key === '' || $prev_key === null ) {
				$prev_key = ( $key !== '' && $key !== null ) ? $key : $prev_key;
				continue;
			}
			$prev_section = $this->section_registry->get_by_key( $prev_key );
			if ( $prev_section !== null ) {
				$compat = $prev_section['compatibility'] ?? array();
				$avoid  = $compat['avoid_adjacent'] ?? array();
				if ( is_array( $avoid ) && in_array( $key, $avoid, true ) ) {
					$found[] = Composition_Validation_Codes::COMPATIBILITY_ADJACENCY;
				}
				$dup = $compat['duplicate_purpose_of'] ?? array();
				if ( is_array( $dup ) && in_array( $key, $dup, true ) ) {
					$found[] = Composition_Validation_Codes::COMPATIBILITY_DUPLICATE_PURPOSE;
				}
			}
			$prev_key = $key;
		}
		return $found;
	}

	/**
	 * @param list<string> $codes
	 * @return array{result: string, codes: list<string>}
	 */
	private function result_from_codes( array $codes ): array {
		$codes        = array_values( array_unique( $codes ) );
		$has_blocking = false;
		$has_warning  = false;
		foreach ( $codes as $code ) {
			if ( Composition_Validation_Codes::is_blocking( $code ) ) {
				$has_blocking = true;
				break;
			}
			$has_warning = true;
		}
		if ( $has_blocking ) {
			$result = in_array( Composition_Validation_Codes::SECTION_DEPRECATED_NO_REPLACEMENT, $codes, true )
				&& ! in_array( Composition_Validation_Codes::SECTION_MISSING, $codes, true )
				? Composition_Validation_Result::DEPRECATED_CONTEXT
				: Composition_Validation_Result::VALIDATION_FAILED;
		} elseif ( $has_warning ) {
			$result = Composition_Validation_Result::WARNING;
		} else {
			$result = Composition_Validation_Result::VALID;
		}
		return array(
			'result' => $result,
			'codes'  => $codes,
		);
	}
}
