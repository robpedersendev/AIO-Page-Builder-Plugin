<?php
/**
 * Read-only result of ACF uninstall inventory (acf-uninstall-inventory-contract).
 * Holds plugin-owned group keys, field definitions, value meta keys, and cleanup artifacts.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Uninstall;

defined( 'ABSPATH' ) || exit;

/**
 * Value object for the inventory produced by ACF_Uninstall_Inventory_Service.
 * Immutable; no mutation of live ACF or post meta.
 */
final class ACF_Uninstall_Inventory_Result {

	/** @var array<int, string> */
	private array $plugin_runtime_group_keys;

	/** @var array<int, array{group_key: string, field_key: string, field_name: string}> */
	private array $field_definitions;

	/** @var array<int, string> */
	private array $value_meta_keys;

	/** @var array<int, string> */
	private array $persistent_group_keys;

	/** @var array<int, string> */
	private array $cleanup_transient_prefixes;

	/** @var array<int, string> */
	private array $cleanup_option_keys;

	/**
	 * @param array<int, string>                                                          $plugin_runtime_group_keys
	 * @param array<int, array{group_key: string, field_key: string, field_name: string}> $field_definitions
	 * @param array<int, string>                                                          $value_meta_keys
	 * @param array<int, string>                                                          $persistent_group_keys
	 * @param array<int, string>                                                          $cleanup_transient_prefixes
	 * @param array<int, string>                                                          $cleanup_option_keys
	 */
	public function __construct(
		array $plugin_runtime_group_keys,
		array $field_definitions,
		array $value_meta_keys,
		array $persistent_group_keys,
		array $cleanup_transient_prefixes,
		array $cleanup_option_keys
	) {
		$this->plugin_runtime_group_keys  = array_values( $plugin_runtime_group_keys );
		$this->field_definitions          = array_values( $field_definitions );
		$this->value_meta_keys            = array_values( array_unique( $value_meta_keys ) );
		$this->persistent_group_keys      = array_values( $persistent_group_keys );
		$this->cleanup_transient_prefixes = array_values( $cleanup_transient_prefixes );
		$this->cleanup_option_keys        = array_values( $cleanup_option_keys );
	}

	/** @return array<int, string> */
	public function get_plugin_runtime_group_keys(): array {
		return $this->plugin_runtime_group_keys;
	}

	/** @return array<int, array{group_key: string, field_key: string, field_name: string}> */
	public function get_field_definitions(): array {
		return $this->field_definitions;
	}

	/** @return array<int, string> */
	public function get_value_meta_keys(): array {
		return $this->value_meta_keys;
	}

	/** @return array<int, string> */
	public function get_persistent_group_keys(): array {
		return $this->persistent_group_keys;
	}

	/** @return array<int, string> */
	public function get_cleanup_transient_prefixes(): array {
		return $this->cleanup_transient_prefixes;
	}

	/** @return array<int, string> */
	public function get_cleanup_option_keys(): array {
		return $this->cleanup_option_keys;
	}
}
