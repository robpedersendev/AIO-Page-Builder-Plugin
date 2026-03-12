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
use AIOPageBuilder\Domain\Storage\Tables\Table_Names;
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

	/** Restore order (spec §52.8). */
	private const RESTORE_ORDER = array(
		'settings',
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

	public function __construct(
		Settings_Service $settings,
		Profile_Store $profile_store,
		Section_Template_Repository $section_repo,
		Page_Template_Repository $page_template_repo,
		Composition_Repository $composition_repo,
		Build_Plan_Repository $plan_repo,
		\wpdb $wpdb,
		?Logger_Interface $logger = null
	) {
		$this->settings          = $settings;
		$this->profile_store     = $profile_store;
		$this->section_repo      = $section_repo;
		$this->page_template_repo = $page_template_repo;
		$this->composition_repo  = $composition_repo;
		$this->plan_repo         = $plan_repo;
		$this->wpdb              = $wpdb;
		$this->logger            = $logger;
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
			$this->log( 'Restore completed.', array( 'restored' => $restored ), $log_ref );
			return Restore_Result::success( $restored, $resolved_actions_log, $log_ref );
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
