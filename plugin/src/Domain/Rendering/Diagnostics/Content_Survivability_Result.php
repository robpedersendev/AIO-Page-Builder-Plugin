<?php
/**
 * Result of survivability inspection for page/assembly content (spec §9.12, §17.3, §17.4, rendering-contract §4, §5).
 * No persistent storage; used for diagnostics and tests.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Diagnostics;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object. Payload keys are stable.
 *
 * - is_survivable: True when content has no prohibited runtime lock-in.
 * - prohibited_runtime_dependencies: List of detected prohibited patterns (plugin shortcodes, unreplaced tokens, etc.).
 * - dynamic_output_flags: List of justified optional dynamic paths (e.g. generateblocks_compatible) for transparency.
 * - human_editability_notes: Notes about block-editor and front-end usability.
 * - deactivation_readiness: True when page remains meaningful after plugin deactivation (same intent as is_survivable).
 *
 * Example passing result (to_array()):
 * [
 *   'is_survivable' => true,
 *   'prohibited_runtime_dependencies' => [],
 *   'dynamic_output_flags' => [ 'generateblocks_compatible_optional' ],
 *   'human_editability_notes' => [ 'block_markup_editable_in_block_editor', 'content_meaningful_without_plugin_runtime' ],
 *   'deactivation_readiness' => true,
 * ]
 *
 * Example failing result (content with plugin shortcode):
 * [
 *   'is_survivable' => false,
 *   'prohibited_runtime_dependencies' => [ 'plugin_shortcode_detected' ],
 *   'dynamic_output_flags' => [],
 *   'human_editability_notes' => [],
 *   'deactivation_readiness' => false,
 * ]
 */
final class Content_Survivability_Result {

	/** @var bool */
	private bool $is_survivable;

	/** @var array<int, string> */
	private array $prohibited_runtime_dependencies;

	/** @var array<int, string> */
	private array $dynamic_output_flags;

	/** @var array<int, string> */
	private array $human_editability_notes;

	/** @var bool */
	private bool $deactivation_readiness;

	/**
	 * @param bool               $is_survivable                   True when no prohibited lock-in detected.
	 * @param array<int, string> $prohibited_runtime_dependencies  Detected prohibited patterns.
	 * @param array<int, string> $dynamic_output_flags            Justified optional dynamic paths.
	 * @param array<int, string> $human_editability_notes         Editability/usability notes.
	 * @param bool               $deactivation_readiness          True when content remains meaningful without plugin.
	 */
	public function __construct(
		bool $is_survivable,
		array $prohibited_runtime_dependencies = array(),
		array $dynamic_output_flags = array(),
		array $human_editability_notes = array(),
		bool $deactivation_readiness = true
	) {
		$this->is_survivable                   = $is_survivable;
		$this->prohibited_runtime_dependencies = $prohibited_runtime_dependencies;
		$this->dynamic_output_flags            = $dynamic_output_flags;
		$this->human_editability_notes         = $human_editability_notes;
		$this->deactivation_readiness          = $deactivation_readiness;
	}

	public function is_survivable(): bool {
		return $this->is_survivable;
	}

	/** @return array<int, string> */
	public function get_prohibited_runtime_dependencies(): array {
		return $this->prohibited_runtime_dependencies;
	}

	/** @return array<int, string> */
	public function get_dynamic_output_flags(): array {
		return $this->dynamic_output_flags;
	}

	/** @return array<int, string> */
	public function get_human_editability_notes(): array {
		return $this->human_editability_notes;
	}

	public function is_deactivation_ready(): bool {
		return $this->deactivation_readiness;
	}

	/**
	 * Full payload for logging or assertions.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'is_survivable'                   => $this->is_survivable,
			'prohibited_runtime_dependencies' => $this->prohibited_runtime_dependencies,
			'dynamic_output_flags'            => $this->dynamic_output_flags,
			'human_editability_notes'         => $this->human_editability_notes,
			'deactivation_readiness'          => $this->deactivation_readiness,
		);
	}
}
