<?php
/**
 * Outcome of approval-gated canonical apply from an approved AI snapshot (no raw payloads in logs).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

final class Template_Lab_Canonical_Apply_Result {

	public const CODE_OK              = 'ok';
	public const CODE_ALREADY_APPLIED = 'already_applied';
	public const CODE_SESSION_MISSING = 'session_missing';
	public const CODE_FORBIDDEN       = 'forbidden';
	public const CODE_NOT_APPROVED    = 'not_approved';
	public const CODE_BAD_REF         = 'bad_snapshot_ref';
	public const CODE_FINGERPRINT     = 'fingerprint_mismatch';
	public const CODE_NO_ARTIFACT     = 'missing_normalized_output';
	public const CODE_TRANSLATION     = 'translation_failed';
	public const CODE_PERSIST         = 'persist_failed';
	public const CODE_BAD_TARGET      = 'invalid_target_kind';

	/** Validation vs trace schema drift or registry dependency mismatch at apply time. */
	public const CODE_STALE_SNAPSHOT_CONTEXT = 'stale_snapshot_context';

	private bool $success;

	private bool $already_applied;

	/** @var self::CODE_* */
	private string $code;

	private string $canonical_internal_key;

	private int $canonical_post_id;

	/** @var list<string> */
	private array $errors;

	/**
	 * @param self::CODE_* $code
	 * @param list<string> $errors
	 */
	private function __construct(
		bool $success,
		bool $already_applied,
		string $code,
		string $canonical_internal_key,
		int $canonical_post_id,
		array $errors
	) {
		$this->success                = $success;
		$this->already_applied        = $already_applied;
		$this->code                   = $code;
		$this->canonical_internal_key = $canonical_internal_key;
		$this->canonical_post_id      = $canonical_post_id;
		$this->errors                 = $errors;
	}

	/**
	 * @param list<string> $errors
	 */
	public static function ok( string $canonical_internal_key, int $canonical_post_id, array $errors = array() ): self {
		return new self( true, false, self::CODE_OK, $canonical_internal_key, $canonical_post_id, $errors );
	}

	public static function already_applied( string $canonical_internal_key, int $canonical_post_id ): self {
		return new self( true, true, self::CODE_ALREADY_APPLIED, $canonical_internal_key, $canonical_post_id, array() );
	}

	/**
	 * @param self::CODE_* $code
	 * @param list<string> $errors
	 */
	public static function failure( string $code, array $errors = array() ): self {
		return new self( false, false, $code, '', 0, $errors );
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function is_already_applied(): bool {
		return $this->already_applied;
	}

	/** @return self::CODE_* */
	public function get_code(): string {
		return $this->code;
	}

	public function get_canonical_internal_key(): string {
		return $this->canonical_internal_key;
	}

	public function get_canonical_post_id(): int {
		return $this->canonical_post_id;
	}

	/**
	 * @return list<string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}
}
