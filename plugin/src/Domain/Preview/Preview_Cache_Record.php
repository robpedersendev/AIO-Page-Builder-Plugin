<?php
/**
 * Immutable record for a single preview snapshot cache entry (Prompt 184, spec §55.8).
 * Carries cache key, type, template key, version hash, rendered HTML, and metadata for invalidation and staleness.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Value object for one cached preview snapshot. No secrets; HTML is renderer output (synthetic data only).
 *
 * Example preview-cache record payload (to_array() / from_array()):
 * [
 *   'cache_key'      => 'aio_preview_abc123...',
 *   'type'           => 'section',
 *   'template_key'   => 'hero_conv_01',
 *   'version_hash'   => 'def456...',
 *   'html'           => '<div class="aio-s-hero_conv_01">...</div>',
 *   'created_at'     => 1234567890,
 *   'reduced_motion' => false,
 *   'animation_tier' => 'none',
 * ]
 */
final class Preview_Cache_Record {

	/** Cache entry type: section template preview. */
	public const TYPE_SECTION = 'section';

	/** Cache entry type: page template preview. */
	public const TYPE_PAGE = 'page';

	/** @var string Unique cache key (hash of context + version). */
	private string $cache_key;

	/** @var string TYPE_SECTION | TYPE_PAGE */
	private string $type;

	/** @var string Section or page template internal_key. */
	private string $template_key;

	/** @var string Hash of definition version/structural data used for invalidation. */
	private string $version_hash;

	/** @var string Rendered preview HTML (from real renderer; synthetic data only). */
	private string $html;

	/** @var int Unix timestamp when the record was created. */
	private int $created_at;

	/** @var bool Whether reduced_motion was applied in this preview. */
	private bool $reduced_motion;

	/** @var string Effective animation tier (none when reduced_motion). */
	private string $animation_tier;

	/**
	 * @param string $cache_key
	 * @param string $type TYPE_SECTION | TYPE_PAGE
	 * @param string $template_key
	 * @param string $version_hash
	 * @param string $html
	 * @param int    $created_at
	 * @param bool   $reduced_motion
	 * @param string $animation_tier
	 */
	public function __construct(
		string $cache_key,
		string $type,
		string $template_key,
		string $version_hash,
		string $html,
		int $created_at,
		bool $reduced_motion = false,
		string $animation_tier = 'none'
	) {
		$this->cache_key      = $cache_key;
		$this->type           = $type;
		$this->template_key   = $template_key;
		$this->version_hash   = $version_hash;
		$this->html           = $html;
		$this->created_at     = $created_at;
		$this->reduced_motion = $reduced_motion;
		$this->animation_tier = $animation_tier;
	}

	public function get_cache_key(): string {
		return $this->cache_key;
	}

	public function get_type(): string {
		return $this->type;
	}

	public function get_template_key(): string {
		return $this->template_key;
	}

	public function get_version_hash(): string {
		return $this->version_hash;
	}

	public function get_html(): string {
		return $this->html;
	}

	public function get_created_at(): int {
		return $this->created_at;
	}

	public function is_reduced_motion(): bool {
		return $this->reduced_motion;
	}

	public function get_animation_tier(): string {
		return $this->animation_tier;
	}

	/**
	 * Exports to array for persistence and debugging.
	 *
	 * @return array{cache_key: string, type: string, template_key: string, version_hash: string, html: string, created_at: int, reduced_motion: bool, animation_tier: string}
	 */
	public function to_array(): array {
		return array(
			'cache_key'      => $this->cache_key,
			'type'           => $this->type,
			'template_key'   => $this->template_key,
			'version_hash'   => $this->version_hash,
			'html'           => $this->html,
			'created_at'     => $this->created_at,
			'reduced_motion' => $this->reduced_motion,
			'animation_tier' => $this->animation_tier,
		);
	}

	/**
	 * Builds a record from a stored array (e.g. from options).
	 *
	 * @param array<string, mixed> $data
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			(string) ( $data['cache_key'] ?? '' ),
			(string) ( $data['type'] ?? self::TYPE_SECTION ),
			(string) ( $data['template_key'] ?? '' ),
			(string) ( $data['version_hash'] ?? '' ),
			(string) ( $data['html'] ?? '' ),
			(int) ( $data['created_at'] ?? 0 ),
			! empty( $data['reduced_motion'] ),
			(string) ( $data['animation_tier'] ?? 'none' )
		);
	}
}
