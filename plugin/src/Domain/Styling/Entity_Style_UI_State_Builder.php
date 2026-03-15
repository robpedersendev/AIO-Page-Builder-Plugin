<?php
/**
 * Builds UI state for per-entity style editing (Prompt 253): payload, field definitions, validation messages.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Forms\Entity_Style_Form_Builder;

/**
 * Produces state for the per-entity styling panel: current payload, field definitions, and validation feedback.
 */
final class Entity_Style_UI_State_Builder {

	/** Nonce action for entity style save. */
	public const NONCE_ACTION = 'aio_entity_style_save';

	/** POST action value for entity style save. */
	public const SAVE_ACTION = 'aio_entity_style_save';

	/** @var Entity_Style_Form_Builder */
	private Entity_Style_Form_Builder $form_builder;

	/** @var Entity_Style_Payload_Repository */
	private Entity_Style_Payload_Repository $payload_repository;

	public function __construct(
		Entity_Style_Form_Builder $form_builder,
		Entity_Style_Payload_Repository $payload_repository
	) {
		$this->form_builder       = $form_builder;
		$this->payload_repository = $payload_repository;
	}

	/**
	 * Builds state for the per-entity styling UI. Include last_result when re-displaying after a failed save.
	 *
	 * @param string                 $entity_type One of Entity_Style_Payload_Schema::ENTITY_TYPES.
	 * @param string                 $entity_key  Entity key (section_key or template_key).
	 * @param Style_Validation_Result|null $last_result Optional validation result from last save attempt.
	 * @return array{payload: array, token_fields_by_group: array, component_fields_by_component: array, validation_errors: list<string>, nonce_action: string, save_action: string, entity_type: string, entity_key: string}
	 */
	public function build_state( string $entity_type, string $entity_key, ?Style_Validation_Result $last_result = null ): array {
		$payload = $this->payload_repository->get_payload( $entity_type, $entity_key );
		$token_fields_by_group       = $this->form_builder->get_token_fields_by_group( $entity_type, $entity_key );
		$component_fields_by_component = $this->form_builder->get_component_fields_by_component( $entity_type, $entity_key );
		$validation_errors = $last_result !== null && ! $last_result->is_valid()
			? $last_result->get_errors()
			: array();
		return array(
			'payload'                      => $payload,
			'token_fields_by_group'         => $token_fields_by_group,
			'component_fields_by_component' => $component_fields_by_component,
			'validation_errors'             => $validation_errors,
			'nonce_action'                  => self::NONCE_ACTION,
			'save_action'                   => self::SAVE_ACTION,
			'entity_type'                   => $entity_type,
			'entity_key'                    => $entity_key,
		);
	}
}
