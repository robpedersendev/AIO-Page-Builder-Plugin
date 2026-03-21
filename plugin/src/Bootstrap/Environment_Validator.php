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
use AIOPageBuilder\Infrastructure\Config\Option_Names;

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

	public const SEVERITY_BLOCKING = 'blocking';
	public const SEVERITY_WARNING  = 'warning';
	public const SEVERITY_INFO     = 'info';

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

	/**
	 * Builds and (optionally) persists environment diagnostics snapshot.
	 *
	 * @param bool $persist Whether to persist to option.
	 * @return array{generated_at: string, checks: list<array{category: string, severity: string, code: string, message: string, is_blocking: bool}>}
	 */
	public function build_snapshot( bool $persist = true ): array {
		$this->validate();
		$snapshot = array(
			'generated_at' => gmdate( 'c' ),
			'checks'       => array_map(
				static fn( Validation_Result $r ) => $r->to_array(),
				$this->results
			),
		);
		if ( $persist ) {
			\update_option( Option_Names::PB_ENVIRONMENT_DIAGNOSTICS, $snapshot, false );
		}
		return $snapshot;
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
					sprintf( 'WordPress %s or newer is required. Current: %s.', $min_wp, ( $wp_ver !== '' ? $wp_ver : 'unknown' ) ),
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
		if ( ! function_exists( 'wp_get_theme' ) ) {
			return;
		}
		$theme = \wp_get_theme();
		$slug  = strtolower( (string) $theme->get_stylesheet() );
		if ( $slug === 'generatepress' ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_THEME_POSTURE,
					self::SEVERITY_INFO,
					'theme_generatepress_supported',
					__( 'Theme: GeneratePress (supported target).', 'aio-page-builder' ),
					false
				)
			);
			return;
		}
		$name  = (string) $theme->get( 'Name' );
		$label = $name !== '' ? $name : $slug;
		$this->add(
			new Validation_Result(
				self::CATEGORY_THEME_POSTURE,
				self::SEVERITY_WARNING,
				'theme_not_generatepress_warning',
				sprintf(
					/* translators: %s: theme name */
					__( 'Theme: %s. GeneratePress is the supported target; other block-capable themes are generally supported but may require additional review.', 'aio-page-builder' ),
					$label !== '' ? $label : __( 'Unknown', 'aio-page-builder' )
				),
				false
			)
		);
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
				$theme_name = (string) $theme->get( 'Name' );
				$name       = $theme_name !== '' ? $theme_name : $slug;
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
				if ( ! is_plugin_active( $plugin_file ) ) {
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
		$this->check_uploads_readiness();
		$this->check_scheduler_readiness();
		$this->check_reporting_transport_readiness();
		$this->check_provider_readiness();
	}

	private function check_uploads_readiness(): void {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return;
		}
		$dir  = \wp_upload_dir( null, false );
		$err  = (string) $dir['error'];
		$path = (string) $dir['basedir'];
		if ( $err !== '' ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_BLOCKING,
					'uploads_unavailable_blocking',
					__( 'Uploads directory is not available. Export, import, and support packages require a working uploads directory.', 'aio-page-builder' ),
					true
				)
			);
			return;
		}
		// * Direct is_writable: bootstrap validation before WP_Filesystem is guaranteed available.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- uploads path check at bootstrap.
		if ( $path === '' || ! is_dir( $path ) || ! is_writable( $path ) ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_BLOCKING,
					'uploads_not_writable_blocking',
					__( 'Uploads directory is not writable. Export/import/preview caches require filesystem write access.', 'aio-page-builder' ),
					true
				)
			);
		} else {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_INFO,
					'uploads_ready',
					__( 'Uploads directory is available and writable.', 'aio-page-builder' ),
					false
				)
			);
		}
	}

	private function check_reporting_transport_readiness(): void {
		if ( ! function_exists( 'wp_mail' ) ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_WARNING,
					'wp_mail_unavailable_warning',
					__( 'wp_mail() is not available. Reporting transports may fail.', 'aio-page-builder' ),
					false
				)
			);
			return;
		}
		$log         = \get_option( Option_Names::REPORTING_LOG, array() );
		$last        = is_array( $log ) && ! empty( $log ) ? end( $log ) : null;
		$last_status = is_array( $last ) ? (string) ( $last['status'] ?? '' ) : '';
		if ( $last_status === 'failed' ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_WARNING,
					'reporting_last_failure_warning',
					__( 'Reporting last delivery attempt failed. See reporting log for details.', 'aio-page-builder' ),
					false
				)
			);
		} else {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_INFO,
					'reporting_transport_callable',
					__( 'Reporting transport is available (wp_mail).', 'aio-page-builder' ),
					false
				)
			);
		}
	}

	private function check_scheduler_readiness(): void {
		$disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
		if ( $disabled ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_WARNING,
					'wp_cron_disabled_warning',
					__( 'WP-Cron is disabled (DISABLE_WP_CRON). Scheduled reporting and automated maintenance may not run unless a real cron triggers wp-cron.php.', 'aio-page-builder' ),
					false
				)
			);
		}
		$hook       = \AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Scheduler::CRON_HOOK;
		$registered = function_exists( 'has_action' ) ? ( has_action( $hook ) !== false ) : true;
		if ( ! $registered ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_WARNING,
					'heartbeat_hook_not_registered_warning',
					__( 'Heartbeat cron hook is not registered. Reporting provider may not be loaded.', 'aio-page-builder' ),
					false
				)
			);
		}
		$scheduled = function_exists( 'wp_next_scheduled' ) ? \wp_next_scheduled( $hook ) : false;
		if ( ! $scheduled ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_WARNING,
					'heartbeat_not_scheduled_warning',
					__( 'Heartbeat schedule is not currently set. Activate the plugin (or re-save schedules) to register cron events.', 'aio-page-builder' ),
					false
				)
			);
		}
	}

	private function check_provider_readiness(): void {
		$provider_ref = \get_option( Option_Names::PROVIDER_CONFIG_REF, array() );
		$has_any      = is_array( $provider_ref ) && ! empty( $provider_ref );
		if ( ! $has_any ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_WARNING,
					'ai_provider_not_configured_warning',
					__( 'No AI provider configuration was found. AI planning workflows will not run until a provider is configured.', 'aio-page-builder' ),
					false
				)
			);
		}
		$health = \get_option( Option_Names::PROVIDER_HEALTH_STATE, array() );
		if ( is_array( $health ) && isset( $health['last_connection_test_status'] ) && (string) $health['last_connection_test_status'] === 'failed' ) {
			$this->add(
				new Validation_Result(
					self::CATEGORY_RUNTIME_READINESS,
					self::SEVERITY_WARNING,
					'ai_provider_last_test_failed_warning',
					__( 'Last AI provider connection test failed. Update credentials and re-test.', 'aio-page-builder' ),
					false
				)
			);
		}
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
		$data    = get_plugin_data( $path, false, false );
		$version = (string) $data['Version'];
		return $version !== '' ? $version : null;
	}
}
