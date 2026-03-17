<?php
/**
 * Result of composing base section helper doc with optional industry overlay (industry-section-helper-overlay-schema).
 * Immutable; carries composed doc and trace metadata for debuggability.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only result: composed helper doc (base + overlay in allowed regions) and trace.
 */
final class Composed_Helper_Doc_Result {

	/** @var array<string, mixed> Composed documentation model (base + overlay fields). */
	private array $composed_doc;

	/** @var string Base documentation_id when base was present; empty otherwise. */
	private string $base_documentation_id;

	/** @var bool Whether an active overlay was applied. */
	private bool $overlay_applied;

	/** @var string Industry key of applied overlay when overlay_applied is true; empty otherwise. */
	private string $overlay_industry_key;

	/** @var string Section key that was resolved. */
	private string $section_key;

	/** @var list<array{rule_key: string, severity: string, caution_summary: string}> Advisory compliance cautions for display (Prompt 407). */
	private array $compliance_warnings;

	/**
	 * @param array<string, mixed> $composed_doc       Effective helper doc (base + overlay in allowed regions).
	 * @param string              $base_documentation_id Base doc id or empty.
	 * @param bool                $overlay_applied    True if an active overlay was merged.
	 * @param string              $overlay_industry_key Industry key of overlay or empty.
	 * @param string              $section_key       Section key that was resolved.
	 * @param list<array{rule_key: string, severity: string, caution_summary: string}> $compliance_warnings Advisory compliance cautions (default empty).
	 */
	public function __construct( array $composed_doc, string $base_documentation_id, bool $overlay_applied, string $overlay_industry_key, string $section_key, array $compliance_warnings = array() ) {
		$this->composed_doc          = $composed_doc;
		$this->base_documentation_id = $base_documentation_id;
		$this->overlay_applied       = $overlay_applied;
		$this->overlay_industry_key  = $overlay_industry_key;
		$this->section_key           = $section_key;
		$this->compliance_warnings   = $compliance_warnings;
	}

	/**
	 * Returns the composed documentation (base + overlay in allowed regions). May be empty when no base doc exists.
	 *
	 * @return array<string, mixed>
	 */
	public function get_composed_doc(): array {
		return $this->composed_doc;
	}

	/**
	 * Returns base documentation_id when base helper was present; empty string otherwise.
	 */
	public function get_base_documentation_id(): string {
		return $this->base_documentation_id;
	}

	/**
	 * Returns whether an active industry overlay was applied.
	 */
	public function is_overlay_applied(): bool {
		return $this->overlay_applied;
	}

	/**
	 * Returns industry key of the applied overlay when overlay was applied; empty otherwise.
	 */
	public function get_overlay_industry_key(): string {
		return $this->overlay_industry_key;
	}

	/**
	 * Returns the section key that was resolved.
	 */
	public function get_section_key(): string {
		return $this->section_key;
	}

	/**
	 * Returns advisory compliance/caution rules for display (Prompt 407). Empty when none.
	 *
	 * @return list<array{rule_key: string, severity: string, caution_summary: string}>
	 */
	public function get_compliance_warnings(): array {
		return $this->compliance_warnings;
	}

	/**
	 * Machine-readable shape for export/debug (trace + composed keys list).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'section_key'            => $this->section_key,
			'base_documentation_id'  => $this->base_documentation_id,
			'overlay_applied'        => $this->overlay_applied,
			'overlay_industry_key'   => $this->overlay_industry_key,
			'composed_doc_keys'      => array_keys( $this->composed_doc ),
		);
	}
}
