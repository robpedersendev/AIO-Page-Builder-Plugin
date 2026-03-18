<?php
/**
 * Admin controls for enabling/disabling industry packs (industry-pack-activation-contract.md, Prompt 389).
 * Reads/writes disabled pack keys; exposes is_pack_active. Profile data is preserved; fallback is generic when pack is disabled.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Manages disabled industry pack list. Toggle action must be capability- and nonce-gated by the caller.
 */
final class Industry_Pack_Toggle_Controller {

	private const STORAGE_KEY = Option_Names::DISABLED_INDUSTRY_PACKS;

	/** @var Settings_Service */
	private Settings_Service $settings;

	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Returns the list of disabled industry pack keys (industry_key). Stored as a list of strings; empty array when none.
	 *
	 * @return list<string>
	 */
	public function get_disabled_pack_keys(): array {
		$raw = $this->settings->get( self::STORAGE_KEY );
		if ( ! \is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $value ) {
			if ( \is_string( $value ) && \trim( $value ) !== '' ) {
				$out[] = \trim( $value );
			}
		}
		return \array_values( \array_unique( $out ) );
	}

	/**
	 * Whether the given industry pack is active (not in the disabled list).
	 *
	 * @param string $industry_key Industry pack key.
	 * @return bool
	 */
	public function is_pack_active( string $industry_key ): bool {
		$key = \trim( $industry_key );
		if ( $key === '' ) {
			return true;
		}
		return ! \in_array( $key, $this->get_disabled_pack_keys(), true );
	}

	/**
	 * Sets a pack's disabled state. Callers must enforce capability and nonce.
	 *
	 * @param string $industry_key Industry pack key.
	 * @param bool   $disabled     True to disable, false to enable.
	 * @return void
	 */
	public function set_pack_disabled( string $industry_key, bool $disabled ): void {
		$key = \trim( $industry_key );
		if ( $key === '' ) {
			return;
		}
		$current = $this->get_disabled_pack_keys();
		$exists  = \in_array( $key, $current, true );
		if ( $disabled && ! $exists ) {
			$current[] = $key;
		}
		if ( ! $disabled && $exists ) {
			$current = \array_values(
				\array_filter(
					$current,
					function ( $k ) use ( $key ) {
						return $k !== $key;
					}
				)
			);
		}
		$this->settings->set( self::STORAGE_KEY, $current );
	}
}
