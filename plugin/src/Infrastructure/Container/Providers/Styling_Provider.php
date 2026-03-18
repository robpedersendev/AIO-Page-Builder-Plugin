<?php
/**
 * Registers style spec loader, style registries (Prompt 244), and front-end base style enqueue (Prompt 245).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Entity_Style_Payload_Repository;
use AIOPageBuilder\Domain\Styling\Frontend_Style_Enqueue_Service;
use AIOPageBuilder\Domain\Styling\Global_Component_Override_Emitter;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Styling\Global_Token_Variable_Emitter;
use AIOPageBuilder\Domain\Styling\Render_Surface_Style_Registry;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use AIOPageBuilder\Domain\Preview\Styling\Preview_Style_Context_Builder;
use AIOPageBuilder\Domain\Styling\Page_Style_Emitter;
use AIOPageBuilder\Domain\Styling\Section_Style_Emitter;
use AIOPageBuilder\Domain\Styling\Style_Cache_Service;
use AIOPageBuilder\Domain\Styling\Styles_JSON_Normalizer;
use AIOPageBuilder\Domain\Styling\Styles_JSON_Sanitizer;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers style spec loader, read-only registries, and conditional front-end base stylesheet enqueue.
 */
final class Styling_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'style_spec_loader',
			function () use ( $container ): Style_Spec_Loader {
				$config = $container->get( 'config' );
				$base   = $config->plugin_dir() . 'specs/';
				if ( ! \is_readable( $base ) || ! @\is_file( $base . Style_Spec_Loader::CORE_SPEC_FILE ) ) {
					$fallback = \dirname( $config->plugin_dir() ) . '/docs/specs/';
					if ( \is_readable( $fallback ) && @\is_file( $fallback . Style_Spec_Loader::CORE_SPEC_FILE ) ) {
						$base = $fallback;
					}
				}
				return new Style_Spec_Loader( $base );
			}
		);

		$container->register(
			'style_token_registry',
			function () use ( $container ): Style_Token_Registry {
				return new Style_Token_Registry( $container->get( 'style_spec_loader' ) );
			}
		);

		$container->register(
			'component_override_registry',
			function () use ( $container ): Component_Override_Registry {
				return new Component_Override_Registry( $container->get( 'style_spec_loader' ) );
			}
		);

		$container->register(
			'render_surface_style_registry',
			function () use ( $container ): Render_Surface_Style_Registry {
				return new Render_Surface_Style_Registry( $container->get( 'style_spec_loader' ) );
			}
		);

		$container->register(
			'entity_style_payload_repository',
			function (): Entity_Style_Payload_Repository {
				return new Entity_Style_Payload_Repository();
			}
		);

		$container->register(
			'styles_json_normalizer',
			function (): Styles_JSON_Normalizer {
				return new Styles_JSON_Normalizer();
			}
		);

		$container->register(
			'styles_json_sanitizer',
			function () use ( $container ): Styles_JSON_Sanitizer {
				return new Styles_JSON_Sanitizer(
					$container->get( 'style_token_registry' ),
					$container->get( 'component_override_registry' ),
					$container->get( 'styles_json_normalizer' )
				);
			}
		);

		$container->register(
			'global_style_settings_repository',
			function () use ( $container ): Global_Style_Settings_Repository {
				$token_registry     = $container->has( 'style_token_registry' ) ? $container->get( 'style_token_registry' ) : null;
				$component_registry = $container->has( 'component_override_registry' ) ? $container->get( 'component_override_registry' ) : null;
				return new Global_Style_Settings_Repository( $token_registry, $component_registry );
			}
		);

		$container->register(
			'global_token_variable_emitter',
			function () use ( $container ): Global_Token_Variable_Emitter {
				$repo     = $container->get( 'global_style_settings_repository' );
				$registry = $container->has( 'style_token_registry' ) ? $container->get( 'style_token_registry' ) : null;
				return new Global_Token_Variable_Emitter( $repo, $registry );
			}
		);

		$container->register(
			'global_component_override_emitter',
			function () use ( $container ): Global_Component_Override_Emitter {
				$repo     = $container->get( 'global_style_settings_repository' );
				$registry = $container->has( 'component_override_registry' ) ? $container->get( 'component_override_registry' ) : null;
				return new Global_Component_Override_Emitter( $repo, $registry );
			}
		);

		$container->register(
			'page_style_emitter',
			function () use ( $container ): Page_Style_Emitter {
				$repo  = $container->get( 'entity_style_payload_repository' );
				$token = $container->has( 'style_token_registry' ) ? $container->get( 'style_token_registry' ) : null;
				$comp  = $container->has( 'component_override_registry' ) ? $container->get( 'component_override_registry' ) : null;
				return new Page_Style_Emitter( $repo, $token, $comp );
			}
		);

		$container->register(
			'section_style_emitter',
			function () use ( $container ): Section_Style_Emitter {
				$repo  = $container->get( 'entity_style_payload_repository' );
				$token = $container->has( 'style_token_registry' ) ? $container->get( 'style_token_registry' ) : null;
				$comp  = $container->has( 'component_override_registry' ) ? $container->get( 'component_override_registry' ) : null;
				return new Section_Style_Emitter( $repo, $token, $comp );
			}
		);

		$container->register(
			'preview_style_context_builder',
			function () use ( $container ): Preview_Style_Context_Builder {
				$token_emitter    = $container->has( 'global_token_variable_emitter' ) ? $container->get( 'global_token_variable_emitter' ) : null;
				$override_emitter = $container->has( 'global_component_override_emitter' ) ? $container->get( 'global_component_override_emitter' ) : null;
				$page_emitter     = $container->has( 'page_style_emitter' ) ? $container->get( 'page_style_emitter' ) : null;
				return new Preview_Style_Context_Builder( $container->get( 'config' ), $token_emitter, $override_emitter, $page_emitter );
			}
		);

		$container->register(
			'style_cache_service',
			function () use ( $container ): Style_Cache_Service {
				$preview_cache = $container->has( 'preview_cache_service' ) ? $container->get( 'preview_cache_service' ) : null;
				return new Style_Cache_Service( $preview_cache instanceof \AIOPageBuilder\Domain\Preview\Preview_Cache_Service ? $preview_cache : null );
			}
		);

		$container->register(
			'frontend_style_enqueue_service',
			function () use ( $container ): Frontend_Style_Enqueue_Service {
				$token_emitter    = $container->has( 'global_token_variable_emitter' ) ? $container->get( 'global_token_variable_emitter' ) : null;
				$override_emitter = $container->has( 'global_component_override_emitter' ) ? $container->get( 'global_component_override_emitter' ) : null;
				$page_emitter     = $container->has( 'page_style_emitter' ) ? $container->get( 'page_style_emitter' ) : null;
				$style_cache      = $container->has( 'style_cache_service' ) ? $container->get( 'style_cache_service' ) : null;
				return new Frontend_Style_Enqueue_Service( $container->get( 'config' ), $token_emitter, $override_emitter, $page_emitter, $style_cache instanceof Style_Cache_Service ? $style_cache : null );
			}
		);

		// * Invalidate style cache (and preview cache) when style data changes (Prompt 256).
		\add_action(
			'update_option_aio_global_style_settings',
			function () use ( $container ): void {
				if ( $container->has( 'style_cache_service' ) ) {
					$svc = $container->get( 'style_cache_service' );
					if ( $svc instanceof Style_Cache_Service ) {
						$svc->invalidate();
					}
				}
			},
			10,
			0
		);
		\add_action(
			'update_option_aio_entity_style_payloads',
			function () use ( $container ): void {
				if ( $container->has( 'style_cache_service' ) ) {
					$svc = $container->get( 'style_cache_service' );
					if ( $svc instanceof Style_Cache_Service ) {
						$svc->invalidate();
					}
				}
			},
			10,
			0
		);

		\add_action(
			'wp_enqueue_scripts',
			function () use ( $container ): void {
				if ( $container->has( 'frontend_style_enqueue_service' ) ) {
					$container->get( 'frontend_style_enqueue_service' )->enqueue_when_needed();
				}
			},
			10
		);
	}
}
