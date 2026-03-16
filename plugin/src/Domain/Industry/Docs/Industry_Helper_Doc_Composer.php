<?php
/**
 * Composes final helper-doc output by combining base section helper with active industry overlay and optional subtype overlay (industry-section-helper-overlay-schema; subtype-section-helper-overlay-schema).
 * Composition order: base → industry overlay → subtype overlay. Deterministic; fallback when overlay absent or invalid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder;
use AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service;
use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry;
use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;

/**
 * Resolves section_key + industry (and optional subtype) into composed helper doc. Read-only; no mutation of base or overlay storage.
 * When cache service and key builder are provided, results are cached (industry-cache-contract).
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

	/** @var Subtype_Section_Helper_Overlay_Registry|null Optional; when set, compose() can apply subtype overlay (Prompt 425). */
	private ?Subtype_Section_Helper_Overlay_Registry $subtype_overlay_registry;

	/** @var Industry_Read_Model_Cache_Service|null */
	private ?Industry_Read_Model_Cache_Service $cache_service;

	/** @var Industry_Cache_Key_Builder|null */
	private ?Industry_Cache_Key_Builder $cache_key_builder;

	public function __construct(
		Documentation_Registry $documentation_registry,
		Industry_Section_Helper_Overlay_Registry $overlay_registry,
		?Industry_Compliance_Warning_Resolver $compliance_warning_resolver = null,
		?Subtype_Section_Helper_Overlay_Registry $subtype_overlay_registry = null,
		?Industry_Read_Model_Cache_Service $cache_service = null,
		?Industry_Cache_Key_Builder $cache_key_builder = null
	) {
		$this->documentation_registry   = $documentation_registry;
		$this->overlay_registry          = $overlay_registry;
		$this->compliance_warning_resolver = $compliance_warning_resolver;
		$this->subtype_overlay_registry  = $subtype_overlay_registry;
		$this->cache_service            = $cache_service;
		$this->cache_key_builder        = $cache_key_builder;
	}

	/**
	 * Composes helper doc for the given section, industry, and optional subtype. Order: base → industry overlay → subtype overlay. Returns base-only when overlays missing or inactive.
	 *
	 * @param string $section_key   Section template internal_key.
	 * @param string $industry_key  Industry pack key (primary industry). Empty = base-only, no overlay.
	 * @param string $subtype_key   Optional subtype key (e.g. from Industry_Subtype_Resolver). When empty or subtype registry not set, no subtype overlay is applied.
	 * @return Composed_Helper_Doc_Result
	 */
	public function compose( string $section_key, string $industry_key, string $subtype_key = '' ): Composed_Helper_Doc_Result {
		$section_key  = trim( $section_key );
		$industry_key = trim( $industry_key );
		$subtype_key  = trim( $subtype_key );
		$base_doc     = $section_key !== '' ? $this->documentation_registry->get_by_section_key( $section_key ) : null;
		$base_id      = '';
		$composed     = array();
		if ( $base_doc !== null && is_array( $base_doc ) ) {
			$composed = $base_doc;
			$base_id  = (string) ( $base_doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
		}
		$overlay_applied  = false;
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
					$overlay_applied  = true;
					$overlay_industry = $industry_key;
				}
			}
		}
		if ( $subtype_key !== '' && $section_key !== '' && $this->subtype_overlay_registry !== null ) {
			$subtype_overlay = $this->subtype_overlay_registry->get( $subtype_key, $section_key );
			if ( $subtype_overlay !== null && is_array( $subtype_overlay ) ) {
				$status = isset( $subtype_overlay[ Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS ] ) && is_string( $subtype_overlay[ Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS ] )
					? $subtype_overlay[ Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS ]
					: '';
				if ( $status === Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE ) {
					foreach ( self::OVERLAY_MERGE_FIELDS as $field ) {
						if ( array_key_exists( $field, $subtype_overlay ) ) {
							$composed[ $field ] = $subtype_overlay[ $field ];
						}
					}
					$overlay_applied = true;
				}
			}
		}
		$compliance_warnings = array();
		if ( $industry_key !== '' && $this->compliance_warning_resolver !== null ) {
			$compliance_warnings = $this->compliance_warning_resolver->get_for_display( $industry_key );
		}
		$result = new Composed_Helper_Doc_Result( $composed, $base_id, $overlay_applied, $overlay_industry, $section_key, $compliance_warnings );
		if ( $this->cache_service !== null && $this->cache_key_builder !== null ) {
			$base_key = $this->cache_key_builder->for_helper_doc( $section_key, $industry_key, $subtype_key );
			$this->cache_service->set( $base_key, array(
				'composed_doc'          => $composed,
				'base_documentation_id' => $base_id,
				'overlay_applied'       => $overlay_applied,
				'overlay_industry_key'  => $overlay_industry,
				'section_key'           => $section_key,
				'compliance_warnings'   => $compliance_warnings,
			) );
		}
		return $result;
	}
}
