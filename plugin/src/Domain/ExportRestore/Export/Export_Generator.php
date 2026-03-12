<?php
/**
 * Orchestrates export generation: gather categories, staging, manifest, ZIP, logging (spec §52, §48.10, §59.13).
 *
 * Mode-aware; excludes secrets; writes to controlled plugin paths. No restore or uninstall UI.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Bundle_Schema;
use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Mode_Keys;
use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Serializer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use AIOPageBuilder\Support\Logging\Error_Record;
use AIOPageBuilder\Support\Logging\Log_Categories;
use AIOPageBuilder\Support\Logging\Logger_Interface;
use AIOPageBuilder\Support\Logging\Log_Severities;

/**
 * Generates export ZIP for a given mode and optional categories. Logs attempt and result.
 */
final class Export_Generator {

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

	public function __construct(
		Plugin_Path_Manager $path_manager,
		Settings_Service $settings,
		Profile_Store $profile_store,
		Registry_Export_Serializer $registry_serializer,
		Build_Plan_Repository $plan_repository,
		Export_Token_Set_Reader $token_set_reader,
		Export_Manifest_Builder $manifest_builder,
		Export_Zip_Packager $packager,
		?Logger_Interface $logger = null
	) {
		$this->path_manager        = $path_manager;
		$this->settings            = $settings;
		$this->profile_store       = $profile_store;
		$this->registry_serializer = $registry_serializer;
		$this->plan_repository     = $plan_repository;
		$this->token_set_reader    = $token_set_reader;
		$this->manifest_builder    = $manifest_builder;
		$this->packager            = $packager;
		$this->logger              = $logger;
	}

	/**
	 * Generates export package for the given mode and optional categories.
	 * Writes ZIP under plugin exports path; logs attempt and result.
	 *
	 * @param string       $mode             Export mode key (Export_Mode_Keys).
	 * @param list<string> $optional_included Optional category keys to include when mode allows (e.g. logs, reporting_history).
	 * @return Export_Result
	 */
	public function generate( string $mode, array $optional_included = array() ): Export_Result {
		if ( ! Export_Mode_Keys::is_valid( $mode ) ) {
			return Export_Result::failure( 'Invalid export mode.', $mode, array(), array() );
		}

		$matrix = $this->get_categories_for_mode( $mode );
		$included = $matrix['included'];
		$excluded = $matrix['excluded'];
		$optional_allowed = $matrix['optional_allowed'];
		$redact = $matrix['redact'];

		foreach ( $optional_included as $cat ) {
			if ( in_array( $cat, $optional_allowed, true ) && Export_Bundle_Schema::is_optional_category( $cat ) && ! in_array( $cat, $included, true ) ) {
				$included[] = $cat;
			}
		}
		foreach ( Export_Bundle_Schema::OPTIONAL_CATEGORIES as $opt ) {
			if ( ! in_array( $opt, $included, true ) && in_array( $opt, $optional_allowed, true ) === false ) {
				$excluded[] = $opt;
			}
		}
		$excluded = array_values( array_unique( $excluded ) );

		$exports_dir = $this->path_manager->get_child_path( Plugin_Path_Manager::CHILD_EXPORTS );
		if ( $exports_dir === '' ) {
			return Export_Result::failure( 'Exports directory unavailable.', $mode, $included, $excluded );
		}
		if ( ! $this->path_manager->ensure_child( Plugin_Path_Manager::CHILD_EXPORTS ) ) {
			return Export_Result::failure( 'Could not create exports directory.', $mode, $included, $excluded );
		}

		$staging_dir = $this->create_staging_dir();
		if ( $staging_dir === '' ) {
			$this->log( 'Export failed: could not create staging directory.', array( 'mode' => $mode ), Log_Severities::ERROR );
			return Export_Result::failure( 'Could not create staging directory.', $mode, $included, $excluded );
		}

		$cleanup = true;
		try {
			$this->write_staging_files( $staging_dir, $mode, $included, $redact );
			$site_slug   = $this->get_site_slug();
			$filename    = $this->packager->build_package_filename( $mode, $site_slug );
			$destination = $this->path_manager->get_export_package_path( $filename );
			if ( $destination === '' ) {
				$destination = rtrim( $exports_dir, '/\\' ) . '/' . $filename;
			}

			$optional_included_list = array_intersect( $optional_included, $included );
			$restore_notes = $mode === Export_Mode_Keys::SUPPORT_BUNDLE
				? 'Support bundle; redacted; not for full restore.'
				: ( $mode === Export_Mode_Keys::PRE_UNINSTALL_BACKUP ? 'Pre-uninstall backup.' : 'Export ' . $mode . '.' );

			$source_site_url = $this->get_source_site_url();
			$manifest_builder = $this->manifest_builder;
			$manifest_factory = function ( array $checksum_list ) use ( $manifest_builder, $mode, $source_site_url, $included, $excluded, $restore_notes, $optional_included_list, $filename ) {
				$manifest = $manifest_builder->build(
					$mode,
					$source_site_url,
					$included,
					$excluded,
					$checksum_list,
					$restore_notes,
					array_values( $optional_included_list ),
					$filename
				);
				$json = \wp_json_encode( $manifest );
				return $json !== false ? $json : '{}';
			};

			$pack_result = $this->packager->pack( $staging_dir, $destination, $manifest_factory );
			if ( ! $pack_result['success'] ) {
				$this->log( 'Export pack failed: ' . $pack_result['error'], array( 'mode' => $mode ), Log_Severities::ERROR );
				return Export_Result::failure( $pack_result['error'], $mode, $included, $excluded );
			}

			$cleanup = false;
			$result = Export_Result::success(
				$destination,
				$mode,
				$included,
				$excluded,
				count( $pack_result['checksum_list'] ),
				$pack_result['size_bytes'],
				$filename,
				'export-' . $mode . '-' . gmdate( 'Y-m-d\TH:i:s\Z' )
			);
			$this->log( 'Export completed.', array(
				'mode'       => $mode,
				'path'       => $destination,
				'size'       => $pack_result['size_bytes'],
				'checksums'  => count( $pack_result['checksum_list'] ),
			) );
			return $result;
		} finally {
			if ( $cleanup && $staging_dir !== '' && is_dir( $staging_dir ) ) {
				$this->remove_staging_dir( $staging_dir );
			}
		}
	}

	/**
	 * Returns category matrix for mode (export-bundle-structure-contract.md §2.1).
	 *
	 * @param string $mode Export mode key.
	 * @return array{included: list<string>, excluded: list<string>, optional_allowed: list<string>, redact: bool}
	 */
	private function get_categories_for_mode( string $mode ): array {
		$included = Export_Bundle_Schema::INCLUDED_CATEGORIES;
		$excluded = array();
		$optional_allowed = array();
		$redact = false;

		switch ( $mode ) {
			case Export_Mode_Keys::FULL_OPERATIONAL_BACKUP:
			case Export_Mode_Keys::PRE_UNINSTALL_BACKUP:
				$optional_allowed = Export_Bundle_Schema::OPTIONAL_CATEGORIES;
				break;
			case Export_Mode_Keys::SUPPORT_BUNDLE:
				$redact = true;
				$optional_allowed = array( 'logs', 'reporting_history' );
				$excluded = array( 'raw_ai_artifacts', 'normalized_ai_outputs', 'crawl_snapshots', 'rollback_snapshots' );
				break;
			case Export_Mode_Keys::TEMPLATE_ONLY_EXPORT:
				$included = array( 'registries', 'compositions' );
				$excluded = array( 'settings', 'profiles', 'plans', 'token_sets', 'uninstall_restore_metadata' );
				$excluded = array_merge( $excluded, Export_Bundle_Schema::OPTIONAL_CATEGORIES );
				break;
			case Export_Mode_Keys::PLAN_ARTIFACT_EXPORT:
				$included = array( 'plans', 'token_sets' );
				$optional_allowed = array( 'normalized_ai_outputs' );
				$excluded = array( 'settings', 'profiles', 'registries', 'compositions', 'uninstall_restore_metadata' );
				$excluded = array_merge( $excluded, array( 'raw_ai_artifacts', 'crawl_snapshots', 'logs', 'reporting_history', 'rollback_snapshots' ) );
				break;
			case Export_Mode_Keys::UNINSTALL_SETTINGS_PROFILE_ONLY:
				$included = array( 'settings', 'profiles', 'uninstall_restore_metadata' );
				$excluded = array( 'registries', 'compositions', 'plans', 'token_sets' );
				$excluded = array_merge( $excluded, Export_Bundle_Schema::OPTIONAL_CATEGORIES );
				break;
			default:
				$excluded = Export_Bundle_Schema::EXCLUDED_CATEGORIES;
		}
		return array(
			'included'         => array_values( $included ),
			'excluded'         => array_values( array_unique( $excluded ) ),
			'optional_allowed' => $optional_allowed,
			'redact'           => $redact,
		);
	}

	/**
	 * Writes category data to staging directory (ZIP layout: settings/, profiles/, registries/, plans/, tokens/).
	 *
	 * @param string       $staging_dir Staging root path.
	 * @param string       $mode        Export mode.
	 * @param list<string> $included    Categories to include.
	 * @param bool         $redact      Whether to redact settings and profile.
	 */
	private function write_staging_files( string $staging_dir, string $mode, array $included, bool $redact ): void {
		$staging_dir = rtrim( $staging_dir, '/\\' ) . '/';

		if ( in_array( 'settings', $included, true ) ) {
			$settings = $this->settings->get( Option_Names::MAIN_SETTINGS );
			if ( $redact ) {
				$settings = $this->redact_settings( $settings );
			}
			$this->write_json_dir( $staging_dir . 'settings', 'settings.json', $settings );
		}
		if ( in_array( 'profiles', $included, true ) ) {
			$profile = $this->profile_store->get_full_profile();
			if ( $redact ) {
				$profile = $this->redact_profile( $profile );
			}
			$this->write_json_dir( $staging_dir . 'profiles', 'profile.json', $profile );
		}
		if ( in_array( 'registries', $included, true ) || in_array( 'compositions', $included, true ) ) {
			$bundle = $this->registry_serializer->build_registry_bundle( 0 );
			$reg = $staging_dir . 'registries';
			if ( ! is_dir( $reg ) ) {
				wp_mkdir_p( $reg );
			}
			$this->write_json_file( $reg . '/sections.json', $bundle['registries']['sections'] ?? array() );
			$this->write_json_file( $reg . '/page_templates.json', $bundle['registries']['page_templates'] ?? array() );
			$this->write_json_file( $reg . '/compositions.json', $bundle['registries']['compositions'] ?? array() );
		}
		if ( in_array( 'plans', $included, true ) ) {
			$plans = array();
			$list = $this->plan_repository->list_recent( 500, 0 );
			foreach ( $list as $record ) {
				$id = (int) ( $record['id'] ?? 0 );
				if ( $id > 0 ) {
					$def = $this->plan_repository->get_plan_definition( $id );
					$plans[] = array( 'id' => $id, 'definition' => $def );
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
			$this->write_json_dir( $staging_dir . 'settings', 'uninstall_restore_metadata.json', $prefs );
		}
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

	/**
	 * Redacts keys that may contain secrets (spec §52.6).
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function redact_settings( array $data ): array {
		return $this->redact_array( $data );
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function redact_profile( array $data ): array {
		return $this->redact_array( $data );
	}

	/** @var array<string> */
	private static $redact_keys = array( 'api_key', 'password', 'secret', 'token', 'credential', 'auth' );

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function redact_array( array $data ): array {
		$out = array();
		foreach ( $data as $k => $v ) {
			$lower = strtolower( (string) $k );
			$blocked = false;
			foreach ( self::$redact_keys as $needle ) {
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

	private function get_site_slug(): string {
		$url = \home_url( '', 'https' );
		$host = is_string( $url ) ? \parse_url( $url, PHP_URL_HOST ) : null;
		if ( $host !== null && $host !== '' ) {
			return preg_replace( '#[^a-zA-Z0-9_-]#', '', $host ) ?: 'site';
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
		$dir = rtrim( $base, '/\\' ) . '/.staging-' . uniqid( 'export', true );
		if ( wp_mkdir_p( $dir ) ) {
			return $dir;
		}
		return '';
	}

	private function remove_staging_dir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $files as $item ) {
			if ( $item->isDir() ) {
				rmdir( $item->getRealPath() );
			} else {
				unlink( $item->getRealPath() );
			}
		}
		rmdir( $dir );
	}

	/**
	 * Logs export event (spec §48.10). No secrets in message or context_reference.
	 *
	 * @param string               $message  Sanitized message.
	 * @param array<string, mixed> $context  Optional safe key-value (e.g. mode, size); encoded for context_reference.
	 * @param string               $severity Log_Severities constant (default INFO).
	 */
	private function log( string $message, array $context = array(), string $severity = Log_Severities::INFO ): void {
		if ( $this->logger === null ) {
			return;
		}
		$ref = $context !== array() ? wp_json_encode( $context ) : '';
		if ( $ref === false ) {
			$ref = '';
		}
		$record = new Error_Record(
			'export-' . uniqid( '', true ),
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
