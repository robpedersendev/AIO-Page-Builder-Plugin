<?php
/**
 * Registers onboarding domain services: draft, prefill, UI state builder (onboarding-state-machine.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Planning_Request_Orchestrator;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Prefill_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_UI_State_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

final class Onboarding_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'onboarding_draft_service',
			function () use ( $container ): Onboarding_Draft_Service {
				return new Onboarding_Draft_Service( $container->get( 'settings' ) );
			}
		);
		$container->register(
			'onboarding_prefill_service',
			function () use ( $container ): Onboarding_Prefill_Service {
				$crawl = $container->has( 'crawl_snapshot_service' ) ? $container->get( 'crawl_snapshot_service' ) : null;
				return new Onboarding_Prefill_Service(
					$container->get( 'profile_store' ),
					$container->get( 'settings' ),
					$crawl,
					$container->get( 'provider_secret_store' )
				);
			}
		);
		$container->register(
			'onboarding_ui_state_builder',
			function () use ( $container ): Onboarding_UI_State_Builder {
				$industry_repo     = $container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE )
					? $container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE )
					: null;
				$qp_registry       = $container->has( 'industry_question_pack_registry' )
					? $container->get( 'industry_question_pack_registry' )
					: null;
				$profile_snapshots = $container->has( 'profile_snapshot_repository' )
					? $container->get( 'profile_snapshot_repository' )
					: null;
				return new Onboarding_UI_State_Builder(
					$container->get( 'onboarding_draft_service' ),
					$container->get( 'onboarding_prefill_service' ),
					$industry_repo instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ? $industry_repo : null,
					$qp_registry instanceof \AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Registry ? $qp_registry : null,
					$profile_snapshots instanceof \AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Repository_Interface ? $profile_snapshots : null,
					$container->get( 'settings' )
				);
			}
		);
		$container->register(
			'onboarding_planning_request_orchestrator',
			function () use ( $container ): Onboarding_Planning_Request_Orchestrator {
				return new Onboarding_Planning_Request_Orchestrator(
					$container->get( 'onboarding_draft_service' ),
					$container->get( 'onboarding_prefill_service' ),
					$container->get( 'prompt_pack_registry_service' ),
					$container->get( 'input_artifact_builder' ),
					$container->get( 'normalized_prompt_package_builder' ),
					$container->get( 'provider_request_context_builder' ),
					$container->get( 'provider_capability_resolver' ),
					$container->get( 'ai_output_validator' ),
					$container->get( 'ai_run_service' ),
					$container->get( 'provider_connection_test_service' ),
					$container->get( 'provider_failover_service' ),
					$container
				);
			}
		);
	}
}
