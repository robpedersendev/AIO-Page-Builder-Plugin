<?php
/**
 * Composition definition schema (spec §10.3, object-model §3.3, composition-validation-state-machine.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Composition;

defined( 'ABSPATH' ) || exit;

/**
 * Required/optional field names for composition object. Ordered section items use section_key, position, variant.
 */
final class Composition_Schema {

	/** Unique composition identifier (e.g. UUID); immutable. */
	public const FIELD_COMPOSITION_ID = 'composition_id';

	/** Human-readable composition name. */
	public const FIELD_NAME = 'name';

	/** Ordered section list: array of { section_key, position, variant? }. */
	public const FIELD_ORDERED_SECTION_LIST = 'ordered_section_list';

	/** Lifecycle status: draft | active | archived. */
	public const FIELD_STATUS = 'status';

	/** Validation result: pending_validation | valid | warning | validation_failed | deprecated_context. */
	public const FIELD_VALIDATION_STATUS = 'validation_status';

	/** Optional: page template internal_key if derived from template. */
	public const FIELD_SOURCE_TEMPLATE_REF = 'source_template_ref';

	/** Optional: composition_id of source if duplicated. */
	public const FIELD_DUPLICATED_FROM_COMPOSITION_ID = 'duplicated_from_composition_id';

	/** Optional: registry snapshot reference at creation/last validation. */
	public const FIELD_REGISTRY_SNAPSHOT_REF = 'registry_snapshot_ref_at_creation';

	/** Optional: helper one-pager documentation ref. */
	public const FIELD_HELPER_ONE_PAGER_REF = 'helper_one_pager_ref';

	/** Validation codes from last run (for explainability). */
	public const FIELD_VALIDATION_CODES = 'validation_codes';

	/** Ordered section item: section template internal_key. */
	public const SECTION_ITEM_KEY = 'section_key';

	/** Ordered section item: zero-based position. */
	public const SECTION_ITEM_POSITION = 'position';

	/** Ordered section item: variant key (optional). */
	public const SECTION_ITEM_VARIANT = 'variant';

	/** @var list<string> */
	private static ?array $required_fields = null;

	/**
	 * @return list<string>
	 */
	public static function get_required_fields(): array {
		if ( self::$required_fields !== null ) {
			return self::$required_fields;
		}
		self::$required_fields = array(
			self::FIELD_COMPOSITION_ID,
			self::FIELD_NAME,
			self::FIELD_ORDERED_SECTION_LIST,
			self::FIELD_STATUS,
			self::FIELD_VALIDATION_STATUS,
		);
		return self::$required_fields;
	}

	/**
	 * Pattern for composition_id (UUID or slug).
	 *
	 * @var string
	 */
	public const COMPOSITION_ID_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for composition_id. */
	public const COMPOSITION_ID_MAX_LENGTH = 64;
}
