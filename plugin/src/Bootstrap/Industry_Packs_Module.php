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

	/** Container key: industry profile store (primary/secondary industry). Placeholder until implemented. */
	public const CONTAINER_KEY_INDUSTRY_PROFILE_STORE = 'industry_profile_store';

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( self::CONTAINER_KEY_INDUSTRY_LOADED, function (): bool {
			return true;
		} );
		// * Placeholder entrypoints for future prompts (industry-pack-service-map). Resolve to null until implemented.
		$container->register( self::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY, function () {
			return null;
		} );
		$container->register( self::CONTAINER_KEY_INDUSTRY_PROFILE_STORE, function () {
			return null;
		} );
	}
}
