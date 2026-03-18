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
use AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Resolver;
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

	/** @var Subtype_Goal_Section_Helper_Overlay_Registry|null Optional; when set, compose() can apply combined subtype+goal overlay after subtype (Prompt 554). */
	private ?Subtype_Goal_Section_Helper_Overlay_Registry $subtype_goal_overlay_registry;

	/** @var Industry_Read_Model_Cache_Service|null */
	private ?Industry_Read_Model_Cache_Service $cache_service;

	/** @var Industry_Cache_Key_Builder|null */
	private ?Industry_Cache_Key_Builder $cache_key_builder;

	/** @var Industry_Shared_Fragment_Resolver|null Optional; when set, overlay fragment refs are resolved (Prompt 477). */
	private ?Industry_Shared_Fragment_Resolver $fragment_resolver;

	/** Consumer scope for fragment resolution (section_helper_overlay). */
	private const FRAGMENT_CONSUMER_SCOPE = 'section_helper_overlay';

	/** Optional overlay keys: fragment_key; when set, resolved content is appended to the corresponding merge field. */
	private const FRAGMENT_REF_FIELDS = array(
		'cta_usage_fragment_ref'           => 'cta_usage_notes',
		'seo_notes_fragment_ref'           => 'seo_notes',
		'compliance_cautions_fragment_ref' => 'compliance_cautions',
	);

	public function __construct(
		Documentation_Registry $documentation_registry,
		Industry_Section_Helper_Overlay_Registry $overlay_registry,
		?Industry_Compliance_Warning_Resolver $compliance_warning_resolver = null,
		?Subtype_Section_Helper_Overlay_Registry $subtype_overlay_registry = null,
		?Subtype_Goal_Section_Helper_Overlay_Registry $subtype_goal_overlay_registry = null,
		?Industry_Read_Model_Cache_Service $cache_service = null,
		?Industry_Cache_Key_Builder $cache_key_builder = null,
		?Industry_Shared_Fragment_Resolver $fragment_resolver = null
	) {
		$this->documentation_registry        = $documentation_registry;
		$this->overlay_registry              = $overlay_registry;
		$this->compliance_warning_resolver   = $compliance_warning_resolver;
		$this->subtype_overlay_registry      = $subtype_overlay_registry;
		$this->subtype_goal_overlay_registry = $subtype_goal_overlay_registry;
		$this->cache_service                 = $cache_service;
		$this->cache_key_builder             = $cache_key_builder;
		$this->fragment_resolver             = $fragment_resolver;
	}

	/**
	 * Composes helper doc for the given section, industry, and optional subtype. Order: base → industry overlay → subtype overlay. Returns base-only when overlays missing or inactive.
	 *
	 * @param string $section_key   Section template internal_key.
	 * @param string $industry_key  Industry pack key (primary industry). Empty = base-only, no overlay.
	 * @param string $subtype_key        Optional subtype key (e.g. from Industry_Subtype_Resolver). When empty or subtype registry not set, no subtype overlay is applied.
	 * @param string $conversion_goal_key Optional conversion goal key (Prompt 554). When set with subtype, combined subtype+goal overlay may be applied.
	 * @return Composed_Helper_Doc_Result
	 */
	public function compose( string $section_key, string $industry_key, string $subtype_key = '', string $conversion_goal_key = '' ): Composed_Helper_Doc_Result {
		$section_key         = trim( $section_key );
		$industry_key        = trim( $industry_key );
		$subtype_key         = trim( $subtype_key );
		$conversion_goal_key = trim( $conversion_goal_key );
		if ( $this->cache_service !== null && $this->cache_key_builder !== null ) {
			$base_key = $this->cache_key_builder->for_helper_doc( $section_key, $industry_key, $subtype_key, $conversion_goal_key );
			$cached   = $this->cache_service->get( $base_key );
			if ( is_array( $cached ) && isset( $cached['composed_doc'] ) && is_array( $cached['composed_doc'] ) ) {
				return new Composed_Helper_Doc_Result(
					$cached['composed_doc'],
					(string) ( $cached['base_documentation_id'] ?? '' ),
					(bool) ( $cached['overlay_applied'] ?? false ),
					(string) ( $cached['overlay_industry_key'] ?? '' ),
					(string) ( $cached['section_key'] ?? $section_key ),
					is_array( $cached['compliance_warnings'] ?? null ) ? $cached['compliance_warnings'] : array()
				);
			}
		}
		$base_doc = $section_key !== '' ? $this->documentation_registry->get_by_section_key( $section_key ) : null;
		$base_id  = '';
		$composed = array();
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
					$this->merge_fragment_refs_into_composed( $overlay, $composed );
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
					$this->merge_fragment_refs_into_composed( $subtype_overlay, $composed );
					$overlay_applied = true;
				}
			}
		}
		if ( $subtype_key !== '' && $conversion_goal_key !== '' && $section_key !== '' && $this->subtype_goal_overlay_registry !== null ) {
			$combined = $this->subtype_goal_overlay_registry->get( $subtype_key, $conversion_goal_key, $section_key );
			if ( $combined !== null && is_array( $combined ) ) {
				$allowed = isset( $combined[ Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS ] ) && is_array( $combined[ Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS ] )
					? $combined[ Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS ]
					: array();
				foreach ( self::OVERLAY_MERGE_FIELDS as $field ) {
					if ( in_array( $field, $allowed, true ) && array_key_exists( $field, $combined ) ) {
						$composed[ $field ] = $combined[ $field ];
					}
				}
				$overlay_applied = true;
			}
		}
		$compliance_warnings = array();
		if ( $industry_key !== '' && $this->compliance_warning_resolver !== null ) {
			$compliance_warnings = $this->compliance_warning_resolver->get_for_display( $industry_key );
		}
		$result = new Composed_Helper_Doc_Result( $composed, $base_id, $overlay_applied, $overlay_industry, $section_key, $compliance_warnings );
		if ( $this->cache_service !== null && $this->cache_key_builder !== null ) {
			$base_key = $this->cache_key_builder->for_helper_doc( $section_key, $industry_key, $subtype_key, $conversion_goal_key );
			$this->cache_service->set(
				$base_key,
				array(
					'composed_doc'          => $composed,
					'base_documentation_id' => $base_id,
					'overlay_applied'       => $overlay_applied,
					'overlay_industry_key'  => $overlay_industry,
					'section_key'           => $section_key,
					'compliance_warnings'   => $compliance_warnings,
				)
			);
		}
		return $result;
	}

	/**
	 * Merges resolved fragment content into composed doc when overlay has fragment refs (Prompt 477).
	 *
	 * @param array<string, mixed> $overlay Overlay or subtype overlay array.
	 * @param array<string, mixed> $composed Composed doc array (mutated).
	 * @return void
	 */
	private function merge_fragment_refs_into_composed( array $overlay, array &$composed ): void {
		if ( $this->fragment_resolver === null ) {
			return;
		}
		foreach ( self::FRAGMENT_REF_FIELDS as $ref_key => $target_field ) {
			$frag_key = isset( $overlay[ $ref_key ] ) && is_string( $overlay[ $ref_key ] )
				? trim( $overlay[ $ref_key ] )
				: '';
			if ( $frag_key === '' ) {
				continue;
			}
			$resolved = $this->fragment_resolver->resolve( $frag_key, self::FRAGMENT_CONSUMER_SCOPE );
			if ( $resolved === null || $resolved === '' ) {
				continue;
			}
			$existing                  = isset( $composed[ $target_field ] ) && is_string( $composed[ $target_field ] )
				? trim( $composed[ $target_field ] )
				: '';
			$composed[ $target_field ] = $existing !== '' ? $existing . "\n\n" . $resolved : $resolved;
		}
	}
}
