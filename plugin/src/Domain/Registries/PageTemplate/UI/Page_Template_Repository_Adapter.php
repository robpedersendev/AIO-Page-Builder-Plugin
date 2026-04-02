<?php
/**
 * Adapts Page_Template_Repository to Page_Template_Definition_Provider (spec §49.7).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;

/**
 * Wraps Page_Template_Repository to implement Page_Template_Definition_Provider.
 */
final class Page_Template_Repository_Adapter implements Page_Template_Definition_Provider {

	/** @var Page_Template_Repository */
	private Page_Template_Repository $repository;

	public function __construct( Page_Template_Repository $repository ) {
		$this->repository = $repository;
	}

	/** @inheritdoc */
	public function get_definition_by_key( string $key ): ?array {
		return $this->repository->get_definition_by_key( $key );
	}
}
