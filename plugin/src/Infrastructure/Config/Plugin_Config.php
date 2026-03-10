<?php
/**
 * Constants-aware plugin config. Read-only access to Constants and Versions.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Config;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Bootstrap\Constants;

/**
 * Exposes plugin identity and version data for services. Immutable.
 */
final class Plugin_Config {

	/** @return string Plugin version. */
	public function plugin_version(): string {
		return Constants::plugin_version();
	}

	/** @return string Plugin root path (trailing slash). */
	public function plugin_dir(): string {
		return Constants::plugin_dir();
	}

	/** @return string Plugin root URL (trailing slash). */
	public function plugin_url(): string {
		return Constants::plugin_url();
	}

	/** @return string Main plugin file path. */
	public function plugin_file(): string {
		return Constants::plugin_file();
	}

	/** @return array<string, string> Full version map from Versions::all(). */
	public function versions(): array {
		return Versions::all();
	}
}
