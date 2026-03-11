<?php
/**
 * Prompt pack schema constants and required keys (prompt-pack-schema.md, spec §26, §10.6).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks;

defined( 'ABSPATH' ) || exit;

/**
 * Declarative constants for prompt-pack root and segment keys. No runtime execution logic.
 */
final class Prompt_Pack_Schema {

	public const ROOT_INTERNAL_KEY         = 'internal_key';
	public const ROOT_NAME                 = 'name';
	public const ROOT_VERSION              = 'version';
	public const ROOT_PACK_TYPE            = 'pack_type';
	public const ROOT_STATUS               = 'status';
	public const ROOT_SEGMENTS             = 'segments';
	public const ROOT_SCHEMA_TARGET_REF    = 'schema_target_ref';
	public const ROOT_REPAIR_PROMPT_REF    = 'repair_prompt_ref';
	public const ROOT_PLACEHOLDER_RULES    = 'placeholder_rules';
	public const ROOT_PROVIDER_COMPATIBILITY = 'provider_compatibility';
	public const ROOT_ARTIFACT_REFS        = 'artifact_refs';
	public const ROOT_REDACTION            = 'redaction';
	public const ROOT_CHANGELOG            = 'changelog';
	public const ROOT_DEPRECATION          = 'deprecation';

	public const SEGMENT_SYSTEM_BASE                = 'system_base';
	public const SEGMENT_ROLE_FRAMING              = 'role_framing';
	public const SEGMENT_PLANNING_INSTRUCTIONS     = 'planning_instructions';
	public const SEGMENT_SCHEMA_REQUIREMENTS       = 'schema_requirements';
	public const SEGMENT_SITE_ANALYSIS_INSTRUCTIONS = 'site_analysis_instructions';
	public const SEGMENT_SAFETY_INSTRUCTIONS       = 'safety_instructions';
	public const SEGMENT_NORMALIZATION_EXPECTATIONS = 'normalization_expectations';
	public const SEGMENT_PROVIDER_NOTES            = 'provider_notes';

	public const STATUS_ACTIVE     = 'active';
	public const STATUS_INACTIVE   = 'inactive';
	public const STATUS_DEPRECATED = 'deprecated';

	public const PACK_TYPE_PLANNING = 'planning';
	public const PACK_TYPE_REPAIR    = 'repair';
	public const PACK_TYPE_SUMMARY   = 'summary';
	public const PACK_TYPE_OTHER    = 'other';

	public const PLACEHOLDER_SOURCE_PROFILE  = 'profile';
	public const PLACEHOLDER_SOURCE_REGISTRY = 'registry';
	public const PLACEHOLDER_SOURCE_CRAWL     = 'crawl';
	public const PLACEHOLDER_SOURCE_GOAL      = 'goal';
	public const PLACEHOLDER_SOURCE_CUSTOM    = 'custom';

	/** @return array<int, string> */
	public static function required_root_keys(): array {
		return array(
			self::ROOT_INTERNAL_KEY,
			self::ROOT_NAME,
			self::ROOT_VERSION,
			self::ROOT_PACK_TYPE,
			self::ROOT_STATUS,
			self::ROOT_SEGMENTS,
		);
	}

	/** @return array<int, string> */
	public static function required_segment_keys(): array {
		return array( self::SEGMENT_SYSTEM_BASE );
	}

	/** @return array<int, string> */
	public static function valid_statuses(): array {
		return array( self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DEPRECATED );
	}

	/** @return array<int, string> */
	public static function valid_pack_types(): array {
		return array( self::PACK_TYPE_PLANNING, self::PACK_TYPE_REPAIR, self::PACK_TYPE_SUMMARY, self::PACK_TYPE_OTHER );
	}

	/** @return array<int, string> */
	public static function valid_placeholder_sources(): array {
		return array(
			self::PLACEHOLDER_SOURCE_PROFILE,
			self::PLACEHOLDER_SOURCE_REGISTRY,
			self::PLACEHOLDER_SOURCE_CRAWL,
			self::PLACEHOLDER_SOURCE_GOAL,
			self::PLACEHOLDER_SOURCE_CUSTOM,
		);
	}
}
