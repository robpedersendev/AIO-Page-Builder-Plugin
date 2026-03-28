<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Routing\AI_Provider_Routing_Settings_Sanitizer;
use AIOPageBuilder\Domain\AI\Routing\AI_Routing_Task;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class AI_Provider_Routing_Settings_Sanitizer_Test extends TestCase {

	/** @var list<string> */
	private const ALLOW = array( 'openai', 'anthropic' );

	public function test_valid_merge_preserves_unrelated_config_keys(): void {
		$existing = array(
			'failover_policy' => array( 'enabled' => true ),
			'extra'           => 1,
		);
		$payload  = array(
			'primary_provider_id'  => 'anthropic',
			'fallback_provider_id' => 'openai',
			'fallback_model'       => 'gpt-4o',
			'task_routing'         => array(),
		);
		$out      = AI_Provider_Routing_Settings_Sanitizer::merge_into_config( $existing, $payload, self::ALLOW );
		$this->assertTrue( $out['ok'] );
		$this->assertSame( 'anthropic', $out['merged']['primary_provider_id'] );
		$this->assertSame( 'openai', $out['merged']['fallback_provider_id'] );
		$this->assertSame( array( 'enabled' => true ), $out['merged']['failover_policy'] );
	}

	public function test_rejects_global_primary_fallback_same(): void {
		$payload = array(
			'primary_provider_id'  => 'openai',
			'fallback_provider_id' => 'openai',
			'fallback_model'       => '',
			'task_routing'         => array(),
		);
		$out     = AI_Provider_Routing_Settings_Sanitizer::merge_into_config( array(), $payload, self::ALLOW );
		$this->assertFalse( $out['ok'] );
		$this->assertSame( AI_Provider_Routing_Settings_Sanitizer::ERROR_GLOBAL_FALLBACK_CHAIN, $out['error_code'] );
	}

	public function test_rejects_unknown_task_primary(): void {
		$payload = array(
			'primary_provider_id'  => 'openai',
			'fallback_provider_id' => '',
			'task_routing'         => array(
				AI_Routing_Task::BUILD_PLAN_GENERATION => array(
					'provider_id' => 'fake',
				),
			),
		);
		$out     = AI_Provider_Routing_Settings_Sanitizer::merge_into_config( array(), $payload, self::ALLOW );
		$this->assertFalse( $out['ok'] );
		$this->assertSame( AI_Provider_Routing_Settings_Sanitizer::ERROR_INVALID_TASK_PRIMARY, $out['error_code'] );
	}

	public function test_task_inherits_global_when_primary_empty_slice(): void {
		$payload = array(
			'primary_provider_id'  => 'anthropic',
			'fallback_provider_id' => 'openai',
			'fallback_model'       => '',
			'task_routing'         => array(
				AI_Routing_Task::TEMPLATE_LAB_CHAT => array(
					'model' => 'claude-3-5-sonnet-20241022',
				),
			),
		);
		$out     = AI_Provider_Routing_Settings_Sanitizer::merge_into_config( array(), $payload, self::ALLOW );
		$this->assertTrue( $out['ok'] );
		$this->assertSame(
			array(
				'model' => 'claude-3-5-sonnet-20241022',
			),
			$out['merged']['task_routing'][ AI_Routing_Task::TEMPLATE_LAB_CHAT ]
		);
	}
}
