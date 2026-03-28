<?php
/**
 * Unit tests for AI_Run_Service: artifact category filtering and run lookup (spec §29, §59.8).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Run_Dispatch_Port;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/AI_Run_Repository.php';
require_once $plugin_root . '/src/Domain/AI/Runs/Artifact_Category_Keys.php';
require_once $plugin_root . '/src/Domain/AI/Runs/AI_Run_Artifact_Service.php';
require_once $plugin_root . '/src/Domain/AI/Runs/AI_Run_Service.php';

final class AI_Run_Service_Test extends TestCase {

	public function test_persist_artifacts_with_empty_array_returns_false(): void {
		$repo     = new AI_Run_Repository();
		$artifact = new AI_Run_Artifact_Service( $repo );
		$svc      = new AI_Run_Service( $repo, $artifact );
		$this->assertFalse( $svc->persist_artifacts( 1, array() ) );
	}

	public function test_persist_artifacts_with_only_invalid_categories_returns_false(): void {
		$repo     = new AI_Run_Repository();
		$artifact = new AI_Run_Artifact_Service( $repo );
		$svc      = new AI_Run_Service( $repo, $artifact );
		$this->assertFalse( $svc->persist_artifacts( 1, array( 'invalid_category' => 'x' ) ) );
	}

	public function test_get_run_by_id_returns_null_for_unknown_run(): void {
		$repo     = new AI_Run_Repository();
		$artifact = new AI_Run_Artifact_Service( $repo );
		$svc      = new AI_Run_Service( $repo, $artifact );
		$this->assertNull( $svc->get_run_by_id( 'nonexistent-run-id-999' ) );
	}

	public function test_template_lab_dispatch_mode_defaults_to_sync(): void {
		$repo     = new AI_Run_Repository();
		$artifact = new AI_Run_Artifact_Service( $repo );
		$svc      = new AI_Run_Service( $repo, $artifact );
		$this->assertSame( 'sync', $svc->get_template_lab_dispatch_mode() );
	}

	public function test_template_lab_dispatch_mode_uses_port(): void {
		$port     = new class() implements Template_Lab_Run_Dispatch_Port {
			public function mode(): string {
				return 'queue_pending';
			}
		};
		$repo     = new AI_Run_Repository();
		$artifact = new AI_Run_Artifact_Service( $repo );
		$svc      = new AI_Run_Service( $repo, $artifact, $port );
		$this->assertSame( 'queue_pending', $svc->get_template_lab_dispatch_mode() );
	}
}
