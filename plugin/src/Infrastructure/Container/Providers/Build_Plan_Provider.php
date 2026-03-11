<?php
/**
 * Registers Build Plan generation services (spec §30.3).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Generator;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Item_Generator;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers build_plan_item_generator and build_plan_generator. Depends on Repositories_Provider (build_plan_repository).
 */
final class Build_Plan_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'build_plan_item_generator', function (): Build_Plan_Item_Generator {
			return new Build_Plan_Item_Generator();
		} );
		$container->register( 'build_plan_generator', function () use ( $container ): Build_Plan_Generator {
			return new Build_Plan_Generator(
				$container->get( 'build_plan_repository' ),
				$container->get( 'build_plan_item_generator' )
			);
		} );
	}
}
