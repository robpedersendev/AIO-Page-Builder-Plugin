<?php
/**
 * Stable artifact category keys for AI runs (spec §29.1, §29.2–29.7). Raw vs normalized separation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Runs;

defined( 'ABSPATH' ) || exit;

/**
 * Category keys for artifact storage and retrieval. Do not flatten; each category is stored separately.
 */
final class Artifact_Category_Keys {

	/** §29.2 Raw prompt (capture-ready). */
	public const RAW_PROMPT = 'raw_prompt';

	/** Normalized prompt package (pre-provider). */
	public const NORMALIZED_PROMPT_PACKAGE = 'normalized_prompt_package';

	/** Input snapshot (artifact assembly). */
	public const INPUT_SNAPSHOT = 'input_snapshot';

	/** File attachment manifest. */
	public const FILE_MANIFEST = 'file_manifest';

	/** §29.3 Raw provider response. */
	public const RAW_PROVIDER_RESPONSE = 'raw_response';

	/** §29.4 Normalized output (validator output when passed/partial). */
	public const NORMALIZED_OUTPUT = 'normalized_output';

	/** Validation report. */
	public const VALIDATION_REPORT = 'validation_report';

	/** Dropped-record report (when partial). */
	public const DROPPED_RECORD_REPORT = 'dropped_record_report';

	/** Retry metadata (attempts, repair). */
	public const RETRY_METADATA = 'retry_metadata';

	/** §29.7 Usage/cost metadata. */
	public const USAGE_METADATA = 'usage_metadata';

	/** Optional second completion (expand pass) usage when present. */
	public const EXPAND_PASS_USAGE = 'expand_pass_usage';

	/** Placeholder for future build plan reference. */
	public const BUILD_PLAN_REF = 'build_plan_ref';

	/** All categories (for iteration). */
	public const ALL = array(
		self::RAW_PROMPT,
		self::NORMALIZED_PROMPT_PACKAGE,
		self::INPUT_SNAPSHOT,
		self::FILE_MANIFEST,
		self::RAW_PROVIDER_RESPONSE,
		self::NORMALIZED_OUTPUT,
		self::VALIDATION_REPORT,
		self::DROPPED_RECORD_REPORT,
		self::RETRY_METADATA,
		self::USAGE_METADATA,
		self::EXPAND_PASS_USAGE,
		self::BUILD_PLAN_REF,
	);

	/** Categories that must be redacted before user-facing display or export. */
	public const REDACT_BEFORE_DISPLAY = array(
		self::RAW_PROMPT,
		self::RAW_PROVIDER_RESPONSE,
		self::NORMALIZED_PROMPT_PACKAGE,
		self::INPUT_SNAPSHOT,
	);

	/**
	 * @return array<int, string>
	 */
	public static function all(): array {
		return self::ALL;
	}

	public static function is_valid( string $category ): bool {
		return in_array( $category, self::ALL, true );
	}
}
