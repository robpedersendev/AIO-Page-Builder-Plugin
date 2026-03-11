<?php
/**
 * Registers repository services (spec §5.2, §10). Data access boundaries only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Documentation_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Job_Queue_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Prompt_Pack_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Repository → storage mapping (see also Object_Type_Keys, Table_Names):
 * - section_template_repository → CPT aio_section_template
 * - page_template_repository → CPT aio_page_template
 * - composition_repository → CPT aio_composition
 * - documentation_repository → CPT aio_documentation
 * - version_snapshot_repository → CPT aio_version_snapshot
 * - build_plan_repository → CPT aio_build_plan
 * - ai_run_repository → CPT aio_ai_run
 * - job_queue_repository → table aio_job_queue
 */
final class Repositories_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'section_template_repository', function (): Section_Template_Repository {
			return new Section_Template_Repository();
		} );
		$container->register( 'page_template_repository', function (): Page_Template_Repository {
			return new Page_Template_Repository();
		} );
		$container->register( 'composition_repository', function (): Composition_Repository {
			return new Composition_Repository();
		} );
		$container->register( 'prompt_pack_repository', function (): Prompt_Pack_Repository {
			return new Prompt_Pack_Repository();
		} );
		$container->register( 'documentation_repository', function (): Documentation_Repository {
			return new Documentation_Repository();
		} );
		$container->register( 'version_snapshot_repository', function (): Version_Snapshot_Repository {
			return new Version_Snapshot_Repository();
		} );
		$container->register( 'build_plan_repository', function (): Build_Plan_Repository {
			return new Build_Plan_Repository();
		} );
		$container->register( 'ai_run_repository', function (): AI_Run_Repository {
			return new AI_Run_Repository();
		} );
		$container->register( 'job_queue_repository', function (): Job_Queue_Repository {
			global $wpdb;
			return new Job_Queue_Repository( $wpdb );
		} );
	}
}
