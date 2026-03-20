<?php
/**
 * Build Plan item object schema constants (spec §30.5, build-plan-schema.md §6).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Item field names and item_type enum. Machine-readable; stable.
 */
final class Build_Plan_Item_Schema {

	/** Required item fields. */
	public const KEY_ITEM_ID   = 'item_id';
	public const KEY_ITEM_TYPE = 'item_type';
	public const KEY_PAYLOAD   = 'payload';

	/** Optional item fields (dependency, blocking, provenance). */
	public const KEY_DEPENDS_ON_ITEM_IDS = 'depends_on_item_ids';
	public const KEY_BLOCKS_ITEM_IDS     = 'blocks_item_ids';
	public const KEY_BLOCKING            = 'blocking';
	public const KEY_STATUS              = 'status';
	public const KEY_SOURCE_SECTION      = 'source_section';
	public const KEY_SOURCE_INDEX        = 'source_index';
	public const KEY_CONFIDENCE          = 'confidence';
	public const KEY_RISK_LEVEL          = 'risk_level';

	/** Item type enum (build-plan-schema.md §6.1). */
	public const ITEM_TYPE_EXISTING_PAGE_CHANGE = 'existing_page_change';
	public const ITEM_TYPE_NEW_PAGE             = 'new_page';
	public const ITEM_TYPE_MENU_CHANGE          = 'menu_change';
	/** Net-new menu creation (v2). payload: menu_name, ?theme_location, ?items. Emits CREATE_MENU envelope. */
	public const ITEM_TYPE_MENU_NEW             = 'menu_new';
	public const ITEM_TYPE_DESIGN_TOKEN         = 'design_token';
	public const ITEM_TYPE_SEO                  = 'seo';
	public const ITEM_TYPE_HIERARCHY_NOTE       = 'hierarchy_note';
	/** Executable hierarchy assignment (v2). payload: page_id, parent_page_id. Emits ASSIGN_PAGE_HIERARCHY envelope. */
	public const ITEM_TYPE_HIERARCHY_ASSIGNMENT = 'hierarchy_assignment';
	public const ITEM_TYPE_OVERVIEW_NOTE        = 'overview_note';
	public const ITEM_TYPE_CONFIRMATION         = 'confirmation';

	public const ITEM_TYPES = array(
		self::ITEM_TYPE_EXISTING_PAGE_CHANGE,
		self::ITEM_TYPE_NEW_PAGE,
		self::ITEM_TYPE_MENU_CHANGE,
		self::ITEM_TYPE_MENU_NEW,
		self::ITEM_TYPE_DESIGN_TOKEN,
		self::ITEM_TYPE_SEO,
		self::ITEM_TYPE_HIERARCHY_NOTE,
		self::ITEM_TYPE_HIERARCHY_ASSIGNMENT,
		self::ITEM_TYPE_OVERVIEW_NOTE,
		self::ITEM_TYPE_CONFIRMATION,
	);

	/** Required item keys. */
	public const REQUIRED_ITEM_KEYS = array(
		self::KEY_ITEM_ID,
		self::KEY_ITEM_TYPE,
		self::KEY_PAYLOAD,
	);

	/** Step object required keys (build-plan-schema.md §5.1). */
	public const KEY_STEP_ID   = 'step_id';
	public const KEY_STEP_TYPE = 'step_type';
	public const KEY_TITLE     = 'title';
	public const KEY_ORDER     = 'order';
	public const KEY_ITEMS     = 'items';

	public const REQUIRED_STEP_KEYS = array(
		self::KEY_STEP_ID,
		self::KEY_STEP_TYPE,
		self::KEY_TITLE,
		self::KEY_ORDER,
		self::KEY_ITEMS,
	);

	/**
	 * Returns required item keys.
	 *
	 * @return array<int, string>
	 */
	public static function get_required_item_keys(): array {
		return self::REQUIRED_ITEM_KEYS;
	}

	/**
	 * Returns required step keys.
	 *
	 * @return array<int, string>
	 */
	public static function get_required_step_keys(): array {
		return self::REQUIRED_STEP_KEYS;
	}

	/**
	 * Returns whether item_type is valid.
	 */
	public static function is_valid_item_type( string $item_type ): bool {
		return in_array( $item_type, self::ITEM_TYPES, true );
	}
}
