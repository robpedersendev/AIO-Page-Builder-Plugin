<?php
/**
 * Central resolution of plugin-owned paths under the WordPress uploads directory (spec §9.8, §52.2). No export/artifact generation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Files;

defined( 'ABSPATH' ) || exit;

/**
 * Deterministic paths for artifacts, exports, docs, restore-temp, and support bundles.
 * All paths under uploads; no user-supplied path concatenation without sanitization; no secrets in path names.
 */
final class Plugin_Path_Manager {

	/** Subdirectory under uploads for all plugin file outputs. */
	private const UPLOAD_SUBDIR = 'aio-page-builder';

	/** Artifacts (AI outputs, file refs). Exportable when included in package. */
	public const CHILD_ARTIFACTS = 'artifacts';

	/** Export packages (ZIP staging, manifests). Permission-gated download. */
	public const CHILD_EXPORTS = 'exports';

	/** Documentation bundles / one-pager outputs. Exportable. */
	public const CHILD_DOCS = 'docs';

	/** Temporary workspace for restore package preparation. Temporary; excluded from export by default. */
	public const CHILD_RESTORE_TEMP = 'restore-temp';

	/** Support bundle outputs. Exportable when requested. */
	public const CHILD_SUPPORT_BUNDLES = 'support-bundles';

	/** Allowed child dir names for resolution. */
	private const ALLOWED_CHILDREN = array(
		self::CHILD_ARTIFACTS,
		self::CHILD_EXPORTS,
		self::CHILD_DOCS,
		self::CHILD_RESTORE_TEMP,
		self::CHILD_SUPPORT_BUNDLES,
	);

	/** Regex for safe relative segment: alphanumeric, hyphen, underscore only; no path traversal. */
	private const SAFE_SEGMENT_REGEX = '#^[a-zA-Z0-9_-]+$#';

	/** @var array{basedir: string}|null Cached upload dir to avoid repeated calls. */
	private ?array $upload_dir = null;

	/**
	 * Returns the plugin base directory under uploads (trailing slash). Uses wp_upload_dir().
	 *
	 * @return string Absolute path; empty if uploads dir not available.
	 */
	public function get_uploads_base(): string {
		$basedir = $this->get_upload_basedir();
		if ( $basedir === '' ) {
			return '';
		}
		return \trailingslashit( $basedir . '/' . self::UPLOAD_SUBDIR );
	}

	/**
	 * Returns the absolute path for a named child directory (trailing slash). No user segment appended.
	 *
	 * @param string $child_name One of CHILD_* constants.
	 * @return string Absolute path or empty if child name invalid or base unavailable.
	 */
	public function get_child_path( string $child_name ): string {
		if ( ! in_array( $child_name, self::ALLOWED_CHILDREN, true ) ) {
			return '';
		}
		$base = $this->get_uploads_base();
		if ( $base === '' ) {
			return '';
		}
		return \trailingslashit( $base . $child_name );
	}

	/**
	 * Returns a path under a named child with one optional safe segment (e.g. run_id, package_id). Rejects path traversal.
	 *
	 * @param string $child_name One of CHILD_* constants.
	 * @param string $segment    Single path segment (alphanumeric, hyphen, underscore only); empty for child root.
	 * @return string Absolute path (trailing slash) or empty if invalid.
	 */
	public function get_child_path_with_segment( string $child_name, string $segment ): string {
		$child_path = $this->get_child_path( $child_name );
		if ( $child_path === '' ) {
			return '';
		}
		if ( $segment === '' ) {
			return $child_path;
		}
		if ( ! $this->is_safe_segment( $segment ) ) {
			return '';
		}
		return \trailingslashit( $child_path . $segment );
	}

	/**
	 * Returns whether the plugin uploads base directory exists on the filesystem.
	 *
	 * @return bool
	 */
	public function base_exists(): bool {
		$base = $this->get_uploads_base();
		if ( $base === '' ) {
			return false;
		}
		return is_dir( $base );
	}

	/**
	 * Returns whether a named child directory exists (or would be the immediate child under base).
	 *
	 * @param string $child_name One of CHILD_* constants.
	 * @return bool
	 */
	public function child_exists( string $child_name ): bool {
		$path = $this->get_child_path( $child_name );
		if ( $path === '' ) {
			return false;
		}
		return is_dir( $path );
	}

	/**
	 * Ensures the plugin base directory exists. Creates with 0755 if missing. Caller responsible for capability checks.
	 *
	 * @return bool True if base exists or was created successfully.
	 */
	public function ensure_base(): bool {
		$basedir = $this->get_upload_basedir();
		if ( $basedir === '' ) {
			return false;
		}
		$base = $basedir . '/' . self::UPLOAD_SUBDIR;
		if ( is_dir( $base ) ) {
			return true;
		}
		return wp_mkdir_p( $base );
	}

	/**
	 * Ensures a named child directory exists under base. Creates with 0755 if missing.
	 *
	 * @param string $child_name One of CHILD_* constants.
	 * @return bool True if child exists or was created successfully.
	 */
	public function ensure_child( string $child_name ): bool {
		$path = $this->get_child_path( $child_name );
		if ( $path === '' ) {
			return false;
		}
		if ( is_dir( $path ) ) {
			return true;
		}
		if ( ! $this->ensure_base() ) {
			return false;
		}
		return wp_mkdir_p( $path );
	}

	/**
	 * Checks that a path is under the plugin uploads base (for future cleanup/ownership checks). Rejects path traversal.
	 *
	 * @param string $absolute_path Absolute filesystem path.
	 * @return bool True if path is within plugin uploads base.
	 */
	public function is_under_base( string $absolute_path ): bool {
		$base = $this->get_uploads_base();
		if ( $base === '' ) {
			return false;
		}
		$real_base = realpath( $base );
		$real_path = realpath( $absolute_path );
		if ( $real_base === false || $real_path === false ) {
			return false;
		}
		return strpos( $real_path, $real_base ) === 0;
	}

	/**
	 * Validates a single path segment for use in get_child_path_with_segment. Rejects ., .., /, \, and other unsafe chars.
	 *
	 * @param string $segment Segment to check.
	 * @return bool
	 */
	public function is_safe_segment( string $segment ): bool {
		if ( $segment === '' ) {
			return true;
		}
		if ( strpos( $segment, '..' ) !== false || strpos( $segment, '/' ) !== false || strpos( $segment, '\\' ) !== false ) {
			return false;
		}
		return (bool) preg_match( self::SAFE_SEGMENT_REGEX, $segment );
	}

	/**
	 * Returns WordPress uploads basedir. Internal.
	 *
	 * @return string Absolute path without trailing slash, or empty if uploads unavailable.
	 */
	private function get_upload_basedir(): string {
		if ( $this->upload_dir !== null ) {
			return $this->upload_dir['basedir'] ?? '';
		}
		$upload = \wp_upload_dir();
		if ( ! empty( $upload['error'] ) || empty( $upload['basedir'] ) ) {
			return '';
		}
		$this->upload_dir = array( 'basedir' => rtrim( $upload['basedir'], '/\\' ) );
		return $this->upload_dir['basedir'];
	}
}
