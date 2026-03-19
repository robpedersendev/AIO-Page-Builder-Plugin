<?php
/**
 * Unit tests for Execution_Action_Types (spec §39, §40.1; Prompt 641).
 *
 * Ensures UPDATE_PAGE_METADATA is not valid for execution in v1 (recommendation-only).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';

final class Execution_Action_Types_Test extends TestCase {

	public function test_update_page_metadata_is_not_valid_for_execution(): void {
		$this->assertFalse(
			Execution_Action_Types::is_valid( Execution_Action_Types::UPDATE_PAGE_METADATA ),
			'UPDATE_PAGE_METADATA must not be valid for execution in v1 (recommendation-only).'
		);
	}

	public function test_all_does_not_contain_update_page_metadata(): void {
		$this->assertNotContains(
			Execution_Action_Types::UPDATE_PAGE_METADATA,
			Execution_Action_Types::ALL,
			true,
			'ALL must not include UPDATE_PAGE_METADATA in v1.'
		);
	}

	public function test_is_valid_accepts_each_type_in_all(): void {
		foreach ( Execution_Action_Types::ALL as $action_type ) {
			$this->assertTrue(
				Execution_Action_Types::is_valid( $action_type ),
				"Action type {$action_type} in ALL must be valid."
			);
		}
	}

	public function test_is_valid_rejects_unknown_type(): void {
		$this->assertFalse( Execution_Action_Types::is_valid( 'unknown_action' ) );
		$this->assertFalse( Execution_Action_Types::is_valid( '' ) );
	}
}
