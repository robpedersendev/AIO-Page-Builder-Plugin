<?php
/**
 * Registers prompt-pack regression harness for QA (spec §26, §56.2, Prompt 120).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\PromptPacks\Regression\Prompt_Pack_Regression_Harness;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers prompt_pack_regression_harness. Internal QA only; fixtures_base_path empty by default (set by caller when running).
 */
final class AI_Regression_Harness_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'prompt_pack_regression_harness', function () use ( $container ): Prompt_Pack_Regression_Harness {
			$validator = $container->has( 'ai_output_validator' ) ? $container->get( 'ai_output_validator' ) : new \AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator();
			return new Prompt_Pack_Regression_Harness( $validator, '' );
		} );
	}
}
