<?php
/**
 * Applies industry style presets through the existing global styling storage (industry-style-preset-application-contract.md).
 * Validates preset and token names; merges token values; no raw CSS or new selectors.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Applies a preset's token_values to global style settings. Admin-only; nonce and capability enforced by caller.
 */
final class Industry_Style_Preset_Application_Service {

	/** @var Industry_Style_Preset_Registry */
	private Industry_Style_Preset_Registry $preset_registry;

	/** @var Global_Style_Settings_Repository */
	private Global_Style_Settings_Repository $style_repository;

	/** @var Style_Token_Registry|null */
	private ?Style_Token_Registry $token_registry;

	public function __construct(
		Industry_Style_Preset_Registry $preset_registry,
		Global_Style_Settings_Repository $style_repository,
		?Style_Token_Registry $token_registry = null
	) {
		$this->preset_registry  = $preset_registry;
		$this->style_repository = $style_repository;
		$this->token_registry   = $token_registry;
	}

	/**
	 * Applies the preset by key: merges token_values into global tokens, records applied preset. Fails safely on invalid key or data.
	 *
	 * @param string $preset_key style_preset_key from registry.
	 * @return bool True if applied and saved; false if preset not found, invalid, or write failed.
	 */
	public function apply_preset( string $preset_key ): bool {
		$key = trim( $preset_key );
		if ( $key === '' ) {
			return false;
		}
		$preset = $this->preset_registry->get( $key );
		if ( $preset === null ) {
			return false;
		}
		$status = $preset[ Industry_Style_Preset_Registry::FIELD_STATUS ] ?? '';
		if ( $status !== Industry_Style_Preset_Registry::STATUS_ACTIVE ) {
			return false;
		}
		$token_values = $preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] ?? null;
		if ( ! is_array( $token_values ) || empty( $token_values ) ) {
			$this->record_applied_preset( $key, (string) ( $preset[ Industry_Style_Preset_Registry::FIELD_LABEL ] ?? '' ) );
			return true;
		}
		$var_to_group_name = $this->build_variable_to_group_name_map();
		if ( empty( $var_to_group_name ) ) {
			return false;
		}
		$current = $this->style_repository->get_global_tokens();
		foreach ( $token_values as $var_name => $value ) {
			if ( ! is_string( $var_name ) || ! is_string( $value ) ) {
				continue;
			}
			$var_name = trim( $var_name );
			if ( ! isset( $var_to_group_name[ $var_name ] ) ) {
				continue;
			}
			list( $group, $name ) = $var_to_group_name[ $var_name ];
			if ( ! isset( $current[ $group ] ) || ! is_array( $current[ $group ] ) ) {
				$current[ $group ] = array();
			}
			$current[ $group ][ $name ] = $value;
		}
		$ok = $this->style_repository->set_global_tokens( $current );
		if ( $ok ) {
			$this->record_applied_preset( $key, (string) ( $preset[ Industry_Style_Preset_Registry::FIELD_LABEL ] ?? '' ) );
		}
		return $ok;
	}

	/**
	 * Clears the recorded applied preset. Does not revert token values (admin may use Global Style Tokens reset).
	 *
	 * @return void
	 */
	public function clear_applied_preset(): void {
		\delete_option( Option_Names::APPLIED_INDUSTRY_PRESET );
	}

	/**
	 * Returns the currently applied preset info, or null if none.
	 *
	 * @return array{preset_key: string, label: string, applied_at: string}|null
	 */
	public function get_applied_preset(): ?array {
		$raw = \get_option( Option_Names::APPLIED_INDUSTRY_PRESET, null );
		if ( ! is_array( $raw ) || empty( $raw['preset_key'] ) ) {
			return null;
		}
		return array(
			'preset_key' => (string) $raw['preset_key'],
			'label'      => (string) ( $raw['label'] ?? '' ),
			'applied_at' => (string) ( $raw['applied_at'] ?? '' ),
		);
	}

	/**
	 * Builds map: full token variable name (--aio-*) => [ group, name ].
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	private function build_variable_to_group_name_map(): array {
		$map = array();
		if ( $this->token_registry === null || ! $this->token_registry->is_loaded() ) {
			return $map;
		}
		$groups = $this->token_registry->get_token_group_names();
		foreach ( $groups as $group ) {
			if ( $group === 'component' ) {
				continue;
			}
			$names = $this->token_registry->get_allowed_names_for_group( $group );
			foreach ( $names as $name ) {
				$var_name = $this->token_registry->get_token_variable_name( $group, $name );
				if ( $var_name !== '' ) {
					$map[ $var_name ] = array( $group, $name );
				}
			}
		}
		return $map;
	}

	private function record_applied_preset( string $preset_key, string $label ): void {
		\update_option( Option_Names::APPLIED_INDUSTRY_PRESET, array(
			'preset_key' => $preset_key,
			'label'      => $label,
			'applied_at' => \gmdate( 'Y-m-d\TH:i:s\Z' ),
		) );
	}
}
