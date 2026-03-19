<?php
/**
 * Canonical documentation object schema for helper paragraphs and one-pagers (spec §10.7, §15–16, documentation-object-schema.md).
 * Consumed by future document generation and registry code; no generation or UI here.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Documentation;

defined( 'ABSPATH' ) || exit;

/**
 * Documentation types, required/optional field names, source reference keys, editing posture.
 * Documentation is a first-class product object with types, provenance, and lifecycle.
 */
final class Documentation_Schema {

	/** Section helper paragraph or block set (§15.1–15.4). */
	public const TYPE_SECTION_HELPER = 'section_helper';

	/** Page-template one-pager (§16.1). */
	public const TYPE_PAGE_TEMPLATE_ONE_PAGER = 'page_template_one_pager';

	/** Composition one-pager (§14.6). */
	public const TYPE_COMPOSITION_ONE_PAGER = 'composition_one_pager';

	/** Required: stable documentation identifier. */
	public const FIELD_DOCUMENTATION_ID = 'documentation_id';

	/** Required: documentation type. */
	public const FIELD_DOCUMENTATION_TYPE = 'documentation_type';

	/** Required: content body. */
	public const FIELD_CONTENT_BODY = 'content_body';

	/** Required: lifecycle status. */
	public const FIELD_STATUS = 'status';

	/** Optional: source reference block. */
	public const FIELD_SOURCE_REFERENCE = 'source_reference';

	/** Optional: generated or human-edited. */
	public const FIELD_GENERATED_OR_HUMAN_EDITED = 'generated_or_human_edited';

	/** Optional: version marker. */
	public const FIELD_VERSION_MARKER = 'version_marker';

	/** Optional: export metadata block. */
	public const FIELD_EXPORT_METADATA = 'export_metadata';

	/** Optional: provenance block. */
	public const FIELD_PROVENANCE = 'provenance';

	/** Optional: superseded by (documentation_id). */
	public const FIELD_SUPERSEDED_BY = 'superseded_by';

	/** Source reference: section template internal_key. */
	public const SOURCE_SECTION_TEMPLATE_KEY = 'section_template_key';

	/** Source reference: page template internal_key. */
	public const SOURCE_PAGE_TEMPLATE_KEY = 'page_template_key';

	/** Source reference: composition id. */
	public const SOURCE_COMPOSITION_ID = 'composition_id';

	/** Editing posture: system-generated. */
	public const EDITING_GENERATED = 'generated';

	/** Editing posture: human-written or edited. */
	public const EDITING_HUMAN_EDITED = 'human_edited';

	/** Editing posture: generated then human-refined. */
	public const EDITING_MIXED = 'mixed';

	/** @var array<int, string> Required field names. */
	private static ?array $required_fields = null;

	/** @var array<int, string> Optional field names. */
	private static ?array $optional_fields = null;

	/** @var array<int, string> Allowed documentation types. */
	private static ?array $documentation_types = null;

	/** @var array<int, string> Allowed status values. */
	private static ?array $statuses = null;

	/** @var array<int, string> Allowed generated_or_human_edited values. */
	private static ?array $editing_postures = null;

	/**
	 * Returns required field names.
	 *
	 * @return array<int, string>
	 */
	public static function get_required_fields(): array {
		if ( self::$required_fields !== null ) {
			return self::$required_fields;
		}
		self::$required_fields = array(
			self::FIELD_DOCUMENTATION_ID,
			self::FIELD_DOCUMENTATION_TYPE,
			self::FIELD_CONTENT_BODY,
			self::FIELD_STATUS,
		);
		return self::$required_fields;
	}

	/**
	 * Returns optional field names.
	 *
	 * @return array<int, string>
	 */
	public static function get_optional_fields(): array {
		if ( self::$optional_fields !== null ) {
			return self::$optional_fields;
		}
		self::$optional_fields = array(
			self::FIELD_SOURCE_REFERENCE,
			self::FIELD_GENERATED_OR_HUMAN_EDITED,
			self::FIELD_VERSION_MARKER,
			self::FIELD_EXPORT_METADATA,
			self::FIELD_PROVENANCE,
			self::FIELD_SUPERSEDED_BY,
		);
		return self::$optional_fields;
	}

	/**
	 * Returns allowed documentation_type values (spec §10.7, §15, §16, §14.6).
	 *
	 * @return array<int, string>
	 */
	public static function get_documentation_types(): array {
		if ( self::$documentation_types !== null ) {
			return self::$documentation_types;
		}
		self::$documentation_types = array(
			self::TYPE_SECTION_HELPER,
			self::TYPE_PAGE_TEMPLATE_ONE_PAGER,
			self::TYPE_COMPOSITION_ONE_PAGER,
		);
		return self::$documentation_types;
	}

	/**
	 * Returns whether the given documentation_type is allowed.
	 *
	 * @param string $type Documentation type value.
	 * @return bool
	 */
	public static function is_valid_documentation_type( string $type ): bool {
		return in_array( $type, self::get_documentation_types(), true );
	}

	/**
	 * Returns required source reference key for a documentation type.
	 *
	 * @param string $documentation_type One of TYPE_* constants.
	 * @return string Source reference field name (SOURCE_* constant), or empty string if unknown.
	 */
	public static function get_required_source_key_for_type( string $documentation_type ): string {
		switch ( $documentation_type ) {
			case self::TYPE_SECTION_HELPER:
				return self::SOURCE_SECTION_TEMPLATE_KEY;
			case self::TYPE_PAGE_TEMPLATE_ONE_PAGER:
				return self::SOURCE_PAGE_TEMPLATE_KEY;
			case self::TYPE_COMPOSITION_ONE_PAGER:
				return self::SOURCE_COMPOSITION_ID;
			default:
				return '';
		}
	}

	/**
	 * Returns allowed status values (object-model §3.7).
	 *
	 * @return array<int, string>
	 */
	public static function get_statuses(): array {
		if ( self::$statuses !== null ) {
			return self::$statuses;
		}
		self::$statuses = array( 'draft', 'active', 'archived' );
		return self::$statuses;
	}

	/**
	 * Returns whether the given status is allowed.
	 *
	 * @param string $status Status value.
	 * @return bool
	 */
	public static function is_valid_status( string $status ): bool {
		return in_array( $status, self::get_statuses(), true );
	}

	/**
	 * Returns allowed generated_or_human_edited values.
	 *
	 * @return array<int, string>
	 */
	public static function get_editing_postures(): array {
		if ( self::$editing_postures !== null ) {
			return self::$editing_postures;
		}
		self::$editing_postures = array(
			self::EDITING_GENERATED,
			self::EDITING_HUMAN_EDITED,
			self::EDITING_MIXED,
		);
		return self::$editing_postures;
	}

	/** Max length for documentation_id. */
	public const DOCUMENTATION_ID_MAX_LENGTH = 64;
}
