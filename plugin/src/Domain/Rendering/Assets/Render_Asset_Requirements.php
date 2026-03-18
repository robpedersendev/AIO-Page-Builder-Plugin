<?php
/**
 * Declarative asset requirement records for rendering (spec §7.7, §17, css-selector-contract).
 * Identifies logical handles, source refs, and scope. No persistent storage; policy-oriented.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Single asset requirement record: handle, source ref, scope.
 * Stable shape for diagnostics and loading control.
 */
final class Render_Asset_Requirements {

	/** Scope: front-end (public page). */
	public const SCOPE_FRONTEND = 'frontend';

	/** Scope: admin (editor/diagnostics). */
	public const SCOPE_ADMIN = 'admin';

	/** Scope: section-level (tied to section_key). */
	public const SCOPE_SECTION = 'section';

	/** @var string Logical asset handle or dependency key (e.g. aio-render-section-st01). */
	private string $handle;

	/** @var string Source section_key or template key. */
	private string $source_ref;

	/** @var string One of SCOPE_*. */
	private string $scope;

	/** @var array<string, mixed> Optional extra metadata (no sensitive paths). */
	private array $meta;

	/**
	 * @param string               $handle    Logical handle.
	 * @param string               $source_ref Section or template reference.
	 * @param string               $scope     SCOPE_FRONTEND | SCOPE_ADMIN | SCOPE_SECTION.
	 * @param array<string, mixed> $meta     Optional metadata.
	 */
	public function __construct( string $handle, string $source_ref, string $scope, array $meta = array() ) {
		$this->handle     = $handle;
		$this->source_ref = $source_ref;
		$this->scope      = $scope;
		$this->meta       = $meta;
	}

	public function get_handle(): string {
		return $this->handle;
	}

	public function get_source_ref(): string {
		return $this->source_ref;
	}

	public function get_scope(): string {
		return $this->scope;
	}

	/** @return array<string, mixed> */
	public function get_meta(): array {
		return $this->meta;
	}

	/**
	 * @return array<string, mixed> Stable summary for reporting.
	 */
	public function to_array(): array {
		return array(
			'handle'     => $this->handle,
			'source_ref' => $this->source_ref,
			'scope'      => $this->scope,
			'meta'       => $this->meta,
		);
	}
}
