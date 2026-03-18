<?php
/**
 * Immutable value object for a single provider connection test (spec §49.9).
 * Safe for admin display and persistence; no secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Result of a provider connection test: reachability and credential validity.
 * Payload shape is stable for UI and storage.
 */
final class Provider_Connection_Test_Result {

	/** @var bool */
	private bool $success;

	/** @var string */
	private string $provider_id;

	/** @var string */
	private string $model_used;

	/** @var array<string, mixed>|null Normalized error object when success is false (contract §5.1). */
	private ?array $normalized_error;

	/** @var string ISO 8601 timestamp when the test ran. */
	private string $tested_at;

	/** @var string Short user-safe message for display. */
	private string $user_message;

	/**
	 * @param bool                      $success          Whether the connection test succeeded.
	 * @param string                    $provider_id      Provider identifier.
	 * @param string                    $model_used       Model used for the test.
	 * @param array<string, mixed>|null $normalized_error Error object when failed; null when success.
	 * @param string                    $tested_at        ISO 8601 timestamp.
	 * @param string                    $user_message     User-safe display message.
	 */
	public function __construct(
		bool $success,
		string $provider_id,
		string $model_used,
		?array $normalized_error,
		string $tested_at,
		string $user_message
	) {
		$this->success          = $success;
		$this->provider_id      = $provider_id;
		$this->model_used       = $model_used;
		$this->normalized_error = $normalized_error;
		$this->tested_at        = $tested_at;
		$this->user_message     = $user_message;
	}

	/** @return bool */
	public function is_success(): bool {
		return $this->success;
	}

	/** @return string */
	public function get_provider_id(): string {
		return $this->provider_id;
	}

	/** @return string */
	public function get_model_used(): string {
		return $this->model_used;
	}

	/** @return array<string, mixed>|null */
	public function get_normalized_error(): ?array {
		return $this->normalized_error;
	}

	/** @return string */
	public function get_tested_at(): string {
		return $this->tested_at;
	}

	/** @return string */
	public function get_user_message(): string {
		return $this->user_message;
	}

	/**
	 * Array shape for persistence and API; no secrets.
	 *
	 * @return array{success: bool, provider_id: string, model_used: string, normalized_error: array|null, tested_at: string, user_message: string}
	 */
	public function to_array(): array {
		return array(
			'success'          => $this->success,
			'provider_id'      => $this->provider_id,
			'model_used'       => $this->model_used,
			'normalized_error' => $this->normalized_error,
			'tested_at'        => $this->tested_at,
			'user_message'     => $this->user_message,
		);
	}

	/**
	 * Rebuilds from a stored array (e.g. from option value).
	 *
	 * @param array<string, mixed> $data Stored shape from to_array().
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$success   = ! empty( $data['success'] );
		$provider  = isset( $data['provider_id'] ) ? (string) $data['provider_id'] : '';
		$model     = isset( $data['model_used'] ) ? (string) $data['model_used'] : 'unknown';
		$error     = isset( $data['normalized_error'] ) && is_array( $data['normalized_error'] ) ? $data['normalized_error'] : null;
		$tested_at = isset( $data['tested_at'] ) && is_string( $data['tested_at'] ) ? $data['tested_at'] : gmdate( 'c' );
		$user_msg  = isset( $data['user_message'] ) && is_string( $data['user_message'] ) ? $data['user_message'] : ( $success ? 'Connection successful.' : 'Connection test failed.' );
		return new self( $success, $provider, $model, $error, $tested_at, $user_msg );
	}
}
