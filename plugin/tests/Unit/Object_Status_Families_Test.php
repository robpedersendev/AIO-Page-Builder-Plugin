<?php
/**
 * Unit tests for Object_Status_Families: status sets per object type (spec §10.10, §10.11).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Status_Families;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';

final class Object_Status_Families_Test extends TestCase {

	public function test_section_template_has_draft_active_inactive_deprecated(): void {
		$statuses = Object_Status_Families::get_statuses_for( Object_Type_Keys::SECTION_TEMPLATE );
		$this->assertSame( array( 'draft', 'active', 'inactive', 'deprecated' ), $statuses );
	}

	public function test_page_template_has_same_status_family_as_section(): void {
		$statuses = Object_Status_Families::get_statuses_for( Object_Type_Keys::PAGE_TEMPLATE );
		$this->assertSame( array( 'draft', 'active', 'inactive', 'deprecated' ), $statuses );
	}

	public function test_composition_has_draft_active_archived(): void {
		$statuses = Object_Status_Families::get_statuses_for( Object_Type_Keys::COMPOSITION );
		$this->assertSame( array( 'draft', 'active', 'archived' ), $statuses );
	}

	public function test_build_plan_has_plan_workflow_statuses(): void {
		$statuses = Object_Status_Families::get_statuses_for( Object_Type_Keys::BUILD_PLAN );
		$this->assertContains( 'pending_review', $statuses );
		$this->assertContains( 'approved', $statuses );
		$this->assertContains( 'completed', $statuses );
		$this->assertContains( 'superseded', $statuses );
	}

	public function test_ai_run_has_workflow_statuses(): void {
		$statuses = Object_Status_Families::get_statuses_for( Object_Type_Keys::AI_RUN );
		$this->assertContains( 'pending_generation', $statuses );
		$this->assertContains( 'completed', $statuses );
		$this->assertContains( 'failed', $statuses );
	}

	public function test_version_snapshot_has_active_superseded(): void {
		$statuses = Object_Status_Families::get_statuses_for( Object_Type_Keys::VERSION_SNAPSHOT );
		$this->assertSame( array( 'active', 'superseded' ), $statuses );
	}

	public function test_is_valid_status_accepts_allowed(): void {
		$this->assertTrue( Object_Status_Families::is_valid_status( Object_Type_Keys::SECTION_TEMPLATE, 'active' ) );
		$this->assertTrue( Object_Status_Families::is_valid_status( Object_Type_Keys::BUILD_PLAN, 'pending_review' ) );
	}

	public function test_is_valid_status_rejects_invalid(): void {
		$this->assertFalse( Object_Status_Families::is_valid_status( Object_Type_Keys::SECTION_TEMPLATE, 'pending_review' ) );
		$this->assertFalse( Object_Status_Families::is_valid_status( Object_Type_Keys::BUILD_PLAN, 'draft' ) );
	}

	public function test_unknown_post_type_returns_empty_statuses(): void {
		$this->assertSame( array(), Object_Status_Families::get_statuses_for( 'unknown_type' ) );
		$this->assertFalse( Object_Status_Families::is_valid_status( 'unknown_type', 'draft' ) );
	}
}
