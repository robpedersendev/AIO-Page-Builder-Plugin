<?php
/**
 * Registry for industry-specific onboarding question packs (industry-question-pack-contract).
 * Lookup by primary industry key; returns pack definition or null when unsupported.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Question pack registry. Read-only after load. get() returns null for unknown industry.
 */
final class Industry_Question_Pack_Registry {

	/** Pack definition: pack_id (same as industry_key for built-in packs). */
	public const FIELD_PACK_ID = 'pack_id';

	/** Pack definition: industry_key. */
	public const FIELD_INDUSTRY_KEY = 'industry_key';

	/** Pack definition: name. */
	public const FIELD_NAME = 'name';

	/** Pack definition: intent. */
	public const FIELD_INTENT = 'intent';

	/** Pack definition: fields list. */
	public const FIELD_FIELDS = 'fields';

	/** @var array<string, array<string, mixed>> industry_key => pack definition. */
	private array $by_industry = array();

	/**
	 * Loads pack definitions. Skips invalid entries (missing industry_key or fields). Duplicate industry_key: first wins.
	 *
	 * @param array<int, array<string, mixed>> $packs List of pack definitions.
	 * @return void
	 */
	public function load( array $packs ): void {
		$this->by_industry = array();
		foreach ( $packs as $pack ) {
			if ( ! is_array( $pack ) ) {
				continue;
			}
			$industry_key = isset( $pack[ self::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ self::FIELD_INDUSTRY_KEY ] )
				? trim( $pack[ self::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $industry_key === '' ) {
				continue;
			}
			if ( ! isset( $pack[ self::FIELD_FIELDS ] ) || ! is_array( $pack[ self::FIELD_FIELDS ] ) ) {
				continue;
			}
			if ( array_key_exists( $industry_key, $this->by_industry ) ) {
				continue;
			}
			$this->by_industry[ $industry_key ] = array(
				self::FIELD_PACK_ID      => isset( $pack[ self::FIELD_PACK_ID ] ) && is_string( $pack[ self::FIELD_PACK_ID ] ) ? trim( $pack[ self::FIELD_PACK_ID ] ) : $industry_key,
				self::FIELD_INDUSTRY_KEY => $industry_key,
				self::FIELD_NAME         => isset( $pack[ self::FIELD_NAME ] ) && is_string( $pack[ self::FIELD_NAME ] ) ? $pack[ self::FIELD_NAME ] : $industry_key,
				self::FIELD_INTENT       => isset( $pack[ self::FIELD_INTENT ] ) && is_string( $pack[ self::FIELD_INTENT ] ) ? $pack[ self::FIELD_INTENT ] : '',
				self::FIELD_FIELDS       => $pack[ self::FIELD_FIELDS ],
			);
		}
	}

	/**
	 * Returns question pack for industry key, or null if not found.
	 *
	 * @param string $industry_key Primary industry key (e.g. cosmetology_nail, realtor).
	 * @return array<string, mixed>|null Pack with pack_id, industry_key, name, intent, fields.
	 */
	public function get( string $industry_key ): ?array {
		$key = trim( $industry_key );
		return $this->by_industry[ $key ] ?? null;
	}

	/**
	 * Returns list of industry keys that have a question pack.
	 *
	 * @return list<string>
	 */
	public function get_supported_industry_keys(): array {
		return array_values( array_keys( $this->by_industry ) );
	}
}
