<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Planning\AI_Prompt_Pack_Keys;
use AIOPageBuilder\Domain\AI\Routing\AI_Routing_Task;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class AI_Prompt_Pack_Keys_Test extends TestCase {

	public function test_for_routing_task_maps_template_lab_tasks(): void {
		$this->assertStringContainsString( 'template_lab', AI_Prompt_Pack_Keys::for_routing_task( AI_Routing_Task::TEMPLATE_LAB_COMPOSITION_DRAFT ) );
		$this->assertStringContainsString( 'build_plan', AI_Prompt_Pack_Keys::for_routing_task( AI_Routing_Task::BUILD_PLAN_GENERATION ) );
		$this->assertSame( AI_Prompt_Pack_Keys::TEMPLATE_LAB_CHAT, AI_Prompt_Pack_Keys::for_routing_task( AI_Routing_Task::TEMPLATE_LAB_CHAT ) );
	}

	public function test_explain_diff_constant_is_stable(): void {
		$this->assertStringStartsWith( 'aio.prompt_pack.', AI_Prompt_Pack_Keys::TEMPLATE_LAB_STRUCTURED_EXPLAIN_DIFF );
	}
}
