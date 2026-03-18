<?php
/**
 * Stable dependency requirement map for environment validation.
 * Defines required and optional plugins with minimum versions (spec §6.11, §6.12).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only dependency requirements. Do not mutate at runtime.
 */
final class Dependency_Requirements {

	/** Required plugins: activation blocked if missing or below minimum version. */
	private const REQUIRED = array(
		'acf_pro'        => array(
			'name'        => 'Advanced Custom Fields Pro',
			'plugin_file' => 'advanced-custom-fields-pro/acf.php',
			'min_version' => '6.2',
			'purpose'     => 'Section-level field architecture, field-group generation, structured edit UI.',
		),
		'generateblocks' => array(
			'name'        => 'GenerateBlocks',
			'plugin_file' => 'generateblocks/generateblocks.php',
			'min_version' => '2.0',
			'purpose'     => 'Block-layer composition and container/grid output for native block build model.',
		),
	);

	/** Optional plugins: missing triggers warning only; related workflows degrade. */
	private const OPTIONAL = array(
		'lpagery' => array(
			'name'        => 'LPagery',
			'plugin_file' => 'lpagery/lpagery.php',
			'purpose'     => 'Token-driven or bulk-generated page workflows.',
		),
	);

	/**
	 * Returns required plugin definitions. Keys are stable identifiers.
	 *
	 * @return array<string, array{name: string, plugin_file: string, min_version: string, purpose: string}>
	 */
	public static function get_required(): array {
		return self::REQUIRED;
	}

	/**
	 * Returns optional plugin definitions.
	 *
	 * @return array<string, array{name: string, plugin_file: string, purpose: string}>
	 */
	public static function get_optional(): array {
		return self::OPTIONAL;
	}

	/** @return string Minimum WordPress version (from Constants). */
	public static function min_wordpress_version(): string {
		return \AIOPageBuilder\Bootstrap\Constants::min_wp_version();
	}

	/** @return string Minimum PHP version (from Constants). */
	public static function min_php_version(): string {
		return \AIOPageBuilder\Bootstrap\Constants::min_php_version();
	}
}
