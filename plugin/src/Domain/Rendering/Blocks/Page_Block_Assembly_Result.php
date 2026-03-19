<?php
/**
 * Result of assembling ordered section render results into page-level block content (spec §17.5, §18, rendering-contract §3.2, §6.2).
 * Save-ready block content; no page creation or persistence in this layer.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object. Payload keys are stable; do not rename.
 *
 * - source_type: 'page_template' | 'composition'.
 * - source_key: Page template internal_key or composition_id.
 * - ordered_sections: Ordered list of section render payloads (to_array() shape) that were assembled.
 * - block_content: Serialized native block markup (save-ready for post_content).
 * - dynamic_dependencies: List of block names that use render callbacks (justified cases); empty when fully durable.
 * - survivability_notes: Human-readable notes about durability (e.g. core_blocks_only).
 * - errors: Non-empty when assembly failed or was partial; no payload use for save.
 *
 * Example page assembly result (from to_array()), abbreviated block_content:
 *
 * [
 *   'source_type'          => 'page_template',
 *   'source_key'           => 'tpl_landing',
 *   'ordered_sections'     => [ /* section to_array() payloads in order *\/ ],
 *   'block_content'        => "<!-- wp:html -->\n<div class=\"aio-s-st01_hero ...\">...</div>\n<!-- /wp:html -->\n\n<!-- wp:html -->...",
 *   'dynamic_dependencies' => [],
 *   'survivability_notes'   => [ 'durable_native_blocks' ],
 *   'errors'               => [],
 * ]
 */
final class Page_Block_Assembly_Result {

	public const SOURCE_TYPE_PAGE_TEMPLATE = 'page_template';
	public const SOURCE_TYPE_COMPOSITION   = 'composition';

	/** @var string */
	private string $source_type;

	/** @var string */
	private string $source_key;

	/** @var array<int, array<string, mixed>> */
	private array $ordered_sections;

	/** @var string */
	private string $block_content;

	/** @var array<int, string> */
	private array $dynamic_dependencies;

	/** @var array<int, string> */
	private array $survivability_notes;

	/** @var array<int, string> */
	private array $errors;

	/**
	 * @param string                           $source_type            One of SOURCE_TYPE_*.
	 * @param string                           $source_key             Template internal_key or composition_id.
	 * @param array<int, array<string, mixed>> $ordered_sections       Section payloads in page order.
	 * @param string                           $block_content          Serialized block markup.
	 * @param array<int, string>               $dynamic_dependencies   Block names using render callbacks.
	 * @param array<int, string>               $survivability_notes    Durability notes.
	 * @param array<int, string>               $errors                 Assembly errors; non-empty when invalid.
	 */
	public function __construct(
		string $source_type,
		string $source_key,
		array $ordered_sections,
		string $block_content,
		array $dynamic_dependencies = array(),
		array $survivability_notes = array(),
		array $errors = array()
	) {
		$this->source_type          = $source_type;
		$this->source_key           = $source_key;
		$this->ordered_sections     = $ordered_sections;
		$this->block_content        = $block_content;
		$this->dynamic_dependencies = $dynamic_dependencies;
		$this->survivability_notes  = $survivability_notes;
		$this->errors               = $errors;
	}

	public function get_source_type(): string {
		return $this->source_type;
	}

	public function get_source_key(): string {
		return $this->source_key;
	}

	/** @return array<int, array<string, mixed>> */
	public function get_ordered_sections(): array {
		return $this->ordered_sections;
	}

	public function get_block_content(): string {
		return $this->block_content;
	}

	/** @return array<int, string> */
	public function get_dynamic_dependencies(): array {
		return $this->dynamic_dependencies;
	}

	/** @return array<int, string> */
	public function get_survivability_notes(): array {
		return $this->survivability_notes;
	}

	/** @return array<int, string> */
	public function get_errors(): array {
		return $this->errors;
	}

	public function is_valid(): bool {
		return empty( $this->errors );
	}

	/**
	 * Full payload for logging or downstream use.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'source_type'          => $this->source_type,
			'source_key'           => $this->source_key,
			'ordered_sections'     => $this->ordered_sections,
			'block_content'        => $this->block_content,
			'dynamic_dependencies' => $this->dynamic_dependencies,
			'survivability_notes'  => $this->survivability_notes,
			'errors'               => $this->errors,
		);
	}
}
