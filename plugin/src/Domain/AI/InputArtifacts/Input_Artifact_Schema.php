<?php
/**
 * AI input artifact schema constants and validation helpers (ai-input-artifact-schema.md, spec §27, §29.1).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\InputArtifacts;

defined( 'ABSPATH' ) || exit;

/**
 * Declarative constants for input artifact root and sections. No runtime assembly logic.
 */
final class Input_Artifact_Schema {

	public const ROOT_ARTIFACT_ID       = 'artifact_id';
	public const ROOT_SCHEMA_VERSION   = 'schema_version';
	public const ROOT_CREATED_AT       = 'created_at';
	public const ROOT_PROMPT_PACK_REF  = 'prompt_pack_ref';
	public const ROOT_PROFILE          = 'profile';
	public const ROOT_CRAWL            = 'crawl';
	public const ROOT_REGISTRY         = 'registry';
	public const ROOT_GOAL             = 'goal';
	public const ROOT_ATTACHMENT_MANIFEST = 'attachment_manifest';
	public const ROOT_REDACTION        = 'redaction';
	public const ROOT_INCLUSION_RATIONALE = 'inclusion_rationale';
	public const ROOT_COMPATIBILITY    = 'compatibility';

	public const PROMPT_PACK_REF_INTERNAL_KEY = 'internal_key';
	public const PROMPT_PACK_REF_VERSION       = 'version';

	public const PROFILE_SOURCE_SNAPSHOT_REF = 'snapshot_ref';
	public const PROFILE_SOURCE_PAYLOAD       = 'payload';
	public const CRAWL_SOURCE_RUN_REF         = 'run_ref';
	public const CRAWL_SOURCE_SUMMARY         = 'summary';
	public const CRAWL_SOURCE_BOTH            = 'both';
	public const REGISTRY_SOURCE_SNAPSHOT_REF = 'snapshot_ref';
	public const REGISTRY_SOURCE_SUMMARY      = 'summary';

	public const REDACTION_APPLIED   = 'redaction_applied';
	public const EXCLUDED_CATEGORIES = 'excluded_categories';
	public const PLACEHOLDER_USED   = 'placeholder_used';

	public const ATTACHMENT_FILE_ID          = 'file_id';
	public const ATTACHMENT_FILE_TYPE        = 'file_type';
	public const ATTACHMENT_SOURCE_CATEGORY  = 'source_category';
	public const ATTACHMENT_PURPOSE          = 'purpose';
	public const ATTACHMENT_REDACTION_STATUS = 'redaction_status';
	public const ATTACHMENT_ATTACHMENT_STATUS = 'attachment_status';
	public const ATTACHMENT_SIZE_BYTES       = 'size_bytes';
	public const ATTACHMENT_DOWNLOAD_ELIGIBLE = 'download_eligible';

	public const REDACTION_STATUS_NONE      = 'none';
	public const REDACTION_STATUS_REDACTED  = 'redacted';
	public const REDACTION_STATUS_EXCLUDED  = 'excluded';
	public const ATTACHMENT_STATUS_ATTACHED  = 'attached';
	public const ATTACHMENT_STATUS_REFERENCE_ONLY = 'reference_only';
	public const ATTACHMENT_STATUS_FAILED    = 'failed';
	public const SOURCE_CATEGORY_PROFILE_ASSET = 'profile_asset';
	public const SOURCE_CATEGORY_CRAWL_EXPORT  = 'crawl_export';
	public const SOURCE_CATEGORY_REGISTRY_EXPORT = 'registry_export';
	public const SOURCE_CATEGORY_USER_UPLOAD   = 'user_upload';
	public const SOURCE_CATEGORY_OTHER         = 'other';

	/** Prohibited keys that must not appear anywhere in the artifact. */
	private const PROHIBITED_KEYS = array(
		'api_key', 'secret', 'token', 'password', 'authorization',
		'apikey', 'client_secret', 'access_token', 'refresh_token', 'bearer_token',
	);

	/**
	 * Required root keys for a valid artifact (submission-eligible).
	 *
	 * @return array<int, string>
	 */
	public static function required_root_keys(): array {
		return array(
			self::ROOT_ARTIFACT_ID,
			self::ROOT_SCHEMA_VERSION,
			self::ROOT_CREATED_AT,
			self::ROOT_PROMPT_PACK_REF,
			self::ROOT_REDACTION,
		);
	}

	/**
	 * Required prompt_pack_ref keys.
	 *
	 * @return array<int, string>
	 */
	public static function required_prompt_pack_ref_keys(): array {
		return array( self::PROMPT_PACK_REF_INTERNAL_KEY, self::PROMPT_PACK_REF_VERSION );
	}

	/**
	 * Required redaction block keys.
	 *
	 * @return array<int, string>
	 */
	public static function required_redaction_keys(): array {
		return array( self::REDACTION_APPLIED );
	}

	/**
	 * Required attachment manifest entry keys.
	 *
	 * @return array<int, string>
	 */
	public static function required_attachment_entry_keys(): array {
		return array(
			self::ATTACHMENT_FILE_ID,
			self::ATTACHMENT_FILE_TYPE,
			self::ATTACHMENT_SOURCE_CATEGORY,
			self::ATTACHMENT_PURPOSE,
			self::ATTACHMENT_REDACTION_STATUS,
			self::ATTACHMENT_ATTACHMENT_STATUS,
		);
	}

	/**
	 * Prohibited key names (must not appear in artifact). Keys are lowercase.
	 *
	 * @return array<int, string>
	 */
	public static function prohibited_keys(): array {
		return self::PROHIBITED_KEYS;
	}

	/**
	 * Whether a key is prohibited anywhere in the artifact.
	 *
	 * @param string $key Key name (e.g. from array key).
	 * @return bool
	 */
	public static function is_prohibited_key( string $key ): bool {
		$key_lower = strtolower( $key );
		foreach ( self::PROHIBITED_KEYS as $prohibited ) {
			if ( $key_lower === $prohibited || str_contains( $key_lower, $prohibited ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks an array recursively for prohibited keys. Returns list of prohibited keys found (paths or key names).
	 *
	 * @param array<string, mixed> $data Artifact or section data.
	 * @param string               $path Current path prefix (for nested reporting).
	 * @return array<int, string> List of prohibited key paths found.
	 */
	public static function find_prohibited_keys_in_array( array $data, string $path = '' ): array {
		$found = array();
		foreach ( $data as $key => $value ) {
			if ( ! is_string( $key ) ) {
				continue;
			}
			$current_path = $path !== '' ? $path . '.' . $key : $key;
			if ( self::is_prohibited_key( $key ) ) {
				$found[] = $current_path;
			}
			if ( is_array( $value ) ) {
				$found = array_merge(
					$found,
					self::find_prohibited_keys_in_array( $value, $current_path )
				);
			}
		}
		return $found;
	}
}
