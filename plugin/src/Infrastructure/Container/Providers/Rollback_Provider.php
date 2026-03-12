<?php
/**
 * Registers rollback validation and eligibility services (spec §38.4, §41.9, §59.11).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Eligibility_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers rollback_eligibility_service. Depends on operational_snapshot_repository.
 */
final class Rollback_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'rollback_eligibility_service', function () use ( $container ): Rollback_Eligibility_Service {
			return new Rollback_Eligibility_Service(
				$container->get( 'operational_snapshot_repository' )
			);
		} );
	}
}
