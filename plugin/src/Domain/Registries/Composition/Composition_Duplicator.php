<?php
/**
 * Duplicates compositions with new unique ID and preserved provenance (composition-validation-state-machine §6).
 * Revalidation runs; validation state is not copied.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Composition;

defined( 'ABSPATH' ) || exit;

/**
 * Clone creates new composition_id. duplicated_from_composition_id preserves provenance.
 */
final class Composition_Duplicator {

	/** @var Composition_Registry_Service */
	private Composition_Registry_Service $registry;

	public function __construct( Composition_Registry_Service $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Duplicates a composition. New composition receives new unique ID. Provenance stored.
	 *
	 * @param int    $source_post_id Post ID of source composition.
	 * @param string $new_name      Optional name for clone; defaults to "Copy of {source name}".
	 * @return Composition_Registry_Result
	 */
	public function duplicate( int $source_post_id, string $new_name = '' ): Composition_Registry_Result {
		$source = $this->registry->get_by_post_id( $source_post_id );
		if ( $source === null ) {
			return Composition_Registry_Result::failure( array( 'Source composition not found' ), 0 );
		}

		$source_comp_id = (string) ( $source[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
		if ( $source_comp_id === '' ) {
			return Composition_Registry_Result::failure( array( 'Source has no composition_id' ), 0 );
		}

		$clone = array(
			Composition_Schema::FIELD_NAME             => $new_name !== '' ? $new_name : ( 'Copy of ' . ( $source[ Composition_Schema::FIELD_NAME ] ?? 'Composition' ) ),
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => $source[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? array(),
			Composition_Schema::FIELD_STATUS           => Composition_Statuses::DRAFT,
			Composition_Schema::FIELD_VALIDATION_STATUS => Composition_Validation_Result::PENDING_VALIDATION,
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF => (string) ( $source[ Composition_Schema::FIELD_SOURCE_TEMPLATE_REF ] ?? '' ),
			Composition_Schema::FIELD_DUPLICATED_FROM_COMPOSITION_ID => $source_comp_id,
			Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF => (string) ( $source[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ?? '' ),
		);

		return $this->registry->create( $clone );
	}
}
