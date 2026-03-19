<?php
/**
 * Machine-readable composition validation result (Prompt 178, cta-sequencing-and-placement-contract).
 * Blockers, warnings, CTA rule violations, compatibility violations, preview readiness warnings.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Compositions\Validation;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable validation result for admin UI and registry enforcement.
 */
final class Composition_Validation_Result {

	/** @var bool */
	private bool $valid;

	/** @var array<int, array{code: string, message: string}> */
	private array $blockers;

	/** @var array<int, array{code: string, message: string}> */
	private array $warnings;

	/** @var array<int, array{code: string, message: string, position?: int}> */
	private array $cta_rule_violations;

	/** @var array<int, array{code: string, message: string, section_key?: string}> */
	private array $compatibility_violations;

	/** @var array<int, array{code: string, message: string}> */
	private array $preview_readiness_warnings;

	/** @var array<int, string> Legacy validation codes from Composition_Validator (for backward compatibility). */
	private array $legacy_codes;

	/**
	 * @param array<int, array{code: string, message: string}>                       $blockers
	 * @param array<int, array{code: string, message: string}>                       $warnings
	 * @param array<int, array{code: string, message: string, position?: int}>       $cta_rule_violations
	 * @param array<int, array{code: string, message: string, section_key?: string}> $compatibility_violations
	 * @param array<int, array{code: string, message: string}>                       $preview_readiness_warnings
	 * @param array<int, string>                                                     $legacy_codes
	 */
	public function __construct(
		bool $valid,
		array $blockers,
		array $warnings,
		array $cta_rule_violations,
		array $compatibility_violations,
		array $preview_readiness_warnings,
		array $legacy_codes = array()
	) {
		$this->valid                      = $valid;
		$this->blockers                   = $blockers;
		$this->warnings                   = $warnings;
		$this->cta_rule_violations        = $cta_rule_violations;
		$this->compatibility_violations   = $compatibility_violations;
		$this->preview_readiness_warnings = $preview_readiness_warnings;
		$this->legacy_codes               = $legacy_codes;
	}

	public function is_valid(): bool {
		return $this->valid;
	}

	/** @return array<int, array{code: string, message: string}> */
	public function get_blockers(): array {
		return $this->blockers;
	}

	/** @return array<int, array{code: string, message: string}> */
	public function get_warnings(): array {
		return $this->warnings;
	}

	/** @return array<int, array{code: string, message: string, position?: int}> */
	public function get_cta_rule_violations(): array {
		return $this->cta_rule_violations;
	}

	/** @return array<int, array{code: string, message: string, section_key?: string}> */
	public function get_compatibility_violations(): array {
		return $this->compatibility_violations;
	}

	/** @return array<int, array{code: string, message: string}> */
	public function get_preview_readiness_warnings(): array {
		return $this->preview_readiness_warnings;
	}

	/** @return array<int, string> */
	public function get_legacy_codes(): array {
		return $this->legacy_codes;
	}

	/**
	 * Machine-readable payload for admin UI and APIs.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'valid'                      => $this->valid,
			'blockers'                   => $this->blockers,
			'warnings'                   => $this->warnings,
			'cta_rule_violations'        => $this->cta_rule_violations,
			'compatibility_violations'   => $this->compatibility_violations,
			'preview_readiness_warnings' => $this->preview_readiness_warnings,
			'legacy_codes'               => $this->legacy_codes,
		);
	}
}
