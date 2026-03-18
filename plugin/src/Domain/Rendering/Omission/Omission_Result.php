<?php
/**
 * Result of smart omission: which nodes were omitted, which were refused, and fallbacks applied (smart-omission-rendering-contract §6, §8).
 * Used for tests and debugging; not exposed as user-facing clutter.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Omission;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of Smart_Omission_Service::apply().
 */
final class Omission_Result {

	/** @var list<string> Field keys that were omitted (optional and empty). */
	private array $omitted_keys;

	/** @var array<string, string> Field key => refusal reason (required, structural heading, primary CTA, etc.). */
	private array $refused;

	/** @var array<string, string> Field key => fallback value applied (required-but-empty). */
	private array $fallbacks_applied;

	/**
	 * @param list<string>          $omitted_keys     Keys omitted (optional + empty).
	 * @param array<string, string> $refused          Key => reason when omission was refused.
	 * @param array<string, string> $fallbacks_applied Key => fallback value applied.
	 */
	public function __construct(
		array $omitted_keys = array(),
		array $refused = array(),
		array $fallbacks_applied = array()
	) {
		$this->omitted_keys      = array_values( $omitted_keys );
		$this->refused           = $refused;
		$this->fallbacks_applied = $fallbacks_applied;
	}

	/** @return list<string> */
	public function get_omitted_keys(): array {
		return $this->omitted_keys;
	}

	/** @return array<string, string> */
	public function get_refused(): array {
		return $this->refused;
	}

	/** @return array<string, string> */
	public function get_fallbacks_applied(): array {
		return $this->fallbacks_applied;
	}

	public function was_omitted( string $field_key ): bool {
		return in_array( $field_key, $this->omitted_keys, true );
	}

	public function was_refused( string $field_key ): bool {
		return isset( $this->refused[ $field_key ] );
	}

	/** @return string|null Refusal reason or null. */
	public function get_refusal_reason( string $field_key ): ?string {
		return $this->refused[ $field_key ] ?? null;
	}

	/**
	 * Payload for tests and debugging (not user-facing).
	 *
	 * @return array{omitted_keys: list<string>, refused: array<string, string>, fallbacks_applied: array<string, string>}
	 */
	public function to_array(): array {
		return array(
			'omitted_keys'      => $this->omitted_keys,
			'refused'           => $this->refused,
			'fallbacks_applied' => $this->fallbacks_applied,
		);
	}
}
