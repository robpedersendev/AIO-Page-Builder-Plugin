<?php
/**
 * Bootstrap entrypoint for the Industry Pack subsystem (industry-pack-extension-contract).
 * Registers the industry subsystem with the container for pack discovery, registry, and onboarding integration.
 * Industry packs extend existing registries, onboarding, docs, AI, and LPagery—they do not replace them.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Industry Pack subsystem module. Registers industry subsystem services including built-in
 * pack, overlay, and registry implementations (per industry-pack-extension-contract).
 * Safe when no industry data is configured; CONTAINER_KEY_INDUSTRY_LOADED indicates subsystem is available.
 */
final class Industry_Packs_Module implements Service_Provider_Interface {

	/** Container key: whether the industry subsystem is loaded (for dependency checks). */
	public const CONTAINER_KEY_INDUSTRY_LOADED = 'industry_packs_loaded';

	/** Container key: industry pack registry (list/get/validate). */
	public const CONTAINER_KEY_INDUSTRY_PACK_REGISTRY = 'industry_pack_registry';

	/** Container key: industry profile store (primary/secondary industry). */
	public const CONTAINER_KEY_INDUSTRY_PROFILE_STORE = 'industry_profile_store';

	/** Container key: CTA pattern registry (industry-cta-pattern-contract). */
	public const CONTAINER_KEY_CTA_PATTERN_REGISTRY = 'industry_cta_pattern_registry';

	/** Container key: industry section-helper overlay registry (industry-section-helper-overlay-schema). */
	public const CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY = 'industry_section_helper_overlay_registry';

	/** Container key: subtype section-helper overlay registry (subtype-section-helper-overlay-schema; Prompt 424). */
	public const CONTAINER_KEY_SUBTYPE_SECTION_HELPER_OVERLAY_REGISTRY = 'subtype_section_helper_overlay_registry';

	/** Container key: goal section-helper overlay registry (conversion-goal-helper-overlay-schema; Prompt 506). */
	public const CONTAINER_KEY_GOAL_SECTION_HELPER_OVERLAY_REGISTRY = 'goal_section_helper_overlay_registry';

	/** Container key: secondary-goal section-helper overlay registry (Prompt 543, 544; secondary-goal-helper-overlay-schema.md). */
	public const CONTAINER_KEY_SECONDARY_GOAL_SECTION_HELPER_OVERLAY_REGISTRY = 'secondary_goal_section_helper_overlay_registry';

	/** Container key: industry page one-pager overlay registry (industry-page-onepager-overlay-schema). */
	public const CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY = 'industry_page_onepager_overlay_registry';

	/** Container key: subtype page one-pager overlay registry (subtype-page-onepager-overlay-schema; Prompt 426). */
	public const CONTAINER_KEY_SUBTYPE_PAGE_ONEPAGER_OVERLAY_REGISTRY = 'subtype_page_onepager_overlay_registry';

	/** Container key: goal page one-pager overlay registry (conversion-goal-page-onepager-overlay-schema; Prompt 508). */
	public const CONTAINER_KEY_GOAL_PAGE_ONEPAGER_OVERLAY_REGISTRY = 'goal_page_onepager_overlay_registry';

	/** Container key: secondary-goal page one-pager overlay registry (Prompt 545, 546; secondary-goal-page-onepager-overlay-schema.md). */
	public const CONTAINER_KEY_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY_REGISTRY = 'secondary_goal_page_onepager_overlay_registry';

	/** Container key: industry SEO guidance registry (Prompt 359). */
	public const CONTAINER_KEY_SEO_GUIDANCE_REGISTRY = 'industry_seo_guidance_registry';

	/** Container key: industry LPagery rule registry (Prompt 360). */
	public const CONTAINER_KEY_LPAGERY_RULE_REGISTRY = 'industry_lpagery_rule_registry';

	/** Container key: industry starter bundle registry (Prompt 386; industry-starter-bundle-schema.md). */
	public const CONTAINER_KEY_STARTER_BUNDLE_REGISTRY = 'industry_starter_bundle_registry';

	/** Container key: secondary-goal starter-bundle overlay registry (Prompt 541/542; secondary-goal-starter-bundle-schema.md). */
	public const CONTAINER_KEY_SECONDARY_GOAL_STARTER_BUNDLE_OVERLAY_REGISTRY = 'secondary_goal_starter_bundle_overlay_registry';

	/** Container key: combined subtype+goal section-helper overlay registry (Prompt 553/554; subtype-goal-doc-overlay-schema.md). */
	public const CONTAINER_KEY_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY_REGISTRY = 'subtype_goal_section_helper_overlay_registry';

	/** Container key: combined subtype+goal page one-pager overlay registry (Prompt 553/554; subtype-goal-doc-overlay-schema.md). */
	public const CONTAINER_KEY_SUBTYPE_GOAL_PAGE_ONEPAGER_OVERLAY_REGISTRY = 'subtype_goal_page_onepager_overlay_registry';

	/** Container key: industry pack toggle controller (Prompt 389; industry-pack-activation-contract.md). */
	public const CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER = 'industry_pack_toggle_controller';

	/** Container key: industry compliance rule registry (Prompt 405); industry compliance warning resolver (Prompt 407). */
	public const CONTAINER_KEY_COMPLIANCE_RULE_REGISTRY = 'industry_compliance_rule_registry';

	/** Container key: goal caution rule registry (Prompt 510; conversion-goal-caution-rule-schema). */
	public const CONTAINER_KEY_GOAL_CAUTION_RULE_REGISTRY = 'goal_caution_rule_registry';

	/** Container key: secondary-goal caution rule registry (Prompt 547, 548; secondary-goal-caution-rule-schema.md). */
	public const CONTAINER_KEY_SECONDARY_GOAL_CAUTION_RULE_REGISTRY = 'secondary_goal_caution_rule_registry';

	/** Container key: industry subtype registry (Prompt 413/414; industry-subtype-schema.md). */
	public const CONTAINER_KEY_SUBTYPE_REGISTRY = 'industry_subtype_registry';

	/** Container key: industry subtype resolver (Prompt 414). */
	public const CONTAINER_KEY_SUBTYPE_RESOLVER = 'industry_subtype_resolver';

	/** Container key: industry read-model cache key builder (industry-cache-contract; Prompt 434). */
	public const CONTAINER_KEY_INDUSTRY_CACHE_KEY_BUILDER = 'industry_cache_key_builder';

	/** Container key: industry read-model cache service (industry-cache-contract; Prompt 434). */
	public const CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE = 'industry_read_model_cache_service';

	/** Container key: industry shared fragment registry (Prompt 475; industry-shared-fragment-schema). */
	public const CONTAINER_KEY_SHARED_FRAGMENT_REGISTRY = 'industry_shared_fragment_registry';

	/** Container key: industry shared fragment resolver (Prompt 475). */
	public const CONTAINER_KEY_SHARED_FRAGMENT_RESOLVER = 'industry_shared_fragment_resolver';

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			self::CONTAINER_KEY_INDUSTRY_LOADED,
			function (): bool {
				return true;
			}
		);
		$container->register(
			'industry_pack_validator',
			function (): \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator {
				return new \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator();
			}
		);
		$container->register(
			self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY,
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry {
				$validator = $container->has( 'industry_pack_validator' ) ? $container->get( 'industry_pack_validator' ) : new \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator();
				$settings  = $container->has( 'settings' ) ? $container->get( 'settings' ) : null;
				$toggle    = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) : null;
				return new \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry(
					$validator,
					$settings instanceof \AIOPageBuilder\Infrastructure\Settings\Settings_Service ? $settings : null,
					$toggle instanceof \AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller ? $toggle : null
				);
			}
		);
		$container->register(
			'industry_profile_validator',
			function (): \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Validator {
				return new \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Validator();
			}
		);
		$container->register(
			'industry_profile_audit_trail_service',
			function (): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Profile_Audit_Trail_Service {
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Profile_Audit_Trail_Service();
			}
		);
		$container->register(
			self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE,
			function () use ( $container ): ?\AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository {
				if ( ! $container->has( 'settings' ) ) {
					return null;
				}
				$audit = $container->has( 'industry_profile_audit_trail_service' ) ? $container->get( 'industry_profile_audit_trail_service' ) : null;
				return new \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository(
					$container->get( 'settings' ),
					$audit instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Profile_Audit_Trail_Service ? $audit : null
				);
			}
		);
		$container->register(
			'industry_secondary_conversion_goal_resolver',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Profile\Secondary_Conversion_Goal_Resolver {
				$profile_repo = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				return new \AIOPageBuilder\Domain\Industry\Profile\Secondary_Conversion_Goal_Resolver(
					$profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ? $profile_repo : null
				);
			}
		);
		$container->register(
			self::CONTAINER_KEY_CTA_PATTERN_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SUBTYPE_SECTION_HELPER_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_GOAL_SECTION_HELPER_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Docs\Goal_Section_Helper_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Docs\Goal_Section_Helper_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Docs\Goal_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SECONDARY_GOAL_SECTION_HELPER_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Section_Helper_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Section_Helper_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SUBTYPE_PAGE_ONEPAGER_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_GOAL_PAGE_ONEPAGER_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Docs\Goal_Page_OnePager_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Docs\Goal_Page_OnePager_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Docs\Goal_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Page_OnePager_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Page_OnePager_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Section_Helper_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Section_Helper_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SUBTYPE_GOAL_PAGE_ONEPAGER_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Page_OnePager_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Page_OnePager_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SEO_GUIDANCE_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_LPAGERY_RULE_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_INDUSTRY_CACHE_KEY_BUILDER,
			function (): \AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder {
				return new \AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder();
			}
		);
		$container->register(
			self::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE,
			function (): \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service {
				return new \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service();
			}
		);
		$container->register(
			self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY,
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry {
				$cache       = $container->has( self::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) : null;
				$key_builder = $container->has( self::CONTAINER_KEY_INDUSTRY_CACHE_KEY_BUILDER ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_CACHE_KEY_BUILDER ) : null;
				$registry    = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry(
					$cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ? $cache : null,
					$key_builder instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder ? $key_builder : null
				);
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SECONDARY_GOAL_STARTER_BUNDLE_OVERLAY_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Starter_Bundle_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Starter_Bundle_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Starter_Bundle_Overlay_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER,
			function () use ( $container ): \AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller {
				$settings = $container->has( 'settings' ) ? $container->get( 'settings' ) : new \AIOPageBuilder\Infrastructure\Settings\Settings_Service();
				return new \AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Toggle_Controller( $settings );
			}
		);
		$container->register(
			self::CONTAINER_KEY_COMPLIANCE_RULE_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SHARED_FRAGMENT_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SHARED_FRAGMENT_RESOLVER,
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Resolver {
				$registry = $container->get( self::CONTAINER_KEY_SHARED_FRAGMENT_REGISTRY );
				return new \AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Resolver( $registry );
			}
		);
		$container->register(
			'subtype_compliance_rule_registry',
			function (): \AIOPageBuilder\Domain\Industry\Registry\Subtype_Compliance_Rule_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Subtype_Compliance_Rule_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Subtype_Compliance_Rule_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_GOAL_CAUTION_RULE_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Registry\Goal_Caution_Rule_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Goal_Caution_Rule_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Goal_Caution_Rule_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SECONDARY_GOAL_CAUTION_RULE_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Caution_Rule_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Caution_Rule_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Caution_Rule_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			'industry_compliance_warning_resolver',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Docs\Industry_Compliance_Warning_Resolver {
				$registry          = $container->get( self::CONTAINER_KEY_COMPLIANCE_RULE_REGISTRY );
				$subtype_registry  = $container->has( 'subtype_compliance_rule_registry' ) ? $container->get( 'subtype_compliance_rule_registry' ) : null;
				$goal_registry     = $container->has( self::CONTAINER_KEY_GOAL_CAUTION_RULE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_GOAL_CAUTION_RULE_REGISTRY ) : null;
				$fragment_resolver = $container->has( self::CONTAINER_KEY_SHARED_FRAGMENT_RESOLVER ) ? $container->get( self::CONTAINER_KEY_SHARED_FRAGMENT_RESOLVER ) : null;
				return new \AIOPageBuilder\Domain\Industry\Docs\Industry_Compliance_Warning_Resolver(
					$registry,
					$subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Subtype_Compliance_Rule_Registry ? $subtype_registry : null,
					$goal_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Goal_Caution_Rule_Registry ? $goal_registry : null,
					$fragment_resolver instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Resolver ? $fragment_resolver : null
				);
			}
		);
		$container->register(
			'industry_question_pack_registry',
			function (): \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Definitions::default_packs() );
				return $registry;
			}
		);
		$container->register(
			'industry_prompt_pack_overlay_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\AI\Industry_Prompt_Pack_Overlay_Service {
				$pack_registry = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY )
				? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY )
				: null;
				return new \AIOPageBuilder\Domain\Industry\AI\Industry_Prompt_Pack_Overlay_Service( $pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null );
			}
		);
		$container->register(
			'industry_subtype_prompt_pack_overlay_service',
			function (): \AIOPageBuilder\Domain\Industry\AI\Industry_Subtype_Prompt_Pack_Overlay_Service {
				return new \AIOPageBuilder\Domain\Industry\AI\Industry_Subtype_Prompt_Pack_Overlay_Service();
			}
		);
		$container->register(
			'conversion_goal_prompt_pack_overlay_service',
			function (): \AIOPageBuilder\Domain\Industry\AI\Conversion_Goal_Prompt_Pack_Overlay_Service {
				return new \AIOPageBuilder\Domain\Industry\AI\Conversion_Goal_Prompt_Pack_Overlay_Service();
			}
		);
		$container->register(
			'industry_style_preset_registry',
			function (): \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\StylePresets\Builtin_Industry_Style_Presets::get_definitions() );
				return $registry;
			}
		);
		$container->register(
			'goal_style_preset_overlay_registry',
			function (): \AIOPageBuilder\Domain\Industry\Registry\Goal_Style_Preset_Overlay_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Goal_Style_Preset_Overlay_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Goal_Style_Preset_Overlay_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			'industry_subtype_content_gap_extender',
			function (): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Content_Gap_Extender {
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Content_Gap_Extender();
			}
		);
		$container->register(
			'conversion_goal_content_gap_extender',
			function (): \AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_Content_Gap_Extender {
				return new \AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_Content_Gap_Extender();
			}
		);
		$container->register(
			'industry_content_gap_detector',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Content_Gap_Detector {
				$starter  = $container->has( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
				$sub_ext  = $container->has( 'industry_subtype_content_gap_extender' ) ? $container->get( 'industry_subtype_content_gap_extender' ) : null;
				$goal_ext = $container->has( 'conversion_goal_content_gap_extender' ) ? $container->get( 'conversion_goal_content_gap_extender' ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Content_Gap_Detector(
					$starter instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $starter : null,
					$sub_ext instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Content_Gap_Extender ? $sub_ext : null,
					$goal_ext instanceof \AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_Content_Gap_Extender ? $goal_ext : null
				);
			}
		);
		$container->register(
			'conversion_goal_conflict_detector',
			function (): \AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_Conflict_Detector {
				return new \AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_Conflict_Detector();
			}
		);
		$container->register(
			'conversion_goal_benchmark_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_Benchmark_Service {
				$comparison = $container->has( 'industry_subtype_comparison_service' ) ? $container->get( 'industry_subtype_comparison_service' ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_Benchmark_Service(
					$comparison instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Comparison_Service ? $comparison : null
				);
			}
		);
		$container->register(
			'industry_subtype_goal_benchmark_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Goal_Benchmark_Service {
				$goal_bench = $container->has( 'conversion_goal_benchmark_service' ) ? $container->get( 'conversion_goal_benchmark_service' ) : null;
				$sub_bench  = null;
				if ( $container->has( 'industry_subtype_registry' ) ) {
					$sub_reg      = $container->get( 'industry_subtype_registry' );
					$sub_overlay  = $container->has( self::CONTAINER_KEY_SUBTYPE_SECTION_HELPER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_SECTION_HELPER_OVERLAY_REGISTRY ) : null;
					$page_overlay = $container->has( self::CONTAINER_KEY_SUBTYPE_PAGE_ONEPAGER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_PAGE_ONEPAGER_OVERLAY_REGISTRY ) : null;
					$bundle_reg   = $container->has( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
					if ( $sub_reg instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ) {
						$sub_bench = new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Benchmark_Service(
							$sub_reg,
							$sub_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry ? $sub_overlay : null,
							$page_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry ? $page_overlay : null,
							$bundle_reg instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $bundle_reg : null,
							null
						);
					}
				}
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Goal_Benchmark_Service(
					$goal_bench instanceof \AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_Benchmark_Service ? $goal_bench : null,
					$sub_bench
				);
			}
		);
		$container->register(
			'conversion_goal_what_if_extender',
			function (): \AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_What_If_Extender {
				return new \AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_What_If_Extender();
			}
		);
		$container->register(
			'industry_override_audit_report_service',
			function (): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Audit_Report_Service {
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Audit_Report_Service();
			}
		);
		$container->register(
			'industry_starter_bundle_diff_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Starter_Bundle_Diff_Service {
				$registry = $container->has( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Starter_Bundle_Diff_Service(
					$registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $registry : null
				);
			}
		);
		$container->register(
			'industry_override_conflict_detector',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Conflict_Detector {
				$read_model = $container->has( 'industry_override_read_model_builder' ) ? $container->get( 'industry_override_read_model_builder' ) : null;
				$plan_repo  = $container->has( 'build_plan_repository' ) ? $container->get( 'build_plan_repository' ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Conflict_Detector(
					$read_model instanceof \AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Read_Model_Builder ? $read_model : null,
					$plan_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository_Interface ? $plan_repo : null
				);
			}
		);
		$container->register(
			'industry_diagnostics_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Diagnostics_Service {
				$profile_repo      = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				$pack_registry     = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$section_overlay   = $container->has( self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY ) : null;
				$page_overlay      = $container->has( self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY ) : null;
				$preset_app        = $container->has( 'industry_style_preset_application_service' ) ? $container->get( 'industry_style_preset_application_service' ) : null;
				$gap_detector      = $container->has( 'industry_content_gap_detector' ) ? $container->get( 'industry_content_gap_detector' ) : null;
				$override_audit    = $container->has( 'industry_override_audit_report_service' ) ? $container->get( 'industry_override_audit_report_service' ) : null;
				$conflict_detector = $container->has( 'industry_override_conflict_detector' ) ? $container->get( 'industry_override_conflict_detector' ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Diagnostics_Service(
					$profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ? $profile_repo : null,
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null,
					$section_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry ? $section_overlay : null,
					$page_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry ? $page_overlay : null,
					$preset_app instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Application_Service ? $preset_app : null,
					$gap_detector instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Content_Gap_Detector ? $gap_detector : null,
					$override_audit instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Audit_Report_Service ? $override_audit : null,
					$conflict_detector instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Conflict_Detector ? $conflict_detector : null
				);
			}
		);
		$container->register(
			'industry_health_check_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service {
				$profile_repo    = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				$pack_registry   = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$cta             = $container->has( self::CONTAINER_KEY_CTA_PATTERN_REGISTRY ) ? $container->get( self::CONTAINER_KEY_CTA_PATTERN_REGISTRY ) : null;
				$seo             = $container->has( self::CONTAINER_KEY_SEO_GUIDANCE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SEO_GUIDANCE_REGISTRY ) : null;
				$lpagery         = $container->has( self::CONTAINER_KEY_LPAGERY_RULE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_LPAGERY_RULE_REGISTRY ) : null;
				$preset_registry = $container->has( 'industry_style_preset_registry' ) ? $container->get( 'industry_style_preset_registry' ) : null;
				$section_overlay = $container->has( self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY ) : null;
				$page_overlay    = $container->has( self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY ) : null;
				$qp              = $container->has( 'industry_question_pack_registry' ) ? $container->get( 'industry_question_pack_registry' ) : null;
				$starter         = $container->has( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
				$toggle          = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service(
					$profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ? $profile_repo : null,
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null,
					$cta instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry ? $cta : null,
					$seo instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry ? $seo : null,
					$lpagery instanceof \AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry ? $lpagery : null,
					$preset_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry ? $preset_registry : null,
					$section_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry ? $section_overlay : null,
					$page_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry ? $page_overlay : null,
					$qp instanceof \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry ? $qp : null,
					$starter instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $starter : null,
					$toggle
				);
			}
		);
		$container->register(
			'industry_coverage_gap_analyzer',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Analyzer {
				$pack_registry    = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$section_overlay  = $container->has( self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY ) : null;
				$page_overlay     = $container->has( self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY ) : null;
				$bundle_registry  = $container->has( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
				$preset           = $container->has( 'industry_style_preset_registry' ) ? $container->get( 'industry_style_preset_registry' ) : null;
				$compliance       = $container->has( self::CONTAINER_KEY_COMPLIANCE_RULE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_COMPLIANCE_RULE_REGISTRY ) : null;
				$seo              = $container->has( self::CONTAINER_KEY_SEO_GUIDANCE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SEO_GUIDANCE_REGISTRY ) : null;
				$qp               = $container->has( 'industry_question_pack_registry' ) ? $container->get( 'industry_question_pack_registry' ) : null;
				$subtype_registry = $container->has( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Analyzer(
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null,
					$section_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry ? $section_overlay : null,
					$page_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry ? $page_overlay : null,
					$bundle_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $bundle_registry : null,
					$preset instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry ? $preset : null,
					$compliance instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry ? $compliance : null,
					$seo instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry ? $seo : null,
					$qp instanceof \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry ? $qp : null,
					$subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null
				);
			}
		);
		$container->register(
			'industry_coverage_gap_prioritization_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Prioritization_Service {
				$analyzer = $container->has( 'industry_coverage_gap_analyzer' ) ? $container->get( 'industry_coverage_gap_analyzer' ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Prioritization_Service(
					$analyzer instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Analyzer ? $analyzer : null
				);
			}
		);
		$container->register(
			'industry_author_task_queue_service',
			function (): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Author_Task_Queue_Service {
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Author_Task_Queue_Service();
			}
		);
		$container->register(
			'industry_documentation_summary_export_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Documentation_Summary_Export_Service {
				$diagnostics    = $container->has( 'industry_diagnostics_service' ) ? $container->get( 'industry_diagnostics_service' ) : null;
				$health         = $container->has( 'industry_health_check_service' ) ? $container->get( 'industry_health_check_service' ) : null;
				$override_audit = $container->has( 'industry_override_audit_report_service' ) ? $container->get( 'industry_override_audit_report_service' ) : null;
				$profile_repo   = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Documentation_Summary_Export_Service(
					$diagnostics instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Diagnostics_Service ? $diagnostics : null,
					$health instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service ? $health : null,
					$override_audit instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Audit_Report_Service ? $override_audit : null,
					$profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ? $profile_repo : null
				);
			}
		);
		$container->register(
			'industry_performance_benchmark_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Performance_Benchmark_Service {
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Performance_Benchmark_Service( $container );
			}
		);
		$container->register(
			'industry_candidate_template_overlap_analyzer',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Candidate_Template_Overlap_Analyzer {
				$pack_registry = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Candidate_Template_Overlap_Analyzer(
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null
				);
			}
		);
		$container->register(
			'industry_section_preview_resolver',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Preview_Resolver {
				$profile_repo                = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				$pack_registry               = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$section_overlay             = $container->has( self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY ) : null;
				$subtype_overlay             = $container->has( self::CONTAINER_KEY_SUBTYPE_SECTION_HELPER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_SECTION_HELPER_OVERLAY_REGISTRY ) : null;
				$warning_resolver            = $container->has( 'industry_compliance_warning_resolver' ) ? $container->get( 'industry_compliance_warning_resolver' ) : null;
				$cache                       = $container->has( self::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) : null;
				$key_builder                 = $container->has( self::CONTAINER_KEY_INDUSTRY_CACHE_KEY_BUILDER ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_CACHE_KEY_BUILDER ) : null;
				$doc_registry                = new \AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry( new \AIOPageBuilder\Domain\Registries\Docs\Documentation_Loader( __DIR__ . '/../Domain/Registries/Docs' ) );
				$fragment_resolver           = $container->has( self::CONTAINER_KEY_SHARED_FRAGMENT_RESOLVER ) ? $container->get( self::CONTAINER_KEY_SHARED_FRAGMENT_RESOLVER ) : null;
				$subtype_goal_helper_overlay = $container->has( self::CONTAINER_KEY_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY_REGISTRY ) : null;
				$helper_composer             = new \AIOPageBuilder\Domain\Industry\Docs\Industry_Helper_Doc_Composer(
					$doc_registry,
					$section_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry ? $section_overlay : new \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry(),
					$warning_resolver instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Compliance_Warning_Resolver ? $warning_resolver : null,
					$subtype_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry ? $subtype_overlay : null,
					$subtype_goal_helper_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Section_Helper_Overlay_Registry ? $subtype_goal_helper_overlay : null,
					$cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ? $cache : null,
					$key_builder instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder ? $key_builder : null,
					$fragment_resolver instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Resolver ? $fragment_resolver : null
				);
				$section_resolver            = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver(
					$cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ? $cache : null,
					$key_builder instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder ? $key_builder : null
				);
				$substitute_engine           = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Substitute_Suggestion_Engine();
				$subtype_resolver            = $container->has( self::CONTAINER_KEY_SUBTYPE_RESOLVER ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_RESOLVER ) : null;
				$subtype_registry            = $container->has( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) : null;
				return new \AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Preview_Resolver(
					$profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ? $profile_repo : null,
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null,
					$section_resolver,
					$helper_composer,
					$substitute_engine,
					$subtype_resolver instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Subtype_Resolver ? $subtype_resolver : null,
					$subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null,
					$subtype_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry ? $subtype_overlay : null
				);
			}
		);
		$container->register(
			'industry_page_template_preview_resolver',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Preview_Resolver {
				$profile_repo              = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				$pack_registry             = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$page_overlay              = $container->has( self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY ) : null;
				$subtype_page_overlay      = $container->has( self::CONTAINER_KEY_SUBTYPE_PAGE_ONEPAGER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_PAGE_ONEPAGER_OVERLAY_REGISTRY ) : null;
				$warning_resolver          = $container->has( 'industry_compliance_warning_resolver' ) ? $container->get( 'industry_compliance_warning_resolver' ) : null;
				$cache                     = $container->has( self::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) : null;
				$key_builder               = $container->has( self::CONTAINER_KEY_INDUSTRY_CACHE_KEY_BUILDER ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_CACHE_KEY_BUILDER ) : null;
				$doc_registry              = new \AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry( new \AIOPageBuilder\Domain\Registries\Docs\Documentation_Loader( __DIR__ . '/../Domain/Registries/Docs' ) );
				$subtype_goal_page_overlay = $container->has( self::CONTAINER_KEY_SUBTYPE_GOAL_PAGE_ONEPAGER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_GOAL_PAGE_ONEPAGER_OVERLAY_REGISTRY ) : null;
				$one_pager_composer        = new \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Composer(
					$doc_registry,
					$page_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry ? $page_overlay : new \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry(),
					$warning_resolver instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Compliance_Warning_Resolver ? $warning_resolver : null,
					$subtype_page_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry ? $subtype_page_overlay : null,
					$subtype_goal_page_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Page_OnePager_Overlay_Registry ? $subtype_goal_page_overlay : null,
					$cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ? $cache : null,
					$key_builder instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder ? $key_builder : null
				);
				$page_resolver             = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver(
					$cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ? $cache : null,
					$key_builder instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder ? $key_builder : null
				);
				$substitute_engine         = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Substitute_Suggestion_Engine();
				$subtype_resolver          = $container->has( self::CONTAINER_KEY_SUBTYPE_RESOLVER ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_RESOLVER ) : null;
				$subtype_registry          = $container->has( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) : null;
				return new \AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Preview_Resolver(
					$profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ? $profile_repo : null,
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null,
					$page_resolver,
					$one_pager_composer,
					$substitute_engine,
					$subtype_resolver instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Subtype_Resolver ? $subtype_resolver : null,
					$subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null,
					$subtype_page_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry ? $subtype_page_overlay : null
				);
			}
		);
		$container->register(
			'industry_starter_bundle_to_build_plan_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service {
				$bundle_registry = $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY );
				$plan_generator  = $container->has( 'build_plan_generator' ) ? $container->get( 'build_plan_generator' ) : null;
				if ( $plan_generator === null || ! $plan_generator instanceof \AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Generator ) {
					return new \AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service(
						$bundle_registry,
						new \AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Generator(
							$container->get( 'build_plan_repository' ),
							$container->get( 'build_plan_item_generator' ),
							$container->has( 'industry_build_plan_scoring_service' ) ? $container->get( 'industry_build_plan_scoring_service' ) : null,
							$container->has( 'design_token_step_minimum_merger' ) ? $container->get( 'design_token_step_minimum_merger' ) : null
						)
					);
				}
				return new \AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service( $bundle_registry, $plan_generator );
			}
		);
		$container->register(
			'conversion_goal_starter_bundle_to_build_plan_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\AI\Conversion_Goal_Starter_Bundle_To_Build_Plan_Service {
				$base   = $container->get( 'industry_starter_bundle_to_build_plan_service' );
				$bundle = $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY );
				return new \AIOPageBuilder\Domain\Industry\AI\Conversion_Goal_Starter_Bundle_To_Build_Plan_Service(
					$base instanceof \AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service ? $base : new \AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service( $bundle, $container->get( 'build_plan_generator' ) ),
					$bundle
				);
			}
		);
		$container->register(
			'industry_subtype_starter_bundle_to_build_plan_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\AI\Industry_Subtype_Starter_Bundle_To_Build_Plan_Service {
				$bundle_registry = $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY );
				$base_service    = $container->get( 'industry_starter_bundle_to_build_plan_service' );
				return new \AIOPageBuilder\Domain\Industry\AI\Industry_Subtype_Starter_Bundle_To_Build_Plan_Service(
					$bundle_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $bundle_registry : new \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry(),
					$base_service instanceof \AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service ? $base_service : new \AIOPageBuilder\Domain\Industry\AI\Industry_Starter_Bundle_To_Build_Plan_Service( $bundle_registry, $container->get( 'build_plan_generator' ) )
				);
			}
		);
		$container->register(
			'industry_pack_migration_executor',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Profile\Industry_Pack_Migration_Executor {
				$profile_repo    = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				$pack_registry   = $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY );
				$bundle_registry = $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY );
				if ( $profile_repo === null || ! $profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
					$profile_repo = new \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository( $container->get( 'settings' ) );
				}
				return new \AIOPageBuilder\Domain\Industry\Profile\Industry_Pack_Migration_Executor( $profile_repo, $pack_registry, $bundle_registry );
			}
		);
		$container->register(
			'industry_subtype_validator',
			function (): \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Validator {
				return new \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Validator();
			}
		);
		$container->register(
			self::CONTAINER_KEY_SUBTYPE_REGISTRY,
			function (): \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry {
				$registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry();
				$registry->load( \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry::get_builtin_definitions() );
				return $registry;
			}
		);
		$container->register(
			self::CONTAINER_KEY_SUBTYPE_RESOLVER,
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Profile\Industry_Subtype_Resolver {
				$profile_repo     = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				$subtype_registry = $container->has( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) : null;
				if ( $profile_repo === null || ! $profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
					$profile_repo = new \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository( $container->get( 'settings' ) );
				}
				return new \AIOPageBuilder\Domain\Industry\Profile\Industry_Subtype_Resolver( $profile_repo, $subtype_registry );
			}
		);
		$container->register(
			'industry_subtype_comparison_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Comparison_Service {
				$profile_repo          = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				$pack_registry         = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$bundle_registry       = $container->has( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
				$subtype_registry      = $container->has( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) : null;
				$cache                 = $container->has( self::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_READ_MODEL_CACHE_SERVICE ) : null;
				$key_builder           = $container->has( self::CONTAINER_KEY_INDUSTRY_CACHE_KEY_BUILDER ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_CACHE_KEY_BUILDER ) : null;
				$page_resolver         = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver(
					$cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ? $cache : null,
					$key_builder instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder ? $key_builder : null
				);
				$section_resolver      = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver(
					$cache instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service ? $cache : null,
					$key_builder instanceof \AIOPageBuilder\Domain\Industry\Cache\Industry_Cache_Key_Builder ? $key_builder : null
				);
				$page_repo             = $container->has( 'page_template_repository' ) ? $container->get( 'page_template_repository' ) : null;
				$section_list_provider = null;
				if ( $container->has( 'section_template_repository' ) ) {
					$section_repo          = $container->get( 'section_template_repository' );
					$section_list_provider = static function () use ( $section_repo ): array {
						return $section_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository_Interface
						? $section_repo->list_all_definitions( 200, 0 )
						: array();
					};
				}
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Comparison_Service(
					$profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ? $profile_repo : null,
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null,
					$bundle_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $bundle_registry : null,
					$subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null,
					$page_resolver,
					$section_resolver,
					$page_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface ? $page_repo : null,
					$section_list_provider
				);
			}
		);
		$container->register(
			'industry_what_if_simulation_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_What_If_Simulation_Service {
				$profile_repo     = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				$pack_registry    = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$subtype_registry = $container->has( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) : null;
				$bundle_registry  = $container->has( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
				$comparison       = $container->get( 'industry_subtype_comparison_service' );
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_What_If_Simulation_Service(
					$profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ? $profile_repo : new \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository( $container->get( 'settings' ) ),
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null,
					$subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null,
					$bundle_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $bundle_registry : null,
					$comparison instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Comparison_Service ? $comparison : null
				);
			}
		);
		$container->register(
			'industry_pack_completeness_report_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Completeness_Report_Service {
				$pack_registry    = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$bundle_registry  = $container->has( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
				$section_overlay  = $container->has( self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SECTION_HELPER_OVERLAY_REGISTRY ) : null;
				$page_overlay     = $container->has( self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY ) ? $container->get( self::CONTAINER_KEY_PAGE_ONEPAGER_OVERLAY_REGISTRY ) : null;
				$cta              = $container->has( self::CONTAINER_KEY_CTA_PATTERN_REGISTRY ) ? $container->get( self::CONTAINER_KEY_CTA_PATTERN_REGISTRY ) : null;
				$seo              = $container->has( self::CONTAINER_KEY_SEO_GUIDANCE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SEO_GUIDANCE_REGISTRY ) : null;
				$preset           = $container->has( 'industry_style_preset_registry' ) ? $container->get( 'industry_style_preset_registry' ) : null;
				$lpagery          = $container->has( self::CONTAINER_KEY_LPAGERY_RULE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_LPAGERY_RULE_REGISTRY ) : null;
				$compliance       = $container->has( self::CONTAINER_KEY_COMPLIANCE_RULE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_COMPLIANCE_RULE_REGISTRY ) : null;
				$subtype_registry = $container->has( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) : null;
				$health           = $container->has( 'industry_health_check_service' ) ? $container->get( 'industry_health_check_service' ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Completeness_Report_Service(
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null,
					$bundle_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $bundle_registry : null,
					$section_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry ? $section_overlay : null,
					$page_overlay instanceof \AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry ? $page_overlay : null,
					$cta instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry ? $cta : null,
					$seo instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry ? $seo : null,
					$preset instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry ? $preset : null,
					$lpagery instanceof \AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry ? $lpagery : null,
					$compliance instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Compliance_Rule_Registry ? $compliance : null,
					$subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null,
					$health instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service ? $health : null
				);
			}
		);
		$container->register(
			'industry_scaffold_completeness_report_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Completeness_Report_Service {
				$pack_registry    = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$bundle_registry  = $container->has( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
				$subtype_registry = $container->has( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Completeness_Report_Service(
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null,
					$bundle_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $bundle_registry : null,
					$subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null
				);
			}
		);
		$container->register(
			'industry_asset_aging_report_service',
			function (): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Asset_Aging_Report_Service {
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Asset_Aging_Report_Service();
			}
		);
		$container->register(
			'industry_maturity_delta_report_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Maturity_Delta_Report_Service {
				$completeness = $container->has( 'industry_pack_completeness_report_service' ) ? $container->get( 'industry_pack_completeness_report_service' ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Maturity_Delta_Report_Service(
					$completeness instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Completeness_Report_Service ? $completeness : null
				);
			}
		);
		$container->register(
			'industry_drift_report_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Drift_Report_Service {
				$pack_registry = $container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Drift_Report_Service(
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null
				);
			}
		);
		$container->register(
			'industry_scaffold_promotion_readiness_report_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Promotion_Readiness_Report_Service {
				$scaffold = $container->has( 'industry_scaffold_completeness_report_service' ) ? $container->get( 'industry_scaffold_completeness_report_service' ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Promotion_Readiness_Report_Service(
					$scaffold instanceof \AIOPageBuilder\Domain\Industry\Reporting\Industry_Scaffold_Completeness_Report_Service ? $scaffold : null
				);
			}
		);
		$container->register(
			'industry_repair_suggestion_engine',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Reporting\Industry_Repair_Suggestion_Engine {
				$profile_repo     = $container->has( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) : null;
				$pack_registry    = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) : null;
				$bundle_registry  = $container->has( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) : null;
				$subtype_registry = $container->has( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) ? $container->get( self::CONTAINER_KEY_SUBTYPE_REGISTRY ) : null;
				$toggle           = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) ? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_TOGGLE_CONTROLLER ) : null;
				return new \AIOPageBuilder\Domain\Industry\Reporting\Industry_Repair_Suggestion_Engine(
					$pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null,
					$bundle_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry ? $bundle_registry : null,
					$subtype_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry ? $subtype_registry : null,
					$profile_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ? $profile_repo : null,
					$toggle
				);
			}
		);
		$container->register(
			'industry_style_preset_application_service',
			function () use ( $container ): \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Application_Service {
				$preset_registry = $container->has( 'industry_style_preset_registry' ) ? $container->get( 'industry_style_preset_registry' ) : null;
				$style_repo      = $container->has( 'global_style_settings_repository' ) ? $container->get( 'global_style_settings_repository' ) : null;
				$token_registry  = $container->has( 'style_token_registry' ) ? $container->get( 'style_token_registry' ) : null;
				if ( $preset_registry === null || $style_repo === null ) {
					return new \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Application_Service(
						new \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry(),
						$style_repo ?? new \AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository(),
						$token_registry instanceof \AIOPageBuilder\Domain\Styling\Style_Token_Registry ? $token_registry : null
					);
				}
				return new \AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Application_Service(
					$preset_registry,
					$style_repo,
					$token_registry instanceof \AIOPageBuilder\Domain\Styling\Style_Token_Registry ? $token_registry : null
				);
			}
		);
	}
}
