<?php
/**
 * Unit tests for Execution_Action_Types (spec §39, §40.1).
 *
 * Ensures de-scoped action types are absent from ALL and rejected by is_valid():
 * - UPDATE_PAGE_METADATA: recommendation-only.
 * - ASSIGN_PAGE_HIERARCHY: hierarchy embedded in CREATE_PAGE.
 * - CREATE_MENU: subsumed by UPDATE_MENU.
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

	/** ASSIGN_PAGE_HIERARCHY is executable in v2 via Assign_Page_Hierarchy_Handler. */
	public function test_assign_page_hierarchy_is_valid_for_execution(): void {
		$this->assertTrue(
			Execution_Action_Types::is_valid( Execution_Action_Types::ASSIGN_PAGE_HIERARCHY ),
			'ASSIGN_PAGE_HIERARCHY must be valid for execution in v2 (Assign_Page_Hierarchy_Handler registered).'
		);
	}

	public function test_all_contains_assign_page_hierarchy(): void {
		$this->assertContains(
			Execution_Action_Types::ASSIGN_PAGE_HIERARCHY,
			Execution_Action_Types::ALL,
			'ALL must include ASSIGN_PAGE_HIERARCHY in v2.'
		);
	}

	/** CREATE_MENU is de-scoped: menu creation is handled by UPDATE_MENU. */
	public function test_create_menu_is_not_valid_for_execution(): void {
		$this->assertFalse(
			Execution_Action_Types::is_valid( Execution_Action_Types::CREATE_MENU ),
			'CREATE_MENU must not be valid for execution in v1 (subsumed by UPDATE_MENU).'
		);
	}

	public function test_all_does_not_contain_create_menu(): void {
		$this->assertNotContains(
			Execution_Action_Types::CREATE_MENU,
			Execution_Action_Types::ALL,
			'ALL must not include CREATE_MENU in v1.'
		);
	}
}
