<?php
/**
 * Bootstrap entrypoint for the Industry Pack subsystem (industry-pack-extension-contract).
 * Registers the industry subsystem with the container so future prompts have a stable home.
 * Industry packs extend existing registries, onboarding, docs, AI, and LPagery—they do not replace them.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Industry Pack subsystem module. Registers placeholder services; full registries and overlays
 * are added in later prompts. Safe when no industry data is configured.
 */
final class Industry_Packs_Module implements Service_Provider_Interface {

	/** Container key: whether the industry subsystem is loaded (for dependency checks). */
	public const CONTAINER_KEY_INDUSTRY_LOADED = 'industry_packs_loaded';

	/** Container key: industry pack registry (list/get/validate). Placeholder until implemented. */
	public const CONTAINER_KEY_INDUSTRY_PACK_REGISTRY = 'industry_pack_registry';

	/** Container key: industry profile store (primary/secondary industry). */
	public const CONTAINER_KEY_INDUSTRY_PROFILE_STORE = 'industry_profile_store';

	/** Container key: CTA pattern registry (industry-cta-pattern-contract). */
	public const CONTAINER_KEY_CTA_PATTERN_REGISTRY = 'industry_cta_pattern_registry';

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( self::CONTAINER_KEY_INDUSTRY_LOADED, function (): bool {
			return true;
		} );
		$container->register( 'industry_pack_validator', function (): \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator {
			return new \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator();
		} );
		$container->register( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY, function () use ( $container ): \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry {
			$validator = $container->has( 'industry_pack_validator' ) ? $container->get( 'industry_pack_validator' ) : new \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator();
			$registry  = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry( $validator );
			$registry->load( array() );
			return $registry;
		} );
		$container->register( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE, function () use ( $container ): ?\AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository {
			if ( ! $container->has( 'settings' ) ) {
				return null;
			}
			return new \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository( $container->get( 'settings' ) );
		} );
		$container->register( self::CONTAINER_KEY_CTA_PATTERN_REGISTRY, function (): \AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry {
			$registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry();
			$registry->load( array() );
			return $registry;
		} );
		$container->register( 'industry_question_pack_registry', function (): \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry {
			$registry = new \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry();
			$registry->load( \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Definitions::default_packs() );
			return $registry;
		} );
		$container->register( 'industry_prompt_pack_overlay_service', function () use ( $container ): \AIOPageBuilder\Domain\Industry\AI\Industry_Prompt_Pack_Overlay_Service {
			$pack_registry = $container->has( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY )
				? $container->get( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY )
				: null;
			return new \AIOPageBuilder\Domain\Industry\AI\Industry_Prompt_Pack_Overlay_Service( $pack_registry instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ? $pack_registry : null );
		} );
	}
}
