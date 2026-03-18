<?php
/**
 * Schema and defaults for global styling settings (Prompt 246).
 * Versioned option structure; no raw CSS or selectors.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Defines option key, top-level keys, schema version, and default payload.
 */
final class Global_Style_Settings_Schema {

	/** Option key for the global style settings blob. */
	public const OPTION_KEY = 'aio_global_style_settings';

	/** Top-level key: schema version for migration. */
	public const KEY_VERSION = 'version';

	/** Top-level key: global token values [ group => [ name => value ] ]. */
	public const KEY_GLOBAL_TOKENS = 'global_tokens';

	/** Top-level key: global component overrides [ component_id => [ token_var_name => value ] ]. */
	public const KEY_GLOBAL_COMPONENT_OVERRIDES = 'global_component_overrides';

	/** Current schema version. Bump when structure or semantics change. */
	public const SCHEMA_VERSION = '1';

	/**
	 * Returns the default full settings array (version + empty tokens + empty overrides).
	 *
	 * @return array{version: string, global_tokens: array<string, array<string, string>>, global_component_overrides: array<string, array<string, string>>}
	 */
	public static function get_defaults(): array {
		return array(
			self::KEY_VERSION                    => self::SCHEMA_VERSION,
			self::KEY_GLOBAL_TOKENS              => array(),
			self::KEY_GLOBAL_COMPONENT_OVERRIDES => array(),
		);
	}
}
