<?php
/**
 * Registers prompt experiment service (spec §26, §58.3, Prompt 121).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\PromptPacks\Experiments\Prompt_Experiment_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers prompt_experiment_service. Depends on settings, ai_run_service, ai_run_repository.
 */
final class AI_Experiments_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'prompt_experiment_service',
			function () use ( $container ): Prompt_Experiment_Service {
				return new Prompt_Experiment_Service(
					$container->get( 'settings' ),
					$container->get( 'ai_run_service' ),
					$container->get( 'ai_run_repository' )
				);
			}
		);
	}
}
