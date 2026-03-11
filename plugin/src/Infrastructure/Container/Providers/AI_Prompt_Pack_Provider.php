<?php
/**
 * Registers prompt-pack registry, input artifact builder, and normalized prompt package builder (spec §26, §27, §29).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Builder;
use AIOPageBuilder\Domain\AI\PromptPacks\Normalized_Prompt_Package_Builder;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Registry_Service;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers prompt-pack registry and input-artifact assembly services. No AI run persistence.
 */
final class AI_Prompt_Pack_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'prompt_pack_registry_service', function () use ( $container ): Prompt_Pack_Registry_Service {
			return new Prompt_Pack_Registry_Service( $container->get( 'prompt_pack_repository' ) );
		} );
		$container->register( 'input_artifact_builder', function (): Input_Artifact_Builder {
			return new Input_Artifact_Builder();
		} );
		$container->register( 'normalized_prompt_package_builder', function (): Normalized_Prompt_Package_Builder {
			return new Normalized_Prompt_Package_Builder();
		} );
	}
}
