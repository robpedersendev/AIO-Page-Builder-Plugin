<?php
/**
 * Result of composing base page one-pager with optional industry overlay (industry-page-onepager-overlay-schema).
 * Immutable; carries composed one-pager and trace metadata. Section-order and base structure preserved.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only result: composed page one-pager (base + overlay in allowed regions) and trace.
 */
final class Composed_Page_OnePager_Result {

	/** @var array<string, mixed> Composed one-pager (base + overlay fields). */
	private array $composed_onepager;

	/** @var string Base documentation_id when base was present; empty otherwise. */
	private string $base_documentation_id;

	/** @var bool Whether an active overlay was applied. */
	private bool $overlay_applied;

	/** @var string Industry key of applied overlay when overlay_applied is true; empty otherwise. */
	private string $overlay_industry_key;

	/** @var string Page template key that was resolved. */
	private string $page_template_key;

	/**
	 * @param array<string, mixed> $composed_onepager   Effective one-pager (base + overlay in allowed regions).
	 * @param string               $base_documentation_id Base doc id or empty.
	 * @param bool                 $overlay_applied    True if an active overlay was merged.
	 * @param string               $overlay_industry_key Industry key of overlay or empty.
	 * @param string               $page_template_key  Page template key that was resolved.
	 */
	public function __construct( array $composed_onepager, string $base_documentation_id, bool $overlay_applied, string $overlay_industry_key, string $page_template_key ) {
		$this->composed_onepager   = $composed_onepager;
		$this->base_documentation_id = $base_documentation_id;
		$this->overlay_applied    = $overlay_applied;
		$this->overlay_industry_key = $overlay_industry_key;
		$this->page_template_key  = $page_template_key;
	}

	/**
	 * Returns the composed one-pager (base + overlay in allowed regions). May be empty when no base exists.
	 *
	 * @return array<string, mixed>
	 */
	public function get_composed_onepager(): array {
		return $this->composed_onepager;
	}

	public function get_base_documentation_id(): string {
		return $this->base_documentation_id;
	}

	public function is_overlay_applied(): bool {
		return $this->overlay_applied;
	}

	public function get_overlay_industry_key(): string {
		return $this->overlay_industry_key;
	}

	public function get_page_template_key(): string {
		return $this->page_template_key;
	}

	/**
	 * Machine-readable shape for export/debug (trace + composed keys list).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'page_template_key'     => $this->page_template_key,
			'base_documentation_id'  => $this->base_documentation_id,
			'overlay_applied'        => $this->overlay_applied,
			'overlay_industry_key'   => $this->overlay_industry_key,
			'composed_onepager_keys' => array_keys( $this->composed_onepager ),
		);
	}
}
