<?php
/**
 * Unit tests for default planning prompt pack definition and registry selection.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Registry_Service;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Schema;
use AIOPageBuilder\Domain\AI\PromptPacks\Seeds\Default_Planning_Prompt_Pack_Definition;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Registry_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Registry_Service.php';
require_once $plugin_root . '/src/Domain/AI/Validation/Build_Plan_Draft_Schema.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Seeds/Default_Planning_Prompt_Pack_Definition.php';
require_once __DIR__ . '/Prompt_Pack_Registry_And_Input_Artifact_Test.php';

final class Default_Planning_Prompt_Pack_Definition_Test extends TestCase {

	public function test_default_definition_selects_for_planning(): void {
		$def  = Default_Planning_Prompt_Pack_Definition::get();
		$repo = new Test_Prompt_Pack_Repo();
		$repo->add_pack( $def );
		$registry = new Prompt_Pack_Registry_Service( $repo );
		$selected = $registry->select_for_planning( Build_Plan_Draft_Schema::SCHEMA_REF, 'openai' );
		$this->assertNotNull( $selected );
		$this->assertSame( Default_Planning_Prompt_Pack_Definition::DEFAULT_INTERNAL_KEY, $selected[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] );
		$this->assertSame( Default_Planning_Prompt_Pack_Definition::DEFAULT_VERSION, $selected[ Prompt_Pack_Schema::ROOT_VERSION ] );
		$this->assertSame( Prompt_Pack_Schema::STATUS_ACTIVE, $selected[ Prompt_Pack_Schema::ROOT_STATUS ] );
	}

	public function test_default_definition_has_core_segments(): void {
		$def      = Default_Planning_Prompt_Pack_Definition::get();
		$segments = $def[ Prompt_Pack_Schema::ROOT_SEGMENTS ] ?? array();
		$this->assertIsArray( $segments );
		$this->assertNotEmpty( $segments[ Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE ] );
		$this->assertNotEmpty( $segments[ Prompt_Pack_Schema::SEGMENT_PLANNING_INSTRUCTIONS ] );
	}
}
