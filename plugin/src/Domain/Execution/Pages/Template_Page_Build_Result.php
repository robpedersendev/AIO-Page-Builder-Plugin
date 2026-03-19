<?php
/**
 * Stable execution result payload for template-driven new-page build (spec §33.5, §33.9, §17.7; Prompt 194).
 *
 * Immutable DTO for traceability: template_key, template_family, hierarchy, one-pager metadata,
 * field assignment count, section count, warnings, log_ref. Used for logging and rollback input recording.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable template page-build execution result. Convertible to array for artifacts and logging.
 *
 * Example template_build_execution_result payload (success):
 *
 * @code
 * array(
 *   'success'                  => true,
 *   'post_id'                  => 42,
 *   'template_key'             => 'tpl_services_hub',
 *   'template_family'          => 'services',
 *   'template_category_class'  => 'hub',
 *   'hierarchy_applied'        => true,
 *   'parent_post_id'            => 10,
 *   'one_pager_available'      => true,
 *   'one_pager_metadata'       => array( 'doc_ref' => 'one-pager-services-hub' ),
 *   'section_count'            => 5,
 *   'field_assignment_count'   => 3,
 *   'warnings'                 => array(),
 *   'errors'                   => array(),
 *   'log_ref'                  => 'log_abc',
 *   'message'                  => 'Page created.',
 * )
 * @endcode
 */
final class Template_Page_Build_Result {

	/** @var bool */
	private $success;

	/** @var int */
	private $post_id;

	/** @var string */
	private $template_key;

	/** @var string */
	private $template_family;

	/** @var string */
	private $template_category_class;

	/** @var bool */
	private $hierarchy_applied;

	/** @var int */
	private $parent_post_id;

	/** @var bool */
	private $one_pager_available;

	/** @var array<string, mixed> */
	private $one_pager_metadata;

	/** @var int */
	private $section_count;

	/** @var int */
	private $field_assignment_count;

	/** @var array<int, string> */
	private $warnings;

	/** @var array<int, string> */
	private $errors;

	/** @var string */
	private $log_ref;

	/** @var string */
	private $message;

	/**
	 * @param bool                 $success
	 * @param int                  $post_id                Created page ID; 0 on failure.
	 * @param string               $template_key
	 * @param string               $template_family
	 * @param string               $template_category_class
	 * @param bool                 $hierarchy_applied
	 * @param int                  $parent_post_id
	 * @param bool                 $one_pager_available
	 * @param array<string, mixed> $one_pager_metadata
	 * @param int                  $section_count
	 * @param int                  $field_assignment_count
	 * @param array<int, string>   $warnings
	 * @param array<int, string>   $errors
	 * @param string               $log_ref
	 * @param string               $message
	 */
	public function __construct(
		bool $success,
		int $post_id,
		string $template_key = '',
		string $template_family = '',
		string $template_category_class = '',
		bool $hierarchy_applied = false,
		int $parent_post_id = 0,
		bool $one_pager_available = false,
		array $one_pager_metadata = array(),
		int $section_count = 0,
		int $field_assignment_count = 0,
		array $warnings = array(),
		array $errors = array(),
		string $log_ref = '',
		string $message = ''
	) {
		$this->success                 = $success;
		$this->post_id                 = $post_id;
		$this->template_key            = $template_key;
		$this->template_family         = $template_family;
		$this->template_category_class = $template_category_class;
		$this->hierarchy_applied       = $hierarchy_applied;
		$this->parent_post_id          = $parent_post_id;
		$this->one_pager_available     = $one_pager_available;
		$this->one_pager_metadata      = $one_pager_metadata;
		$this->section_count           = $section_count;
		$this->field_assignment_count  = $field_assignment_count;
		$this->warnings                = $warnings;
		$this->errors                  = $errors;
		$this->log_ref                 = $log_ref;
		$this->message                 = $message;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_post_id(): int {
		return $this->post_id;
	}

	public function get_template_key(): string {
		return $this->template_key;
	}

	public function get_template_family(): string {
		return $this->template_family;
	}

	public function get_template_category_class(): string {
		return $this->template_category_class;
	}

	public function is_hierarchy_applied(): bool {
		return $this->hierarchy_applied;
	}

	public function get_parent_post_id(): int {
		return $this->parent_post_id;
	}

	public function is_one_pager_available(): bool {
		return $this->one_pager_available;
	}

	/** @return array<string, mixed> */
	public function get_one_pager_metadata(): array {
		return $this->one_pager_metadata;
	}

	public function get_section_count(): int {
		return $this->section_count;
	}

	public function get_field_assignment_count(): int {
		return $this->field_assignment_count;
	}

	/** @return array<int, string> */
	public function get_warnings(): array {
		return $this->warnings;
	}

	/** @return array<int, string> */
	public function get_errors(): array {
		return $this->errors;
	}

	public function get_log_ref(): string {
		return $this->log_ref;
	}

	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Stable payload for artifacts and logging (template_build_execution_result).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'success'                 => $this->success,
			'post_id'                 => $this->post_id,
			'template_key'            => $this->template_key,
			'template_family'         => $this->template_family,
			'template_category_class' => $this->template_category_class,
			'hierarchy_applied'       => $this->hierarchy_applied,
			'parent_post_id'          => $this->parent_post_id,
			'one_pager_available'     => $this->one_pager_available,
			'one_pager_metadata'      => $this->one_pager_metadata,
			'section_count'           => $this->section_count,
			'field_assignment_count'  => $this->field_assignment_count,
			'warnings'                => $this->warnings,
			'errors'                  => $this->errors,
			'log_ref'                 => $this->log_ref,
			'message'                 => $this->message,
		);
	}

	/**
	 * Builds a success result with full template and hierarchy metadata.
	 *
	 * @param int                  $post_id
	 * @param string               $template_key
	 * @param string               $template_family
	 * @param string               $template_category_class
	 * @param bool                 $hierarchy_applied
	 * @param int                  $parent_post_id
	 * @param bool                 $one_pager_available
	 * @param array<string, mixed> $one_pager_metadata
	 * @param int                  $section_count
	 * @param int                  $field_assignment_count
	 * @param array<int, string>   $warnings
	 * @param string               $log_ref
	 * @return self
	 */
	public static function success(
		int $post_id,
		string $template_key,
		string $template_family = '',
		string $template_category_class = '',
		bool $hierarchy_applied = false,
		int $parent_post_id = 0,
		bool $one_pager_available = false,
		array $one_pager_metadata = array(),
		int $section_count = 0,
		int $field_assignment_count = 0,
		array $warnings = array(),
		string $log_ref = ''
	): self {
		return new self(
			true,
			$post_id,
			$template_key,
			$template_family,
			$template_category_class,
			$hierarchy_applied,
			$parent_post_id,
			$one_pager_available,
			$one_pager_metadata,
			$section_count,
			$field_assignment_count,
			$warnings,
			array(),
			$log_ref,
			__( 'Page created.', 'aio-page-builder' )
		);
	}

	/**
	 * Builds a failure result with message and errors.
	 *
	 * @param string             $message
	 * @param array<int, string> $errors
	 * @param string             $template_key
	 * @param string             $log_ref
	 * @return self
	 */
	public static function failure( string $message, array $errors = array(), string $template_key = '', string $log_ref = '' ): self {
		return new self(
			false,
			0,
			$template_key,
			'',
			'',
			false,
			0,
			false,
			array(),
			0,
			0,
			array(),
			$errors,
			$log_ref,
			$message
		);
	}

	/**
	 * Returns an example template_build_execution_result payload for documentation and tests.
	 *
	 * @return array<string, mixed>
	 */
	public static function example_payload(): array {
		return array(
			'success'                 => true,
			'post_id'                 => 42,
			'template_key'            => 'tpl_services_hub',
			'template_family'         => 'services',
			'template_category_class' => 'hub',
			'hierarchy_applied'       => true,
			'parent_post_id'          => 10,
			'one_pager_available'     => true,
			'one_pager_metadata'      => array( 'doc_ref' => 'one-pager-services-hub' ),
			'section_count'           => 5,
			'field_assignment_count'  => 3,
			'warnings'                => array(),
			'errors'                  => array(),
			'log_ref'                 => 'log_abc',
			'message'                 => __( 'Page created.', 'aio-page-builder' ),
		);
	}
}
