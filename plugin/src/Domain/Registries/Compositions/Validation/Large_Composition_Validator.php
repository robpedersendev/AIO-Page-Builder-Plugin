<?php
/**
 * Large-library composition validator (Prompt 178): CTA rules, compatibility, preview/one-pager.
 * Enforces cta-sequencing-and-placement-contract, section compatibility, preview readiness. Server-authoritative.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Compositions\Validation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validation_Codes;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validator;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;

/**
 * Full enforcement: CTA count by class, bottom CTA, no adjacent CTA, compatibility, preview/one-pager.
 * Delegates base checks to Composition_Validator; adds CTA-law and readiness checks.
 */
final class Large_Composition_Validator {

	/** CTA-classified values (cta-sequencing-and-placement-contract §2.1). */
	private const CTA_CLASSIFIED = array( 'primary_cta', 'contact_cta', 'navigation_cta' );

	/** Min CTA sections by template_category_class (contract §3). */
	private const CTA_MIN_BY_CLASS = array(
		'top_level'    => 3,
		'hub'          => 4,
		'nested_hub'   => 4,
		'child_detail' => 5,
	);

	private const NON_CTA_MIN = 8;
	private const NON_CTA_MAX = 14;

	private Composition_Validator $legacy_validator;
	private Section_Registry_Service $section_registry;
	private Page_Template_Registry_Service $page_template_registry;

	public function __construct(
		Composition_Validator $legacy_validator,
		Section_Registry_Service $section_registry,
		Page_Template_Registry_Service $page_template_registry
	) {
		$this->legacy_validator       = $legacy_validator;
		$this->section_registry       = $section_registry;
		$this->page_template_registry = $page_template_registry;
	}

	/**
	 * Runs full validation and returns structured result with blockers and warnings.
	 *
	 * @param array<string, mixed> $composition Normalized composition definition.
	 * @return Composition_Validation_Result
	 */
	public function validate( array $composition ): Composition_Validation_Result {
		$blockers                  = array();
		$warnings                  = array();
		$cta_rule_violations       = array();
		$compatibility_violations  = array();
		$preview_readiness_warnings = array();
		$legacy_codes              = array();

		$legacy = $this->legacy_validator->validate( $composition );
		$legacy_codes = $legacy['codes'];
		foreach ( $legacy_codes as $code ) {
			if ( Composition_Validation_Codes::is_blocking( $code ) ) {
				$blockers[] = array( 'code' => $code, 'message' => $this->message_for_legacy_code( $code ) );
			} else {
				$warnings[] = array( 'code' => $code, 'message' => $this->message_for_legacy_code( $code ) );
			}
		}
		if ( in_array( Composition_Validation_Codes::COMPATIBILITY_ADJACENCY, $legacy_codes, true ) ) {
			$compatibility_violations[] = array( 'code' => 'compatibility_adjacency', 'message' => __( 'Two sections are adjacent in violation of compatibility rules.', 'aio-page-builder' ) );
		}
		if ( in_array( Composition_Validation_Codes::COMPATIBILITY_DUPLICATE_PURPOSE, $legacy_codes, true ) ) {
			$compatibility_violations[] = array( 'code' => 'compatibility_duplicate_purpose', 'message' => __( 'Multiple sections with same purpose stacked; consider spacing.', 'aio-page-builder' ) );
		}

		$ordered = $composition[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? array();
		if ( ! is_array( $ordered ) || empty( $ordered ) ) {
			return new Composition_Validation_Result(
				false,
				$blockers,
				$warnings,
				$cta_rule_violations,
				$compatibility_violations,
				$preview_readiness_warnings,
				$legacy_codes
			);
		}

		usort( $ordered, function ( $a, $b ) {
			$pa = isset( $a[ Composition_Schema::SECTION_ITEM_POSITION ] ) ? (int) $a[ Composition_Schema::SECTION_ITEM_POSITION ] : 0;
			$pb = isset( $b[ Composition_Schema::SECTION_ITEM_POSITION ] ) ? (int) $b[ Composition_Schema::SECTION_ITEM_POSITION ] : 0;
			return $pa <=> $pb;
		} );

		$cta_flags = array();
		$section_defs = array();
		foreach ( $ordered as $item ) {
			$key = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
			$section = $this->section_registry->get_by_key( $key );
			if ( $section !== null ) {
				$section_defs[ $key ] = $section;
				$cta_flags[] = $this->is_cta_classified( $section );
			} else {
				$cta_flags[] = false;
			}
		}

		// * Mandatory bottom CTA (contract §5).
		if ( count( $cta_flags ) > 0 && ! end( $cta_flags ) ) {
			$cta_rule_violations[] = array(
				'code'    => 'bottom_cta_missing',
				'message' => __( 'Last section must be CTA-classified.', 'aio-page-builder' ),
				'position' => count( $cta_flags ) - 1,
			);
			$blockers[] = array( 'code' => 'bottom_cta_missing', 'message' => __( 'Last section must be CTA-classified.', 'aio-page-builder' ) );
		}

		// * No adjacent CTA sections (contract §6).
		for ( $i = 0; $i < count( $cta_flags ) - 1; $i++ ) {
			if ( $cta_flags[ $i ] && $cta_flags[ $i + 1 ] ) {
				$cta_rule_violations[] = array(
					'code'     => 'adjacent_cta_violation',
					'message'  => __( 'Two CTA sections are adjacent; add a non-CTA section between them.', 'aio-page-builder' ),
					'position' => $i,
				);
				$blockers[] = array( 'code' => 'adjacent_cta_violation', 'message' => __( 'Two CTA sections are adjacent.', 'aio-page-builder' ) );
			}
		}

		// * CTA count by class when class is present (contract §3).
		$template_class = $this->resolve_template_category_class( $composition );
		if ( $template_class !== '' && isset( self::CTA_MIN_BY_CLASS[ $template_class ] ) ) {
			$min_cta = self::CTA_MIN_BY_CLASS[ $template_class ];
			$cta_count = (int) array_sum( array_map( 'intval', $cta_flags ) );
			if ( $cta_count < $min_cta ) {
				$cta_rule_violations[] = array(
					'code'    => 'cta_count_below_minimum',
					'message' => sprintf( __( 'Composition has %d CTA section(s); minimum for class %s is %d.', 'aio-page-builder' ), $cta_count, $template_class, $min_cta ),
				);
				$blockers[] = array( 'code' => 'cta_count_below_minimum', 'message' => sprintf( __( 'Minimum %d CTA sections required for this class.', 'aio-page-builder' ), $min_cta ) );
			}
			$non_cta_count = count( $cta_flags ) - $cta_count;
			if ( $non_cta_count < self::NON_CTA_MIN ) {
				$cta_rule_violations[] = array(
					'code'    => 'non_cta_count_below_minimum',
					'message' => sprintf( __( 'Composition has %d non-CTA section(s); minimum is %d.', 'aio-page-builder' ), $non_cta_count, self::NON_CTA_MIN ),
				);
				$blockers[] = array( 'code' => 'non_cta_count_below_minimum', 'message' => __( 'At least 8 non-CTA sections required.', 'aio-page-builder' ) );
			}
			if ( $non_cta_count > self::NON_CTA_MAX ) {
				$warnings[] = array( 'code' => 'non_cta_count_above_max', 'message' => sprintf( __( 'Composition has %d non-CTA sections (max recommended %d).', 'aio-page-builder' ), $non_cta_count, self::NON_CTA_MAX ) );
			}
		}

		// * Preview readiness: sections without preview data.
		foreach ( $ordered as $i => $item ) {
			$key = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
			$def = $section_defs[ $key ] ?? null;
			if ( $def !== null ) {
				$has_preview = ( ! empty( $def['preview_defaults'] ) && is_array( $def['preview_defaults'] ) )
					|| ( (string) ( $def['preview_image_ref'] ?? '' ) ) !== ''
					|| ( (string) ( $def['preview_description'] ?? '' ) ) !== '';
				if ( ! $has_preview ) {
					$preview_readiness_warnings[] = array( 'code' => 'section_missing_preview', 'message' => sprintf( __( 'Section %s has no preview data.', 'aio-page-builder' ), $key ) );
				}
			}
		}

		// * One-pager completeness.
		$one_pager_ref = (string) ( $composition[ Composition_Schema::FIELD_HELPER_ONE_PAGER_REF ] ?? '' );
		if ( $one_pager_ref === '' ) {
			$preview_readiness_warnings[] = array( 'code' => 'one_pager_missing', 'message' => __( 'Composition has no one-pager reference.', 'aio-page-builder' ) );
		}

		$valid = empty( $blockers );
		return new Composition_Validation_Result(
			$valid,
			$blockers,
			$warnings,
			$cta_rule_violations,
			$compatibility_violations,
			$preview_readiness_warnings,
			$legacy_codes
		);
	}

	private function is_cta_classified( array $section ): bool {
		$v = (string) ( $section['cta_classification'] ?? '' );
		return in_array( $v, self::CTA_CLASSIFIED, true );
	}

	private function resolve_template_category_class( array $composition ): string {
		$class = (string) ( $composition['template_category_class'] ?? '' );
		if ( $class !== '' ) {
			return $class;
		}
		$source_ref = (string) ( $composition[ Composition_Schema::FIELD_SOURCE_TEMPLATE_REF ] ?? '' );
		if ( $source_ref === '' ) {
			return '';
		}
		$tpl = $this->page_template_registry->get_by_key( $source_ref );
		if ( $tpl === null ) {
			return '';
		}
		return (string) ( $tpl['template_category_class'] ?? '' );
	}

	private function message_for_legacy_code( string $code ): string {
		$messages = array(
			Composition_Validation_Codes::SECTION_MISSING                   => __( 'One or more section references are missing from the registry.', 'aio-page-builder' ),
			Composition_Validation_Codes::SECTION_DEPRECATED_NO_REPLACEMENT => __( 'A section is deprecated with no replacement.', 'aio-page-builder' ),
			Composition_Validation_Codes::SECTION_DEPRECATED_HAS_REPLACEMENT => __( 'A section is deprecated; a replacement is available.', 'aio-page-builder' ),
			Composition_Validation_Codes::ORDERING_INVALID                 => __( 'Section order is invalid.', 'aio-page-builder' ),
			Composition_Validation_Codes::COMPATIBILITY_ADJACENCY          => __( 'Adjacent sections violate compatibility rules.', 'aio-page-builder' ),
			Composition_Validation_Codes::COMPATIBILITY_DUPLICATE_PURPOSE   => __( 'Duplicate purpose sections are adjacent.', 'aio-page-builder' ),
			Composition_Validation_Codes::EMPTY_SECTION_LIST               => __( 'Composition has no sections.', 'aio-page-builder' ),
			Composition_Validation_Codes::SNAPSHOT_MISSING                => __( 'No registry snapshot reference.', 'aio-page-builder' ),
			Composition_Validation_Codes::SOURCE_TEMPLATE_UNAVAILABLE     => __( 'Source page template is missing or deprecated.', 'aio-page-builder' ),
		);
		return $messages[ $code ] ?? $code;
	}
}
