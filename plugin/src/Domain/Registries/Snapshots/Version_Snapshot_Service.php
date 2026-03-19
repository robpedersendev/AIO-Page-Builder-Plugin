<?php
/**
 * Snapshot capture and retrieval for registry state (spec §10.8, §14.8).
 * Creates/reads version snapshots for section, page template, composition contexts.
 * Callers must enforce capabilities and nonces before mutating.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Snapshots;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository;

/**
 * Registry-oriented snapshot capture and query. No rollback, diff, or execution snapshots.
 */
final class Version_Snapshot_Service {

	/** Schema version for snapshot definitions. */
	private const SCHEMA_VERSION = '1';

	/** @var Section_Registry_Service */
	private Section_Registry_Service $section_registry;

	/** @var Page_Template_Registry_Service */
	private Page_Template_Registry_Service $page_template_registry;

	/** @var Version_Snapshot_Repository */
	private Version_Snapshot_Repository $repository;

	/** @var Composition_Repository */
	private Composition_Repository $composition_repository;

	public function __construct(
		Section_Registry_Service $section_registry,
		Page_Template_Registry_Service $page_template_registry,
		Version_Snapshot_Repository $repository,
		Composition_Repository $composition_repository
	) {
		$this->section_registry       = $section_registry;
		$this->page_template_registry = $page_template_registry;
		$this->repository             = $repository;
		$this->composition_repository = $composition_repository;
	}

	/**
	 * Captures section registry state and persists a snapshot.
	 *
	 * @param string $scope_id Optional scope identifier (e.g. 'section_registry').
	 * @return array{success: bool, snapshot_id: string, post_id: int, errors: array<int, string>}
	 */
	public function capture_section_registry( string $scope_id = 'section_registry' ): array {
		$sections = $this->section_registry->list_by_status( 'active', 500, 0 );
		$payload  = Snapshot_Payload_Builder::build_section_registry_payload( $sections );
		if ( ! Snapshot_Payload_Builder::has_no_prohibited_fields( $payload ) ) {
			return array(
				'success'     => false,
				'snapshot_id' => '',
				'post_id'     => 0,
				'errors'      => array( 'Payload contains prohibited fields' ),
			);
		}
		return $this->persist_snapshot(
			Version_Snapshot_Schema::SCOPE_REGISTRY,
			$scope_id !== '' && $scope_id !== null ? $scope_id : 'section_registry',
			$payload,
			array( 'sections_count' => count( $sections ) )
		);
	}

	/**
	 * Captures page template registry state and persists a snapshot.
	 *
	 * @param string $scope_id Optional scope identifier (e.g. 'page_template_registry').
	 * @return array{success: bool, snapshot_id: string, post_id: int, errors: array<int, string>}
	 */
	public function capture_page_template_registry( string $scope_id = 'page_template_registry' ): array {
		$templates = $this->page_template_registry->list_by_status( 'active', 500, 0 );
		$payload   = Snapshot_Payload_Builder::build_page_template_registry_payload( $templates );
		if ( ! Snapshot_Payload_Builder::has_no_prohibited_fields( $payload ) ) {
			return array(
				'success'     => false,
				'snapshot_id' => '',
				'post_id'     => 0,
				'errors'      => array( 'Payload contains prohibited fields' ),
			);
		}
		return $this->persist_snapshot(
			Version_Snapshot_Schema::SCOPE_REGISTRY,
			$scope_id !== '' && $scope_id !== null ? $scope_id : 'page_template_registry',
			$payload,
			array( 'templates_count' => count( $templates ) )
		);
	}

	/**
	 * Captures composition validation context and persists a snapshot.
	 *
	 * @param array<string, mixed> $composition_definition Normalized composition definition.
	 * @return array{success: bool, snapshot_id: string, post_id: int, errors: array<int, string>}
	 */
	public function capture_composition_context( array $composition_definition ): array {
		$comp_id = (string) ( $composition_definition[ \AIOPageBuilder\Domain\Registries\Composition\Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
		if ( $comp_id === '' ) {
			return array(
				'success'     => false,
				'snapshot_id' => '',
				'post_id'     => 0,
				'errors'      => array( 'Composition definition must include composition_id' ),
			);
		}
		$payload = Snapshot_Payload_Builder::build_composition_context_payload( $composition_definition );
		if ( ! Snapshot_Payload_Builder::has_no_prohibited_fields( $payload ) ) {
			return array(
				'success'     => false,
				'snapshot_id' => '',
				'post_id'     => 0,
				'errors'      => array( 'Payload contains prohibited fields' ),
			);
		}
		return $this->persist_snapshot(
			Version_Snapshot_Schema::SCOPE_REGISTRY,
			$comp_id,
			$payload,
			array( 'composition_id' => $comp_id )
		);
	}

	/**
	 * Retrieves snapshot definition by post ID.
	 *
	 * @param int $post_id
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( int $post_id ): ?array {
		return $this->repository->get_definition_by_id( $post_id );
	}

	/**
	 * Retrieves snapshot definition by snapshot_id.
	 *
	 * @param string $snapshot_id
	 * @return array<string, mixed>|null
	 */
	public function get_by_snapshot_id( string $snapshot_id ): ?array {
		return $this->repository->get_definition_by_key( $snapshot_id );
	}

	/**
	 * Lists snapshot definitions by scope_type.
	 *
	 * @param string $scope_type Version_Snapshot_Schema::SCOPE_* constant.
	 * @param int    $limit
	 * @param int    $offset
	 * @return array<int, array<string, mixed>>
	 */
	public function list_by_scope_type( string $scope_type, int $limit = 0, int $offset = 0 ): array {
		return $this->repository->list_definitions_by_scope_type( $scope_type, $limit, $offset );
	}

	/**
	 * Lists snapshot definitions by scope_id.
	 *
	 * @param string $scope_id
	 * @param int    $limit
	 * @param int    $offset
	 * @return array<int, array<string, mixed>>
	 */
	public function list_by_scope_id( string $scope_id, int $limit = 0, int $offset = 0 ): array {
		return $this->repository->list_definitions_by_scope_id( $scope_id, $limit, $offset );
	}

	/**
	 * Attaches a snapshot reference to a composition (updates registry_snapshot_ref_at_creation).
	 *
	 * @param int    $composition_post_id Composition CPT post ID.
	 * @param string $snapshot_id         Valid snapshot_id to attach.
	 * @return bool True if attachment succeeded.
	 */
	public function attach_snapshot_reference_to_composition( int $composition_post_id, string $snapshot_id ): bool {
		$definition = $this->composition_repository->get_definition_by_id( $composition_post_id );
		if ( $definition === null ) {
			return false;
		}
		$snapshot = $this->repository->get_definition_by_key( $snapshot_id );
		if ( $snapshot === null ) {
			return false;
		}
		$definition[ \AIOPageBuilder\Domain\Registries\Composition\Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] = $snapshot_id;
		$id = $this->composition_repository->save_definition( $definition );
		return $id > 0;
	}

	/**
	 * @param string               $scope_type
	 * @param string               $scope_id
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $object_refs
	 * @return array{success: bool, snapshot_id: string, post_id: int, errors: array<int, string>}
	 */
	private function persist_snapshot(
		string $scope_type,
		string $scope_id,
		array $payload,
		array $object_refs = array()
	): array {
		$snapshot_id = $this->generate_snapshot_id();
		$definition  = array(
			Version_Snapshot_Schema::FIELD_SNAPSHOT_ID    => $snapshot_id,
			Version_Snapshot_Schema::FIELD_SCOPE_TYPE     => $scope_type,
			Version_Snapshot_Schema::FIELD_SCOPE_ID       => $scope_id,
			Version_Snapshot_Schema::FIELD_CREATED_AT     => gmdate( 'Y-m-d\TH:i:s\Z' ),
			Version_Snapshot_Schema::FIELD_SCHEMA_VERSION => self::SCHEMA_VERSION,
			Version_Snapshot_Schema::FIELD_STATUS         => Version_Snapshot_Schema::STATUS_ACTIVE,
			Version_Snapshot_Schema::FIELD_OBJECT_REFS    => $object_refs,
			Version_Snapshot_Schema::FIELD_PROVENANCE     => array(
				'source'      => 'version_snapshot_service',
				'captured_at' => $payload['captured_at'] ?? gmdate( 'Y-m-d\TH:i:s\Z' ),
			),
			'payload'                                     => $payload,
		);
		$id          = $this->repository->save_definition( $definition );
		if ( $id <= 0 ) {
			return array(
				'success'     => false,
				'snapshot_id' => '',
				'post_id'     => 0,
				'errors'      => array( 'Persistence failed' ),
			);
		}
		return array(
			'success'     => true,
			'snapshot_id' => $snapshot_id,
			'post_id'     => $id,
			'errors'      => array(),
		);
	}

	private function generate_snapshot_id(): string {
		return 'snap_' . bin2hex( random_bytes( 8 ) );
	}
}
