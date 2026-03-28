<?php
/**
 * Result of resolving which provider (and optional model override) serves a task.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Secrets-free routing decision. Failover policy remains Provider_Failover_Service.
 */
final class AI_Provider_Route_Result {

	private string $primary_provider_id;

	/** @var string|null When non-null and non-empty, callers should prefer this model over capability default. */
	private ?string $primary_model_override;

	private bool $valid;

	/** @var string|null Configured fallback provider id (secrets-free; for failover UX). */
	private ?string $fallback_provider_id;

	/** @var string|null Optional model hint for fallback provider. */
	private ?string $fallback_model_override;

	public function __construct(
		string $primary_provider_id,
		?string $primary_model_override,
		bool $valid,
		?string $fallback_provider_id = null,
		?string $fallback_model_override = null
	) {
		$this->primary_provider_id      = $primary_provider_id;
		$this->primary_model_override   = $primary_model_override;
		$this->valid                    = $valid;
		$this->fallback_provider_id     = $fallback_provider_id;
		$this->fallback_model_override  = $fallback_model_override;
	}

	public static function invalid(): self {
		return new self( '', null, false, null, null );
	}

	public function is_valid(): bool {
		return $this->valid && $this->primary_provider_id !== '';
	}

	public function get_primary_provider_id(): string {
		return $this->primary_provider_id;
	}

	public function get_primary_model_override(): ?string {
		return $this->primary_model_override;
	}

	public function get_fallback_provider_id(): ?string {
		return $this->fallback_provider_id;
	}

	public function get_fallback_model_override(): ?string {
		return $this->fallback_model_override;
	}
}
