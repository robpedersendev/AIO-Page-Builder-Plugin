<?php
/**
 * Schema for per-entity style payloads (Prompt 251). Versioned; token and component override branches only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Defines the shape and version of per-entity style payloads. No raw CSS or selectors.
 */
final class Entity_Style_Payload_Schema {

	/** Option key for the aggregate per-entity payload store. */
	public const OPTION_KEY = 'aio_entity_style_payloads';

	/** Top-level key for schema version (migration support). */
	public const KEY_VERSION = 'version';

	/** Top-level key for payloads by entity type and key. */
	public const KEY_PAYLOADS = 'payloads';

	/** Payload branch: token overrides [ group => [ name => value ] ]. */
	public const KEY_TOKEN_OVERRIDES = 'token_overrides';

	/** Payload branch: component overrides [ component_id => [ token_var_name => value ] ]. */
	public const KEY_COMPONENT_OVERRIDES = 'component_overrides';

	/** Payload-level version (per-payload migration). */
	public const KEY_PAYLOAD_VERSION = 'version';

	/** Current schema version for the option. */
	public const SCHEMA_VERSION = '1';

	/** Current payload version for each entity payload. */
	public const PAYLOAD_VERSION = '1';

	/** Allowed entity types (render surfaces that may have style payloads). */
	public const ENTITY_TYPES = array( 'section_template', 'page_template' );

	/**
	 * Returns default structure for the option (version + empty payloads per type).
	 *
	 * @return array{version: string, payloads: array{section_template: array, page_template: array}}
	 */
	public static function get_default_option(): array {
		return array(
			self::KEY_VERSION  => self::SCHEMA_VERSION,
			self::KEY_PAYLOADS => array(
				'section_template' => array(),
				'page_template'    => array(),
			),
		);
	}

	/**
	 * Returns default structure for a single entity payload (version + empty branches).
	 *
	 * @return array{version: string, token_overrides: array, component_overrides: array}
	 */
	public static function get_default_payload(): array {
		return array(
			self::KEY_PAYLOAD_VERSION     => self::PAYLOAD_VERSION,
			self::KEY_TOKEN_OVERRIDES     => array(),
			self::KEY_COMPONENT_OVERRIDES => array(),
		);
	}

	/**
	 * Whether the given entity type is allowed.
	 *
	 * @param string $entity_type
	 * @return bool
	 */
	public static function is_allowed_entity_type( string $entity_type ): bool {
		return in_array( $entity_type, self::ENTITY_TYPES, true );
	}
}
