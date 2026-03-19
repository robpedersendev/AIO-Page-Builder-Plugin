<?php
/**
 * Environment and dependency validation. Inspect and report only; no mutation.
 * Used at activation and for admin diagnostics (spec §6.13, §53.1).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Dependency_Requirements;

/**
 * Single validation outcome: category, severity, code, message, blocking flag.
 */
final class Validation_Result {

	/** @var string Category: platform (WP/PHP), required_dependency, optional_integration, theme_posture, runtime_readiness */
	public string $category;

	/** @var string Severity: blocking_failure, warning, informational */
	public string $severity;

	/** @var string Stable code for this rule (see environment-validation-contract.md) */
	public string $code;

	/** @var string Admin-safe message; no sensitive server details */
	public string $message;

	/** @var bool Whether this result blocks activation or the affected workflow */
	public bool $is_blocking;

	public function __construct( string $category, string $severity, string $code, string $message, bool $is_blocking ) {
		$this->category    = $category;
		$this->severity    = $severity;
		$this->code        = $code;
		$this->message     = $message;
		$this->is_blocking = $is_blocking;
	}

	/** @return array{category: string, severity: string, code: string, message: string, is_blocking: bool} */
	public function to_array(): array {
		return array(
			'category'    => $this->category,
			'severity'    => $this->severity,
			'code'        => $this->code,
			'message'     => $this->message,
			'is_blocking' => $this->is_blocking,
		);
	}
}

/**
 * Runs environment and dependency checks. Returns structured results; does not mutate state.
 */
final class Environment_Validator {

	public const SEVERITY_BLOCKING = 'blocking_failure';
	public const SEVERITY_WARNING  = 'warning';
	public const SEVERITY_INFO     = 'informational';

	public const CATEGORY_PLATFORM             = 'platform';
	public const CATEGORY_REQUIRED_DEPENDENCY  = 'required_dependency';
	public const CATEGORY_OPTIONAL_INTEGRATION = 'optional_integration';
	public const CATEGORY_THEME_POSTURE        = 'theme_posture';
	public const CATEGORY_RUNTIME_READINESS    = 'runtime_readiness';
	public const CATEGORY_EXTENSION_PACK       = 'extension_pack';

	/** Theme slugs that are in the extension-pack additional-tested set (spec §54, Prompt 127). Informational only. */
	private const EXTENSION_PACK_THEMES = array( 'generatepress', 'astra', 'kadence' );

	/** Plugin basenames that are in the extension-pack additional-tested set. Informational only. */
	private const EXTENSION_PACK_PLUGINS = array( 'wordpress-seo/wp-seo.php' );

	/** Display names for extension-pack plugins (plugin_file => name). */
	private const EXTENSION_PACK_PLUGIN_NAMES = array( 'wordpress-seo/wp-seo.php' => 'Yoast SEO' );

	/** @var Validation_Result[] */
	private array $results = array();

	/**
	 * Runs all validation checks. Use for activation-time or diagnostics.
	 * Call get_results() after to retrieve the list; use to_lifecycle_result() for activation.
	 *
	 * @return void
	 */
	public function validate(): void {
		$this->results = array();
		$this->run_platform_checks();
		$this->run_required_dependency_checks();
		$this->run_optional_dependency_checks();
		$this->run_theme_posture_checks();
		$this->run_extension_pack_detection();
		$this->run_runtime_readiness_checks();
	}

	/** @return Validation_Result[] */
	public function get_results(): array {
		return $this->results;
	}

	/**
	 * Returns true if no blocking result exists.
	 *
	 * @return bool
	 */
	public function passes(): bool {
		foreach ( $this->results as $r ) {
			if ( $r->is_blocking ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * First blocking result message, or empty string. For Lifecycle_Result.
	 *
	 * @return string
	 */
	public function get_first_blocking_message(): string {
		foreach ( $this->results as $r ) {
			if ( $r->is_blocking ) {
				return $r->message;
			}
		}
		return '';
	}

	/**
	 * Converts validation outcome to a Lifecycle_Result for activation phase.
	 *
	 * @return Lifecycle_Result
	 */
	public function to_lifecycle_result( string $phase ): Lifecycle_Result {
		if ( $this->passes() ) {
			return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', $phase, array( 'validation_results' => array_map( fn( Validation_Result $r ) => $r->to_array(), $this->results ) ) );
		}
		return new Lifecycle_Result(
			Lifecycle_Result::STATUS_BLOCKING_FAILURE,
			$this->get_first_blocking_message(),
			$phase,
			array( 'validation_results' => array_map( fn( Validation_Result $r ) => $r->to_array(), $this->results ) )
		);
	}

	private function add( Validation_Result $r ): void {
		$this->results[] = $r;
	}

	private function run_platform_checks(): void {
		$min_wp = Dependency_Requirements::min_wordpress_version();
		$wp_ver = $GLOBALS['wp_version'] ?? '';
		if ( $wp_ver === '' || ! version_compare( $wp_ver, $min_wp, '>=' ) ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_PLATFORM,
					self::SEVERITY_BLOCKING,
					'wp_version_blocking',
					sprintf( 'WordPress %s or newer is required. Current: %s.', $min_wp, $wp_ver ?: 'unknown' ),
					true
				)
			);
		}

		$min_php = Dependency_Requirements::min_php_version();
		$php_ver = PHP_VERSION;
		if ( ! version_compare( $php_ver, $min_php, '>=' ) ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_PLATFORM,
					self::SEVERITY_BLOCKING,
					'php_version_blocking',
					sprintf( 'PHP %s or newer is required. Current: %s.', $min_php, $php_ver ),
					true
				)
			);
		}
	}

	private function run_required_dependency_checks(): void {
		$this->load_plugin_api();
		if ( ! function_exists( 'is_plugin_active' ) ) {
			return;
		}
		$required = Dependency_Requirements::get_required();
		foreach ( $required as $key => $def ) {
			$file = $def['plugin_file'];
			$name = $def['name'];
			$min  = $def['min_version'];
			if ( ! is_plugin_active( $file ) ) {
				$this->add(
					new Validation_Result(
						self::CATEGORY_REQUIRED_DEPENDENCY,
						self::SEVERITY_BLOCKING,
						$key . '_missing_blocking',
						sprintf( '%s is required (minimum version %s). Please install and activate it.', $name, $min ),
						true
					)
				);
				continue;
			}
			$version = $this->get_plugin_version( $file );
			if ( $version !== null && ! version_compare( $version, $min, '>=' ) ) {
				$this->add(
					new Validation_Result(
						self::CATEGORY_REQUIRED_DEPENDENCY,
						self::SEVERITY_BLOCKING,
						$key . '_version_blocking',
						sprintf( '%s version %s or newer is required. Current: %s.', $name, $min, $version ),
						true
					)
				);
			}
		}
	}

	private function run_optional_dependency_checks(): void {
		$this->load_plugin_api();
		if ( ! function_exists( 'is_plugin_active' ) ) {
			return;
		}
		$optional = Dependency_Requirements::get_optional();
		foreach ( $optional as $key => $def ) {
			$file = $def['plugin_file'];
			$name = $def['name'];
			if ( ! is_plugin_active( $file ) ) {
				$this->add(
					new Validation_Result(
						self::CATEGORY_OPTIONAL_INTEGRATION,
						self::SEVERITY_WARNING,
						$key . '_missing_warning',
						sprintf( '%s is not active. Related workflows will be disabled.', $name ),
						false
					)
				);
			}
		}
	}

	private function run_theme_posture_checks(): void {
		// Placeholder: theme compatibility / GeneratePress posture. Later prompt.
		// No add() unless we have a concrete check; avoids noise.
	}

	/**
	 * Adds informational results when current theme or plugins are in the extension-pack tested set (spec §54, Prompt 127).
	 * Non-blocking; for diagnostics and environment summary only.
	 */
	private function run_extension_pack_detection(): void {
		if ( function_exists( 'wp_get_theme' ) ) {
			$theme = wp_get_theme();
			$slug  = $theme->get_stylesheet();
			if ( $slug !== '' && in_array( strtolower( $slug ), self::EXTENSION_PACK_THEMES, true ) ) {
				$name = $theme->get( 'Name' ) ?: $slug;
				$this->add(
					new Validation_Result(
						self::CATEGORY_EXTENSION_PACK,
						self::SEVERITY_INFO,
						'extension_pack_theme_detected',
						sprintf( 'Theme "%s" is in the extension-pack tested set. See compatibility matrix.', $name ),
						false
					)
				);
			}
		}
		$this->load_plugin_api();
		if ( function_exists( 'is_plugin_active' ) ) {
			foreach ( self::EXTENSION_PACK_PLUGINS as $plugin_file ) {
				if ( ! is_plugin_active( $plugin_file ) || ! isset( self::EXTENSION_PACK_PLUGIN_NAMES[ $plugin_file ] ) ) {
					continue;
				}
				$this->add(
					new Validation_Result(
						self::CATEGORY_EXTENSION_PACK,
						self::SEVERITY_INFO,
						'extension_pack_plugin_detected',
						sprintf( 'Plugin "%s" detected; extension-pack coexistence tested. See compatibility matrix.', self::EXTENSION_PACK_PLUGIN_NAMES[ $plugin_file ] ),
						false
					)
				);
			}
		}
	}

	private function run_runtime_readiness_checks(): void {
		// Placeholder: uploads directory readiness.
		// Placeholder: mail/report transport.
		// Placeholder: scheduler readiness.
		// Placeholder: provider readiness.
		// No add() for now.
	}

	private function load_plugin_api(): void {
		if ( function_exists( 'is_plugin_active' ) ) {
			return;
		}
		if ( defined( 'AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES' ) && is_readable( AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES ) ) {
			require_once AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES;
			return;
		}
		if ( is_readable( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	/**
	 * Gets plugin version from WordPress plugin data. Returns null if not readable.
	 *
	 * @param string $plugin_file Plugin basename (e.g. 'advanced-custom-fields-pro/acf.php').
	 * @return string|null
	 */
	private function get_plugin_version( string $plugin_file ): ?string {
		if ( ! function_exists( 'get_plugin_data' ) && is_readable( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! function_exists( 'get_plugin_data' ) ) {
			return null;
		}
		$path = WP_PLUGIN_DIR . '/' . $plugin_file;
		if ( ! is_readable( $path ) ) {
			return null;
		}
		$data = get_plugin_data( $path, false, false );
		return isset( $data['Version'] ) ? (string) $data['Version'] : null;
	}
}
