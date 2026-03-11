<?php
/**
 * Registers AI run and artifact services (spec §29, §59.8).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers ai_run_artifact_service and ai_run_service. Depends on Repositories_Provider (ai_run_repository).
 */
final class AI_Runs_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'ai_run_artifact_service', function () use ( $container ): AI_Run_Artifact_Service {
			return new AI_Run_Artifact_Service( $container->get( 'ai_run_repository' ) );
		} );
		$container->register( 'ai_run_service', function () use ( $container ): AI_Run_Service {
			return new AI_Run_Service(
				$container->get( 'ai_run_repository' ),
				$container->get( 'ai_run_artifact_service' )
			);
		} );
	}
}
