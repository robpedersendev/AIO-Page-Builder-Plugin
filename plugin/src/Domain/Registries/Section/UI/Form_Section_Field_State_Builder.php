<?php
/**
 * Builds display/editor state for provider-backed form section fields (form_provider, form_id, headline).
 * Used by section template detail screens and any UI that surfaces or edits form bindings (Prompt 228, form-provider-integration-contract).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Picker_Discovery_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Produces state for form section fields: registered provider list, current values, validation flags,
 * and messages for missing-provider, missing-form, or stale-form. Optional picker discovery adds
 * picker_states for adapter-driven UI (Prompt 236). Additive; does not change stored values.
 */
final class Form_Section_Field_State_Builder {

	/** @var Form_Provider_Registry */
	private Form_Provider_Registry $provider_registry;

	/** @var Form_Provider_Picker_Discovery_Service|null */
	private ?Form_Provider_Picker_Discovery_Service $picker_discovery;

	public function __construct(
		Form_Provider_Registry $provider_registry,
		?Form_Provider_Picker_Discovery_Service $picker_discovery = null
	) {
		$this->provider_registry = $provider_registry;
		$this->picker_discovery  = $picker_discovery;
	}

	/**
	 * Builds form section field state for display or editor context.
	 *
	 * @param array<string, mixed> $definition Section definition (category form_embed; may include default or saved field context).
	 * @param array<string, mixed> $field_values Optional saved/current values: form_provider, form_id, headline.
	 * @return array{
	 *   is_form_section: bool,
	 *   registered_provider_ids: list<string>,
	 *   form_provider: string,
	 *   form_id: string,
	 *   headline: string,
	 *   provider_valid: bool,
	 *   form_id_valid: bool,
	 *   shortcode_preview: string|null,
	 *   messages: list<string>,
	 *   labels: array{form_provider: string, form_id: string, headline: string},
	 *   picker_states: array<string, array>|null (when picker_discovery set)
	 * }
	 */
	public function build_state( array $definition, array $field_values = array() ): array {
		$category = (string) ( $definition[ Section_Schema::FIELD_CATEGORY ] ?? '' );
		$is_form_section = $category === 'form_embed';

		$registered = $this->provider_registry->get_registered_provider_ids();
		$form_provider = \sanitize_text_field( (string) ( $field_values[ Form_Provider_Registry::FIELD_FORM_PROVIDER ] ?? '' ) );
		if ( $is_form_section && $form_provider === '' ) {
			$form_provider = 'ndr_forms';
		}
		$form_id       = \sanitize_text_field( (string) ( $field_values[ Form_Provider_Registry::FIELD_FORM_ID ] ?? '' ) );
		$headline      = \sanitize_text_field( (string) ( $field_values['headline'] ?? '' ) );

		$provider_valid = $form_provider !== '' && $this->provider_registry->has_provider( $form_provider );
		$form_id_valid  = $form_id !== '' && preg_match( '/^[a-zA-Z0-9_\-]+$/', $form_id );
		$shortcode_preview = null;
		$messages = array();

		if ( $is_form_section ) {
			if ( $form_provider === '' ) {
				$messages[] = __( 'Form provider is not set. Choose a registered provider.', 'aio-page-builder' );
			} elseif ( ! $provider_valid ) {
				$messages[] = __( 'Selected form provider is not registered. Re-activate the provider plugin or choose another.', 'aio-page-builder' );
			}
			if ( $form_id === '' ) {
				$messages[] = __( 'Form ID is not set. Enter the form identifier from your form manager.', 'aio-page-builder' );
			} elseif ( ! $form_id_valid ) {
				$messages[] = __( 'Form ID contains invalid characters. Use letters, numbers, hyphens, and underscores only.', 'aio-page-builder' );
			}
			if ( $provider_valid && $form_id_valid ) {
				$shortcode_preview = $this->provider_registry->build_shortcode( $form_provider, $form_id );
			}
		}

		$state = array(
			'is_form_section'          => $is_form_section,
			'registered_provider_ids'  => $registered,
			'form_provider'           => $form_provider,
			'form_id'                 => $form_id,
			'headline'                => $headline,
			'provider_valid'         => $provider_valid,
			'form_id_valid'          => $form_id_valid,
			'shortcode_preview'       => $shortcode_preview,
			'messages'                => $messages,
			'labels'                  => array(
				'form_provider' => __( 'Form provider', 'aio-page-builder' ),
				'form_id'       => __( 'Form identifier', 'aio-page-builder' ),
				'headline'      => __( 'Heading (optional)', 'aio-page-builder' ),
			),
		);
		if ( $this->picker_discovery !== null && $is_form_section ) {
			$picker_states = array();
			foreach ( $registered as $pid ) {
				$picker_states[ $pid ] = $this->picker_discovery->get_picker_state_for_provider( $pid );
			}
			$state['picker_states'] = $picker_states;
		}
		return $state;
	}
}
