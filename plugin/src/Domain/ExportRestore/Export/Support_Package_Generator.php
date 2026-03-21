<?php
/**
 * Generates support-safe bundles: redacted settings/profile, registries, plans, environment, optional logs (spec §52.1, §48.10, §45.9, §59.15).
 *
 * Distinct from full backup; curated categories and mandatory redaction. No secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Bundle_Schema;
use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Mode_Keys;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Audit_Report_Service;
use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Serializer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Config\Versions;
use AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use AIOPageBuilder\Support\Logging\Error_Record;
use AIOPageBuilder\Support\Logging\Log_Categories;
use AIOPageBuilder\Support\Logging\Logger_Interface;
use AIOPageBuilder\Support\Logging\Log_Severities;

/**
 * First-class support package workflow: approved categories, systematic redaction, support manifest, controlled path.
 */
final class Support_Package_Generator {

	/** Support-safe included categories (contract §3). */
	private const INCLUDED = array(
		'settings',
		'profiles',
		'registries',
		'compositions',
		'plans',
		'token_sets',
		'uninstall_restore_metadata',
	);

	/** Optional categories allowed for support (logs, reporting_history). */
	private const OPTIONAL_ALLOWED = array( 'logs', 'reporting_history' );

	/** Categories never included in support package (contract §4). */
	private const EXCLUDED = array(
		'raw_ai_artifacts',
		'normalized_ai_outputs',
		'crawl_snapshots',
		'rollback_snapshots',
	);

	/** Keys whose values are redacted (spec §45.9). */
	private const REDACT_KEYS = array( 'api_key', 'password', 'secret', 'token', 'credential', 'auth' );

	/** @var Plugin_Path_Manager */
	private Plugin_Path_Manager $path_manager;

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Profile_Store */
	private Profile_Store $profile_store;

	/** @var Registry_Export_Serializer */
	private Registry_Export_Serializer $registry_serializer;

	/** @var Build_Plan_Repository */
	private Build_Plan_Repository $plan_repository;

	/** @var Export_Token_Set_Reader */
	private Export_Token_Set_Reader $token_set_reader;

	/** @var Export_Manifest_Builder */
	private Export_Manifest_Builder $manifest_builder;

	/** @var Export_Zip_Packager */
	private Export_Zip_Packager $packager;

	/** @var Logger_Interface|null */
	private ?Logger_Interface $logger;

	/** @var Template_Library_Support_Summary_Builder|null Optional; when set, support bundle includes template_library_support_summary (Prompt 198). */
	private ?Template_Library_Support_Summary_Builder $template_library_summary_builder;

	/** @var Industry_Override_Audit_Report_Service|null Optional; when set, support bundle includes industry_override_audit_summary (Prompt 437). */
	private ?Industry_Override_Audit_Report_Service $override_audit_report_service;

	public function __construct(
		Plugin_Path_Manager $path_manager,
		Settings_Service $settings,
		Profile_Store $profile_store,
		Registry_Export_Serializer $registry_serializer,
		Build_Plan_Repository $plan_repository,
		Export_Token_Set_Reader $token_set_reader,
		Export_Manifest_Builder $manifest_builder,
		Export_Zip_Packager $packager,
		?Logger_Interface $logger = null,
		?Template_Library_Support_Summary_Builder $template_library_summary_builder = null,
		?Industry_Override_Audit_Report_Service $override_audit_report_service = null
	) {
		$this->path_manager                     = $path_manager;
		$this->settings                         = $settings;
		$this->profile_store                    = $profile_store;
		$this->registry_serializer              = $registry_serializer;
		$this->plan_repository                  = $plan_repository;
		$this->token_set_reader                 = $token_set_reader;
		$this->manifest_builder                 = $manifest_builder;
		$this->packager                         = $packager;
		$this->logger                           = $logger;
		$this->template_library_summary_builder = $template_library_summary_builder;
		$this->override_audit_report_service    = $override_audit_report_service;
	}

	/**
	 * Generates a support-safe package. Writes ZIP to plugin exports path; logs result.
	 *
	 * @param list<string> $optional_included Optional categories to add (e.g. logs, reporting_history).
	 * @return Support_Package_Result
	 */
	public function generate( array $optional_included = array() ): Support_Package_Result {
		$log_ref  = 'support-pkg-' . gmdate( 'Y-m-d\TH:i:s\Z' );
		$included = array_values( self::INCLUDED );
		$excluded = array_values( array_unique( array_merge( self::EXCLUDED, Export_Bundle_Schema::EXCLUDED_CATEGORIES ) ) );
		foreach ( $optional_included as $cat ) {
			if ( in_array( $cat, self::OPTIONAL_ALLOWED, true ) && Export_Bundle_Schema::is_optional_category( $cat ) && ! in_array( $cat, $included, true ) ) {
				$included[] = $cat;
			}
		}
		$keys_redacted = array();

		$exports_dir = $this->path_manager->get_child_path( Plugin_Path_Manager::CHILD_EXPORTS );
		if ( $exports_dir === '' ) {
			$this->log( 'Support package failed: exports directory unavailable.', array(), $log_ref, Log_Severities::ERROR );
			return Support_Package_Result::failure( __( 'Exports directory unavailable.', 'aio-page-builder' ), $log_ref );
		}
		if ( ! $this->path_manager->ensure_child( Plugin_Path_Manager::CHILD_EXPORTS ) ) {
			$this->log( 'Support package failed: could not create exports directory.', array(), $log_ref, Log_Severities::ERROR );
			return Support_Package_Result::failure( __( 'Could not create exports directory.', 'aio-page-builder' ), $log_ref );
		}

		$staging_dir = $this->create_staging_dir();
		if ( $staging_dir === '' ) {
			$this->log( 'Support package failed: could not create staging directory.', array(), $log_ref, Log_Severities::ERROR );
			return Support_Package_Result::failure( __( 'Could not create staging directory.', 'aio-page-builder' ), $log_ref );
		}

		$cleanup = true;
		try {
			$staging_dir = rtrim( $staging_dir, '/\\' ) . '/';

			if ( in_array( 'settings', $included, true ) ) {
				$raw      = $this->settings->get( Option_Names::MAIN_SETTINGS );
				$redacted = $this->redact_array( $raw );
				if ( $raw !== $redacted ) {
					$keys_redacted[] = 'settings';
				}
				$this->write_json_dir( $staging_dir . 'settings', 'settings.json', $redacted );
			}
			if ( in_array( 'profiles', $included, true ) ) {
				$raw      = $this->profile_store->get_full_profile();
				$redacted = $this->redact_array( is_array( $raw ) ? $raw : array() );
				if ( is_array( $raw ) && $raw !== $redacted ) {
					$keys_redacted[] = 'profiles';
				}
				$this->write_json_dir( $staging_dir . 'profiles', 'profile.json', $redacted );
			}
			if ( in_array( 'registries', $included, true ) || in_array( 'compositions', $included, true ) ) {
				$bundle = $this->registry_serializer->build_registry_bundle( 0 );
				$reg    = $staging_dir . 'registries';
				if ( ! is_dir( $reg ) ) {
					wp_mkdir_p( $reg );
				}
				$this->write_json_file( $reg . '/sections.json', $bundle['registries']['sections'] ?? array() );
				$this->write_json_file( $reg . '/page_templates.json', $bundle['registries']['page_templates'] ?? array() );
				$this->write_json_file( $reg . '/compositions.json', $bundle['registries']['compositions'] ?? array() );
			}
			if ( in_array( 'plans', $included, true ) ) {
				$plans = array();
				$list  = $this->plan_repository->list_recent( 500, 0 );
				foreach ( $list as $record ) {
					$id = (int) ( $record['id'] ?? 0 );
					if ( $id > 0 ) {
						$def     = $this->plan_repository->get_plan_definition( $id );
						$plans[] = array(
							'id'         => $id,
							'definition' => $def,
						);
					}
				}
				$this->write_json_dir( $staging_dir . 'plans', 'plans.json', $plans );
			}
			if ( in_array( 'token_sets', $included, true ) ) {
				$tokens = $this->token_set_reader->list_for_export( 0 );
				$this->write_json_dir( $staging_dir . 'tokens', 'token_sets.json', $tokens );
			}
			if ( in_array( 'uninstall_restore_metadata', $included, true ) ) {
				$prefs = $this->settings->get( Option_Names::UNINSTALL_PREFS );
				$this->write_json_dir( $staging_dir . 'settings', 'uninstall_restore_metadata.json', is_array( $prefs ) ? $prefs : array() );
			}

			$this->write_json_dir( $staging_dir . 'docs', 'environment_summary.json', $this->build_environment_summary() );

			if ( $this->template_library_summary_builder !== null ) {
				$template_summary = $this->template_library_summary_builder->build();
				$this->write_json_dir( $staging_dir . 'docs', 'template_library_support_summary.json', $template_summary );
			}

			if ( $this->override_audit_report_service !== null ) {
				$override_summary = $this->override_audit_report_service->build_report();
				$this->write_json_dir( $staging_dir . 'docs', 'industry_override_audit_summary.json', $override_summary );
			}

			if ( in_array( 'reporting_history', $included, true ) ) {
				$reporting_log   = \get_option( Option_Names::REPORTING_LOG, array() );
				$log_entries     = is_array( $reporting_log ) ? array_slice( $reporting_log, -500 ) : array();
				$redacted_log    = $this->redact_reporting_log_entries( $log_entries );
				$keys_redacted[] = 'reporting_history';
				$this->write_json_dir( $staging_dir . 'logs', 'reporting_history.json', array( 'entries' => $redacted_log ) );
			}

			$site_slug   = $this->get_site_slug();
			$filename    = $this->packager->build_package_filename( Export_Mode_Keys::SUPPORT_BUNDLE, $site_slug );
			$destination = $this->path_manager->get_export_package_path( $filename );
			if ( $destination === '' ) {
				$destination = rtrim( $exports_dir, '/\\' ) . '/' . $filename;
			}

			$source_site_url   = $this->get_source_site_url();
			$restore_notes     = 'Support bundle; redacted; not for full restore.';
			$redaction_summary = array(
				'applied'       => true,
				'keys_redacted' => array_values( array_unique( $keys_redacted ) ),
			);
			$manifest_builder  = $this->manifest_builder;
			$manifest_factory  = function ( array $checksum_list ) use ( $manifest_builder, $source_site_url, $included, $excluded, $restore_notes, $filename, $redaction_summary ) {
				$manifest                      = $manifest_builder->build(
					Export_Mode_Keys::SUPPORT_BUNDLE,
					$source_site_url,
					$included,
					$excluded,
					$checksum_list,
					$restore_notes,
					array(),
					$filename
				);
				$manifest['redaction_summary'] = $redaction_summary;
				$json                          = \wp_json_encode( $manifest );
				return $json !== false ? $json : '{}';
			};

			$pack_result = $this->packager->pack( $staging_dir, $destination, $manifest_factory );
			if ( ! $pack_result['success'] ) {
				$this->log( 'Support package pack failed: ' . $pack_result['error'], array(), $log_ref, Log_Severities::ERROR );
				return Support_Package_Result::failure( $pack_result['error'], $log_ref );
			}

			$cleanup = false;
			$result  = Support_Package_Result::success(
				$destination,
				$filename,
				$included,
				$excluded,
				$redaction_summary,
				count( $pack_result['checksum_list'] ),
				$pack_result['size_bytes'],
				$log_ref
			);
			$this->log(
				'Support package generated.',
				array(
					'size'      => $pack_result['size_bytes'],
					'checksums' => count( $pack_result['checksum_list'] ),
					'filename'  => $filename,
				),
				$log_ref
			);
			return $result;
		} finally {
			if ( $cleanup && $staging_dir !== '' && is_dir( $staging_dir ) ) {
				$this->remove_staging_dir( $staging_dir );
			}
		}
	}

	/**
	 * @return array{php_version: string, wp_version: string, plugin_version: string}
	 */
	private function build_environment_summary(): array {
		$wp = $GLOBALS['wp_version'] ?? '';
		return array(
			'php_version'    => PHP_VERSION,
			'wp_version'     => $wp !== '' ? $wp : 'Unknown',
			'plugin_version' => Versions::plugin(),
		);
	}

	/**
	 * Redacts reporting log entries for support export (§48.10, §45.9). No secrets in output.
	 *
	 * @param list<array<string, mixed>> $entries
	 * @return list<array<string, mixed>>
	 */
	private function redact_reporting_log_entries( array $entries ): array {
		$out       = array();
		$safe_keys = array( 'event_type', 'dedupe_key', 'attempted_at', 'delivery_status', 'log_reference', 'failure_reason' );
		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$row = array();
			foreach ( $safe_keys as $key ) {
				if ( array_key_exists( $key, $entry ) ) {
					$val         = $entry[ $key ];
					$row[ $key ] = is_string( $val ) ? $val : (string) $val;
				}
			}
			$out[] = $row;
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function redact_array( array $data ): array {
		$out = array();
		foreach ( $data as $k => $v ) {
			$lower   = strtolower( (string) $k );
			$blocked = false;
			foreach ( self::REDACT_KEYS as $needle ) {
				if ( strpos( $lower, $needle ) !== false ) {
					$blocked = true;
					break;
				}
			}
			if ( $blocked ) {
				continue;
			}
			$out[ $k ] = is_array( $v ) ? $this->redact_array( $v ) : $v;
		}
		return $out;
	}

	private function write_json_dir( string $dir, string $filename, array $data ): void {
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$this->write_json_file( $dir . '/' . $filename, $data );
	}

	private function write_json_file( string $path, array $data ): void {
		$json = \wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		if ( $json !== false ) {
			file_put_contents( $path, $json );
		}
	}

	private function get_site_slug(): string {
		$url  = \home_url( '', 'https' );
		$host = is_string( $url ) ? \wp_parse_url( $url, PHP_URL_HOST ) : null;
		if ( $host !== null && $host !== '' ) {
			$slug = preg_replace( '#[^a-zA-Z0-9_-]#', '', $host );
			return $slug !== '' ? $slug : 'site';
		}
		return 'site';
	}

	private function get_source_site_url(): string {
		$url = \home_url( '', 'https' );
		return is_string( $url ) ? $url : '';
	}

	private function create_staging_dir(): string {
		$base = $this->path_manager->get_child_path( Plugin_Path_Manager::CHILD_EXPORTS );
		if ( $base === '' ) {
			$base = sys_get_temp_dir();
		}
		$dir = rtrim( $base, '/\\' ) . '/.staging-support-' . uniqid( '', true );
		if ( wp_mkdir_p( $dir ) ) {
			return $dir;
		}
		return '';
	}

	private function remove_staging_dir( string $dir ): void {
		if ( $dir === '' || ! is_dir( $dir ) ) {
			return;
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( ! \WP_Filesystem() ) {
				return;
			}
		}
		if ( $wp_filesystem instanceof \WP_Filesystem_Base ) {
			$wp_filesystem->delete( $dir, true );
		}
	}

	private function log( string $message, array $context, string $log_ref, string $severity = Log_Severities::INFO ): void {
		if ( $this->logger === null ) {
			return;
		}
		$context['log_ref'] = $log_ref;
		$ref                = $context !== array() ? wp_json_encode( $context ) : '';
		if ( $ref === false ) {
			$ref = '';
		}
		$record = new Error_Record(
			$log_ref,
			Log_Categories::IMPORT_EXPORT,
			$severity,
			$message,
			gmdate( 'c' ),
			'',
			'',
			'',
			$ref
		);
		$this->logger->log( $record );
	}
}
