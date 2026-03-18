<?php
/**
 * Contract for form provider picker adapters (Prompt 236, form-provider-picker-adapter-contract.md).
 * Enables provider discovery, normalized picker items, and fallback-to-manual-entry when no form-list API.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Integrations\FormProviders;

defined( 'ABSPATH' ) || exit;

/**
 * Adapter for a single form provider: display label, availability, optional form list, stale detection, fallback.
 * Provider-returned labels and ids must be treated as untrusted until validated/sanitized by consumers.
 */
interface Form_Provider_Picker_Adapter_Interface {

	/**
	 * Provider key (matches Form_Provider_Registry; e.g. ndr_forms).
	 *
	 * @return string
	 */
	public function get_provider_key(): string;

	/**
	 * Human-readable label for UI (e.g. "NDR Form Manager").
	 *
	 * @return string
	 */
	public function get_display_label(): string;

	/**
	 * Whether the provider is available (e.g. plugin active, API reachable).
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Whether this provider exposes a form-list API for picker dropdowns.
	 *
	 * @return bool
	 */
	public function supports_form_list(): bool;

	/**
	 * List of forms for picker (normalized). Only called when supports_form_list() is true.
	 * Returned item_id and item_label are provider-supplied; consumers must validate/sanitize.
	 *
	 * @return list<array{provider_key: string, item_id: string, item_label: string, status_hint?: string}>
	 */
	public function get_form_list(): array;

	/**
	 * Whether the given form_id is stale (e.g. form deleted in provider).
	 *
	 * @param string $form_id Stored form identifier.
	 * @return bool
	 */
	public function is_item_stale( string $form_id ): bool;

	/**
	 * Label for manual form_id entry when no form list (e.g. "Form ID (from Form Manager)").
	 *
	 * @return string
	 */
	public function get_fallback_entry_label(): string;
}
