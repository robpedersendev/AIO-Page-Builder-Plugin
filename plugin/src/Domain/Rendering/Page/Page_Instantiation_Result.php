<?php
/**
 * Result of a page create or update via the instantiator (spec §17.7, §19, rendering-contract §7).
 * Does not imply execution authority; callers must be authorized.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Page;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object. Payload keys are stable.
 *
 * - success: Whether the create/update completed without failure.
 * - post_id: Created or updated page ID; 0 when failed or not applicable.
 * - payload_used: Snapshot of the instantiation payload that was applied (for traceability).
 * - errors: Non-empty when success is false or warnings occurred.
 */
final class Page_Instantiation_Result {

	/** @var bool */
	private bool $success;

	/** @var int */
	private int $post_id;

	/** @var array<string, mixed> */
	private array $payload_used;

	/** @var list<string> */
	private array $errors;

	/**
	 * @param bool                 $success     Whether the operation succeeded.
	 * @param int                  $post_id     Page ID (0 if failed).
	 * @param array<string, mixed> $payload_used Payload snapshot.
	 * @param list<string>         $errors      Error or warning messages.
	 */
	public function __construct( bool $success, int $post_id, array $payload_used, array $errors = array() ) {
		$this->success      = $success;
		$this->post_id      = $post_id;
		$this->payload_used = $payload_used;
		$this->errors       = $errors;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_post_id(): int {
		return $this->post_id;
	}

	/** @return array<string, mixed> */
	public function get_payload_used(): array {
		return $this->payload_used;
	}

	/** @return list<string> */
	public function get_errors(): array {
		return $this->errors;
	}
}
