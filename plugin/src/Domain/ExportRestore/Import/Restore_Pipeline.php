<?php
/**
 * Restore pipeline: runs in approved order after validation (spec §52.8, §53.8, §53.9). No page content rewrite.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Import;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Domain\ExportRestore\Contracts\Industry_Export_Restore_Schema;
use AIOPageBuilder\Domain\ExportRestore\Validation\Template_Library_Restore_Validator;
use AIOPageBuilder\Domain\Industry\Cache\Industry_Read_Model_Cache_Service;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Storage\Tables\Table_Names;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Schema;
use AIOPageBuilder\Domain\Styling\Style_Cache_Service;
use AIOPageBuilder\Domain\Styling\Styles_JSON_Normalizer;
use AIOPageBuilder\Domain\Styling\Styles_JSON_Sanitizer;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use AIOPageBuilder\Support\Logging\Error_Record;
use AIOPageBuilder\Support\Logging\Log_Categories;
use AIOPageBuilder\Support\Logging\Logger_Interface;
use AIOPageBuilder\Support\Logging\Log_Severities;

/**
 * Restores plugin-owned state from validated package in contract order. Does not rewrite page content.
 */
final class Restore_Pipeline {

	/** Restore order (spec §52.8). Styling after settings (Prompt 257). */
	private const RESTORE_ORDER = array(
		'settings',
		'styling',
		'profiles',
		'registries',
		'compositions',
		'token_sets',
		'plans',
		'uninstall_restore_metadata',
	);

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Profile_Store */
	private Profile_Store $profile_store;

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repo;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $page_template_repo;

	/** @var Composition_Repository */
	private Composition_Repository $composition_repo;

	/** @var Build_Plan_Repository */
	private Build_Plan_Repository $plan_repo;

	/** @var \wpdb */
	private \wpdb $wpdb;

	/** @var Logger_Interface|null */
	private ?Logger_Interface $logger;

	/** @var Template_Library_Restore_Validator|null Template-library coherence after restore (Prompt 185). */
	private ?Template_Library_Restore_Validator $template_library_restore_validator;

	/** @var Style_Cache_Service|null Post-restore style cache invalidation (Prompt 257). */
	private ?Style_Cache_Service $style_cache_service;

	/** @var Styles_JSON_Normalizer|null Styling restore: normalize before sanitize (Prompt 259). */
	private ?Styles_JSON_Normalizer $styles_normalizer;

	/** @var Styles_JSON_Sanitizer|null Styling restore: only persist sanitized data (Prompt 259). */
	private ?Styles_JSON_Sanitizer $styles_sanitizer;

	/** @var Industry_Read_Model_Cache_Service|null Invalidate industry read-model caches after industry profile restore (Prompt 435). */
	private ?Industry_Read_Model_Cache_Service $industry_cache_service;

	public function __construct(
		Settings_Service $settings,
		Profile_Store $profile_store,
		Section_Template_Repository $section_repo,
		Page_Template_Repository $page_template_repo,
		Composition_Repository $composition_repo,
		Build_Plan_Repository $plan_repo,
		\wpdb $wpdb,
		?Logger_Interface $logger = null,
		?Template_Library_Restore_Validator $template_library_restore_validator = null,
		?Style_Cache_Service $style_cache_service = null,
		?Styles_JSON_Normalizer $styles_normalizer = null,
		?Styles_JSON_Sanitizer $styles_sanitizer = null,
		?Industry_Read_Model_Cache_Service $industry_cache_service = null
	) {
		$this->settings                           = $settings;
		$this->profile_store                      = $profile_store;
		$this->section_repo                       = $section_repo;
		$this->page_template_repo                 = $page_template_repo;
		$this->composition_repo                   = $composition_repo;
		$this->plan_repo                          = $plan_repo;
		$this->wpdb                               = $wpdb;
		$this->logger                             = $logger;
		$this->template_library_restore_validator = $template_library_restore_validator;
		$this->style_cache_service               = $style_cache_service;
		$this->styles_normalizer                  = $styles_normalizer;
		$this->styles_sanitizer                   = $styles_sanitizer;
		$this->industry_cache_service             = $industry_cache_service;
	}

	/**
	 * Runs restore only if validation passed. Applies resolution mode to conflicts. Order: settings, profile, registries, compositions, token_sets, plans, uninstall_restore_metadata.
	 *
	 * @param Import_Validation_Result $validation_result Must have validation_passed true.
	 * @param string                  $resolution_mode   Conflict_Resolution_Service::MODE_*.
	 * @return Restore_Result
	 */
	public function restore( Import_Validation_Result $validation_result, string $resolution_mode ): Restore_Result {
		if ( ! $validation_result->validation_passed() ) {
			return Restore_Result::failure(
				'Restore blocked: validation did not pass.',
				$validation_result->get_blocking_failures(),
				false,
				'restore-blocked'
			);
		}

		$conflict_service = new Conflict_Resolution_Service();
		if ( ! Conflict_Resolution_Service::is_valid_mode( $resolution_mode ) ) {
			return Restore_Result::failure( 'Invalid conflict resolution mode.', array(), true, '' );
		}
		$resolved = $conflict_service->resolve( $validation_result->get_conflicts(), $resolution_mode );
		if ( $resolved['cancelled'] ) {
			return Restore_Result::failure( 'Restore cancelled by user.', array(), true, 'restore-cancelled' );
		}
		$resolved_actions_map = $this->build_resolved_map( $resolved['resolved'] );

		$zip_path = $validation_result->get_package_path();
		if ( ! is_file( $zip_path ) || ! class_exists( 'ZipArchive' ) ) {
			return Restore_Result::failure( 'Package unavailable or ZipArchive missing.', array(), true, '' );
		}
		$zip = new \ZipArchive();
		if ( $zip->open( $zip_path, \ZipArchive::RDONLY ) !== true ) {
			return Restore_Result::failure( 'Could not open package.', array(), true, '' );
		}
		$manifest = $validation_result->get_manifest();
		$included = isset( $manifest['included_categories'] ) && is_array( $manifest['included_categories'] )
			? $manifest['included_categories']
			: array();

		$restored = array();
		$resolved_actions_log = array();
		$log_ref = 'restore-' . gmdate( 'Y-m-d\TH:i:s\Z' );

		try {
			foreach ( self::RESTORE_ORDER as $category ) {
				if ( ! in_array( $category, $included, true ) ) {
					continue;
				}
				$action_taken = $this->restore_category( $zip, $category, $manifest, $resolved_actions_map );
				if ( $action_taken !== null ) {
					$restored[] = $category;
					$resolved_actions_log = array_merge( $resolved_actions_log, $action_taken );
				}
			}
			$zip->close();

			$template_library_summary = array();
			if ( $this->template_library_restore_validator !== null ) {
				$template_library_summary = $this->template_library_restore_validator->validate( $restored, $manifest );
				if ( ! empty( $template_library_summary['errors'] ) ) {
					$this->log( 'Template library restore validation had errors.', array(
						'errors' => $template_library_summary['errors'],
						'ref'    => $template_library_summary['log_reference'] ?? '',
					), $log_ref, Log_Severities::WARNING );
				}
			}

			$this->log( 'Restore completed.', array( 'restored' => $restored ), $log_ref );
			return Restore_Result::success( $restored, $resolved_actions_log, $log_ref, 'Restore completed.', $template_library_summary );
		} catch ( \Throwable $e ) {
			$zip->close();
			$this->log( 'Restore failed: ' . $e->getMessage(), array(), $log_ref, Log_Severities::ERROR );
			return Restore_Result::failure( $e->getMessage(), array(), true, $log_ref );
		}
	}

	/**
	 * @param list<array{category: string, key: string, action: string}> $resolved
	 * @return array<string, array<string, string>> Map category -> ( key -> action ).
	 */
	private function build_resolved_map( array $resolved ): array {
		$map = array();
		foreach ( $resolved as $r ) {
			$cat = $r['category'] ?? '';
			$key = $r['key'] ?? '*';
			$act = $r['action'] ?? Conflict_Resolution_Service::ACTION_SKIP;
			if ( ! isset( $map[ $cat ] ) ) {
				$map[ $cat ] = array();
			}
			$map[ $cat ][ $key ] = $act;
		}
		return $map;
	}

	/**
	 * @param \ZipArchive              $zip
	 * @param string                   $category
	 * @param array<string, mixed>     $manifest
	 * @param array<string, array<string, string>> $resolved_map
	 * @return list<array{category: string, action: string, key?: string}>|null Actions taken, or null if skipped.
	 */
	private function restore_category( \ZipArchive $zip, string $category, array $manifest, array $resolved_map ): ?array {
		$actions = array();
		switch ( $category ) {
			case 'settings':
				$json = $zip->getFromName( 'settings/settings.json' );
				if ( $json === false ) {
					return null;
				}
				$data = json_decode( $json, true );
				if ( ! is_array( $data ) ) {
					return null;
				}
				$action = $resolved_map['settings']['*'] ?? $resolved_map['settings']['settings'] ?? Conflict_Resolution_Service::ACTION_OVERWRITE;
				if ( $action === Conflict_Resolution_Service::ACTION_KEEP_CURRENT ) {
					return null;
				}
				$this->settings->set( Option_Names::MAIN_SETTINGS, $data );
				$actions[] = array( 'category' => 'settings', 'action' => 'overwrite' );
				return $actions;

			case 'styling':
				$actions = array();
				if ( $this->styles_normalizer !== null && $this->styles_sanitizer !== null ) {
					$global_json = $zip->getFromName( 'styling/global_settings.json' );
					if ( $global_json !== false ) {
						$data = json_decode( $global_json, true );
						if ( \is_array( $data ) && $this->is_supported_global_style_version( $data ) ) {
							$norm_tokens = $this->styles_normalizer->normalize_global_tokens( $data[ Global_Style_Settings_Schema::KEY_GLOBAL_TOKENS ] ?? array() );
							$norm_comps  = $this->styles_normalizer->normalize_global_component_overrides( $data[ Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES ] ?? array() );
							$res_tokens  = $this->styles_sanitizer->sanitize_global_tokens( $norm_tokens );
							$res_comps   = $this->styles_sanitizer->sanitize_global_component_overrides( $norm_comps );
							if ( $res_tokens->is_valid() && $res_comps->is_valid() ) {
								$safe = array(
									Global_Style_Settings_Schema::KEY_VERSION                   => isset( $data[ Global_Style_Settings_Schema::KEY_VERSION ] ) && is_string( $data[ Global_Style_Settings_Schema::KEY_VERSION ] ) ? $data[ Global_Style_Settings_Schema::KEY_VERSION ] : Global_Style_Settings_Schema::SCHEMA_VERSION,
									Global_Style_Settings_Schema::KEY_GLOBAL_TOKENS             => $res_tokens->get_sanitized(),
									Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES => $res_comps->get_sanitized(),
								);
								\update_option( Global_Style_Settings_Schema::OPTION_KEY, $safe, false );
								$actions[] = array( 'category' => 'styling', 'action' => 'overwrite', 'key' => 'global_settings' );
							} else {
								$this->log( 'Styling restore: global_settings validation failed; skipped.', array( 'token_errors' => $res_tokens->get_errors(), 'comp_errors' => $res_comps->get_errors() ), 'restore-styling', Log_Severities::WARNING );
							}
						}
					}
					$entity_json = $zip->getFromName( 'styling/entity_payloads.json' );
					if ( $entity_json !== false ) {
						$data = json_decode( $entity_json, true );
						if ( \is_array( $data ) && $this->is_supported_entity_payload_version( $data ) ) {
							$payloads_raw = $data[ Entity_Style_Payload_Schema::KEY_PAYLOADS ] ?? array();
							$version      = isset( $data[ Entity_Style_Payload_Schema::KEY_VERSION ] ) && is_string( $data[ Entity_Style_Payload_Schema::KEY_VERSION ] ) ? $data[ Entity_Style_Payload_Schema::KEY_VERSION ] : Entity_Style_Payload_Schema::SCHEMA_VERSION;
							$out_payloads = array( 'section_template' => array(), 'page_template' => array() );
							foreach ( Entity_Style_Payload_Schema::ENTITY_TYPES as $entity_type ) {
								$by_key = isset( $payloads_raw[ $entity_type ] ) && is_array( $payloads_raw[ $entity_type ] ) ? $payloads_raw[ $entity_type ] : array();
								foreach ( $by_key as $key => $raw_payload ) {
									if ( ! is_string( $key ) || ! is_array( $raw_payload ) ) {
										continue;
									}
									$normalized = $this->styles_normalizer->normalize_entity_payload( $raw_payload );
									$result     = $this->styles_sanitizer->sanitize_entity_payload( $normalized );
									if ( $result->is_valid() ) {
										$out_payloads[ $entity_type ][ $key ] = $result->get_sanitized();
									}
								}
							}
							$safe = array(
								Entity_Style_Payload_Schema::KEY_VERSION  => $version,
								Entity_Style_Payload_Schema::KEY_PAYLOADS => $out_payloads,
							);
							\update_option( Entity_Style_Payload_Schema::OPTION_KEY, $safe, false );
							$actions[] = array( 'category' => 'styling', 'action' => 'overwrite', 'key' => 'entity_payloads' );
						}
					}
				} else {
					$this->log( 'Styling restore skipped: normalizer or sanitizer not available.', array(), 'restore-styling', Log_Severities::WARNING );
				}
				if ( $this->style_cache_service !== null && $actions !== array() ) {
					$this->style_cache_service->invalidate();
				}
				return $actions !== array() ? $actions : null;

			case 'profiles':
				$json = $zip->getFromName( 'profiles/profile.json' );
				if ( $json === false ) {
					return null;
				}
				$data = json_decode( $json, true );
				if ( ! is_array( $data ) ) {
					return null;
				}
				$action = $resolved_map['profiles']['*'] ?? Conflict_Resolution_Service::ACTION_OVERWRITE;
				if ( $action === Conflict_Resolution_Service::ACTION_KEEP_CURRENT ) {
					return null;
				}
				$this->profile_store->set_full_profile( $data );
				$actions[] = array( 'category' => 'profiles', 'action' => 'overwrite' );
				$industry_json = $zip->getFromName( 'profiles/industry.json' );
				if ( $industry_json !== false ) {
					$industry_data = json_decode( $industry_json, true );
					if ( is_array( $industry_data ) ) {
						$version = isset( $industry_data[ Industry_Export_Restore_Schema::KEY_SCHEMA_VERSION ] ) && is_string( $industry_data[ Industry_Export_Restore_Schema::KEY_SCHEMA_VERSION ] )
							? trim( $industry_data[ Industry_Export_Restore_Schema::KEY_SCHEMA_VERSION ] )
							: '';
						if ( Industry_Export_Restore_Schema::is_supported_version( $version ) ) {
							$industry_profile = isset( $industry_data[ Industry_Export_Restore_Schema::KEY_INDUSTRY_PROFILE ] ) && is_array( $industry_data[ Industry_Export_Restore_Schema::KEY_INDUSTRY_PROFILE ] )
								? Industry_Profile_Schema::normalize( $industry_data[ Industry_Export_Restore_Schema::KEY_INDUSTRY_PROFILE ] )
								: Industry_Profile_Schema::get_empty_profile();
							$this->settings->set( Option_Names::INDUSTRY_PROFILE, $industry_profile );
							$applied = $industry_data[ Industry_Export_Restore_Schema::KEY_APPLIED_PRESET ] ?? null;
							$this->settings->set( Option_Names::APPLIED_INDUSTRY_PRESET, is_array( $applied ) ? $applied : array() );
							if ( $this->industry_cache_service !== null ) {
								$this->industry_cache_service->invalidate_all_industry_read_models();
							}
							$actions[] = array( 'category' => 'profiles', 'action' => 'overwrite', 'key' => 'industry' );
						} else {
							$this->log( 'Industry restore skipped: unsupported or missing schema_version.', array( 'version' => $version ), 'restore-profiles', Log_Severities::WARNING );
						}
					}
				}
				return $actions;

			case 'registries':
				$actions = array();
				$sections_json = $zip->getFromName( 'registries/sections.json' );
				if ( $sections_json !== false ) {
					$arr = json_decode( $sections_json, true );
					if ( is_array( $arr ) ) {
						foreach ( $arr as $frag ) {
							$payload = isset( $frag['payload'] ) && is_array( $frag['payload'] ) ? $frag['payload'] : array();
							$key = $frag['object_key'] ?? ( $payload[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
							$act = $resolved_map['registries'][ $key ] ?? Conflict_Resolution_Service::ACTION_OVERWRITE;
							if ( $act === Conflict_Resolution_Service::ACTION_KEEP_CURRENT ) {
								continue;
							}
							if ( $act === Conflict_Resolution_Service::ACTION_DUPLICATE && $key !== '' ) {
								$payload[ Section_Schema::FIELD_INTERNAL_KEY ] = $key . '_imported_' . uniqid();
							}
							$this->section_repo->save_definition( $payload );
							$actions[] = array( 'category' => 'registries', 'action' => $act, 'key' => $key );
						}
					}
				}
				$pt_json = $zip->getFromName( 'registries/page_templates.json' );
				if ( $pt_json !== false ) {
					$arr = json_decode( $pt_json, true );
					if ( is_array( $arr ) ) {
						foreach ( $arr as $frag ) {
							$payload = isset( $frag['payload'] ) && is_array( $frag['payload'] ) ? $frag['payload'] : array();
							$key = $frag['object_key'] ?? ( $payload[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
							$act = $resolved_map['registries'][ $key ] ?? Conflict_Resolution_Service::ACTION_OVERWRITE;
							if ( $act === Conflict_Resolution_Service::ACTION_KEEP_CURRENT ) {
								continue;
							}
							if ( $act === Conflict_Resolution_Service::ACTION_DUPLICATE && $key !== '' ) {
								$payload[ Page_Template_Schema::FIELD_INTERNAL_KEY ] = $key . '_imported_' . uniqid();
							}
							$this->page_template_repo->save_definition( $payload );
							$actions[] = array( 'category' => 'registries', 'action' => $act, 'key' => $key );
						}
					}
				}
				return $actions !== array() ? $actions : null;

			case 'compositions':
				$json = $zip->getFromName( 'registries/compositions.json' );
				if ( $json === false ) {
					return null;
				}
				$arr = json_decode( $json, true );
				if ( ! is_array( $arr ) ) {
					return null;
				}
				$actions = array();
				foreach ( $arr as $frag ) {
					$payload = isset( $frag['payload'] ) && is_array( $frag['payload'] ) ? $frag['payload'] : array();
					$key = $frag['object_key'] ?? ( $payload[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
					$act = $resolved_map['registries'][ $key ] ?? $resolved_map['compositions'][ $key ] ?? Conflict_Resolution_Service::ACTION_OVERWRITE;
					if ( $act === Conflict_Resolution_Service::ACTION_KEEP_CURRENT ) {
						continue;
					}
					if ( $act === Conflict_Resolution_Service::ACTION_DUPLICATE && $key !== '' ) {
						$payload[ Composition_Schema::FIELD_COMPOSITION_ID ] = $key . '_imported_' . uniqid();
					}
					$this->composition_repo->save_definition( $payload );
					$actions[] = array( 'category' => 'compositions', 'action' => $act, 'key' => $key );
				}
				return $actions !== array() ? $actions : null;

			case 'token_sets':
				$json = $zip->getFromName( 'tokens/token_sets.json' );
				if ( $json === false ) {
					return null;
				}
				$arr = json_decode( $json, true );
				if ( ! is_array( $arr ) ) {
					return null;
				}
				$table = $this->wpdb->prefix . Table_Names::TOKEN_SETS;
				if ( $this->wpdb->get_var( $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
					return null;
				}
				$actions = array();
				foreach ( $arr as $row ) {
					$ref = $row['token_set_ref'] ?? '';
					if ( $ref === '' ) {
						continue;
					}
					$act = $resolved_map['token_sets'][ $ref ] ?? Conflict_Resolution_Service::ACTION_OVERWRITE;
					if ( $act === Conflict_Resolution_Service::ACTION_KEEP_CURRENT ) {
						continue;
					}
					$value_payload = isset( $row['value_payload'] ) ? ( is_string( $row['value_payload'] ) ? $row['value_payload'] : wp_json_encode( $row['value_payload'] ) ) : '';
					$this->wpdb->replace(
						$table,
						array(
							'token_set_ref'     => $ref,
							'source_type'       => $row['source_type'] ?? 'import',
							'state'             => $row['state'] ?? 'proposed',
							'plan_ref'          => $row['plan_ref'] ?? null,
							'scope_ref'         => $row['scope_ref'] ?? null,
							'value_payload'     => $value_payload,
							'acceptance_status' => $row['acceptance_status'] ?? 'pending',
							'schema_version'    => $row['schema_version'] ?? '1',
						),
						array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
					);
					$actions[] = array( 'category' => 'token_sets', 'action' => $act, 'key' => $ref );
				}
				return $actions !== array() ? $actions : null;

			case 'plans':
				$json = $zip->getFromName( 'plans/plans.json' );
				if ( $json === false ) {
					return null;
				}
				$arr = json_decode( $json, true );
				if ( ! is_array( $arr ) ) {
					return null;
				}
				$actions = array();
				foreach ( $arr as $item ) {
					$def = isset( $item['definition'] ) && is_array( $item['definition'] ) ? $item['definition'] : $item;
					$plan_id = $def['plan_id'] ?? '';
					$act = $resolved_map['plans'][ $plan_id ] ?? Conflict_Resolution_Service::ACTION_OVERWRITE;
					if ( $act === Conflict_Resolution_Service::ACTION_KEEP_CURRENT ) {
						continue;
					}
					if ( $act === Conflict_Resolution_Service::ACTION_DUPLICATE && $plan_id !== '' ) {
						$def['plan_id'] = $plan_id . '_imported_' . uniqid();
					}
					$existing = $plan_id !== '' ? $this->plan_repo->get_by_key( $plan_id ) : null;
					$save_data = array( 'plan_definition' => $def );
					if ( $existing !== null && isset( $existing['id'] ) && $act === Conflict_Resolution_Service::ACTION_OVERWRITE ) {
						$save_data['id'] = (int) $existing['id'];
					}
					$post_id = $this->plan_repo->save( $save_data );
					if ( $post_id > 0 ) {
						$actions[] = array( 'category' => 'plans', 'action' => $act, 'key' => $plan_id );
					}
				}
				return $actions !== array() ? $actions : null;

			case 'uninstall_restore_metadata':
				$json = $zip->getFromName( 'settings/uninstall_restore_metadata.json' );
				if ( $json === false ) {
					return null;
				}
				$data = json_decode( $json, true );
				if ( ! is_array( $data ) ) {
					return null;
				}
				$this->settings->set( Option_Names::UNINSTALL_PREFS, $data );
				return array( array( 'category' => 'uninstall_restore_metadata', 'action' => 'overwrite' ) );
		}
		return null;
	}

	/**
	 * Returns whether the global style settings version is supported for restore (Prompt 257).
	 *
	 * @param array<string, mixed> $data Decoded global_settings.json.
	 * @return bool
	 */
	private function is_supported_global_style_version( array $data ): bool {
		$ver = isset( $data[ Global_Style_Settings_Schema::KEY_VERSION ] ) ? (string) $data[ Global_Style_Settings_Schema::KEY_VERSION ] : '';
		return $ver === Global_Style_Settings_Schema::SCHEMA_VERSION;
	}

	/**
	 * Returns whether the entity style payloads version is supported for restore (Prompt 257).
	 *
	 * @param array<string, mixed> $data Decoded entity_payloads.json.
	 * @return bool
	 */
	private function is_supported_entity_payload_version( array $data ): bool {
		$ver = isset( $data[ Entity_Style_Payload_Schema::KEY_VERSION ] ) ? (string) $data[ Entity_Style_Payload_Schema::KEY_VERSION ] : '';
		return $ver === Entity_Style_Payload_Schema::SCHEMA_VERSION;
	}

	private function log( string $message, array $context, string $log_ref, string $severity = Log_Severities::INFO ): void {
		if ( $this->logger === null ) {
			return;
		}
		$ref = $context !== array() ? wp_json_encode( $context ) : $log_ref;
		if ( $ref === false ) {
			$ref = $log_ref;
		}
		$record = new Error_Record(
			'restore-' . uniqid( '', true ),
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
