<?php
/**
 * Shared deprecation transition helpers (spec §12.15, §13.13, §58.2).
 * Validates reason and replacement references; builds normalized deprecation metadata.
 * Callers must enforce capability and nonce checks before mutation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Shared;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Centralized deprecation validation and metadata. Does not mutate; returns blocks for registry services to apply.
 * Uses repositories to avoid circular dependency with registry services.
 */
final class Registry_Deprecation_Service {

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repository;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $page_template_repository;

	public function __construct(
		Section_Template_Repository $section_repository,
		Page_Template_Repository $page_template_repository
	) {
		$this->section_repository       = $section_repository;
		$this->page_template_repository = $page_template_repository;
	}

	/**
	 * Validates section deprecation transition. Reason required; replacement must point to valid active section.
	 *
	 * @param int    $post_id         Section post ID.
	 * @param string $reason          Required deprecation reason.
	 * @param string $replacement_key Optional replacement section internal_key.
	 * @return Registry_Validation_Result
	 */
	public function validate_section_deprecation(
		int $post_id,
		string $reason,
		string $replacement_key = ''
	): Registry_Validation_Result {
		$codes  = array();
		$errors = array();

		$reason = \sanitize_text_field( $reason );
		if ( $reason === '' ) {
			$errors[] = 'Deprecation reason is required';
			$codes[]  = Registry_Validation_Result::CODE_DEPRECATION_REASON_REQUIRED;
			return Registry_Validation_Result::invalid( $errors, $codes );
		}

		$existing = $this->section_repository->get_definition_by_id( $post_id );
		if ( $existing === null ) {
			$errors[] = 'Section not found';
			$codes[]  = Registry_Validation_Result::CODE_REFERENCE_MISSING;
			return Registry_Validation_Result::invalid( $errors, $codes );
		}

		if ( $replacement_key !== '' ) {
			$replacement_key = $this->sanitize_key( $replacement_key );
			if ( $replacement_key !== '' ) {
				$replacement = $this->section_repository->get_definition_by_key( $replacement_key );
				if ( $replacement === null ) {
					$errors[] = 'Replacement section does not exist';
					$codes[]  = Registry_Validation_Result::CODE_REPLACEMENT_INVALID;
					return Registry_Validation_Result::invalid( $errors, $codes );
				}
				$rep_status = (string) ( $replacement['status'] ?? '' );
				if ( $rep_status === 'deprecated' ) {
					$errors[] = 'Replacement section must not be deprecated';
					$codes[]  = Registry_Validation_Result::CODE_REPLACEMENT_DEPRECATED;
					return Registry_Validation_Result::invalid( $errors, $codes );
				}
			}
		}

		return Registry_Validation_Result::valid();
	}

	/**
	 * Validates page template deprecation transition.
	 *
	 * @param int    $post_id
	 * @param string $reason
	 * @param string $replacement_key
	 * @return Registry_Validation_Result
	 */
	public function validate_page_template_deprecation(
		int $post_id,
		string $reason,
		string $replacement_key = ''
	): Registry_Validation_Result {
		$codes  = array();
		$errors = array();

		$reason = \sanitize_text_field( $reason );
		if ( $reason === '' ) {
			$errors[] = 'Deprecation reason is required';
			$codes[]  = Registry_Validation_Result::CODE_DEPRECATION_REASON_REQUIRED;
			return Registry_Validation_Result::invalid( $errors, $codes );
		}

		$existing = $this->page_template_repository->get_definition_by_id( $post_id );
		if ( $existing === null ) {
			$errors[] = 'Page template not found';
			$codes[]  = Registry_Validation_Result::CODE_REFERENCE_MISSING;
			return Registry_Validation_Result::invalid( $errors, $codes );
		}

		if ( $replacement_key !== '' ) {
			$replacement_key = $this->sanitize_key( $replacement_key );
			if ( $replacement_key !== '' ) {
				$replacement = $this->page_template_repository->get_definition_by_key( $replacement_key );
				if ( $replacement === null ) {
					$errors[] = 'Replacement template does not exist';
					$codes[]  = Registry_Validation_Result::CODE_REPLACEMENT_INVALID;
					return Registry_Validation_Result::invalid( $errors, $codes );
				}
				$rep_status = (string) ( $replacement['status'] ?? '' );
				if ( $rep_status === 'deprecated' ) {
					$errors[] = 'Replacement template must not be deprecated';
					$codes[]  = Registry_Validation_Result::CODE_REPLACEMENT_DEPRECATED;
					return Registry_Validation_Result::invalid( $errors, $codes );
				}
			}
		}

		return Registry_Validation_Result::valid();
	}

	/**
	 * Returns deprecation block for section (applies to definition before save).
	 *
	 * @param string $reason
	 * @param string $replacement_key
	 * @return array<string, mixed>
	 */
	public function get_section_deprecation_block( string $reason, string $replacement_key = '' ): array {
		$block = Deprecation_Metadata::for_section( $reason, $replacement_key );
		if ( $replacement_key !== '' ) {
			$key                                      = $this->sanitize_key( $replacement_key );
			$block['replacement_section_suggestions'] = array( $key );
		}
		return $block;
	}

	/**
	 * Returns deprecation block for page template.
	 *
	 * @param string $reason
	 * @param string $replacement_key
	 * @return array<string, mixed>
	 */
	public function get_page_template_deprecation_block( string $reason, string $replacement_key = '' ): array {
		$block = Deprecation_Metadata::for_page_template( $reason, $replacement_key );
		if ( $replacement_key !== '' ) {
			$key                                = $this->sanitize_key( $replacement_key );
			$block['replacement_template_refs'] = array( $key );
		}
		return $block;
	}

	private function sanitize_key( string $key ): string {
		$key = \sanitize_text_field( strtolower( $key ) );
		return substr( preg_replace( '/[^a-z0-9_]/', '', $key ), 0, 64 );
	}
}
