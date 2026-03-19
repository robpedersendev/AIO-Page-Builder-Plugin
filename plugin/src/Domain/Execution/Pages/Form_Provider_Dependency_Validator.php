<?php
/**
 * Validates that page templates using provider-backed form sections have required form providers registered (Prompt 230, spec §33.8, §40.4).
 * Used before build/replacement to block execution when provider is missing; also surfaces warnings in Build Plan UI.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Pages;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Returns validation result: valid, errors (blocking), warnings (advisory).
 * Does not mutate state; safe to call before build or replacement.
 */
final class Form_Provider_Dependency_Validator {

	/** @var Form_Provider_Registry */
	private Form_Provider_Registry $provider_registry;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $page_repository;

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repository;

	public function __construct(
		Form_Provider_Registry $provider_registry,
		Page_Template_Repository $page_repository,
		Section_Template_Repository $section_repository
	) {
		$this->provider_registry  = $provider_registry;
		$this->page_repository    = $page_repository;
		$this->section_repository = $section_repository;
	}

	/**
	 * Validates that any form_embed sections in the template have their (default or required) provider registered.
	 *
	 * @param string $template_key Page template internal_key.
	 * @return array{valid: bool, errors: array<int, string>, warnings: array<int, string>}
	 */
	public function validate_for_template( string $template_key ): array {
		$errors       = array();
		$warnings     = array();
		$template_key = \sanitize_key( $template_key );
		if ( $template_key === '' ) {
			return array(
				'valid'    => true,
				'errors'   => array(),
				'warnings' => array(),
			);
		}

		$page_def = $this->page_repository->get_definition_by_key( $template_key );
		if ( $page_def === null || empty( $page_def ) ) {
			return array(
				'valid'    => true,
				'errors'   => array(),
				'warnings' => array(),
			);
		}

		$ordered = $page_def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		if ( ! is_array( $ordered ) ) {
			return array(
				'valid'    => true,
				'errors'   => array(),
				'warnings' => array(),
			);
		}

		foreach ( $ordered as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$section_key = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			if ( $section_key === '' ) {
				continue;
			}
			$section_def = $this->section_repository->get_definition_by_key( $section_key );
			if ( $section_def === null || empty( $section_def ) ) {
				continue;
			}
			$category = (string) ( $section_def[ Section_Schema::FIELD_CATEGORY ] ?? '' );
			if ( $category !== 'form_embed' ) {
				continue;
			}
			$provider = $this->get_default_form_provider_for_section( $section_def );
			if ( $provider === '' ) {
				$warnings[] = sprintf(
					/* translators: 1: section key */
					__( 'Form section "%1$s" does not specify a default provider; ensure one is set when editing the page.', 'aio-page-builder' ),
					$section_key
				);
				continue;
			}
			if ( ! $this->provider_registry->has_provider( $provider ) ) {
				$errors[] = sprintf(
					/* translators: 1: provider id, 2: template key */
					__( 'Form provider "%1$s" is not registered. The template "%2$s" uses a form section that requires this provider. Activate the provider plugin or choose another template.', 'aio-page-builder' ),
					$provider,
					$template_key
				);
			}
		}

		return array(
			'valid'    => empty( $errors ),
			'errors'   => $errors,
			'warnings' => $warnings,
		);
	}

	/**
	 * Extracts default form_provider from section definition (field_blueprint first field with name form_provider).
	 *
	 * @param array<string, mixed> $section_def
	 * @return string
	 */
	private function get_default_form_provider_for_section( array $section_def ): string {
		$blueprint = $section_def['field_blueprint'] ?? null;
		if ( ! is_array( $blueprint ) || empty( $blueprint['fields'] ) || ! is_array( $blueprint['fields'] ) ) {
			return 'ndr_forms';
		}
		foreach ( $blueprint['fields'] as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$name = (string) ( $field['name'] ?? $field['key'] ?? '' );
			if ( $name === Form_Provider_Registry::FIELD_FORM_PROVIDER ) {
				$default = $field['default_value'] ?? '';
				return is_string( $default ) && trim( $default ) !== '' ? trim( $default ) : 'ndr_forms';
			}
		}
		return 'ndr_forms';
	}

	/**
	 * Returns whether the page template uses at least one form_embed section (for closure/trace metadata).
	 *
	 * @param string $template_key Page template internal_key.
	 * @return bool
	 */
	public function template_uses_form_sections( string $template_key ): bool {
		$template_key = \sanitize_key( $template_key );
		if ( $template_key === '' ) {
			return false;
		}
		$page_def = $this->page_repository->get_definition_by_key( $template_key );
		if ( $page_def === null || empty( $page_def ) ) {
			return false;
		}
		$ordered = $page_def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
		if ( ! is_array( $ordered ) ) {
			return false;
		}
		foreach ( $ordered as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$section_key = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			if ( $section_key === '' ) {
				continue;
			}
			$section_def = $this->section_repository->get_definition_by_key( $section_key );
			if ( $section_def === null || empty( $section_def ) ) {
				continue;
			}
			$category = (string) ( $section_def[ Section_Schema::FIELD_CATEGORY ] ?? '' );
			if ( $category === 'form_embed' ) {
				return true;
			}
		}
		return false;
	}
}
