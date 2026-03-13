<?php
/**
 * Result DTO for a single LPagery token mapping (spec §7.4, §20.7, §35).
 *
 * Immutable. Preserves canonical token identity; carries supported flag, optional warning, reversible flag.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\LPagery;

defined( 'ABSPATH' ) || exit;

/**
 * Result of mapping core token to/from LPagery-compatible key. Canonical identity is never mutated.
 */
final class LPagery_Token_Mapping_Result {

	/** @var bool */
	private $supported;

	/** @var string */
	private $canonical_token_group;

	/** @var string */
	private $canonical_token_name;

	/** @var string */
	private $lpagery_key;

	/** @var string|null */
	private $warning;

	/** @var bool */
	private $reversible;

	public function __construct(
		bool $supported,
		string $canonical_token_group,
		string $canonical_token_name,
		string $lpagery_key,
		?string $warning = null,
		bool $reversible = false
	) {
		$this->supported             = $supported;
		$this->canonical_token_group = $canonical_token_group;
		$this->canonical_token_name  = $canonical_token_name;
		$this->lpagery_key           = $lpagery_key;
		$this->warning               = $warning;
		$this->reversible            = $reversible;
	}

	public function is_supported(): bool {
		return $this->supported;
	}

	public function get_canonical_token_group(): string {
		return $this->canonical_token_group;
	}

	public function get_canonical_token_name(): string {
		return $this->canonical_token_name;
	}

	public function get_lpagery_key(): string {
		return $this->lpagery_key;
	}

	public function get_warning(): ?string {
		return $this->warning;
	}

	public function is_reversible(): bool {
		return $this->reversible;
	}

	/**
	 * Builds a supported, reversible mapping result.
	 */
	public static function supported( string $canonical_group, string $canonical_name, string $lpagery_key ): self {
		return new self( true, $canonical_group, $canonical_name, $lpagery_key, null, true );
	}

	/**
	 * Builds an unsupported result with warning (canonical identity preserved).
	 */
	public static function unsupported( string $canonical_group, string $canonical_name, string $warning ): self {
		return new self( false, $canonical_group, $canonical_name, '', $warning, false );
	}

	/**
	 * Converts to array for diagnostics and API.
	 *
	 * @return array{supported: bool, canonical_token_group: string, canonical_token_name: string, lpagery_key: string, warning: string|null, reversible: bool}
	 */
	public function to_array(): array {
		return array(
			'supported'               => $this->supported,
			'canonical_token_group'   => $this->canonical_token_group,
			'canonical_token_name'    => $this->canonical_token_name,
			'lpagery_key'             => $this->lpagery_key,
			'warning'                 => $this->warning,
			'reversible'              => $this->reversible,
		);
	}
}
