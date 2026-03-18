<?php
/**
 * Internal validation and health check for the industry subsystem (Prompt 390).
 * Validates pack refs, profile selections, starter bundles, and cross-registry consistency.
 * No auto-fix; admin/support-only; bounded output.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;
use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry;
use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry;

/**
 * Runs health checks across industry registries and profile. Returns issues grouped by severity.
 * Object-type agnostic; callers receive a list of issue records with object_type, key, severity, issue_summary, related_refs.
 */
final class Industry_Health_Check_Service {

	public const SEVERITY_ERROR   = 'error';
	public const SEVERITY_WARNING = 'warning';

	public const OBJECT_TYPE_PACK           = 'pack';
	public const OBJECT_TYPE_PROFILE        = 'profile';
	public const OBJECT_TYPE_STARTER_BUNDLE = 'starter_bundle';

	/** @var Industry_Profile_Repository|null */
	private $profile_repo;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_CTA_Pattern_Registry|null */
	private $cta_registry;

	/** @var Industry_SEO_Guidance_Registry|null */
	private $seo_registry;

	/** @var Industry_LPagery_Rule_Registry|null */
	private $lpagery_registry;

	/** @var Industry_Style_Preset_Registry|null */
	private $preset_registry;

	/** @var Industry_Section_Helper_Overlay_Registry|null */
	private $section_overlay_registry;

	/** @var Industry_Page_OnePager_Overlay_Registry|null */
	private $page_overlay_registry;

	/** @var Industry_Question_Pack_Registry|null */
	private $question_pack_registry;

	/** @var Industry_Starter_Bundle_Registry|null */
	private $starter_bundle_registry;

	/** @var object|null Optional; must have is_pack_active(string): bool. */
	private $pack_toggle_controller;

	public function __construct(
		?Industry_Profile_Repository $profile_repo = null,
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_CTA_Pattern_Registry $cta_registry = null,
		?Industry_SEO_Guidance_Registry $seo_registry = null,
		?Industry_LPagery_Rule_Registry $lpagery_registry = null,
		?Industry_Style_Preset_Registry $preset_registry = null,
		?Industry_Section_Helper_Overlay_Registry $section_overlay_registry = null,
		?Industry_Page_OnePager_Overlay_Registry $page_overlay_registry = null,
		?Industry_Question_Pack_Registry $question_pack_registry = null,
		?Industry_Starter_Bundle_Registry $starter_bundle_registry = null,
		?object $pack_toggle_controller = null
	) {
		$this->profile_repo             = $profile_repo;
		$this->pack_registry            = $pack_registry;
		$this->cta_registry             = $cta_registry;
		$this->seo_registry             = $seo_registry;
		$this->lpagery_registry         = $lpagery_registry;
		$this->preset_registry          = $preset_registry;
		$this->section_overlay_registry = $section_overlay_registry;
		$this->page_overlay_registry    = $page_overlay_registry;
		$this->question_pack_registry   = $question_pack_registry;
		$this->starter_bundle_registry  = $starter_bundle_registry;
		$this->pack_toggle_controller   = $pack_toggle_controller;
	}

	/**
	 * Runs validation and returns issues. Grouped by severity; each issue has object_type, key, severity, issue_summary, related_refs.
	 *
	 * @return array{errors: list<array{object_type: string, key: string, severity: string, issue_summary: string, related_refs: list<string>}>, warnings: list<array{object_type: string, key: string, severity: string, issue_summary: string, related_refs: list<string>}>}
	 */
	public function run(): array {
		static $request_cache = array();
		$id                   = \spl_object_id( $this );
		if ( isset( $request_cache[ $id ] ) && \is_array( $request_cache[ $id ] ) ) {
			return $request_cache[ $id ];
		}

		$errors   = array();
		$warnings = array();

		$add_error   = static function ( string $object_type, string $key, string $summary, array $refs = array() ) use ( &$errors ): void {
			$errors[] = array(
				'object_type'   => $object_type,
				'key'           => $key,
				'severity'      => self::SEVERITY_ERROR,
				'issue_summary' => $summary,
				'related_refs'  => $refs,
			);
		};
		$add_warning = static function ( string $object_type, string $key, string $summary, array $refs = array() ) use ( &$warnings ): void {
			$warnings[] = array(
				'object_type'   => $object_type,
				'key'           => $key,
				'severity'      => self::SEVERITY_WARNING,
				'issue_summary' => $summary,
				'related_refs'  => $refs,
			);
		};

		// Packs: validate refs for each pack.
		if ( $this->pack_registry !== null ) {
			foreach ( $this->pack_registry->get_all() as $pack ) {
				$industry_key = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					? trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					: '';
				if ( $industry_key === '' ) {
					continue;
				}

				$token_ref = isset( $pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] )
					? trim( $pack[ Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF ] )
					: '';
				if ( $token_ref !== '' && $this->preset_registry !== null && $this->preset_registry->get( $token_ref ) === null ) {
					$add_error( self::OBJECT_TYPE_PACK, $industry_key, 'Pack token_preset_ref does not resolve.', array( $token_ref ) );
				}

				$seo_ref = isset( $pack[ Industry_Pack_Schema::FIELD_SEO_GUIDANCE_REF ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_SEO_GUIDANCE_REF ] )
					? trim( $pack[ Industry_Pack_Schema::FIELD_SEO_GUIDANCE_REF ] )
					: '';
				if ( $seo_ref !== '' && $this->seo_registry !== null && $this->seo_registry->get( $seo_ref ) === null ) {
					$add_error( self::OBJECT_TYPE_PACK, $industry_key, 'Pack seo_guidance_ref does not resolve.', array( $seo_ref ) );
				}

				$lpagery_ref = isset( $pack[ Industry_Pack_Schema::FIELD_LPAGERY_RULE_REF ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_LPAGERY_RULE_REF ] )
					? trim( $pack[ Industry_Pack_Schema::FIELD_LPAGERY_RULE_REF ] )
					: '';
				if ( $lpagery_ref !== '' && $this->lpagery_registry !== null && $this->lpagery_registry->get( $lpagery_ref ) === null ) {
					$add_error( self::OBJECT_TYPE_PACK, $industry_key, 'Pack lpagery_rule_ref does not resolve.', array( $lpagery_ref ) );
				}

				$starter_ref = isset( $pack['starter_bundle_ref'] ) && is_string( $pack['starter_bundle_ref'] )
					? trim( $pack['starter_bundle_ref'] )
					: '';
				if ( $starter_ref !== '' && $this->starter_bundle_registry !== null ) {
					$bundle = $this->starter_bundle_registry->get( $starter_ref );
					if ( $bundle === null ) {
						$add_error( self::OBJECT_TYPE_PACK, $industry_key, 'Pack starter_bundle_ref does not resolve.', array( $starter_ref ) );
					} elseif ( isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) && trim( (string) $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) !== $industry_key ) {
						$add_warning( self::OBJECT_TYPE_PACK, $industry_key, 'Starter bundle industry_key does not match pack.', array( $starter_ref ) );
					}
				}

				$cta_refs = $this->collect_pack_cta_refs( $pack );
				foreach ( $cta_refs as $ref ) {
					if ( $ref === '' ) {
						continue;
					}
					if ( $this->cta_registry !== null && $this->cta_registry->get( $ref ) === null ) {
						$add_error( self::OBJECT_TYPE_PACK, $industry_key, 'Pack CTA pattern ref does not resolve.', array( $ref ) );
					}
				}

				$helper_refs = isset( $pack[ Industry_Pack_Schema::FIELD_HELPER_OVERLAY_REFS ] ) && is_array( $pack[ Industry_Pack_Schema::FIELD_HELPER_OVERLAY_REFS ] )
					? $pack[ Industry_Pack_Schema::FIELD_HELPER_OVERLAY_REFS ]
					: array();
				foreach ( $helper_refs as $ref ) {
					if ( ! is_string( $ref ) || trim( $ref ) === '' ) {
						continue;
					}
					$ref = trim( $ref );
					if ( $this->section_overlay_registry !== null && $this->section_overlay_registry->get( $industry_key, $ref ) === null ) {
						$add_warning( self::OBJECT_TYPE_PACK, $industry_key, 'Pack helper_overlay_ref does not resolve for this industry.', array( $ref ) );
					}
				}

				$one_pager_refs = isset( $pack[ Industry_Pack_Schema::FIELD_ONE_PAGER_OVERLAY_REFS ] ) && is_array( $pack[ Industry_Pack_Schema::FIELD_ONE_PAGER_OVERLAY_REFS ] )
					? $pack[ Industry_Pack_Schema::FIELD_ONE_PAGER_OVERLAY_REFS ]
					: array();
				foreach ( $one_pager_refs as $ref ) {
					if ( ! is_string( $ref ) || trim( $ref ) === '' ) {
						continue;
					}
					$ref = trim( $ref );
					if ( $this->page_overlay_registry !== null && $this->page_overlay_registry->get( $industry_key, $ref ) === null ) {
						$add_warning( self::OBJECT_TYPE_PACK, $industry_key, 'Pack one_pager_overlay_ref does not resolve for this industry.', array( $ref ) );
					}
				}
			}
		}

		// Profile: primary/secondary pack resolution and selected starter bundle.
		if ( $this->profile_repo !== null ) {
			$profile         = $this->profile_repo->get_profile();
			$primary         = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
				: '';
			$secondary       = isset( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] ) && is_array( $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] )
				? array_values(
					array_filter(
						array_map(
							function ( $v ) {
								return is_string( $v ) ? trim( $v ) : '';
							},
							$profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ]
						)
					)
				)
				: array();
			$selected_bundle = isset( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
				? trim( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
				: '';

			if ( $primary !== '' && $this->pack_registry !== null && $this->pack_registry->get( $primary ) === null ) {
				$add_error( self::OBJECT_TYPE_PROFILE, 'primary_industry_key', 'Profile primary industry pack not found.', array( $primary ) );
			}
			foreach ( $secondary as $sec ) {
				if ( $sec === '' ) {
					continue;
				}
				if ( $this->pack_registry !== null && $this->pack_registry->get( $sec ) === null ) {
					$add_error( self::OBJECT_TYPE_PROFILE, 'secondary_industry_keys', 'Profile secondary industry pack not found.', array( $sec ) );
				}
			}
			if ( $primary !== '' && $this->pack_toggle_controller !== null && method_exists( $this->pack_toggle_controller, 'is_pack_active' ) && ! $this->pack_toggle_controller->is_pack_active( $primary ) ) {
				$add_warning( self::OBJECT_TYPE_PROFILE, 'primary_industry_key', 'Primary industry pack is disabled; recommendations use generic fallback.', array( $primary ) );
			}
			if ( $selected_bundle !== '' ) {
				if ( $this->starter_bundle_registry === null || $this->starter_bundle_registry->get( $selected_bundle ) === null ) {
					$add_error( self::OBJECT_TYPE_PROFILE, 'selected_starter_bundle_key', 'Profile selected starter bundle not found.', array( $selected_bundle ) );
				} else {
					$bundle          = $this->starter_bundle_registry->get( $selected_bundle );
					$bundle_industry = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) ? trim( (string) $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) : '';
					if ( $primary !== '' && $bundle_industry !== '' && $bundle_industry !== $primary ) {
						$add_warning( self::OBJECT_TYPE_PROFILE, 'selected_starter_bundle_key', 'Selected bundle belongs to a different industry than primary.', array( $selected_bundle, $primary ) );
					}
				}
			}
		}

		// Starter bundles: industry_key must resolve to pack; optional refs advisory.
		if ( $this->starter_bundle_registry !== null && $this->pack_registry !== null ) {
			foreach ( $this->starter_bundle_registry->list_all() as $bundle ) {
				$bundle_key      = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ) ? trim( (string) $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ) : '';
				$bundle_industry = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) ? trim( (string) $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) : '';
				if ( $bundle_industry !== '' && $this->pack_registry->get( $bundle_industry ) === null ) {
					$add_warning( self::OBJECT_TYPE_STARTER_BUNDLE, $bundle_key, 'Starter bundle industry_key has no matching pack.', array( $bundle_industry ) );
				}
			}
		}

		$result               = array(
			'errors'   => $errors,
			'warnings' => $warnings,
		);
		$request_cache[ $id ] = $result;
		return $result;
	}

	/**
	 * @param array<string, mixed> $pack
	 * @return list<string>
	 */
	private function collect_pack_cta_refs( array $pack ): array {
		$out    = array();
		$fields = array(
			Industry_Pack_Schema::FIELD_PREFERRED_CTA_PATTERNS,
			Industry_Pack_Schema::FIELD_DISCOURAGED_CTA_PATTERNS,
			Industry_Pack_Schema::FIELD_REQUIRED_CTA_PATTERNS,
			Industry_Pack_Schema::FIELD_DEFAULT_CTA_PATTERNS,
		);
		foreach ( $fields as $field ) {
			$val = $pack[ $field ] ?? null;
			if ( is_array( $val ) ) {
				foreach ( $val as $v ) {
					if ( is_string( $v ) && trim( $v ) !== '' ) {
						$out[] = trim( $v );
					}
				}
			}
		}
		return array_values( array_unique( $out ) );
	}
}
