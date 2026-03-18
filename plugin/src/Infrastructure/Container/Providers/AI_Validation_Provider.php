<?php
/**
 * Registers AI output validation pipeline services (spec §28.11–28.14, ai-output-validation-contract.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator;
use AIOPageBuilder\Domain\AI\Validation\Normalized_Output_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers validator and normalized output builder. Validation is server-side only; no secrets in reports.
 */
final class AI_Validation_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'normalized_output_builder',
			function (): Normalized_Output_Builder {
				return new Normalized_Output_Builder();
			}
		);
		$container->register(
			'ai_output_validator',
			function () use ( $container ): AI_Output_Validator {
				return new AI_Output_Validator( $container->get( 'normalized_output_builder' ) );
			}
		);
	}
}
