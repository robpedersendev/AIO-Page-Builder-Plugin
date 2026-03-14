<?php
/**
 * Picker adapter for NDR Form Manager (Prompt 236). Conforms to Form_Provider_Picker_Adapter_Interface;
 * no form-list API — fallback to manual form_id entry.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Integrations\FormProviders;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;

/**
 * NDR Form Manager adapter: display label, availability from registry, no form list, fallback label.
 */
final class Ndr_Form_Provider_Picker_Adapter implements Form_Provider_Picker_Adapter_Interface {

	private const PROVIDER_KEY = 'ndr_forms';

	/** @var Form_Provider_Registry */
	private Form_Provider_Registry $registry;

	public function __construct( Form_Provider_Registry $registry ) {
		$this->registry = $registry;
	}

	/** @inheritdoc */
	public function get_provider_key(): string {
		return self::PROVIDER_KEY;
	}

	/** @inheritdoc */
	public function get_display_label(): string {
		return __( 'NDR Form Manager', 'aio-page-builder' );
	}

	/** @inheritdoc */
	public function is_available(): bool {
		return $this->registry->has_provider( self::PROVIDER_KEY );
	}

	/** @inheritdoc */
	public function supports_form_list(): bool {
		return false;
	}

	/** @inheritdoc */
	public function get_form_list(): array {
		return array();
	}

	/** @inheritdoc */
	public function is_item_stale( string $form_id ): bool {
		return false;
	}

	/** @inheritdoc */
	public function get_fallback_entry_label(): string {
		return __( 'Form ID (from Form Manager)', 'aio-page-builder' );
	}
}
