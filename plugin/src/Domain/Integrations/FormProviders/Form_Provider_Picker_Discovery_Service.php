<?php
/**
 * Discovers form providers that support the picker adapter contract (Prompt 236).
 * Exposes provider metadata, availability, form-list vs fallback, and normalized picker payloads.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Integrations\FormProviders;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;

/**
 * Discovery layer: which providers have picker adapters, availability, normalized picker state.
 * Picker responses are sanitized; provider-returned labels/ids treated as untrusted.
 */
final class Form_Provider_Picker_Discovery_Service {

	/** Pattern for allowed form_id (same as registry). */
	private const FORM_ID_PATTERN = '/^[a-zA-Z0-9_\-]+$/';

	/** @var Form_Provider_Registry */
	private Form_Provider_Registry $registry;

	/**
	 * provider_key => Form_Provider_Picker_Adapter_Interface
	 *
	 * @var array<string, Form_Provider_Picker_Adapter_Interface>
	 */
	private array $adapters = array();

	public function __construct(
		Form_Provider_Registry $registry,
		array $adapters = array()
	) {
		$this->registry = $registry;
		foreach ( $adapters as $key => $adapter ) {
			if ( $adapter instanceof Form_Provider_Picker_Adapter_Interface ) {
				$this->adapters[ $adapter->get_provider_key() ] = $adapter;
			}
		}
	}

	/**
	 * Registers a picker adapter (e.g. from container).
	 *
	 * @param Form_Provider_Picker_Adapter_Interface $adapter
	 * @return void
	 */
	public function register_adapter( Form_Provider_Picker_Adapter_Interface $adapter ): void {
		$this->adapters[ $adapter->get_provider_key() ] = $adapter;
	}

	/**
	 * Provider keys that have an adapter and are registered and available.
	 *
	 * @return array<int, string>
	 */
	public function get_providers_with_picker_support(): array {
		$out = array();
		foreach ( $this->adapters as $key => $adapter ) {
			if ( $this->registry->has_provider( $key ) && $adapter->is_available() ) {
				$out[] = $key;
			}
		}
		return $out;
	}

	/**
	 * Normalized picker state for a provider (display label, availability, form list or fallback).
	 *
	 * @param string $provider_key
	 * @return array{
	 *   provider_key: string,
	 *   display_label: string,
	 *   available: bool,
	 *   supports_form_list: bool,
	 *   picker_items: array<int, array{provider_key: string, item_id: string, item_label: string, status_hint?: string}>,
	 *   fallback_entry_label: string,
	 *   empty_state_message: string
	 * }
	 */
	public function get_picker_state_for_provider( string $provider_key ): array {
		$provider_key       = $this->sanitize_provider_key( $provider_key );
		$adapter            = $this->adapters[ $provider_key ] ?? null;
		$available          = $adapter !== null && $this->registry->has_provider( $provider_key ) && $adapter->is_available();
		$display_label      = $adapter !== null ? $adapter->get_display_label() : $provider_key;
		$supports_form_list = $adapter !== null && $adapter->supports_form_list();
		$picker_items       = array();
		if ( $adapter !== null && $supports_form_list ) {
			$picker_items = $this->normalize_picker_items( $provider_key, $adapter->get_form_list() );
		}
		$fallback_label      = $adapter !== null ? $adapter->get_fallback_entry_label() : __( 'Form ID', 'aio-page-builder' );
		$empty_state_message = $adapter !== null && ! $supports_form_list
			? __( 'Enter the form identifier from your form manager.', 'aio-page-builder' )
			: ( $supports_form_list && empty( $picker_items ) ? __( 'No forms available from this provider.', 'aio-page-builder' ) : '' );

		return array(
			'provider_key'         => $provider_key,
			'display_label'        => $display_label,
			'available'            => $available,
			'supports_form_list'   => $supports_form_list,
			'picker_items'         => $picker_items,
			'fallback_entry_label' => $fallback_label,
			'empty_state_message'  => $empty_state_message,
		);
	}

	/**
	 * Metadata-only picker info (no form list fetch). Use with availability layer to avoid double fetch.
	 *
	 * @param string $provider_key
	 * @return array{provider_key: string, display_label: string, available: bool, supports_form_list: bool, fallback_entry_label: string, empty_state_message: string}
	 */
	public function get_picker_metadata_for_provider( string $provider_key ): array {
		$provider_key        = $this->sanitize_provider_key( $provider_key );
		$adapter             = $this->adapters[ $provider_key ] ?? null;
		$available           = $adapter !== null && $this->registry->has_provider( $provider_key ) && $adapter->is_available();
		$display_label       = $adapter !== null ? $adapter->get_display_label() : $provider_key;
		$supports_form_list  = $adapter !== null && $adapter->supports_form_list();
		$fallback_label      = $adapter !== null ? $adapter->get_fallback_entry_label() : __( 'Form ID', 'aio-page-builder' );
		$empty_state_message = $adapter !== null && ! $supports_form_list
			? __( 'Enter the form identifier from your form manager.', 'aio-page-builder' )
			: ( $supports_form_list ? __( 'No forms available from this provider.', 'aio-page-builder' ) : '' );

		return array(
			'provider_key'         => $provider_key,
			'display_label'        => $display_label,
			'available'            => $available,
			'supports_form_list'   => $supports_form_list,
			'fallback_entry_label' => $fallback_label,
			'empty_state_message'  => $empty_state_message,
		);
	}

	/**
	 * Whether the provider has a registered picker adapter.
	 *
	 * @param string $provider_key
	 * @return bool
	 */
	public function has_adapter( string $provider_key ): bool {
		return isset( $this->adapters[ $this->sanitize_provider_key( $provider_key ) ] );
	}

	/**
	 * Returns the adapter for a provider (for use by availability/cache layer).
	 *
	 * @param string $provider_key
	 * @return Form_Provider_Picker_Adapter_Interface|null
	 */
	public function get_adapter( string $provider_key ): ?Form_Provider_Picker_Adapter_Interface {
		$key = $this->sanitize_provider_key( $provider_key );
		return $this->adapters[ $key ] ?? null;
	}

	/**
	 * Normalizes raw form list items for a provider (item_id pattern, escaped label). Public for availability/cache layer.
	 *
	 * @param string                                                                                        $provider_key
	 * @param array<int, array{provider_key?: string, item_id: string, item_label: string, status_hint?: string}> $items
	 * @return array<int, array{provider_key: string, item_id: string, item_label: string, status_hint?: string|null}>
	 */
	public function normalize_picker_items_for_provider( string $provider_key, array $items ): array {
		return $this->normalize_picker_items( $this->sanitize_provider_key( $provider_key ), $items );
	}

	/**
	 * Checks if current form_id is stale for the given provider (when adapter exists).
	 *
	 * @param string $provider_key
	 * @param string $form_id
	 * @return bool
	 */
	public function is_item_stale( string $provider_key, string $form_id ): bool {
		$provider_key = $this->sanitize_provider_key( $provider_key );
		$adapter      = $this->adapters[ $provider_key ] ?? null;
		return $adapter !== null ? $adapter->is_item_stale( $form_id ) : false;
	}

	/**
	 * Sanitizes and filters picker items (item_id pattern; item_label escaped for display).
	 *
	 * @param string                                                                                       $provider_key
	 * @param array<int, array{provider_key: string, item_id: string, item_label: string, status_hint?: string}> $items
	 * @return array<int, array{provider_key: string, item_id: string, item_label: string, status_hint?: string}>
	 */
	private function normalize_picker_items( string $provider_key, array $items ): array {
		$out = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$item_id = isset( $item['item_id'] ) && is_string( $item['item_id'] ) ? trim( $item['item_id'] ) : '';
			if ( $item_id === '' || ! preg_match( self::FORM_ID_PATTERN, $item_id ) ) {
				continue;
			}
			$item_label = isset( $item['item_label'] ) && is_string( $item['item_label'] ) ? $item['item_label'] : $item_id;
			$out[]      = array(
				'provider_key' => $provider_key,
				'item_id'      => $item_id,
				'item_label'   => \esc_html( $item_label ),
				'status_hint'  => isset( $item['status_hint'] ) && is_string( $item['status_hint'] ) ? \esc_html( $item['status_hint'] ) : null,
			);
		}
		return $out;
	}

	private function sanitize_provider_key( string $key ): string {
		$key = preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) );
		return is_string( $key ) ? $key : '';
	}
}
