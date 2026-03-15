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

	/** @var list<string> */
	private array $plugin_runtime_group_keys;

	/** @var list<array{group_key: string, field_key: string, field_name: string}> */
	private array $field_definitions;

	/** @var list<string> */
	private array $value_meta_keys;

	/** @var list<string> */
	private array $persistent_group_keys;

	/** @var list<string> */
	private array $cleanup_transient_prefixes;

	/** @var list<string> */
	private array $cleanup_option_keys;

	/**
	 * @param list<string>                                                                 $plugin_runtime_group_keys
	 * @param list<array{group_key: string, field_key: string, field_name: string}>       $field_definitions
	 * @param list<string>                                                                 $value_meta_keys
	 * @param list<string>                                                                 $persistent_group_keys
	 * @param list<string>                                                                 $cleanup_transient_prefixes
	 * @param list<string>                                                                 $cleanup_option_keys
	 */
	public function __construct(
		array $plugin_runtime_group_keys,
		array $field_definitions,
		array $value_meta_keys,
		array $persistent_group_keys,
		array $cleanup_transient_prefixes,
		array $cleanup_option_keys
	) {
		$this->plugin_runtime_group_keys   = array_values( $plugin_runtime_group_keys );
		$this->field_definitions           = array_values( $field_definitions );
		$this->value_meta_keys             = array_values( array_unique( $value_meta_keys ) );
		$this->persistent_group_keys       = array_values( $persistent_group_keys );
		$this->cleanup_transient_prefixes  = array_values( $cleanup_transient_prefixes );
		$this->cleanup_option_keys         = array_values( $cleanup_option_keys );
	}

	/** @return list<string> */
	public function get_plugin_runtime_group_keys(): array {
		return $this->plugin_runtime_group_keys;
	}

	/** @return list<array{group_key: string, field_key: string, field_name: string}> */
	public function get_field_definitions(): array {
		return $this->field_definitions;
	}

	/** @return list<string> */
	public function get_value_meta_keys(): array {
		return $this->value_meta_keys;
	}

	/** @return list<string> */
	public function get_persistent_group_keys(): array {
		return $this->persistent_group_keys;
	}

	/** @return list<string> */
	public function get_cleanup_transient_prefixes(): array {
		return $this->cleanup_transient_prefixes;
	}

	/** @return list<string> */
	public function get_cleanup_option_keys(): array {
		return $this->cleanup_option_keys;
	}
}
