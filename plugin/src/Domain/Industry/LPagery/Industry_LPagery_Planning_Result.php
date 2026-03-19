<?php
/**
 * Immutable planning result from Industry LPagery advisor (industry-lpagery-planning-contract.md).
 * Documents LPagery posture, required/optional tokens, suggested page families, and warning flags.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\LPagery;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only result for Build Plan and UI explanation layers. No execution or mutation.
 */
final class Industry_LPagery_Planning_Result {

	/** @var string */
	private string $lpagery_posture;

	/** @var array<int, string> */
	private array $required_tokens;

	/** @var array<int, string> */
	private array $optional_tokens;

	/** @var array<int, string> */
	private array $suggested_page_families;

	/** @var array<int, string> */
	private array $warning_flags;

	/** @var string */
	private string $hierarchy_guidance;

	/** @var array<int, string> */
	private array $weak_page_warnings;

	/**
	 * @param string             $lpagery_posture       central | optional | discouraged.
	 * @param array<int, string> $required_tokens       Token refs required when LPagery is used.
	 * @param array<int, string> $optional_tokens       Optional token refs.
	 * @param array<int, string> $suggested_page_families Advisory page family or hierarchy hints.
	 * @param array<int, string> $warning_flags         Warning codes for planning/UI.
	 * @param string             $hierarchy_guidance     Concatenated hierarchy guidance from rules.
	 * @param array<int, string> $weak_page_warnings    Page types or patterns that are weak fit.
	 */
	public function __construct(
		string $lpagery_posture = Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL,
		array $required_tokens = array(),
		array $optional_tokens = array(),
		array $suggested_page_families = array(),
		array $warning_flags = array(),
		string $hierarchy_guidance = '',
		array $weak_page_warnings = array()
	) {
		$this->lpagery_posture         = $lpagery_posture;
		$this->required_tokens         = array_values( $required_tokens );
		$this->optional_tokens         = array_values( $optional_tokens );
		$this->suggested_page_families = array_values( $suggested_page_families );
		$this->warning_flags           = array_values( $warning_flags );
		$this->hierarchy_guidance      = $hierarchy_guidance;
		$this->weak_page_warnings      = array_values( $weak_page_warnings );
	}

	public function get_lpagery_posture(): string {
		return $this->lpagery_posture;
	}

	/** @return array<int, string> */
	public function get_required_tokens(): array {
		return $this->required_tokens;
	}

	/** @return array<int, string> */
	public function get_optional_tokens(): array {
		return $this->optional_tokens;
	}

	/** @return array<int, string> */
	public function get_suggested_page_families(): array {
		return $this->suggested_page_families;
	}

	/** @return array<int, string> */
	public function get_warning_flags(): array {
		return $this->warning_flags;
	}

	public function get_hierarchy_guidance(): string {
		return $this->hierarchy_guidance;
	}

	/** @return array<int, string> */
	public function get_weak_page_warnings(): array {
		return $this->weak_page_warnings;
	}

	/**
	 * Machine-readable shape for APIs and logging.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'lpagery_posture'         => $this->lpagery_posture,
			'required_tokens'         => $this->required_tokens,
			'optional_tokens'         => $this->optional_tokens,
			'suggested_page_families' => $this->suggested_page_families,
			'warning_flags'           => $this->warning_flags,
			'hierarchy_guidance'      => $this->hierarchy_guidance,
			'weak_page_warnings'      => $this->weak_page_warnings,
		);
	}
}
