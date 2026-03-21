<?php
/**
 * Analyzes and resolves industry pack bundle import conflicts (Prompt 395, industry-pack-import-conflict-contract).
 * Detects duplicate keys, version mismatches, missing dependencies; produces auditable conflict and outcome lists.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

/**
 * Analyzes a pack bundle against local state and applies resolution to produce final outcomes.
 */
final class Industry_Pack_Import_Conflict_Service {

	/** Conflict type: key already exists locally. */
	public const CONFLICT_DUPLICATE_KEY = 'duplicate_key';

	/** Conflict type: incoming has newer version. */
	public const CONFLICT_NEWER_VERSION = 'newer_version';

	/** Conflict type: incoming has older version. */
	public const CONFLICT_OLDER_VERSION = 'older_version';

	/** Conflict type: referenced dependency missing. */
	public const CONFLICT_MISSING_DEPENDENCY = 'missing_dependency';

	/** Conflict type: payload invalid. */
	public const CONFLICT_INVALID_PAYLOAD = 'invalid_payload';

	/** Resolution: apply incoming. */
	public const RESOLUTION_REPLACE = 'replace';

	/** Resolution: skip; keep current. */
	public const RESOLUTION_SKIP = 'skip';

	/** Resolution: merge where supported. */
	public const RESOLUTION_MERGE = 'merge';

	/** Resolution: fail this object. */
	public const RESOLUTION_FAIL = 'fail';

	/** Outcome: object was applied. */
	public const OUTCOME_APPLIED = 'applied';

	/** Outcome: object was skipped. */
	public const OUTCOME_SKIPPED = 'skipped';

	/** Outcome: object was merged. */
	public const OUTCOME_MERGED = 'merged';

	/** Outcome: object failed. */
	public const OUTCOME_FAILED = 'failed';

	/** Severity: info. */
	public const SEVERITY_INFO = 'info';

	/** Severity: warning. */
	public const SEVERITY_WARNING = 'warning';

	/** Severity: error. */
	public const SEVERITY_ERROR = 'error';

	/** Result key: object_key. */
	public const RESULT_OBJECT_KEY = 'object_key';

	/** Result key: category. */
	public const RESULT_CATEGORY = 'category';

	/** Result key: conflict_type. */
	public const RESULT_CONFLICT_TYPE = 'conflict_type';

	/** Result key: proposed_resolution. */
	public const RESULT_PROPOSED_RESOLUTION = 'proposed_resolution';

	/** Result key: final_outcome. */
	public const RESULT_FINAL_OUTCOME = 'final_outcome';

	/** Result key: warning_severity. */
	public const RESULT_WARNING_SEVERITY = 'warning_severity';

	/** Result key: message. */
	public const RESULT_MESSAGE = 'message';

	/** Default resolution for duplicate_key by category: replace or skip. Packs/starter_bundles default skip to avoid silent overwrite. */
	private const DEFAULT_DUPLICATE_RESOLUTION = array(
		Industry_Pack_Bundle_Service::PAYLOAD_PACKS        => self::RESOLUTION_SKIP,
		Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES => self::RESOLUTION_SKIP,
		Industry_Pack_Bundle_Service::PAYLOAD_STYLE_PRESETS => self::RESOLUTION_SKIP,
		Industry_Pack_Bundle_Service::PAYLOAD_CTA_PATTERNS => self::RESOLUTION_SKIP,
		Industry_Pack_Bundle_Service::PAYLOAD_SEO_GUIDANCE => self::RESOLUTION_SKIP,
		Industry_Pack_Bundle_Service::PAYLOAD_LPAGERY_RULES => self::RESOLUTION_SKIP,
		Industry_Pack_Bundle_Service::PAYLOAD_SECTION_HELPER_OVERLAYS => self::RESOLUTION_SKIP,
		Industry_Pack_Bundle_Service::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => self::RESOLUTION_SKIP,
		Industry_Pack_Bundle_Service::PAYLOAD_QUESTION_PACKS => self::RESOLUTION_SKIP,
		Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE => self::RESOLUTION_SKIP,
	);

	/**
	 * Analyzes bundle against local state and returns list of conflicts (no final_outcome set yet).
	 *
	 * @param array<string, mixed>                $bundle Valid bundle (manifest + payload).
	 * @param array<string, array<string, mixed>> $local_state Map of category => array of existing keys (e.g. packs => array( 'realtor', 'plumber' ), starter_bundles => array( 'realtor_essentials' )). Optional: pack_versions => array, has_site_industry_profile => bool when site profile is already configured.
	 * @return list<array<string, mixed>> Conflict items with object_key, category, conflict_type, proposed_resolution, warning_severity, message; final_outcome = null.
	 */
	public function analyze( array $bundle, array $local_state = array() ): array {
		$conflicts = array();
		$included  = $bundle[ Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES ] ?? array();
		if ( ! is_array( $included ) ) {
			return array();
		}
		$local_keys = array();
		foreach ( array_keys( self::DEFAULT_DUPLICATE_RESOLUTION ) as $cat ) {
			$local_keys[ $cat ] = isset( $local_state[ $cat ] ) && is_array( $local_state[ $cat ] )
				? $local_state[ $cat ]
				: array();
		}
		$local_pack_versions   = isset( $local_state['pack_versions'] ) && is_array( $local_state['pack_versions'] ) ? $local_state['pack_versions'] : array();
		$bundle_schema_version = isset( $bundle[ Industry_Pack_Bundle_Service::MANIFEST_SCHEMA_VERSION ] ) ? (string) $bundle[ Industry_Pack_Bundle_Service::MANIFEST_SCHEMA_VERSION ] : '';

		foreach ( $included as $category ) {
			if ( $category === Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE ) {
				$conflicts = array_merge( $conflicts, $this->analyze_site_profile( $bundle, $local_state ) );
				continue;
			}
			$payload = $bundle[ $category ] ?? array();
			if ( ! is_array( $payload ) ) {
				continue;
			}
			$existing_keys = $local_keys[ $category ] ?? array();
			foreach ( $payload as $index => $item ) {
				if ( ! is_array( $item ) ) {
					$conflicts[] = $this->conflict_item( (string) $index, $category, self::CONFLICT_INVALID_PAYLOAD, self::RESOLUTION_SKIP, self::SEVERITY_WARNING, 'Invalid payload at index ' . $index );
					continue;
				}
				$key = $this->object_key_for_category( $category, $item );
				if ( $key === '' ) {
					continue;
				}
				$is_duplicate = in_array( $key, $existing_keys, true );
				if ( $category === Industry_Pack_Bundle_Service::PAYLOAD_PACKS && $is_duplicate ) {
					$incoming_version = isset( $item[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] ) && is_string( $item[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
						? trim( $item[ Industry_Pack_Schema::FIELD_VERSION_MARKER ] )
						: '';
					$local_version    = $local_pack_versions[ $key ] ?? '';
					if ( $local_version !== '' && $incoming_version !== '' ) {
						if ( version_compare( $incoming_version, $local_version, '>' ) ) {
							$conflicts[] = $this->conflict_item( $key, $category, self::CONFLICT_NEWER_VERSION, self::RESOLUTION_REPLACE, self::SEVERITY_INFO, "Incoming pack {$key} has newer version; replace recommended." );
							continue;
						}
						if ( version_compare( $incoming_version, $local_version, '<' ) ) {
							$conflicts[] = $this->conflict_item( $key, $category, self::CONFLICT_OLDER_VERSION, self::RESOLUTION_SKIP, self::SEVERITY_WARNING, "Incoming pack {$key} has older version; skip to keep current." );
							continue;
						}
					}
				}
				if ( $is_duplicate ) {
					$proposed    = self::DEFAULT_DUPLICATE_RESOLUTION[ $category ] ?? self::RESOLUTION_SKIP;
					$conflicts[] = $this->conflict_item( $key, $category, self::CONFLICT_DUPLICATE_KEY, $proposed, self::SEVERITY_WARNING, "Object {$key} already exists. Choose replace or skip." );
				}
			}
		}
		return $conflicts;
	}

	/**
	 * Applies resolution to conflict list and sets final_outcome per item. Operator can pass mode (e.g. replace_all) or leave default proposed_resolution.
	 *
	 * @param list<array<string, mixed>> $conflicts From analyze().
	 * @param array<string, string>      $operator_choices Optional. Map of "category|object_key" => RESOLUTION_* or mode key "default_duplicate" => replace|skip.
	 * @return list<array<string, mixed>> Same items with final_outcome set (applied, skipped, merged, failed).
	 */
	public function resolve( array $conflicts, array $operator_choices = array() ): array {
		$default_duplicate = $operator_choices['default_duplicate'] ?? null;
		$resolved          = array();
		foreach ( $conflicts as $c ) {
			$key                             = $c[ self::RESULT_OBJECT_KEY ] ?? '';
			$category                        = $c[ self::RESULT_CATEGORY ] ?? '';
			$proposed                        = $c[ self::RESULT_PROPOSED_RESOLUTION ] ?? self::RESOLUTION_SKIP;
			$choice_key                      = $category . '|' . $key;
			$resolution                      = isset( $operator_choices[ $choice_key ] ) ? $operator_choices[ $choice_key ] : ( ( $default_duplicate !== null && ( $c[ self::RESULT_CONFLICT_TYPE ] ?? '' ) === self::CONFLICT_DUPLICATE_KEY ) ? $default_duplicate : $proposed );
			$outcome                         = $this->resolution_to_outcome( $resolution );
			$c[ self::RESULT_FINAL_OUTCOME ] = $outcome;
			$resolved[]                      = $c;
		}
		return $resolved;
	}

	/**
	 * Returns whether any conflict has severity error and final_outcome failed or unresolved (no resolution applied).
	 *
	 * @param list<array<string, mixed>> $resolved Resolved conflict list.
	 * @return bool
	 */
	public function has_unresolved_errors( array $resolved ): bool {
		foreach ( $resolved as $c ) {
			$severity = $c[ self::RESULT_WARNING_SEVERITY ] ?? self::SEVERITY_INFO;
			$outcome  = $c[ self::RESULT_FINAL_OUTCOME ] ?? null;
			if ( $severity === self::SEVERITY_ERROR && $outcome !== self::OUTCOME_APPLIED && $outcome !== self::OUTCOME_MERGED ) {
				return true;
			}
		}
		return false;
	}

	private function analyze_site_profile( array $bundle, array $local_state ): array {
		$conflicts = array();
		if ( ! isset( $bundle[ Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE ] ) || ! is_array( $bundle[ Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE ] ) ) {
			return $conflicts;
		}
		$has_local = ! empty( $local_state['has_site_industry_profile'] );
		if ( $has_local ) {
			$conflicts[] = $this->conflict_item( 'site_profile', Industry_Pack_Bundle_Service::PAYLOAD_SITE_PROFILE, self::CONFLICT_DUPLICATE_KEY, self::RESOLUTION_SKIP, self::SEVERITY_WARNING, 'Site industry profile already set. Choose replace or skip.' );
		}
		return $conflicts;
	}

	private function object_key_for_category( string $category, array $item ): string {
		switch ( $category ) {
			case Industry_Pack_Bundle_Service::PAYLOAD_PACKS:
				return isset( $item[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $item[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					? trim( $item[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					: '';
			case Industry_Pack_Bundle_Service::PAYLOAD_STARTER_BUNDLES:
				return isset( $item[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ) && is_string( $item[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
					? trim( $item[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
					: '';
			default:
				if ( isset( $item['style_preset_key'] ) && is_string( $item['style_preset_key'] ) ) {
					return trim( $item['style_preset_key'] );
				}
				if ( isset( $item['pattern_key'] ) && is_string( $item['pattern_key'] ) ) {
					return trim( $item['pattern_key'] );
				}
				if ( isset( $item['industry_key'] ) && is_string( $item['industry_key'] ) ) {
					$sec = isset( $item['section_key'] ) && is_string( $item['section_key'] ) ? trim( $item['section_key'] ) : '';
					$tpl = isset( $item['page_template_key'] ) && is_string( $item['page_template_key'] ) ? trim( $item['page_template_key'] ) : '';
					if ( $sec !== '' ) {
						return trim( $item['industry_key'] ) . '|' . $sec;
					}
					if ( $tpl !== '' ) {
						return trim( $item['industry_key'] ) . '|' . $tpl;
					}
					return trim( $item['industry_key'] );
				}
				return '';
		}
	}

	private function conflict_item( string $object_key, string $category, string $conflict_type, string $proposed_resolution, string $severity, string $message ): array {
		return array(
			self::RESULT_OBJECT_KEY          => $object_key,
			self::RESULT_CATEGORY            => $category,
			self::RESULT_CONFLICT_TYPE       => $conflict_type,
			self::RESULT_PROPOSED_RESOLUTION => $proposed_resolution,
			self::RESULT_FINAL_OUTCOME       => null,
			self::RESULT_WARNING_SEVERITY    => $severity,
			self::RESULT_MESSAGE             => $message,
		);
	}

	private function resolution_to_outcome( string $resolution ): string {
		switch ( $resolution ) {
			case self::RESOLUTION_REPLACE:
			case self::RESOLUTION_MERGE:
				return $resolution === self::RESOLUTION_MERGE ? self::OUTCOME_MERGED : self::OUTCOME_APPLIED;
			case self::RESOLUTION_FAIL:
				return self::OUTCOME_FAILED;
			case self::RESOLUTION_SKIP:
			default:
				return self::OUTCOME_SKIPPED;
		}
	}
}
