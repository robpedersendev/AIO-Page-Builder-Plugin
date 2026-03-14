<?php
/**
 * Result value object for library-wide LPagery compatibility (Prompt 179, large-scale-acf-lpagery-binding-contract).
 * Carries lpagery_mapping_summary, lpagery_compatibility_state, unsupported_mapping_reasons; preview_safe and canonical_identity_preserved.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\LPagery;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable compatibility result for a section or page template. Machine-readable for admin directory/detail views.
 */
final class LPagery_Compatibility_Result {

	/** Compatibility state: at least one supported mapping; no blockers. */
	public const STATE_SUPPORTED = 'supported';

	/** Compatibility state: some supported, some unsupported or conditional. */
	public const STATE_PARTIAL = 'partial';

	/** Compatibility state: no supported mappings or explicit unsupported. */
	public const STATE_UNSUPPORTED = 'unsupported';

	/** Compatibility state: cannot determine (e.g. no blueprint). */
	public const STATE_UNKNOWN = 'unknown';

	/** @var bool */
	private bool $compatible;

	/** @var string One of STATE_* constants. */
	private string $compatibility_state;

	/** @var array<string, mixed> lpagery_mapping_summary: supported_mappings, unsupported_mappings, allowed_groups, canonical_identity_preserved, preview_safe. */
	private array $lpagery_mapping_summary;

	/** @var list<array{field_name?: string, token_key?: string, reason: string}> */
	private array $unsupported_mapping_reasons;

	/**
	 * @param bool $compatible
	 * @param string $compatibility_state
	 * @param array<string, mixed> $lpagery_mapping_summary
	 * @param list<array{field_name?: string, token_key?: string, reason: string}> $unsupported_mapping_reasons
	 */
	public function __construct(
		bool $compatible,
		string $compatibility_state,
		array $lpagery_mapping_summary,
		array $unsupported_mapping_reasons = array()
	) {
		$this->compatible                  = $compatible;
		$this->compatibility_state         = $compatibility_state;
		$this->lpagery_mapping_summary     = $lpagery_mapping_summary;
		$this->unsupported_mapping_reasons = $unsupported_mapping_reasons;
	}

	public function is_compatible(): bool {
		return $this->compatible;
	}

	public function get_compatibility_state(): string {
		return $this->compatibility_state;
	}

	/** @return array<string, mixed> */
	public function get_lpagery_mapping_summary(): array {
		return $this->lpagery_mapping_summary;
	}

	/** @return list<array{field_name?: string, token_key?: string, reason: string}> */
	public function get_unsupported_mapping_reasons(): array {
		return $this->unsupported_mapping_reasons;
	}

	/**
	 * Machine-readable payload for admin UI and APIs (lpagery_compatibility_state, lpagery_mapping_summary, unsupported_mapping_reason).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'compatible'                    => $this->compatible,
			'lpagery_compatibility_state'   => $this->compatibility_state,
			'lpagery_mapping_summary'       => $this->lpagery_mapping_summary,
			'unsupported_mapping_reasons'   => $this->unsupported_mapping_reasons,
		);
	}
}
