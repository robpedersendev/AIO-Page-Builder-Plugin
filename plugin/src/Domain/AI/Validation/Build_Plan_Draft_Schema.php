<?php
/**
 * Build-plan-draft AI output schema constants (spec §28.2–28.10, ai-output-validation-contract.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Validation;

defined( 'ABSPATH' ) || exit;

final class Build_Plan_Draft_Schema {

	public const SCHEMA_REF = 'aio/build-plan-draft-v1';

	public const KEY_SCHEMA_VERSION               = 'schema_version';
	public const KEY_RUN_SUMMARY                  = 'run_summary';
	public const KEY_SITE_PURPOSE                 = 'site_purpose';
	public const KEY_SITE_STRUCTURE               = 'site_structure';
	public const KEY_EXISTING_PAGE_CHANGES        = 'existing_page_changes';
	public const KEY_NEW_PAGES_TO_CREATE          = 'new_pages_to_create';
	public const KEY_MENU_CHANGE_PLAN             = 'menu_change_plan';
	public const KEY_DESIGN_TOKEN_RECOMMENDATIONS = 'design_token_recommendations';
	public const KEY_SEO_RECOMMENDATIONS          = 'seo_recommendations';
	public const KEY_WARNINGS                     = 'warnings';
	public const KEY_ASSUMPTIONS                  = 'assumptions';
	public const KEY_CONFIDENCE                   = 'confidence';

	public const REQUIRED_TOP_LEVEL_KEYS = array(
		self::KEY_SCHEMA_VERSION,
		self::KEY_RUN_SUMMARY,
		self::KEY_SITE_PURPOSE,
		self::KEY_SITE_STRUCTURE,
		self::KEY_EXISTING_PAGE_CHANGES,
		self::KEY_NEW_PAGES_TO_CREATE,
		self::KEY_MENU_CHANGE_PLAN,
		self::KEY_DESIGN_TOKEN_RECOMMENDATIONS,
		self::KEY_SEO_RECOMMENDATIONS,
		self::KEY_WARNINGS,
		self::KEY_ASSUMPTIONS,
		self::KEY_CONFIDENCE,
	);

	public const ARRAY_SECTIONS = array(
		self::KEY_EXISTING_PAGE_CHANGES,
		self::KEY_NEW_PAGES_TO_CREATE,
		self::KEY_MENU_CHANGE_PLAN,
		self::KEY_DESIGN_TOKEN_RECOMMENDATIONS,
		self::KEY_SEO_RECOMMENDATIONS,
		self::KEY_WARNINGS,
		self::KEY_ASSUMPTIONS,
	);

	public const RUN_SUMMARY_SUMMARY_TEXT       = 'summary_text';
	public const RUN_SUMMARY_PLANNING_MODE      = 'planning_mode';
	public const RUN_SUMMARY_OVERALL_CONFIDENCE = 'overall_confidence';
	public const ENUM_PLANNING_MODE             = array( 'new_site', 'restructure_existing_site', 'mixed' );
	public const ENUM_CONFIDENCE                = array( 'high', 'medium', 'low' );

	public const EPC_ACTION      = 'action';
	public const EPC_ENUM_ACTION = array( 'keep', 'replace_with_new_page', 'rebuild_from_template', 'merge_and_archive', 'defer' );
	public const EPC_RISK_LEVEL  = 'risk_level';
	public const EPC_ENUM_RISK   = array( 'low', 'medium', 'high' );
	public const EPC_CONFIDENCE  = 'confidence';
	public const EPC_REQUIRED    = array( 'current_page_url', 'current_page_title', 'action', 'reason', 'risk_level', 'confidence' );

	public const NPC_CONFIDENCE     = 'confidence';
	public const NPC_PAGE_TYPE      = 'page_type';
	public const NPC_ENUM_PAGE_TYPE = array( 'hub', 'detail', 'faq', 'pricing', 'request', 'location', 'service', 'other' );
	public const NPC_REQUIRED       = array( 'proposed_page_title', 'proposed_slug', 'purpose', 'template_key', 'menu_eligible', 'section_guidance', 'confidence' );

	public const MCP_MENU_CONTEXT = 'menu_context';
	public const MCP_ENUM_CONTEXT = array( 'header', 'footer', 'mobile', 'off_canvas', 'sidebar' );
	public const MCP_ACTION       = 'action';
	public const MCP_ENUM_ACTION  = array( 'create', 'rename', 'replace', 'update_existing' );
	public const MCP_REQUIRED     = array( 'menu_context', 'action', 'proposed_menu_name', 'items' );

	public const DTR_TOKEN_GROUP = 'token_group';
	public const DTR_ENUM_GROUP  = array( 'color', 'typography', 'spacing', 'radius', 'shadow', 'component' );
	public const DTR_REQUIRED    = array( 'token_group', 'token_name', 'proposed_value', 'rationale', 'confidence' );

	public const SEO_REQUIRED = array( 'target_page_title_or_url', 'confidence' );

	public const ENUM_SEVERITY = array( 'low', 'medium', 'high' );

	/** @return array<int, string> */
	public static function required_top_level_keys(): array {
		return self::REQUIRED_TOP_LEVEL_KEYS;
	}

	/** @return array<int, string> */
	public static function array_sections(): array {
		return self::ARRAY_SECTIONS;
	}
}
