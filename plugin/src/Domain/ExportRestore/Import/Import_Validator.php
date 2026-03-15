<?php
/**
 * Validates import package before any write (spec §52.7, §52.10). Stops on blocking failure.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Import;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Bundle_Schema;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Schema;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Token_Set_Reader;
use AIOPageBuilder\Infrastructure\Config\Versions;

/**
 * Validates ZIP integrity, manifest, schema version, checksums, prohibited paths, and optional conflict pre-scan.
 */
final class Import_Validator {

	/** Allowed path prefixes inside ZIP (contract §52.2). No path traversal. Styling (Prompt 257). */
	private const ALLOWED_PREFIXES = array(
		'manifest.json',
		'settings/',
		'styling/',
		'profiles/',
		'registries/',
		'compositions/',
		'plans/',
		'tokens/',
		'artifacts/',
		'logs/',
		'docs/',
	);

	/** @var Section_Template_Repository|null For conflict pre-scan. */
	private ?Section_Template_Repository $section_repo;

	/** @var Page_Template_Repository|null */
	private ?Page_Template_Repository $page_template_repo;

	/** @var Composition_Repository|null */
	private ?Composition_Repository $composition_repo;

	/** @var Build_Plan_Repository|null */
	private ?Build_Plan_Repository $plan_repo;

	/** @var Export_Token_Set_Reader|null */
	private ?Export_Token_Set_Reader $token_set_reader;

	public function __construct(
		?Section_Template_Repository $section_repo = null,
		?Page_Template_Repository $page_template_repo = null,
		?Composition_Repository $composition_repo = null,
		?Build_Plan_Repository $plan_repo = null,
		?Export_Token_Set_Reader $token_set_reader = null
	) {
		$this->section_repo       = $section_repo;
		$this->page_template_repo = $page_template_repo;
		$this->composition_repo   = $composition_repo;
		$this->plan_repo          = $plan_repo;
		$this->token_set_reader   = $token_set_reader;
	}

	/**
	 * Validates the package at the given path. No writes. Returns validation result with conflicts if pre-scan runs.
	 *
	 * @param string $zip_path Absolute path to ZIP file.
	 * @return Import_Validation_Result
	 */
	public function validate( string $zip_path ): Import_Validation_Result {
		$failures = array();
		$warnings = array();
		$conflicts = array();
		$manifest = array();
		$checksum_verified = false;

		if ( ! is_file( $zip_path ) || ! is_readable( $zip_path ) ) {
			$failures[] = 'Package file missing or not readable.';
			return new Import_Validation_Result( false, $failures, $conflicts, $warnings, $manifest, $zip_path, false );
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			$failures[] = 'ZipArchive not available.';
			return new Import_Validation_Result( false, $failures, $conflicts, $warnings, $manifest, $zip_path, false );
		}

		$zip = new \ZipArchive();
		if ( $zip->open( $zip_path, \ZipArchive::RDONLY ) !== true ) {
			$failures[] = 'ZIP integrity check failed: could not open archive.';
			return new Import_Validation_Result( false, $failures, $conflicts, $warnings, $manifest, $zip_path, false );
		}

		$manifest_content = $zip->getFromName( 'manifest.json' );
		if ( $manifest_content === false || $manifest_content === '' ) {
			$zip->close();
			$failures[] = 'manifest.json missing or empty.';
			return new Import_Validation_Result( false, $failures, $conflicts, $warnings, $manifest, $zip_path, false );
		}

		$manifest = json_decode( $manifest_content, true );
		if ( ! is_array( $manifest ) ) {
			$zip->close();
			$failures[] = 'manifest.json is not valid JSON.';
			return new Import_Validation_Result( false, $failures, $conflicts, $warnings, $manifest, $zip_path, false );
		}

		if ( ! Export_Bundle_Schema::manifest_has_required_keys( $manifest ) ) {
			$zip->close();
			$failures[] = 'Manifest missing required fields.';
			return new Import_Validation_Result( false, $failures, $conflicts, $warnings, $manifest, $zip_path, false );
		}

		$schema_check = $this->check_schema_version( $manifest );
		if ( $schema_check !== '' ) {
			$zip->close();
			$failures[] = $schema_check;
			return new Import_Validation_Result( false, $failures, $conflicts, $warnings, $manifest, $zip_path, false );
		}

		$prohibited = $this->check_prohibited_paths( $zip );
		if ( $prohibited !== array() ) {
			$zip->close();
			$failures[] = 'Prohibited paths in package: ' . implode( ', ', $prohibited );
			return new Import_Validation_Result( false, $failures, $conflicts, $warnings, $manifest, $zip_path, false );
		}

		$checksum_list = $manifest['package_checksum_list'] ?? array();
		if ( is_array( $checksum_list ) && $checksum_list !== array() ) {
			$checksum_verified = $this->verify_checksums( $zip, $checksum_list );
			if ( ! $checksum_verified ) {
				$warnings[] = 'One or more checksums did not match; integrity may be compromised.';
			}
		} else {
			$checksum_verified = true;
		}

		$conflicts = $this->conflict_pre_scan( $zip, $manifest );
		$zip->close();

		$validation_passed = $failures === array();
		return new Import_Validation_Result(
			$validation_passed,
			$failures,
			$conflicts,
			$warnings,
			$manifest,
			$zip_path,
			$checksum_verified
		);
	}

	/**
	 * Cross-version rules (contract §52.10): same major allowed; older with migration; newer blocked; below floor blocked.
	 *
	 * @param array<string, mixed> $manifest
	 * @return string Empty if OK; otherwise failure message.
	 */
	private function check_schema_version( array $manifest ): string {
		$incoming = isset( $manifest['schema_version'] ) ? (string) $manifest['schema_version'] : '0';
		$current  = Versions::export_schema();
		$flags    = isset( $manifest['compatibility_flags'] ) && is_array( $manifest['compatibility_flags'] )
			? $manifest['compatibility_flags']
			: array();
		$floor    = isset( $flags['migration_floor'] ) ? (string) $flags['migration_floor'] : null;
		$same_major = ! empty( $flags['same_major_required'] );

		if ( $floor !== null && $floor !== '' && $this->version_compare($incoming, $floor) < 0 ) {
			return 'Export schema version is below migration floor; import blocked.';
		}
		$incoming_major = $this->major_version( $incoming );
		$current_major  = $this->major_version( $current );
		if ( $same_major && $incoming_major !== $current_major ) {
			return 'Same major schema version required; import blocked.';
		}
		if ( $this->version_compare( $incoming, $current ) > 0 ) {
			return 'Export schema version is newer than supported; import blocked.';
		}
		return '';
	}

	/**
	 * Validates styling schema versions when styling category is included (Prompt 257). Returns failure message or empty.
	 *
	 * @param \ZipArchive $zip
	 * @return string
	 */
	private function check_styling_schema_versions( \ZipArchive $zip ): string {
		$global_json = $zip->getFromName( 'styling/global_settings.json' );
		$entity_json = $zip->getFromName( 'styling/entity_payloads.json' );
		if ( $global_json !== false ) {
			$data = json_decode( $global_json, true );
			if ( \is_array( $data ) ) {
				$ver = isset( $data[ Global_Style_Settings_Schema::KEY_VERSION ] ) ? (string) $data[ Global_Style_Settings_Schema::KEY_VERSION ] : '';
				if ( $ver !== Global_Style_Settings_Schema::SCHEMA_VERSION ) {
					return 'Unsupported global styling schema version: ' . ( $ver !== '' ? $ver : 'missing' ) . '; expected ' . Global_Style_Settings_Schema::SCHEMA_VERSION . '.';
				}
			}
		}
		if ( $entity_json !== false ) {
			$data = json_decode( $entity_json, true );
			if ( \is_array( $data ) ) {
				$ver = isset( $data[ Entity_Style_Payload_Schema::KEY_VERSION ] ) ? (string) $data[ Entity_Style_Payload_Schema::KEY_VERSION ] : '';
				if ( $ver !== Entity_Style_Payload_Schema::SCHEMA_VERSION ) {
					return 'Unsupported entity style payloads schema version: ' . ( $ver !== '' ? $ver : 'missing' ) . '; expected ' . Entity_Style_Payload_Schema::SCHEMA_VERSION . '.';
				}
			}
		}
		return '';
	}

	private function version_compare( string $a, string $b ): int {
		return version_compare( $a, $b );
	}

	private function major_version( string $v ): string {
		$parts = explode( '.', $v, 2 );
		return $parts[0] ?? '0';
	}

	/**
	 * @param \ZipArchive $zip
	 * @return list<string> Prohibited path names.
	 */
	private function check_prohibited_paths( \ZipArchive $zip ): array {
		$prohibited = array();
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = $zip->getNameIndex( $i );
			if ( $name === false ) {
				continue;
			}
			$name = str_replace( '\\', '/', $name );
			if ( strpos( $name, '..' ) !== false ) {
				$prohibited[] = $name;
				continue;
			}
			$allowed = false;
			foreach ( self::ALLOWED_PREFIXES as $prefix ) {
				if ( $prefix === 'manifest.json' && $name === 'manifest.json' ) {
					$allowed = true;
					break;
				}
				if ( $prefix !== 'manifest.json' && strpos( $name, $prefix ) === 0 ) {
					$allowed = true;
					break;
				}
			}
			if ( ! $allowed ) {
				$prohibited[] = $name;
			}
		}
		return $prohibited;
	}

	/**
	 * @param \ZipArchive $zip
	 * @param array<string, string>|array<int, array{path: string, checksum: string}> $checksum_list
	 * @return bool
	 */
	private function verify_checksums( \ZipArchive $zip, array $checksum_list ): bool {
		$all_ok = true;
		foreach ( $checksum_list as $path => $value ) {
			if ( is_int( $path ) && is_array( $value ) ) {
				$path   = $value['path'] ?? '';
				$value  = $value['checksum'] ?? '';
			}
			if ( $path === '' || $value === '' ) {
				continue;
			}
			$content = $zip->getFromName( $path );
			if ( $content === false ) {
				$all_ok = false;
				continue;
			}
			if ( strpos( (string) $value, 'sha256:' ) === 0 ) {
				$expected = substr( (string) $value, 7 );
				$actual   = hash( 'sha256', $content );
				if ( strtolower( $expected ) !== strtolower( $actual ) ) {
					$all_ok = false;
				}
			}
		}
		return $all_ok;
	}

	/**
	 * Pre-scan conflicts: compare incoming keys with current site (when repos available).
	 *
	 * @param \ZipArchive $zip
	 * @param array<string, mixed> $manifest
	 * @return list<array{category: string, key: string, message: string}>
	 */
	private function conflict_pre_scan( \ZipArchive $zip, array $manifest ): array {
		$conflicts = array();
		$included = isset( $manifest['included_categories'] ) && is_array( $manifest['included_categories'] )
			? $manifest['included_categories']
			: array();

		if ( in_array( 'registries', $included, true ) && $this->section_repo !== null ) {
			$json = $zip->getFromName( 'registries/sections.json' );
			if ( $json !== false ) {
				$arr = json_decode( $json, true );
				if ( is_array( $arr ) ) {
					$current = $this->section_repo->list_all_definitions( 9999, 0 );
					$current_keys = array();
					foreach ( $current as $def ) {
						$k = $def['internal_key'] ?? '';
						if ( $k !== '' ) {
							$current_keys[ $k ] = true;
						}
					}
					foreach ( $arr as $frag ) {
						$key = isset( $frag['object_key'] ) ? (string) $frag['object_key'] : ( $frag['payload']['internal_key'] ?? '' );
						if ( $key !== '' && isset( $current_keys[ $key ] ) ) {
							$conflicts[] = array( 'category' => 'registries', 'key' => $key, 'message' => 'Section key exists.' );
						}
					}
				}
			}
		}

		if ( in_array( 'plans', $included, true ) && $this->plan_repo !== null ) {
			$json = $zip->getFromName( 'plans/plans.json' );
			if ( $json !== false ) {
				$arr = json_decode( $json, true );
				if ( is_array( $arr ) ) {
					$list = $this->plan_repo->list_recent( 9999, 0 );
					$current_plan_ids = array();
					foreach ( $list as $rec ) {
						$kid = $rec['internal_key'] ?? '';
						if ( $kid !== '' ) {
							$current_plan_ids[ $kid ] = true;
						}
					}
					foreach ( $arr as $item ) {
						$def = $item['definition'] ?? $item;
						$plan_id = $def['plan_id'] ?? '';
						if ( $plan_id !== '' && isset( $current_plan_ids[ $plan_id ] ) ) {
							$conflicts[] = array( 'category' => 'plans', 'key' => $plan_id, 'message' => 'Plan exists.' );
						}
					}
				}
			}
		}

		if ( in_array( 'token_sets', $included, true ) && $this->token_set_reader !== null ) {
			$json = $zip->getFromName( 'tokens/token_sets.json' );
			if ( $json !== false ) {
				$arr = json_decode( $json, true );
				if ( is_array( $arr ) ) {
					$current = $this->token_set_reader->list_for_export( 0 );
					$current_refs = array();
					foreach ( $current as $row ) {
						$ref = $row['token_set_ref'] ?? '';
						if ( $ref !== '' ) {
							$current_refs[ $ref ] = true;
						}
					}
					foreach ( $arr as $row ) {
						$ref = $row['token_set_ref'] ?? '';
						if ( $ref !== '' && isset( $current_refs[ $ref ] ) ) {
							$conflicts[] = array( 'category' => 'token_sets', 'key' => $ref, 'message' => 'Token set exists.' );
						}
					}
				}
			}
		}

		return $conflicts;
	}
}
