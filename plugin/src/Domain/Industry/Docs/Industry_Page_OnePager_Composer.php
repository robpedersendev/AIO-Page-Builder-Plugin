<?php
/**
 * Composes final page one-pager by combining base one-pager with active industry page overlay (industry-page-onepager-overlay-schema).
 * Deterministic; preserves section-order and base structure; fallback to base-only when overlay absent or invalid.
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
 * Resolves page_template_key + industry context into composed one-pager. Read-only; no mutation of base or overlay storage.
 * When cache service and key builder are provided, results are cached (industry-cache-contract).
 */
final class Industry_Page_OnePager_Composer {

	/** Overlay fields that may be merged onto base (allowed regions per schema). */
	private const OVERLAY_MERGE_FIELDS = array(
		'hierarchy_hints',
		'cta_strategy',
		'lpagery_seo_notes',
		'compliance_cautions',
		'additive_blocks',
	);

	/** @var Documentation_Registry */
	private Documentation_Registry $documentation_registry;

	/** @var Industry_Page_OnePager_Overlay_Registry */
	private Industry_Page_OnePager_Overlay_Registry $overlay_registry;

	/** @var Subtype_Page_OnePager_Overlay_Registry|null Optional; when set, compose() applies subtype overlay after industry (Prompt 427). */
	private ?Subtype_Page_OnePager_Overlay_Registry $subtype_overlay_registry;

	/** @var Subtype_Goal_Page_OnePager_Overlay_Registry|null Optional; when set, compose() can apply combined subtype+goal overlay after subtype (Prompt 554). */
	private ?Subtype_Goal_Page_OnePager_Overlay_Registry $subtype_goal_overlay_registry;

	/** @var Industry_Compliance_Warning_Resolver|null Optional; when set, composed result includes compliance warnings (Prompt 407). */
	private ?Industry_Compliance_Warning_Resolver $compliance_warning_resolver;

	/** @var Industry_Read_Model_Cache_Service|null */
	private ?Industry_Read_Model_Cache_Service $cache_service;

	/** @var Industry_Cache_Key_Builder|null */
	private ?Industry_Cache_Key_Builder $cache_key_builder;

	public function __construct(
		Documentation_Registry $documentation_registry,
		Industry_Page_OnePager_Overlay_Registry $overlay_registry,
		?Industry_Compliance_Warning_Resolver $compliance_warning_resolver = null,
		?Subtype_Page_OnePager_Overlay_Registry $subtype_overlay_registry = null,
		?Subtype_Goal_Page_OnePager_Overlay_Registry $subtype_goal_overlay_registry = null,
		?Industry_Read_Model_Cache_Service $cache_service = null,
		?Industry_Cache_Key_Builder $cache_key_builder = null
	) {
		$this->documentation_registry        = $documentation_registry;
		$this->overlay_registry              = $overlay_registry;
		$this->compliance_warning_resolver   = $compliance_warning_resolver;
		$this->subtype_overlay_registry      = $subtype_overlay_registry;
		$this->subtype_goal_overlay_registry = $subtype_goal_overlay_registry;
		$this->cache_service                 = $cache_service;
		$this->cache_key_builder             = $cache_key_builder;
	}

	/**
	 * Composes one-pager for the given page template, industry, and optional subtype. Order: base → industry overlay → subtype overlay.
	 *
	 * @param string $page_template_key Page template internal_key.
	 * @param string $industry_key      Industry pack key. Empty = base-only, no overlay.
	 * @param string $subtype_key         Optional subtype key (e.g. realtor_buyer_agent). Empty = no subtype overlay (Prompt 427).
	 * @param string $conversion_goal_key Optional conversion goal key (Prompt 554). When set with subtype, combined subtype+goal overlay may be applied.
	 * @return Composed_Page_OnePager_Result
	 */
	public function compose( string $page_template_key, string $industry_key, string $subtype_key = '', string $conversion_goal_key = '' ): Composed_Page_OnePager_Result {
		$page_template_key   = trim( $page_template_key );
		$industry_key        = trim( $industry_key );
		$subtype_key         = trim( $subtype_key );
		$conversion_goal_key = trim( $conversion_goal_key );
		if ( $this->cache_service !== null && $this->cache_key_builder !== null ) {
			$base_key = $this->cache_key_builder->for_page_onepager( $page_template_key, $industry_key, $subtype_key, $conversion_goal_key );
			$cached   = $this->cache_service->get( $base_key );
			if ( is_array( $cached ) && isset( $cached['composed_onepager'] ) && is_array( $cached['composed_onepager'] ) ) {
				return new Composed_Page_OnePager_Result(
					$cached['composed_onepager'],
					(string) ( $cached['base_documentation_id'] ?? '' ),
					(bool) ( $cached['overlay_applied'] ?? false ),
					(string) ( $cached['overlay_industry_key'] ?? '' ),
					(string) ( $cached['page_template_key'] ?? $page_template_key ),
					is_array( $cached['compliance_warnings'] ?? null ) ? $cached['compliance_warnings'] : array()
				);
			}
		}
		$base_doc = $page_template_key !== '' ? $this->documentation_registry->get_by_page_template_key( $page_template_key ) : null;
		$base_id  = '';
		$composed = array();
		if ( $base_doc !== null && is_array( $base_doc ) ) {
			$composed = $base_doc;
			$base_id  = (string) ( $base_doc[ Documentation_Schema::FIELD_DOCUMENTATION_ID ] ?? '' );
		}
		$overlay_applied  = false;
		$overlay_industry = '';
		if ( $industry_key !== '' && $page_template_key !== '' ) {
			$overlay = $this->overlay_registry->get( $industry_key, $page_template_key );
			if ( $overlay !== null && is_array( $overlay ) ) {
				$status = isset( $overlay[ Industry_Page_OnePager_Overlay_Registry::FIELD_STATUS ] ) && is_string( $overlay[ Industry_Page_OnePager_Overlay_Registry::FIELD_STATUS ] )
					? $overlay[ Industry_Page_OnePager_Overlay_Registry::FIELD_STATUS ]
					: '';
				if ( $status === Industry_Page_OnePager_Overlay_Registry::STATUS_ACTIVE ) {
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
		if ( $subtype_key !== '' && $page_template_key !== '' && $this->subtype_overlay_registry !== null ) {
			$subtype_overlay = $this->subtype_overlay_registry->get( $subtype_key, $page_template_key );
			if ( $subtype_overlay !== null && is_array( $subtype_overlay ) ) {
				$status = isset( $subtype_overlay[ Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS ] ) && is_string( $subtype_overlay[ Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS ] )
					? $subtype_overlay[ Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS ]
					: '';
				if ( $status === Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE ) {
					foreach ( self::OVERLAY_MERGE_FIELDS as $field ) {
						if ( array_key_exists( $field, $subtype_overlay ) ) {
							$composed[ $field ] = $subtype_overlay[ $field ];
						}
					}
				}
			}
		}
		if ( $subtype_key !== '' && $conversion_goal_key !== '' && $page_template_key !== '' && $this->subtype_goal_overlay_registry !== null ) {
			$combined = $this->subtype_goal_overlay_registry->get( $subtype_key, $conversion_goal_key, $page_template_key );
			if ( $combined !== null && is_array( $combined ) ) {
				$allowed = isset( $combined[ Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS ] ) && is_array( $combined[ Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS ] )
					? $combined[ Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS ]
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
		$result = new Composed_Page_OnePager_Result( $composed, $base_id, $overlay_applied, $overlay_industry, $page_template_key, $compliance_warnings );
		if ( $this->cache_service !== null && $this->cache_key_builder !== null ) {
			$base_key = $this->cache_key_builder->for_page_onepager( $page_template_key, $industry_key, $subtype_key, $conversion_goal_key );
			$this->cache_service->set(
				$base_key,
				array(
					'composed_onepager'     => $composed,
					'base_documentation_id' => $base_id,
					'overlay_applied'       => $overlay_applied,
					'overlay_industry_key'  => $overlay_industry,
					'page_template_key'     => $page_template_key,
					'compliance_warnings'   => $compliance_warnings,
				)
			);
		}
		return $result;
	}
}
