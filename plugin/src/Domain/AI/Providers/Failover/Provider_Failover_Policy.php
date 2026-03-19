<?php
/**
 * Bounded failover policy model (spec §25.1, §25.5, §45.1, Prompt 119).
 * Defines when a planning request may be routed to a fallback provider after primary failure.
 * Policy is explicit, logged, and reversible in interpretation; no silent substitution.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers\Failover;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;

/**
 * Immutable policy: enabled flag, primary/fallback provider ids, eligible error categories, max attempts.
 */
final class Provider_Failover_Policy {

	/** Policy key in run metadata snapshot. */
	public const METADATA_KEY = 'failover_policy';

	/** Default max fallback attempts (bounded; no unbounded loops). */
	public const DEFAULT_MAX_FALLBACK_ATTEMPTS = 1;

	/** Default eligible categories: transient/retriable only (rate_limit, timeout, provider_error, network_error). */
	private const DEFAULT_ELIGIBLE_CATEGORIES = array(
		Provider_Response_Normalizer::ERROR_RATE_LIMIT,
		Provider_Response_Normalizer::ERROR_TIMEOUT,
		Provider_Response_Normalizer::ERROR_PROVIDER_ERROR,
		Provider_Response_Normalizer::ERROR_NETWORK_ERROR,
	);

	/** @var bool */
	private bool $enabled;

	/** @var string */
	private string $primary_provider_id;

	/** @var string */
	private string $fallback_provider_id;

	/** @var array<int, string> */
	private array $eligible_categories;

	/** @var int */
	private int $max_fallback_attempts;

	/**
	 * @param bool               $enabled               Whether failover is allowed.
	 * @param string             $primary_provider_id   Primary provider id.
	 * @param string             $fallback_provider_id  Fallback provider id (must differ from primary).
	 * @param array<int, string> $eligible_categories   Error categories that allow fallback (ERROR_* constants).
	 * @param int                $max_fallback_attempts Upper bound (typically 1).
	 */
	public function __construct(
		bool $enabled,
		string $primary_provider_id,
		string $fallback_provider_id,
		array $eligible_categories = array(),
		int $max_fallback_attempts = self::DEFAULT_MAX_FALLBACK_ATTEMPTS
	) {
		$this->enabled               = $enabled;
		$this->primary_provider_id   = $primary_provider_id;
		$this->fallback_provider_id  = $fallback_provider_id;
		$this->eligible_categories   = array_values( array_unique( $eligible_categories ) );
		$this->max_fallback_attempts = $max_fallback_attempts > 0 ? min( $max_fallback_attempts, 1 ) : 0;
	}

	public function is_enabled(): bool {
		return $this->enabled;
	}

	public function get_primary_provider_id(): string {
		return $this->primary_provider_id;
	}

	public function get_fallback_provider_id(): string {
		return $this->fallback_provider_id;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_eligible_categories(): array {
		return $this->eligible_categories;
	}

	public function get_max_fallback_attempts(): int {
		return $this->max_fallback_attempts;
	}

	/**
	 * Whether the given normalized error category is eligible for fallback.
	 *
	 * @param string $category One of Provider_Response_Normalizer::ERROR_*.
	 * @return bool
	 */
	public function is_eligible_category( string $category ): bool {
		return in_array( $category, $this->eligible_categories, true );
	}

	/**
	 * Whether we can attempt fallback (enabled, primary failed, fallback differs, attempts left).
	 *
	 * @param string $failed_provider_id Provider that just failed.
	 * @param int    $attempts_so_far    Number of fallback attempts already made.
	 * @return bool
	 */
	public function can_attempt_fallback( string $failed_provider_id, int $attempts_so_far ): bool {
		if ( ! $this->enabled || $attempts_so_far >= $this->max_fallback_attempts ) {
			return false;
		}
		if ( $failed_provider_id === '' || $this->fallback_provider_id === '' ) {
			return false;
		}
		if ( $failed_provider_id === $this->fallback_provider_id ) {
			return false;
		}
		return $failed_provider_id === $this->primary_provider_id;
	}

	/**
	 * Snapshot for run metadata (no secrets). Stable payload per spec.
	 *
	 * @return array{enabled: bool, primary_provider_id: string, fallback_provider_id: string, eligible_categories: array<int, string>, max_fallback_attempts: int}
	 */
	public function to_metadata_snapshot(): array {
		return array(
			'enabled'               => $this->enabled,
			'primary_provider_id'   => $this->primary_provider_id,
			'fallback_provider_id'  => $this->fallback_provider_id,
			'eligible_categories'   => $this->eligible_categories,
			'max_fallback_attempts' => $this->max_fallback_attempts,
		);
	}

	/**
	 * Build policy from stored config array (e.g. provider_config['failover_policy']).
	 * Returns disabled policy when key missing or invalid.
	 *
	 * @param array<string, mixed> $config Config slice (failover_policy).
	 * @param string               $primary_provider_id Primary provider id (from prefill/selection).
	 * @return self
	 */
	public static function from_config( array $config, string $primary_provider_id ): self {
		$enabled    = isset( $config['enabled'] ) && $config['enabled'] === true;
		$fallback   = isset( $config['fallback_provider_id'] ) && is_string( $config['fallback_provider_id'] )
			? trim( $config['fallback_provider_id'] )
			: '';
		$categories = array();
		if ( isset( $config['eligible_categories'] ) && is_array( $config['eligible_categories'] ) ) {
			foreach ( $config['eligible_categories'] as $c ) {
				if ( is_string( $c ) && $c !== '' ) {
					$categories[] = $c;
				}
			}
		}
		if ( empty( $categories ) ) {
			$categories = self::DEFAULT_ELIGIBLE_CATEGORIES;
		}
		$max = self::DEFAULT_MAX_FALLBACK_ATTEMPTS;
		if ( isset( $config['max_fallback_attempts'] ) && is_numeric( $config['max_fallback_attempts'] ) ) {
			$max = (int) $config['max_fallback_attempts'];
			$max = $max > 0 ? min( $max, 1 ) : 0;
		}
		$primary = $primary_provider_id !== '' ? $primary_provider_id : 'primary';
		if ( $fallback === '' || $fallback === $primary ) {
			$enabled = false;
		}
		return new self( $enabled, $primary, $fallback, $categories, $max );
	}

	/**
	 * Disabled policy (no failover).
	 *
	 * @param string $primary_provider_id Primary provider id.
	 * @return self
	 */
	public static function disabled( string $primary_provider_id ): self {
		return new self( false, $primary_provider_id, '', array(), 0 );
	}
}
