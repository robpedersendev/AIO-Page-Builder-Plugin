<?php
/**
 * Template-aware context for diff and rollback summaries (spec §59.11, §58.2, §58.8; Prompt 197).
 *
 * Immutable DTO: template_key, template_family, variation, CTA-pattern shift, version/deprecation
 * context. Used in template_diff_summary and rollback_template_context payloads. Permission-safe;
 * no secret-bearing or raw artifact data.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Diff;

defined( 'ABSPATH' ) || exit;

/**
 * Template context for diff/rollback explainability. Convertible to array for API and UI.
 */
final class Template_Diff_Context {

	/** @var string */
	private $template_key;

	/** @var string */
	private $template_family;

	/** @var string */
	private $template_variation;

	/** @var bool */
	private $cta_pattern_shift;

	/** @var string */
	private $version_context;

	/** @var string */
	private $deprecation_context;

	public function __construct(
		string $template_key = '',
		string $template_family = '',
		string $template_variation = '',
		bool $cta_pattern_shift = false,
		string $version_context = '',
		string $deprecation_context = ''
	) {
		$this->template_key        = $template_key;
		$this->template_family     = $template_family;
		$this->template_variation  = $template_variation;
		$this->cta_pattern_shift   = $cta_pattern_shift;
		$this->version_context     = $version_context;
		$this->deprecation_context = $deprecation_context;
	}

	public function get_template_key(): string {
		return $this->template_key;
	}

	public function get_template_family(): string {
		return $this->template_family;
	}

	public function get_template_variation(): string {
		return $this->template_variation;
	}

	public function has_cta_pattern_shift(): bool {
		return $this->cta_pattern_shift;
	}

	public function get_version_context(): string {
		return $this->version_context;
	}

	public function get_deprecation_context(): string {
		return $this->deprecation_context;
	}

	/**
	 * Stable payload for rollback_template_context and template_diff_summary (spec §59.11).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'template_key'        => $this->template_key,
			'template_family'     => $this->template_family,
			'template_variation'  => $this->template_variation,
			'cta_pattern_shift'   => $this->cta_pattern_shift,
			'version_context'     => $this->version_context,
			'deprecation_context' => $this->deprecation_context,
		);
	}

	/**
	 * Returns an example rollback_template_context payload for documentation and tests.
	 *
	 * @return array<string, mixed>
	 */
	public static function example_rollback_template_context_payload(): array {
		return array(
			'template_key'        => 'tpl_services_hub',
			'template_family'     => 'services',
			'template_variation'  => 'hub',
			'cta_pattern_shift'   => false,
			'version_context'     => 'stable_key_retained',
			'deprecation_context' => '',
		);
	}
}
