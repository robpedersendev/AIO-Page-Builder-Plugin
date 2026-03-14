<?php
/**
 * Aggregates provider-backed form references from ordered section results at the page level (Prompt 229, form-provider-integration-contract).
 * Used for preview metadata, diagnostics, compare/detail views, and scoped provider asset detection.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\FormProviders;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;

/**
 * Collects (form_provider, form_id) pairs from section render results so callers can:
 * - Expose form dependencies in preview/detail/compare state
 * - Scope provider asset enqueue to pages that contain form sections
 * - Validate/sanitize provider and form_id before any shortcode construction
 */
final class Page_Form_Reference_Aggregator {

	/** @var Form_Provider_Registry */
	private Form_Provider_Registry $provider_registry;

	public function __construct( Form_Provider_Registry $provider_registry ) {
		$this->provider_registry = $provider_registry;
	}

	/**
	 * Aggregates unique (form_provider, form_id) references from ordered section results.
	 * Only includes pairs where provider is registered and form_id is non-empty; excludes invalid refs.
	 *
	 * @param array<Section_Render_Result|array> $ordered_section_results Section render results (object or to_array() shape).
	 * @return list<array{form_provider: string, form_id: string}>
	 */
	public function aggregate( array $ordered_section_results ): array {
		$seen = array();
		$out  = array();
		foreach ( $ordered_section_results as $item ) {
			$fields = $this->field_values_from_item( $item );
			if ( $fields === null ) {
				continue;
			}
			$provider = trim( (string) ( $fields[ Form_Provider_Registry::FIELD_FORM_PROVIDER ] ?? '' ) );
			$form_id  = trim( (string) ( $fields[ Form_Provider_Registry::FIELD_FORM_ID ] ?? '' ) );
			if ( $provider === '' || $form_id === '' ) {
				continue;
			}
			if ( ! $this->provider_registry->has_provider( $provider ) ) {
				continue;
			}
			$key = $provider . '|' . $form_id;
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$out[] = array(
				'form_provider' => $provider,
				'form_id'       => $form_id,
			);
		}
		return $out;
	}

	/**
	 * Extracts field_values from a section result item (object or array).
	 *
	 * @param Section_Render_Result|array $item
	 * @return array<string, mixed>|null
	 */
	private function field_values_from_item( $item ): ?array {
		if ( $item instanceof Section_Render_Result ) {
			return $item->get_field_values();
		}
		if ( is_array( $item ) && isset( $item['field_values'] ) && is_array( $item['field_values'] ) ) {
			return $item['field_values'];
		}
		return null;
	}
}
