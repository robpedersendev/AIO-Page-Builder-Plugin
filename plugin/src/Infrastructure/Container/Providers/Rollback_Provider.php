<?php
/**
 * Registers rollback validation, eligibility, and execution services (spec §38.4, §38.5, §41.9, §59.11).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rollback\Execution\Rollback_Executor;
use AIOPageBuilder\Domain\Rollback\UI\Rollback_State_Builder;
use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Eligibility_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers rollback_eligibility_service, rollback_executor, and rollback_state_builder (Prompt 197).
 * Depends on operational_snapshot_repository and diff_summarizer_service.
 */
final class Rollback_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'rollback_eligibility_service', function () use ( $container ): Rollback_Eligibility_Service {
			return new Rollback_Eligibility_Service(
				$container->get( 'operational_snapshot_repository' )
			);
		} );
		$container->register( 'rollback_executor', function () use ( $container ): Rollback_Executor {
			return new Rollback_Executor(
				$container->get( 'rollback_eligibility_service' ),
				$container->get( 'operational_snapshot_repository' )
			);
		} );
		$container->register( 'rollback_state_builder', function () use ( $container ): Rollback_State_Builder {
			return new Rollback_State_Builder(
				$container->get( 'rollback_eligibility_service' ),
				$container->get( 'diff_summarizer_service' )
			);
		} );
	}
}
