<?php
/**
 * Result DTO for menu/navigation change job (spec §34, §40.2; Prompt 083).
 *
 * Immutable: success, menu_id, message, errors, artifacts (menu_ref, location_assigned, etc.).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable menu-change job result.
 */
final class Menu_Change_Result {

	/** @var bool */
	private $success;

	/** @var int WordPress nav menu term_id; 0 on failure. */
	private $menu_id;

	/** @var string */
	private $message;

	/** @var list<string> */
	private $errors;

	/** @var array<string, mixed> */
	private $artifacts;

	public function __construct(
		bool $success,
		int $menu_id,
		string $message = '',
		array $errors = array(),
		array $artifacts = array()
	) {
		$this->success   = $success;
		$this->menu_id   = $menu_id;
		$this->message   = $message;
		$this->errors    = $errors;
		$this->artifacts = $artifacts;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_menu_id(): int {
		return $this->menu_id;
	}

	public function get_message(): string {
		return $this->message;
	}

	/** @return list<string> */
	public function get_errors(): array {
		return $this->errors;
	}

	/** @return array<string, mixed> */
	public function get_artifacts(): array {
		return $this->artifacts;
	}

	/**
	 * Handler result shape (success, message, artifacts).
	 *
	 * @return array<string, mixed>
	 */
	public function to_handler_result(): array {
		$out = array(
			'success'   => $this->success,
			'message'   => $this->message,
			'artifacts' => array_merge( array( 'menu_id' => $this->menu_id ), $this->artifacts ),
		);
		if ( ! empty( $this->errors ) ) {
			$out['errors'] = $this->errors;
		}
		return $out;
	}

	public static function success( int $menu_id, string $action, string $menu_name, string $location_slug = '', array $extra = array() ): self {
		$artifacts = array(
			'action'    => $action,
			'menu_name' => $menu_name,
		);
		if ( $location_slug !== '' ) {
			$artifacts['location_assigned'] = $location_slug;
		}
		return new self(
			true,
			$menu_id,
			__( 'Menu change applied.', 'aio-page-builder' ),
			array(),
			array_merge( $artifacts, $extra )
		);
	}

	public static function failure( string $message, array $errors = array() ): self {
		return new self( false, 0, $message, $errors, array() );
	}
}
