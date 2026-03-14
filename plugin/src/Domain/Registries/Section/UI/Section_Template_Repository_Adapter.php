<?php
/**
 * Adapts Section_Template_Repository to Section_Definition_Provider (spec §49.6).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Wraps Section_Template_Repository to implement Section_Definition_Provider.
 */
final class Section_Template_Repository_Adapter implements Section_Definition_Provider {

	/** @var Section_Template_Repository */
	private Section_Template_Repository $repository;

	public function __construct( Section_Template_Repository $repository ) {
		$this->repository = $repository;
	}

	/** @inheritdoc */
	public function get_definition_by_key( string $key ): ?array {
		return $this->repository->get_definition_by_key( $key );
	}
}
