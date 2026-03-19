<?php
/**
 * Result of a planning request with optional failover (spec §29.6, Prompt 119).
 * Records original attempt, failure reason (category only; admin-safe), fallback target, and effective provider used.
 * No secrets; safe for run metadata and operator display.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers\Failover;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object: whether primary was used, effective provider/model, and per-attempt log.
 */
final class Failover_Result {

	/** Metadata key for failover result in run metadata. */
	public const METADATA_KEY_EFFECTIVE_PROVIDER = 'effective_provider_used';

	/** Metadata key for fallback provider reference (when fallback was used). */
	public const METADATA_KEY_FALLBACK_REF = 'fallback_provider_reference';

	/** Metadata key for failover attempt log. */
	public const METADATA_KEY_ATTEMPTS = 'failover_attempt';

	/** @var bool True if primary provider succeeded (no fallback). */
	private bool $used_primary;

	/** @var string Provider id that ultimately produced the result (or last attempted). */
	private string $effective_provider_id;

	/** @var string Model used by effective provider. */
	private string $effective_model_used;

	/** @var array<int, array{provider_id: string, model_used: string, category: string, attempted_at: string}> */
	private array $attempts;

	/** @var array<string, mixed> Policy snapshot used (to_metadata_snapshot()). */
	private array $policy_snapshot;

	/**
	 * @param bool                                                                                         $used_primary        True if primary succeeded.
	 * @param string                                                                                       $effective_provider_id Provider that produced or last attempted.
	 * @param string                                                                                       $effective_model_used  Model used.
	 * @param array<int, array{provider_id: string, model_used: string, category: string, attempted_at: string}> $attempts Per-attempt log (no secrets).
	 * @param array<string, mixed>                                                                         $policy_snapshot Policy snapshot for audit.
	 */
	public function __construct(
		bool $used_primary,
		string $effective_provider_id,
		string $effective_model_used,
		array $attempts,
		array $policy_snapshot
	) {
		$this->used_primary          = $used_primary;
		$this->effective_provider_id = $effective_provider_id;
		$this->effective_model_used  = $effective_model_used;
		$this->attempts              = $attempts;
		$this->policy_snapshot       = $policy_snapshot;
	}

	public function used_primary(): bool {
		return $this->used_primary;
	}

	public function get_effective_provider_id(): string {
		return $this->effective_provider_id;
	}

	public function get_effective_model_used(): string {
		return $this->effective_model_used;
	}

	/**
	 * @return array<int, array{provider_id: string, model_used: string, category: string, attempted_at: string}>
	 */
	public function get_attempts(): array {
		return $this->attempts;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_policy_snapshot(): array {
		return $this->policy_snapshot;
	}

	/**
	 * Merge this result into run metadata (stable keys per spec).
	 *
	 * @return array<string, mixed> Keys: failover_policy, failover_attempt, fallback_provider_reference, effective_provider_used (+ model when present).
	 */
	public function to_run_metadata(): array {
		$meta = array(
			Provider_Failover_Policy::METADATA_KEY => $this->policy_snapshot,
			self::METADATA_KEY_ATTEMPTS            => $this->attempts,
			self::METADATA_KEY_EFFECTIVE_PROVIDER  => array(
				'provider_id' => $this->effective_provider_id,
				'model_used'  => $this->effective_model_used,
			),
		);
		if ( ! $this->used_primary && $this->effective_provider_id !== '' ) {
			$meta[ self::METADATA_KEY_FALLBACK_REF ] = array(
				'provider_id' => $this->effective_provider_id,
				'model_used'  => $this->effective_model_used,
			);
		}
		return $meta;
	}

	/**
	 * Create result for primary success (no fallback).
	 *
	 * @param string               $provider_id Provider that succeeded.
	 * @param string               $model_used  Model used.
	 * @param array<string, mixed> $policy_snapshot Policy snapshot (may be disabled).
	 * @return self
	 */
	public static function primary_success( string $provider_id, string $model_used, array $policy_snapshot ): self {
		$attempts = array(
			array(
				'provider_id'  => $provider_id,
				'model_used'   => $model_used,
				'category'     => 'success',
				'attempted_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			),
		);
		return new self( true, $provider_id, $model_used, $attempts, $policy_snapshot );
	}

	/**
	 * Create result for primary failure with no fallback (policy disabled or ineligible).
	 *
	 * @param string               $provider_id Provider that failed.
	 * @param string               $model_used  Model used.
	 * @param string               $category    Normalized error category (no secrets).
	 * @param array<string, mixed> $policy_snapshot Policy snapshot.
	 * @return self
	 */
	public static function primary_failure_no_fallback( string $provider_id, string $model_used, string $category, array $policy_snapshot ): self {
		$attempts = array(
			array(
				'provider_id'  => $provider_id,
				'model_used'   => $model_used,
				'category'     => $category,
				'attempted_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			),
		);
		return new self( false, $provider_id, $model_used, $attempts, $policy_snapshot );
	}

	/**
	 * Create result for fallback success (primary failed, fallback succeeded).
	 *
	 * @param string                                                                                       $fallback_provider_id Fallback provider that succeeded.
	 * @param string                                                                                       $fallback_model       Model used by fallback.
	 * @param array<int, array{provider_id: string, model_used: string, category: string, attempted_at: string}> $attempts Primary failure + fallback success.
	 * @param array<string, mixed>                                                                         $policy_snapshot Policy snapshot.
	 * @return self
	 */
	public static function fallback_success( string $fallback_provider_id, string $fallback_model, array $attempts, array $policy_snapshot ): self {
		return new self( false, $fallback_provider_id, $fallback_model, $attempts, $policy_snapshot );
	}

	/**
	 * Create result for fallback failure (primary failed, fallback attempted and failed).
	 *
	 * @param string                                                                                       $last_provider_id Last provider attempted (fallback).
	 * @param string                                                                                       $last_model_used   Model used.
	 * @param array<int, array{provider_id: string, model_used: string, category: string, attempted_at: string}> $attempts All attempts.
	 * @param array<string, mixed>                                                                         $policy_snapshot Policy snapshot.
	 * @return self
	 */
	public static function fallback_failure( string $last_provider_id, string $last_model_used, array $attempts, array $policy_snapshot ): self {
		return new self( false, $last_provider_id, $last_model_used, $attempts, $policy_snapshot );
	}
}
