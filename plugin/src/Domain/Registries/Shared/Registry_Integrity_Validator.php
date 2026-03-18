<?php
/**
 * Shared registry integrity validation (spec §12.13, §13, §14.7).
 * Cross-reference checks, deprecated-dependency warnings, integrity scans.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Shared;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;

/**
 * Registry-wide integrity and eligibility checks. Read-only; no mutations.
 */
final class Registry_Integrity_Validator {

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
	 * Validates page template section references. Returns validation result with errors/warnings.
	 *
	 * @param array<string, mixed> $template_definition
	 * @return Registry_Validation_Result
	 */
	public function validate_page_template_section_refs( array $template_definition ): Registry_Validation_Result {
		$errors   = array();
		$warnings = array();
		$codes    = array();

		$ordered = $template_definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		if ( ! is_array( $ordered ) ) {
			$errors[] = 'Ordered sections must be an array';
			$codes[]  = Registry_Validation_Result::CODE_REFERENCE_MISSING;
			return Registry_Validation_Result::invalid( $errors, $codes );
		}

		foreach ( $ordered as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$key = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			if ( $key === '' ) {
				continue;
			}
			$section = $this->section_registry->get_by_key( $key );
			if ( $section === null ) {
				$errors[] = "Section reference missing: {$key}";
				$codes[]  = Registry_Validation_Result::CODE_REFERENCE_MISSING;
			} elseif ( ( (string) ( $section['status'] ?? '' ) ) === 'deprecated' ) {
				$warnings[] = "Section {$key} is deprecated";
				$codes[]    = Registry_Validation_Result::CODE_REFERENCE_DEPRECATED;
				$codes[]    = Registry_Validation_Result::CODE_HAS_DEPRECATED_DEPENDENCIES;
			}
		}

		$codes = array_values( array_unique( $codes ) );
		if ( ! empty( $errors ) ) {
			return Registry_Validation_Result::invalid( $errors, $codes );
		}
		if ( ! empty( $warnings ) ) {
			return Registry_Validation_Result::valid_with_warnings( $warnings, $codes );
		}
		return Registry_Validation_Result::valid();
	}

	/**
	 * Validates composition section references.
	 *
	 * @param array<string, mixed> $composition_definition
	 * @return Registry_Validation_Result
	 */
	public function validate_composition_section_refs( array $composition_definition ): Registry_Validation_Result {
		$errors   = array();
		$warnings = array();
		$codes    = array();

		$ordered = $composition_definition[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? array();
		if ( ! is_array( $ordered ) ) {
			$errors[] = 'Ordered section list must be an array';
			$codes[]  = Registry_Validation_Result::CODE_REFERENCE_MISSING;
			return Registry_Validation_Result::invalid( $errors, $codes );
		}

		foreach ( $ordered as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$key = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
			if ( $key === '' ) {
				continue;
			}
			$section = $this->section_registry->get_by_key( $key );
			if ( $section === null ) {
				$errors[] = "Section reference missing: {$key}";
				$codes[]  = Registry_Validation_Result::CODE_REFERENCE_MISSING;
			} elseif ( ( (string) ( $section['status'] ?? '' ) ) === 'deprecated' ) {
				$warnings[] = "Section {$key} is deprecated";
				$codes[]    = Registry_Validation_Result::CODE_REFERENCE_DEPRECATED;
				$codes[]    = Registry_Validation_Result::CODE_HAS_DEPRECATED_DEPENDENCIES;
			}
		}

		$codes = array_values( array_unique( $codes ) );
		if ( ! empty( $errors ) ) {
			return Registry_Validation_Result::invalid( $errors, $codes );
		}
		if ( ! empty( $warnings ) ) {
			return Registry_Validation_Result::valid_with_warnings( $warnings, $codes );
		}
		return Registry_Validation_Result::valid();
	}

	/**
	 * Returns read-time warnings for objects depending on deprecated sections/templates.
	 *
	 * @param array<string, mixed> $definition Section, page template, or composition definition.
	 * @param string               $object_type 'section'|'page_template'|'composition'
	 * @return list<string>
	 */
	public function get_deprecation_warnings( array $definition, string $object_type = 'composition' ): array {
		if ( $object_type === 'page_template' ) {
			$result = $this->validate_page_template_section_refs( $definition );
			return $result->warnings;
		}
		if ( $object_type === 'composition' ) {
			$result = $this->validate_composition_section_refs( $definition );
			return $result->warnings;
		}
		return array();
	}

	/**
	 * Filters definitions to those eligible for new selection (excludes deprecated).
	 *
	 * @param list<array<string, mixed>> $definitions
	 * @return list<array<string, mixed>>
	 */
	public function filter_eligible_for_new_selection( array $definitions ): array {
		$out = array();
		foreach ( $definitions as $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			if ( Deprecation_Metadata::is_eligible_for_new_use( $def ) ) {
				$out[] = $def;
			}
		}
		return $out;
	}

	/**
	 * Runs registry-wide integrity scan. Returns summary for diagnostics/export validation.
	 *
	 * @return array{missing_section_refs: list<string>, deprecated_section_refs: list<string>, missing_template_refs: list<string>, deprecated_template_refs: list<string>}
	 */
	public function scan_registry_integrity(): array {
		$missing_section_refs     = array();
		$deprecated_section_refs  = array();
		$missing_template_refs    = array();
		$deprecated_template_refs = array();

		$templates        = $this->page_template_registry->list_by_status( 'active', 500, 0 );
		$templates        = array_merge( $templates, $this->page_template_registry->list_by_status( 'deprecated', 500, 0 ) );
		$all_section_keys = array();

		foreach ( $templates as $tpl ) {
			$ordered = $tpl[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			if ( ! is_array( $ordered ) ) {
				continue;
			}
			foreach ( $ordered as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$key = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
				if ( $key === '' ) {
					continue;
				}
				$all_section_keys[ $key ] = true;
			}
		}

		foreach ( array_keys( $all_section_keys ) as $key ) {
			$section = $this->section_registry->get_by_key( $key );
			if ( $section === null ) {
				$missing_section_refs[] = $key;
			} elseif ( ( (string) ( $section['status'] ?? '' ) ) === 'deprecated' ) {
				$deprecated_section_refs[] = $key;
			}
		}

		return array(
			'missing_section_refs'     => array_values( array_unique( $missing_section_refs ) ),
			'deprecated_section_refs'  => array_values( array_unique( $deprecated_section_refs ) ),
			'missing_template_refs'    => $missing_template_refs,
			'deprecated_template_refs' => $deprecated_template_refs,
		);
	}
}
