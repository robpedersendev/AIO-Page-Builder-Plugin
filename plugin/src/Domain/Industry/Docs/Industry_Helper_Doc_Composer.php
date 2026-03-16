<?php
/**
 * Composes final helper-doc output by combining base section helper with active industry section-helper overlay (industry-section-helper-overlay-schema).
 * Deterministic; fallback to base-only when overlay absent or invalid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry;
use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;

/**
 * Resolves section_key + industry context into composed helper doc. Read-only; no mutation of base or overlay storage.
 */
final class Industry_Helper_Doc_Composer {

	/** Overlay fields that may be merged onto base (allowed regions per schema). */
	private const OVERLAY_MERGE_FIELDS = array(
		'tone_notes',
		'cta_usage_notes',
		'compliance_cautions',
		'media_notes',
		'seo_notes',
		'additive_blocks',
	);

	/** @var Documentation_Registry */
	private Documentation_Registry $documentation_registry;

	/** @var Industry_Section_Helper_Overlay_Registry */
	private Industry_Section_Helper_Overlay_Registry $overlay_registry;

	/** @var Industry_Compliance_Warning_Resolver|null Optional; when set, composed result includes compliance warnings (Prompt 407). */
	private ?Industry_Compliance_Warning_Resolver $compliance_warning_resolver;

	public function __construct( Documentation_Registry $documentation_registry, Industry_Section_Helper_Overlay_Registry $overlay_registry, ?Industry_Compliance_Warning_Resolver $compliance_warning_resolver = null ) {
		$this->documentation_registry      = $documentation_registry;
		$this->overlay_registry            = $overlay_registry;
		$this->compliance_warning_resolver = $compliance_warning_resolver;
	}

	/**
	 * Composes helper doc for the given section and industry. Returns base-only when overlay missing or inactive; overlay applied only when active.
	 *
	 * @param string $section_key  Section template internal_key.
	 * @param string $industry_key Industry pack key (primary industry). Empty = base-only, no overlay.
	 * @return Composed_Helper_Doc_Result
	 */
	public function compose( string $section_key, string $industry_key ): Composed_Helper_Doc_Result {
		$section_key  = trim( $section_key );
		$industry_key = trim( $industry_key );
		$base_doc     = $section_key !== '' ? $this->documentation_registry->get_by_section_key( $section_key ) : null;
		$base_id      = '';
		$composed     = array();
		if ( $base_doc !== null && is_array( $base_doc ) ) {
			$composed = $base_doc;
			$base_id  = (string) ( $base_doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
		}
		$overlay_applied = false;
		$overlay_industry = '';
		if ( $industry_key !== '' && $section_key !== '' ) {
			$overlay = $this->overlay_registry->get( $industry_key, $section_key );
			if ( $overlay !== null && is_array( $overlay ) ) {
				$status = isset( $overlay[ Industry_Section_Helper_Overlay_Registry::FIELD_STATUS ] ) && is_string( $overlay[ Industry_Section_Helper_Overlay_Registry::FIELD_STATUS ] )
					? $overlay[ Industry_Section_Helper_Overlay_Registry::FIELD_STATUS ]
					: '';
				if ( $status === Industry_Section_Helper_Overlay_Registry::STATUS_ACTIVE ) {
					foreach ( self::OVERLAY_MERGE_FIELDS as $field ) {
						if ( array_key_exists( $field, $overlay ) ) {
							$composed[ $field ] = $overlay[ $field ];
						}
					}
					$overlay_applied = true;
					$overlay_industry = $industry_key;
				}
			}
		}
		$compliance_warnings = array();
		if ( $industry_key !== '' && $this->compliance_warning_resolver !== null ) {
			$compliance_warnings = $this->compliance_warning_resolver->get_for_display( $industry_key );
		}
		return new Composed_Helper_Doc_Result( $composed, $base_id, $overlay_applied, $overlay_industry, $section_key, $compliance_warnings );
	}
}
