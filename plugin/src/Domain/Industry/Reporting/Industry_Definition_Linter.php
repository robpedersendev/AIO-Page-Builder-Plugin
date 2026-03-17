<?php
/**
 * Internal linting and registry-graph validation for industry authors (Prompt 438).
 * Checks schema conformance, missing refs, parent/subtype consistency, duplicate keys, and broken bundle graph.
 * No auto-fix; internal-only; human-readable results for authoring and release workflows.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

/**
 * Lints industry subsystem definitions and ref graph. Produces human-readable results for authors.
 */
final class Industry_Definition_Linter {

	public const SEVERITY_ERROR   = 'error';
	public const SEVERITY_WARNING = 'warning';

	public const OBJECT_TYPE_PACK          = 'pack';
	public const OBJECT_TYPE_STARTER_BUNDLE = 'starter_bundle';
	public const OBJECT_TYPE_SUBTYPE       = 'subtype';
	public const OBJECT_TYPE_PROFILE       = 'profile';

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Pack_Validator|null */
	private $pack_validator;

	/** @var Industry_Health_Check_Service|null */
	private $health_check;

	/** @var Industry_Starter_Bundle_Registry|null */
	private $starter_bundle_registry;

	/** @var Industry_Subtype_Registry|null */
	private $subtype_registry;

	public function __construct(
		?Industry_Pack_Registry $pack_registry = null,
		?Industry_Pack_Validator $pack_validator = null,
		?Industry_Health_Check_Service $health_check = null,
		?Industry_Starter_Bundle_Registry $starter_bundle_registry = null,
		?Industry_Subtype_Registry $subtype_registry = null
	) {
		$this->pack_registry            = $pack_registry;
		$this->pack_validator           = $pack_validator ?? new Industry_Pack_Validator();
		$this->health_check             = $health_check;
		$this->starter_bundle_registry  = $starter_bundle_registry;
		$this->subtype_registry         = $subtype_registry;
	}

	/**
	 * Runs lint across packs, refs, subtypes, and bundle graph. Returns human-readable results.
	 *
	 * @return array{errors: list<array{severity: string, code: string, message: string, object_type: string, key: string, field?: string, related_refs: list<string>}>, warnings: list<array{severity: string, code: string, message: string, object_type: string, key: string, field?: string, related_refs: list<string>}>, summary: array{error_count: int, warning_count: int}}
	 */
	public function lint(): array {
		$errors   = array();
		$warnings = array();

		$add_error = static function ( string $code, string $message, string $object_type, string $key, array $refs = array(), ?string $field = null ) use ( &$errors ): void {
			$errors[] = array(
				'severity'     => self::SEVERITY_ERROR,
				'code'         => $code,
				'message'      => $message,
				'object_type'  => $object_type,
				'key'          => $key,
				'related_refs' => $refs,
			) + ( $field !== null ? array( 'field' => $field ) : array() );
		};
		$add_warning = static function ( string $code, string $message, string $object_type, string $key, array $refs = array(), ?string $field = null ) use ( &$warnings ): void {
			$warnings[] = array(
				'severity'     => self::SEVERITY_WARNING,
				'code'         => $code,
				'message'      => $message,
				'object_type'  => $object_type,
				'key'          => $key,
				'related_refs' => $refs,
			) + ( $field !== null ? array( 'field' => $field ) : array() );
		};

		// 1. Pack schema and duplicate keys (on current registry state).
		if ( $this->pack_registry !== null ) {
			$packs = $this->pack_registry->get_all();
			$seen_keys = array();
			foreach ( $packs as $index => $pack ) {
				if ( ! is_array( $pack ) ) {
					continue;
				}
				$pack_errors = $this->pack_validator->validate_pack( $pack );
				$industry_key = isset( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					? trim( $pack[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ] )
					: '';
				foreach ( $pack_errors as $err ) {
					$add_error(
						$err['code'] ?? 'validation_error',
											'Pack validation failed: ' . ( $err['code'] ?? '' ) . ( isset( $err['field'] ) ? ' (field: ' . $err['field'] . ')' : '' ),
						self::OBJECT_TYPE_PACK,
						$industry_key !== '' ? $industry_key : (string) $index,
						array(),
						isset( $err['field'] ) ? $err['field'] : null
					);
				}
				if ( $industry_key !== '' && isset( $seen_keys[ $industry_key ] ) ) {
					$add_error( 'duplicate_key', 'Duplicate pack industry_key.', self::OBJECT_TYPE_PACK, $industry_key, array() );
				}
				if ( $industry_key !== '' ) {
					$seen_keys[ $industry_key ] = true;
				}
			}
		}

		// 2. Health check (ref resolution, profile, bundle graph).
		if ( $this->health_check !== null ) {
			$health = $this->health_check->run();
			foreach ( $health['errors'] as $h ) {
				$add_error(
					'ref_not_resolved',
					$h['issue_summary'] ?? 'Reference does not resolve.',
					$h['object_type'] ?? 'pack',
					$h['key'] ?? '',
					$h['related_refs'] ?? array()
				);
			}
			foreach ( $health['warnings'] as $h ) {
				$add_warning(
					'ref_warning',
					$h['issue_summary'] ?? 'Reference warning.',
					$h['object_type'] ?? 'pack',
					$h['key'] ?? '',
					$h['related_refs'] ?? array()
				);
			}
		}

		// 3. Subtype parent_industry_key must exist in pack registry.
		if ( $this->subtype_registry !== null && $this->pack_registry !== null ) {
			$subtypes = $this->subtype_registry->get_all();
			foreach ( $subtypes as $sub ) {
				if ( ! is_array( $sub ) ) {
					continue;
				}
				$subtype_key = trim( (string) ( $sub[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] ?? '' ) );
				$parent_key  = trim( (string) ( $sub[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ?? '' ) );
				if ( $parent_key !== '' && $this->pack_registry->get( $parent_key ) === null ) {
					$add_error(
						'subtype_parent_missing',
						'Subtype parent_industry_key has no matching pack.',
						self::OBJECT_TYPE_SUBTYPE,
						$subtype_key !== '' ? $subtype_key : 'unknown',
						array( $parent_key )
					);
				}
			}
		}

		// 4. Starter bundle industry_key must resolve to pack (health check already does this; optional duplicate check).
		if ( $this->starter_bundle_registry !== null && $this->pack_registry !== null ) {
			$seen_bundle_keys = array();
			foreach ( $this->starter_bundle_registry->list_all() as $bundle ) {
				$bundle_key = trim( (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '' ) );
				if ( $bundle_key !== '' && isset( $seen_bundle_keys[ $bundle_key ] ) ) {
					$add_warning( 'duplicate_bundle_key', 'Duplicate starter bundle_key.', self::OBJECT_TYPE_STARTER_BUNDLE, $bundle_key, array() );
				}
				if ( $bundle_key !== '' ) {
					$seen_bundle_keys[ $bundle_key ] = true;
				}
			}
		}

		return array(
			'errors'   => $errors,
			'warnings' => $warnings,
			'summary'  => array(
				'error_count'   => count( $errors ),
				'warning_count' => count( $warnings ),
			),
		);
	}

	/**
	 * Returns a flat list of all issues (errors then warnings) for simple iteration or display.
	 *
	 * @return list<array{severity: string, code: string, message: string, object_type: string, key: string, field?: string, related_refs: list<string>}>
	 */
	public function get_all_issues(): array {
		$result = $this->lint();
		return array_merge( $result['errors'], $result['warnings'] );
	}
}
