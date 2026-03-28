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

	public function __construct( string $primary_provider_id, ?string $primary_model_override, bool $valid ) {
		$this->primary_provider_id    = $primary_provider_id;
		$this->primary_model_override = $primary_model_override;
		$this->valid                  = $valid;
	}

	public static function invalid(): self {
		return new self( '', null, false );
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
}
