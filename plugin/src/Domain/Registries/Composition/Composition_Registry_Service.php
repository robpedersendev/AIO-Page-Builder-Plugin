<?php
/**
 * Composition registry service: create, read, update (spec §10.3, §14.6–14.10).
 * Validates via Composition_Validator; persists via Composition_Repository and Assignment_Map_Service.
 * Callers must enforce capabilities and nonces before mutating.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Composition;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Compositions\Validation\Large_Composition_Validator;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Integrity_Validator;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Map_Service;
use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Types;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;

/**
 * Governed custom compositions. Validation state and provenance are first-class.
 */
final class Composition_Registry_Service {

	/** @var Composition_Validator */
	private Composition_Validator $validator;

	/** @var Composition_Repository */
	private Composition_Repository $repository;

	/** @var Assignment_Map_Service */
	private Assignment_Map_Service $assignment_map;

	/** @var Registry_Integrity_Validator|null */
	private ?Registry_Integrity_Validator $integrity_validator;

	/** @var Large_Composition_Validator|null When set, used for full CTA/compatibility/preview enforcement (Prompt 178). */
	private ?Large_Composition_Validator $large_validator = null;

	public function __construct(
		Composition_Validator $validator,
		Composition_Repository $repository,
		Assignment_Map_Service $assignment_map,
		?Registry_Integrity_Validator $integrity_validator = null
	) {
		$this->validator           = $validator;
		$this->repository          = $repository;
		$this->assignment_map      = $assignment_map;
		$this->integrity_validator = $integrity_validator;
	}

	/**
	 * Sets the large-library validator for CTA and preview enforcement (Prompt 178). Optional.
	 *
	 * @param Large_Composition_Validator|null $validator
	 * @return void
	 */
	public function set_large_validator( ?Large_Composition_Validator $validator ): void {
		$this->large_validator = $validator;
	}

	/**
	 * Creates a new composition. Generates composition_id if not provided. Runs validation.
	 *
	 * @param array<string, mixed> $input Must include name, ordered_section_list; optional source_template_ref, registry_snapshot_ref.
	 * @return Composition_Registry_Result
	 */
	public function create( array $input ): Composition_Registry_Result {
		$comp_id = (string) ( $input[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
		if ( $comp_id === '' ) {
			$comp_id = $this->generate_composition_id();
		}
		$comp_id = $this->sanitize_composition_id( $comp_id );
		if ( $comp_id === '' ) {
			return Composition_Registry_Result::failure( array( 'composition_id is required or invalid' ), 0 );
		}

		$existing = $this->repository->get_by_key( $comp_id );
		if ( $existing !== null ) {
			return Composition_Registry_Result::failure( array( 'composition_id already exists' ), 0 );
		}

		$definition = $this->normalize_definition( $input, $comp_id );
		if ( $this->large_validator !== null ) {
			$result = $this->large_validator->validate( $definition );
			$definition[ Composition_Schema::FIELD_VALIDATION_STATUS ] = $result->is_valid()
				? ( count( $result->get_warnings() ) > 0 ? Composition_Validation_Result::WARNING : Composition_Validation_Result::VALID )
				: Composition_Validation_Result::VALIDATION_FAILED;
			$codes = array_merge( $result->get_legacy_codes(), array_column( $result->get_blockers(), 'code' ) );
			$definition[ Composition_Schema::FIELD_VALIDATION_CODES ] = array_values( array_unique( $codes ) );
		} else {
			$validation = $this->validator->validate( $definition );
			$definition[ Composition_Schema::FIELD_VALIDATION_STATUS ] = $validation['result'];
			$definition[ Composition_Schema::FIELD_VALIDATION_CODES ]  = $validation['codes'];
		}

		$id = $this->repository->save_definition( $definition );
		if ( $id <= 0 ) {
			return Composition_Registry_Result::failure( array( 'Persistence failed' ), 0 );
		}

		$this->save_section_mappings( $comp_id, $definition[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] );

		return Composition_Registry_Result::success( $id, $definition );
	}

	/**
	 * Updates an existing composition. Revalidates. Replaces section mappings.
	 *
	 * @param int                  $post_id
	 * @param array<string, mixed> $input Partial updates; composition_id is immutable.
	 * @return Composition_Registry_Result
	 */
	public function update( int $post_id, array $input ): Composition_Registry_Result {
		$existing = $this->repository->get_definition_by_id( $post_id );
		if ( $existing === null ) {
			return Composition_Registry_Result::failure( array( 'Composition not found' ), 0 );
		}

		$comp_id = (string) ( $existing[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
		$merged  = array_merge( $existing, $input );
		$merged[ Composition_Schema::FIELD_COMPOSITION_ID ] = $comp_id;

		$definition = $this->normalize_definition( $merged, $comp_id );
		if ( $this->large_validator !== null ) {
			$result = $this->large_validator->validate( $definition );
			$definition[ Composition_Schema::FIELD_VALIDATION_STATUS ] = $result->is_valid()
				? ( count( $result->get_warnings() ) > 0 ? Composition_Validation_Result::WARNING : Composition_Validation_Result::VALID )
				: Composition_Validation_Result::VALIDATION_FAILED;
			$codes = array_merge( $result->get_legacy_codes(), array_column( $result->get_blockers(), 'code' ) );
			$definition[ Composition_Schema::FIELD_VALIDATION_CODES ] = array_values( array_unique( $codes ) );
		} else {
			$validation = $this->validator->validate( $definition );
			$definition[ Composition_Schema::FIELD_VALIDATION_STATUS ] = $validation['result'];
			$definition[ Composition_Schema::FIELD_VALIDATION_CODES ]  = $validation['codes'];
		}

		$id = $this->repository->save_definition( $definition );
		if ( $id <= 0 ) {
			return Composition_Registry_Result::failure( array( 'Persistence failed' ), 0 );
		}

		$this->assignment_map->delete_by_source_and_type( Assignment_Types::COMPOSITION_SECTION, $comp_id );
		$this->save_section_mappings( $comp_id, $definition[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] );

		return Composition_Registry_Result::success( $id, $definition );
	}

	/**
	 * Revalidates and updates validation state without other changes.
	 *
	 * @param int $post_id
	 * @return Composition_Registry_Result
	 */
	public function revalidate( int $post_id ): Composition_Registry_Result {
		$existing = $this->repository->get_definition_by_id( $post_id );
		if ( $existing === null ) {
			return Composition_Registry_Result::failure( array( 'Composition not found' ), 0 );
		}
		return $this->update( $post_id, array() );
	}

	/**
	 * Transitions lifecycle status. Enforces state machine: draft→active only when validation allows activation.
	 *
	 * @param int    $post_id
	 * @param string $new_status
	 * @return Composition_Registry_Result
	 */
	public function set_status( int $post_id, string $new_status ): Composition_Registry_Result {
		$existing = $this->repository->get_definition_by_id( $post_id );
		if ( $existing === null ) {
			return Composition_Registry_Result::failure( array( 'Composition not found' ), 0 );
		}

		$current = (string) ( $existing[ Composition_Schema::FIELD_STATUS ] ?? 'draft' );
		if ( ! Composition_Statuses::is_valid_lifecycle_status( $new_status ) ) {
			return Composition_Registry_Result::failure( array( 'Invalid status' ), 0 );
		}

		if ( $current === 'draft' && $new_status === 'active' ) {
			$val_status = (string) ( $existing[ Composition_Schema::FIELD_VALIDATION_STATUS ] ?? Composition_Validation_Result::PENDING_VALIDATION );
			if ( ! Composition_Validation_Result::allows_activation( $val_status ) ) {
				return Composition_Registry_Result::failure(
					array( 'Cannot activate: validation_result must be valid or warning' ),
					0
				);
			}
		}

		$existing[ Composition_Schema::FIELD_STATUS ] = $new_status;
		$id = $this->repository->save_definition( $existing );
		if ( $id <= 0 ) {
			return Composition_Registry_Result::failure( array( 'Persistence failed' ), 0 );
		}
		return Composition_Registry_Result::success( $id, $existing );
	}

	/**
	 * Reads composition by composition_id.
	 *
	 * @param string $composition_id
	 * @return array<string, mixed>|null
	 */
	public function get_by_id_string( string $composition_id ): ?array {
		return $this->repository->get_definition_by_key( $composition_id );
	}

	/**
	 * Reads composition by post ID.
	 *
	 * @param int $post_id
	 * @return array<string, mixed>|null
	 */
	public function get_by_post_id( int $post_id ): ?array {
		return $this->repository->get_definition_by_id( $post_id );
	}

	/**
	 * Lists composition definitions by status.
	 *
	 * @param string $status
	 * @param int    $limit
	 * @param int    $offset
	 * @return array<int, array<string, mixed>>
	 */
	public function list_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		return $this->repository->list_definitions_by_status( $status, $limit, $offset );
	}

	/**
	 * Returns read-time warnings for deprecated section dependencies in a composition.
	 *
	 * @param string $composition_id
	 * @return array<int, string>
	 */
	public function get_deprecation_warnings( string $composition_id ): array {
		$definition = $this->repository->get_definition_by_key( $composition_id );
		if ( $definition === null || $this->integrity_validator === null ) {
			return array();
		}
		return $this->integrity_validator->get_deprecation_warnings( $definition, 'composition' );
	}

	/**
	 * Attaches a registry snapshot reference to a composition (spec §14.8).
	 *
	 * @param int    $post_id     Composition CPT post ID.
	 * @param string $snapshot_id Valid snapshot_id from Version_Snapshot_Service.
	 * @return Composition_Registry_Result
	 */
	public function attach_registry_snapshot_ref( int $post_id, string $snapshot_id ): Composition_Registry_Result {
		$existing = $this->repository->get_definition_by_id( $post_id );
		if ( $existing === null ) {
			return Composition_Registry_Result::failure( array( 'Composition not found' ), 0 );
		}
		$ref = $this->sanitize_ref( $snapshot_id );
		if ( $ref === '' ) {
			return Composition_Registry_Result::failure( array( 'Invalid snapshot_id' ), 0 );
		}
		$existing[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] = $ref;
		$id = $this->repository->save_definition( $existing );
		if ( $id <= 0 ) {
			return Composition_Registry_Result::failure( array( 'Persistence failed' ), 0 );
		}
		return Composition_Registry_Result::success( $id, $existing );
	}

	/**
	 * Returns ordered section mappings from assignment map for a composition.
	 *
	 * @param string $composition_id
	 * @return array<int, array{section_key: string, position: int, variant: string}>
	 */
	public function get_section_mappings( string $composition_id ): array {
		$rows = $this->assignment_map->list_by_source( Assignment_Types::COMPOSITION_SECTION, $composition_id, 500 );
		$out  = array();
		foreach ( $rows as $row ) {
			$target = (string) ( $row['target_ref'] ?? '' );
			if ( $target === '' ) {
				continue;
			}
			$payload = isset( $row['payload'] ) ? json_decode( (string) $row['payload'], true ) : array();
			$pos     = isset( $payload['position'] ) ? (int) $payload['position'] : count( $out );
			$variant = isset( $payload['variant'] ) ? (string) $payload['variant'] : 'default';
			$out[]   = array(
				'section_key' => $target,
				'position'    => $pos,
				'variant'     => $variant,
			);
		}
		usort( $out, fn( $a, $b ) => $a['position'] <=> $b['position'] );
		return $out;
	}

	/**
	 * @param array<string, mixed> $input
	 * @param string               $comp_id
	 * @return array<string, mixed>
	 */
	private function normalize_definition( array $input, string $comp_id ): array {
		$ordered = $this->normalize_ordered_sections( $input[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? array() );
		return array(
			Composition_Schema::FIELD_COMPOSITION_ID       => $comp_id,
			Composition_Schema::FIELD_NAME                 => \sanitize_text_field( (string) ( $input[ Composition_Schema::FIELD_NAME ] ?? 'Untitled' ) ),
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => $ordered,
			Composition_Schema::FIELD_STATUS               => $this->sanitize_status( (string) ( $input[ Composition_Schema::FIELD_STATUS ] ?? 'draft' ) ),
			Composition_Schema::FIELD_VALIDATION_STATUS    => (string) ( $input[ Composition_Schema::FIELD_VALIDATION_STATUS ] ?? Composition_Validation_Result::PENDING_VALIDATION ),
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => $this->sanitize_ref( (string) ( $input[ Composition_Schema::FIELD_SOURCE_TEMPLATE_REF ] ?? '' ) ),
			Composition_Schema::FIELD_DUPLICATED_FROM_COMPOSITION_ID => $this->sanitize_ref( (string) ( $input[ Composition_Schema::FIELD_DUPLICATED_FROM_COMPOSITION_ID ] ?? '' ) ),
			Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF => $this->sanitize_ref( (string) ( $input[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ?? '' ) ),
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => $this->sanitize_ref( (string) ( $input[ Composition_Schema::FIELD_HELPER_ONE_PAGER_REF ] ?? '' ) ),
		);
	}

	/**
	 * @param array<int, mixed> $raw
	 * @return array<int, array{section_key: string, position: int, variant: string}>
	 */
	private function normalize_ordered_sections( array $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$key = $this->sanitize_section_key( (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' ) );
			if ( $key === '' ) {
				continue;
			}
			$pos     = isset( $item[ Composition_Schema::SECTION_ITEM_POSITION ] ) ? (int) $item[ Composition_Schema::SECTION_ITEM_POSITION ] : $i;
			$variant = isset( $item[ Composition_Schema::SECTION_ITEM_VARIANT ] ) ? \sanitize_text_field( (string) $item[ Composition_Schema::SECTION_ITEM_VARIANT ] ) : 'default';
			$out[]   = array(
				Composition_Schema::SECTION_ITEM_KEY      => $key,
				Composition_Schema::SECTION_ITEM_POSITION => $pos,
				Composition_Schema::SECTION_ITEM_VARIANT  => ( $variant !== '' && $variant !== null ) ? $variant : 'default',
			);
		}
		usort( $out, fn( $a, $b ) => $a[ Composition_Schema::SECTION_ITEM_POSITION ] <=> $b[ Composition_Schema::SECTION_ITEM_POSITION ] );
		for ( $i = 0; $i < count( $out ); $i++ ) {
			$out[ $i ][ Composition_Schema::SECTION_ITEM_POSITION ] = $i;
		}
		return $out;
	}

	/**
	 * @param string                           $comp_id
	 * @param array<int, array<string, mixed>> $ordered
	 */
	private function save_section_mappings( string $comp_id, array $ordered ): void {
		foreach ( $ordered as $item ) {
			$key     = (string) ( $item[ Composition_Schema::SECTION_ITEM_KEY ] ?? '' );
			$pos     = (int) ( $item[ Composition_Schema::SECTION_ITEM_POSITION ] ?? 0 );
			$variant = (string) ( $item[ Composition_Schema::SECTION_ITEM_VARIANT ] ?? 'default' );
			if ( $key === '' ) {
				continue;
			}
			$payload = wp_json_encode(
				array(
					'position' => $pos,
					'variant'  => $variant,
				)
			);
			$this->assignment_map->create(
				Assignment_Types::COMPOSITION_SECTION,
				$comp_id,
				$key,
				'',
				$payload
			);
		}
	}

	private function generate_composition_id(): string {
		return 'comp_' . bin2hex( random_bytes( 8 ) );
	}

	private function sanitize_composition_id( string $id ): string {
		$id = \sanitize_text_field( strtolower( $id ) );
		$id = preg_replace( '/[^a-z0-9_-]/', '', $id );
		return substr( $id, 0, Composition_Schema::COMPOSITION_ID_MAX_LENGTH );
	}

	private function sanitize_section_key( string $key ): string {
		$key = \sanitize_text_field( strtolower( $key ) );
		return substr( preg_replace( '/[^a-z0-9_]/', '', $key ), 0, 64 );
	}

	private function sanitize_ref( string $ref ): string {
		$ref = \sanitize_text_field( $ref );
		return substr( $ref, 0, 64 );
	}

	private function sanitize_status( string $status ): string {
		return Composition_Statuses::is_valid_lifecycle_status( $status ) ? $status : Composition_Statuses::DRAFT;
	}
}
