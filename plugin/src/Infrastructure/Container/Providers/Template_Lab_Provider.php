<?php
/**
 * Template-lab chat persistence, orchestration, and REST-facing application services.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Stale_Guard;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Apply_Lineage_Snapshot_Recorder;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Canonical_Apply_Service;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Telemetry;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Canonical_Registry_Persist_Service;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Chat_Application_Service;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Run_Orchestrator;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Validation_Port;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Validation_Port_Default;
use AIOPageBuilder\Domain\AI\Translation\Composition_AI_Draft_Translator;
use AIOPageBuilder\Domain\AI\Translation\Page_Template_AI_Draft_Translator;
use AIOPageBuilder\Domain\AI\Translation\Section_Template_AI_Draft_Translator;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

final class Template_Lab_Provider implements Service_Provider_Interface {

	public function register( Service_Container $container ): void {
		$container->register(
			'template_lab_telemetry',
			function () use ( $container ): Template_Lab_Telemetry {
				return new Template_Lab_Telemetry( $container->get( 'settings' ) );
			}
		);
		$container->register(
			'template_lab_validation_port',
			function () use ( $container ): Template_Lab_Validation_Port {
				return new Template_Lab_Validation_Port_Default( $container->get( 'ai_output_validator' ) );
			}
		);
		$container->register(
			'template_lab_run_orchestrator',
			function () use ( $container ): Template_Lab_Run_Orchestrator {
				return new Template_Lab_Run_Orchestrator(
					$container->get( 'ai_run_service' ),
					$container->get( 'ai_run_repository' ),
					$container->get( 'template_lab_validation_port' )
				);
			}
		);
		$container->register(
			'template_lab_chat_application_service',
			function () use ( $container ): Template_Lab_Chat_Application_Service {
				return new Template_Lab_Chat_Application_Service(
					$container->get( 'ai_chat_session_repository' ),
					$container->get( 'ai_run_service' ),
					$container->get( 'template_lab_telemetry' )
				);
			}
		);
		$container->register(
			'template_lab_canonical_registry_persist_service',
			function () use ( $container ): Template_Lab_Canonical_Registry_Persist_Service {
				return new Template_Lab_Canonical_Registry_Persist_Service(
					$container->get( 'composition_repository' ),
					$container->get( 'page_template_repository' ),
					$container->get( 'section_template_repository' )
				);
			}
		);
		$container->register(
			'template_lab_apply_lineage_snapshot_recorder',
			function () use ( $container ): Template_Lab_Apply_Lineage_Snapshot_Recorder {
				return new Template_Lab_Apply_Lineage_Snapshot_Recorder(
					$container->get( 'version_snapshot_repository' )
				);
			}
		);
		$container->register(
			'template_lab_canonical_apply_service',
			function () use ( $container ): Template_Lab_Canonical_Apply_Service {
				return new Template_Lab_Canonical_Apply_Service(
					$container->get( 'ai_chat_session_repository' ),
					$container->get( 'ai_run_repository' ),
					$container->get( 'ai_run_artifact_service' ),
					$container->get( 'template_lab_canonical_registry_persist_service' ),
					new Composition_AI_Draft_Translator(),
					new Page_Template_AI_Draft_Translator(),
					new Section_Template_AI_Draft_Translator(),
					$container->get( 'template_lab_apply_lineage_snapshot_recorder' ),
					$container->get( 'template_lab_telemetry' ),
					new Template_Lab_Approved_Snapshot_Stale_Guard( $container->get( 'section_template_repository' ) )
				);
			}
		);
	}
}
