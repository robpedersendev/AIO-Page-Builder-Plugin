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

use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry;
use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;

/**
 * Resolves page_template_key + industry context into composed one-pager. Read-only; no mutation of base or overlay storage.
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

	/** @var Industry_Compliance_Warning_Resolver|null Optional; when set, composed result includes compliance warnings (Prompt 407). */
	private ?Industry_Compliance_Warning_Resolver $compliance_warning_resolver;

	public function __construct(
		Documentation_Registry $documentation_registry,
		Industry_Page_OnePager_Overlay_Registry $overlay_registry,
		?Industry_Compliance_Warning_Resolver $compliance_warning_resolver = null,
		?Subtype_Page_OnePager_Overlay_Registry $subtype_overlay_registry = null
	) {
		$this->documentation_registry       = $documentation_registry;
		$this->overlay_registry             = $overlay_registry;
		$this->compliance_warning_resolver  = $compliance_warning_resolver;
		$this->subtype_overlay_registry     = $subtype_overlay_registry;
	}

	/**
	 * Composes one-pager for the given page template, industry, and optional subtype. Order: base → industry overlay → subtype overlay.
	 *
	 * @param string $page_template_key Page template internal_key.
	 * @param string $industry_key      Industry pack key. Empty = base-only, no overlay.
	 * @param string $subtype_key       Optional subtype key (e.g. realtor_buyer_agent). Empty = no subtype overlay (Prompt 427).
	 * @return Composed_Page_OnePager_Result
	 */
	public function compose( string $page_template_key, string $industry_key, string $subtype_key = '' ): Composed_Page_OnePager_Result {
		$page_template_key = trim( $page_template_key );
		$industry_key      = trim( $industry_key );
		$subtype_key       = trim( $subtype_key );
		$base_doc          = $page_template_key !== '' ? $this->documentation_registry->get_by_page_template_key( $page_template_key ) : null;
		$base_id           = '';
		$composed          = array();
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
		$compliance_warnings = array();
		if ( $industry_key !== '' && $this->compliance_warning_resolver !== null ) {
			$compliance_warnings = $this->compliance_warning_resolver->get_for_display( $industry_key );
		}
		return new Composed_Page_OnePager_Result( $composed, $base_id, $overlay_applied, $overlay_industry, $page_template_key, $compliance_warnings );
	}
}
